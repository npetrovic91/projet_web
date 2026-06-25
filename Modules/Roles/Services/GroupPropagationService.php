<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Roles\Services;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

/**
 * AUTOSAV — Propagation massive groupe -> concessions (ACC-009)
 *
 * Cahier des charges (workflows.propagation_groupe) : "Une propagation
 * massive de groupe vers ses concessions nécessite prévisualisation,
 * confirmation, journalisation et rollback possible." Un directeur_groupe
 * (rôle responsable_groupe_concessions/directeur_groupe/administrateur_
 * groupe_concessions) peut ainsi diffuser un rôle, une fonction, une
 * compétence ou une certification qu'il détient au niveau du groupe vers
 * les concessions rattachées, sans que celles-ci puissent refuser une
 * propagation déjà validée.
 *
 * "Propager" signifie ici : créer, pour chaque concession cible qui ne
 * possède pas déjà un élément de même code, une copie de l'élément
 * source dont la société propriétaire devient la concession. Les
 * conflits (élément déjà présent) sont détectés en prévisualisation et
 * ignorés à l'exécution (jamais écrasés silencieusement).
 */
final class GroupPropagationService implements ServiceInterface
{
    /** @var array<string, array{table:string, prefix:string}> */
    private const TYPES = [
        'role' => ['table' => 'sav_roles', 'prefix' => 'rol'],
        'fonction' => ['table' => 'sav_fonctions', 'prefix' => 'fon'],
        'competence' => ['table' => 'sav_competences', 'prefix' => 'cmp'],
        'certification' => ['table' => 'sav_certifications', 'prefix' => 'cer'],
    ];

    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public static function typesValides(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * Concessions rattachées à un groupe (soc_societe_parente_id ou
     * soc_holding_id = groupe), actives uniquement.
     */
    public function concessionsRattachees(int $groupeSocieteId): array
    {
        return $this->db->fetchAll(
            "SELECT soc_id, soc_nom FROM sav_societes
             WHERE (soc_societe_parente_id = :g OR soc_holding_id = :g2)
               AND soc_supprime_le IS NULL AND soc_archive_le IS NULL
             ORDER BY soc_nom ASC",
            ['g' => $groupeSocieteId, 'g2' => $groupeSocieteId]
        );
    }

    /**
     * Catalogue propageable détenu par le groupe pour un type donné.
     */
    public function catalogueGroupe(string $type, int $groupeSocieteId): array
    {
        $meta = $this->meta($type);
        $p = $meta['prefix'];
        return $this->db->fetchAll(
            "SELECT {$p}_id, {$p}_code, {$p}_nom FROM {$meta['table']}
             WHERE {$p}_societe_proprietaire_id = :g AND {$p}_supprime_le IS NULL AND {$p}_archive_le IS NULL
             ORDER BY {$p}_nom ASC",
            ['g' => $groupeSocieteId]
        );
    }

    /**
     * Calcule la prévisualisation : pour chaque concession cible, indique
     * si une propagation créerait un nouvel élément ou se heurte à un
     * conflit (élément de même code déjà détenu par cette concession).
     * Persiste le résultat (statut 'preview') pour qu'il soit rejoué tel
     * quel à la confirmation, sans recalcul TOCTOU.
     */
    public function previsualiser(string $type, int $cibleId, int $groupeSocieteId, array $concessionIds, int $userId): array
    {
        $meta = $this->meta($type);
        $p = $meta['prefix'];

        $source = $this->db->fetch(
            "SELECT * FROM {$meta['table']} WHERE {$p}_id = :id AND {$p}_societe_proprietaire_id = :g AND {$p}_supprime_le IS NULL",
            ['id' => $cibleId, 'g' => $groupeSocieteId]
        );
        if ($source === null) {
            throw new \InvalidArgumentException('Élément introuvable ou non détenu par ce groupe.');
        }

        $rattachees = array_column($this->concessionsRattachees($groupeSocieteId), 'soc_id');
        $concessionIds = array_values(array_intersect(array_map('intval', $concessionIds), array_map('intval', $rattachees)));
        if ($concessionIds === []) {
            throw new \InvalidArgumentException('Aucune concession cible valide (doit être rattachée à ce groupe).');
        }

        $detail = [];
        foreach ($concessionIds as $concessionId) {
            $existe = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$meta['table']} WHERE {$p}_code = :code AND {$p}_societe_proprietaire_id = :c AND {$p}_supprime_le IS NULL",
                ['code' => $source["{$p}_code"], 'c' => $concessionId]
            );
            $detail[] = [
                'concession_id' => $concessionId,
                'conflit' => ((int) $existe) > 0,
            ];
        }

        $preview = [
            'source_code' => $source["{$p}_code"],
            'source_nom' => $source["{$p}_nom"],
            'detail' => $detail,
            'a_creer' => count(array_filter($detail, static fn($d) => !$d['conflit'])),
            'conflits' => count(array_filter($detail, static fn($d) => $d['conflit'])),
        ];

        $this->db->execute(
            "INSERT INTO sav_bulk_actions
                (bac_groupe_societe_id, bac_type_cible, bac_cible_id, bac_concession_ids, bac_statut, bac_preview_json, bac_cree_par_utilisateur_id, bac_cree_le)
             VALUES (:groupe, :type, :cible, :concessions, 'preview', :preview, :user_id, NOW())",
            [
                'groupe' => $groupeSocieteId,
                'type' => $type,
                'cible' => $cibleId,
                'concessions' => json_encode($concessionIds),
                'preview' => json_encode($preview, JSON_UNESCAPED_UNICODE),
                'user_id' => $userId,
            ]
        );

        return ['id' => (int) $this->db->lastInsertId(), 'preview' => $preview];
    }

    /**
     * Exécute une propagation déjà prévisualisée : crée les éléments
     * absents, ignore les conflits, journalise le résultat précis
     * (IDs créés par concession) pour permettre un rollback exact.
     */
    public function confirmer(int $bulkActionId, int $userId): array
    {
        $action = $this->trouver($bulkActionId);
        if ($action === null || $action['bac_statut'] !== 'preview') {
            throw new \InvalidArgumentException('Cette propagation a déjà été traitée ou est introuvable.');
        }

        $meta = $this->meta($action['bac_type_cible']);
        $p = $meta['prefix'];
        $source = $this->db->fetch("SELECT * FROM {$meta['table']} WHERE {$p}_id = :id", ['id' => $action['bac_cible_id']]);
        if ($source === null) {
            throw new \InvalidArgumentException('Élément source introuvable, propagation annulée.');
        }

        $concessionIds = (array) json_decode((string) $action['bac_concession_ids'], true);
        $crees = [];
        $erreurs = [];

        foreach ($concessionIds as $concessionId) {
            $concessionId = (int) $concessionId;
            $existe = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM {$meta['table']} WHERE {$p}_code = :code AND {$p}_societe_proprietaire_id = :c AND {$p}_supprime_le IS NULL",
                ['code' => $source["{$p}_code"], 'c' => $concessionId]
            );
            if ((int) $existe > 0) {
                continue; // conflit déjà signalé en preview : on ne propage jamais en écrasant.
            }

            try {
                $this->db->execute(
                    "INSERT INTO {$meta['table']} ({$p}_code, {$p}_nom, {$p}_description, {$p}_societe_proprietaire_id, {$p}_portee_code, {$p}_statut_id, {$p}_cree_par_utilisateur_id)
                     VALUES (:code, :nom, :description, :concession, 'interne', :statut, :user_id)",
                    [
                        'code' => $source["{$p}_code"],
                        'nom' => $source["{$p}_nom"],
                        'description' => $source["{$p}_description"] ?? null,
                        'concession' => $concessionId,
                        'statut' => $source["{$p}_statut_id"] ?? 1,
                        'user_id' => $userId,
                    ]
                );
                $crees[] = ['concession_id' => $concessionId, 'nouvel_id' => (int) $this->db->lastInsertId()];
            } catch (\Throwable $e) {
                $erreurs[] = ['concession_id' => $concessionId, 'erreur' => $e->getMessage()];
            }
        }

        $resultat = ['crees' => $crees, 'erreurs' => $erreurs];
        $this->db->execute(
            "UPDATE sav_bulk_actions
             SET bac_statut = 'executee', bac_resultat_json = :resultat,
                 bac_valide_par_utilisateur_id = :user_id, bac_valide_le = NOW(), bac_execute_le = NOW()
             WHERE bac_id = :id",
            ['resultat' => json_encode($resultat, JSON_UNESCAPED_UNICODE), 'user_id' => $userId, 'id' => $bulkActionId]
        );

        return $resultat;
    }

    /**
     * Annule une propagation déjà exécutée : supprime logiquement chaque
     * élément créé par cette action précise (jamais les éléments
     * préexistants en conflit, qui n'avaient pas été touchés).
     */
    public function annuler(int $bulkActionId, int $userId): void
    {
        $action = $this->trouver($bulkActionId);
        if ($action === null || $action['bac_statut'] !== 'executee') {
            throw new \InvalidArgumentException('Seule une propagation exécutée peut être annulée.');
        }

        $meta = $this->meta($action['bac_type_cible']);
        $p = $meta['prefix'];
        $resultat = (array) json_decode((string) $action['bac_resultat_json'], true);

        foreach (($resultat['crees'] ?? []) as $entree) {
            $this->db->execute(
                "UPDATE {$meta['table']} SET {$p}_supprime_le = NOW(), {$p}_supprime_par_utilisateur_id = :user_id WHERE {$p}_id = :id",
                ['id' => (int) $entree['nouvel_id'], 'user_id' => $userId]
            );
        }

        $this->db->execute(
            "UPDATE sav_bulk_actions SET bac_statut = 'rollback', bac_rollback_le = NOW(), bac_rollback_par_utilisateur_id = :user_id WHERE bac_id = :id",
            ['user_id' => $userId, 'id' => $bulkActionId]
        );
    }

    public function historique(int $groupeSocieteId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM sav_bulk_actions WHERE bac_groupe_societe_id = :g ORDER BY bac_cree_le DESC",
            ['g' => $groupeSocieteId]
        );
    }

    public function trouver(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM sav_bulk_actions WHERE bac_id = :id", ['id' => $id]);
    }

    private function meta(string $type): array
    {
        if (!isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException("Type de propagation inconnu : {$type}");
        }
        return self::TYPES[$type];
    }
}
