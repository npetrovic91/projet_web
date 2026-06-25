<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

use Nenad\Autosav\Core\Database\Database;
use PDO;

/**
 * Gestion des 4 tables d'extension Utilisateur (client individuel) :
 *
 *  - sav_utilisateurs_infos_complementaires (1-1) : pièce identité, conditions commerciales
 *  - sav_utilisateurs_comptes_bancaires     (1-N) : RIB / IBAN / BIC
 *  - sav_utilisateurs_mandats_prelevement   (1-N) : mandats SEPA
 *  - sav_utilisateurs_vehicules             (1-N) : parc SAV
 */
class UserComplementModel
{
    private Database $db;
    private PDO $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db  = $db ?? Database::getInstance();
        $this->pdo = $this->db->getPdo();
    }

    // ═══════════════════════════════════════════════════════════════════
    // LECTURE GLOBALE
    // ═══════════════════════════════════════════════════════════════════

    public function toutPourUtilisateur(int $utiId): array
    {
        return [
            'infos_complementaires' => $this->infosComplementaires($utiId),
            'comptes_bancaires'     => $this->comptesBancaires($utiId),
            'mandats'               => $this->mandats($utiId),
            'vehicules'             => $this->vehicules($utiId),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // INFOS COMPLÉMENTAIRES (1-1)
    // ═══════════════════════════════════════════════════════════════════

    public function infosComplementaires(int $utiId): array
    {
        return $this->db->fetch(
            "SELECT uic.*,
                    nac.nac_code AS nature_client_code, nac.nac_libelle AS nature_client_libelle,
                    mrg.mrg_code AS mode_reglement_code, mrg.mrg_libelle AS mode_reglement_libelle,
                    cre.cre_code AS code_remise_code,   cre.cre_libelle AS code_remise_libelle
             FROM   sav_utilisateurs_infos_complementaires uic
             LEFT JOIN sav_natures_clients nac ON nac.nac_id = uic.uic_nature_client_id
             LEFT JOIN sav_modes_reglement mrg ON mrg.mrg_id = uic.uic_mode_reglement_id
             LEFT JOIN sav_codes_remise    cre ON cre.cre_id = uic.uic_code_remise_id
             WHERE  uic.uic_utilisateur_id = :id
               AND  uic.uic_supprime_le IS NULL
             LIMIT 1",
            ['id' => $utiId]
        ) ?: [];
    }

    public function sauvegarderInfosComplementaires(int $utiId, array $data, int $userId): bool
    {
        $existant = $this->db->fetchColumn(
            'SELECT uic_id FROM sav_utilisateurs_infos_complementaires WHERE uic_utilisateur_id = :id AND uic_supprime_le IS NULL LIMIT 1',
            ['id' => $utiId]
        );

        $payload = [
            'uic_code_client'                  => $this->str($data['uic_code_client'] ?? null),
            'uic_titre_identite'               => $this->str($data['uic_titre_identite'] ?? null),
            'uic_numero_piece_identite'        => $this->str($data['uic_numero_piece_identite'] ?? null),
            'uic_date_delivrance_piece'        => $this->date($data['uic_date_delivrance_piece'] ?? null),
            'uic_autorite_delivrance'          => $this->str($data['uic_autorite_delivrance'] ?? null),
            'uic_categorie_socioprofessionnelle' => $this->str($data['uic_categorie_socioprofessionnelle'] ?? null),
            'uic_nature_client_id'             => $this->int($data['uic_nature_client_id'] ?? null),
            'uic_flag_garantie'                => empty($data['uic_flag_garantie']) ? 0 : 1,
            'uic_flag_passager'                => empty($data['uic_flag_passager']) ? 0 : 1,
            'uic_flag_releve'                  => empty($data['uic_flag_releve']) ? 0 : 1,
            'uic_flag_assureur'                => empty($data['uic_flag_assureur']) ? 0 : 1,
            'uic_flag_blocage_facture'         => empty($data['uic_flag_blocage_facture']) ? 0 : 1,
            'uic_flag_douteux'                 => empty($data['uic_flag_douteux']) ? 0 : 1,
            'uic_mode_reglement_id'            => $this->int($data['uic_mode_reglement_id'] ?? null),
            'uic_plafond_credit'               => $this->decimal($data['uic_plafond_credit'] ?? null),
            'uic_delai_paiement_mois'          => $this->int($data['uic_delai_paiement_mois'] ?? null),
            'uic_delai_paiement_jours'         => $this->int($data['uic_delai_paiement_jours'] ?? null),
            'uic_code_remise_id'               => $this->int($data['uic_code_remise_id'] ?? null),
            'uic_zone_libre'                   => $this->str($data['uic_zone_libre'] ?? null),
        ];

        if ($existant) {
            $sets = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($payload)));
            $payload['uic_modifie_par_utilisateur_id'] = $userId ?: null;
            $payload['uic_id'] = (int) $existant;
            $stmt = $this->pdo->prepare(
                "UPDATE sav_utilisateurs_infos_complementaires
                 SET {$sets}, uic_modifie_par_utilisateur_id = :uic_modifie_par_utilisateur_id
                 WHERE uic_id = :uic_id"
            );
        } else {
            $payload['uic_utilisateur_id']           = $utiId;
            $payload['uic_cree_par_utilisateur_id']  = $userId ?: null;
            $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($payload)));
            $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($payload)));
            $stmt = $this->pdo->prepare("INSERT INTO sav_utilisateurs_infos_complementaires ({$cols}) VALUES ({$vals})");
        }

        $this->bindAll($stmt, $payload);
        return $stmt->execute();
    }

    // ═══════════════════════════════════════════════════════════════════
    // COMPTES BANCAIRES (1-N)
    // ═══════════════════════════════════════════════════════════════════

    public function comptesBancaires(int $utiId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM sav_utilisateurs_comptes_bancaires
             WHERE ucb_utilisateur_id = :id AND ucb_supprime_le IS NULL
             ORDER BY ucb_est_principal DESC, ucb_id ASC",
            ['id' => $utiId]
        );
    }

    public function ajouterCompteBancaire(int $utiId, array $data, int $userId): int
    {
        if (!empty($data['ucb_est_principal'])) {
            $this->db->execute(
                'UPDATE sav_utilisateurs_comptes_bancaires SET ucb_est_principal = 0 WHERE ucb_utilisateur_id = :id AND ucb_supprime_le IS NULL',
                ['id' => $utiId]
            );
        }

        $this->db->execute(
            "INSERT INTO sav_utilisateurs_comptes_bancaires
             (ucb_utilisateur_id, ucb_code_banque, ucb_code_guichet, ucb_numero_compte, ucb_cle_rib,
              ucb_iban, ucb_bic, ucb_est_principal, ucb_est_actif, ucb_cree_par_utilisateur_id)
             VALUES (:uti_id, :code_banque, :code_guichet, :numero_compte, :cle_rib,
                     :iban, :bic, :principal, :actif, :cree_par)",
            [
                'uti_id'        => $utiId,
                'code_banque'   => $this->str($data['ucb_code_banque'] ?? null),
                'code_guichet'  => $this->str($data['ucb_code_guichet'] ?? null),
                'numero_compte' => $this->str($data['ucb_numero_compte'] ?? null),
                'cle_rib'       => $this->str($data['ucb_cle_rib'] ?? null),
                'iban'          => $this->str($data['ucb_iban'] ?? null),
                'bic'           => $this->str($data['ucb_bic'] ?? null),
                'principal'     => empty($data['ucb_est_principal']) ? 0 : 1,
                'actif'         => 1,
                'cree_par'      => $userId ?: null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function supprimerCompteBancaire(int $ucbId, int $utiId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_utilisateurs_comptes_bancaires
             SET ucb_supprime_le = NOW(), ucb_supprime_par_utilisateur_id = :uid
             WHERE ucb_id = :id AND ucb_utilisateur_id = :uti_id AND ucb_supprime_le IS NULL",
            ['id' => $ucbId, 'uti_id' => $utiId, 'uid' => $userId ?: null]
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // MANDATS SEPA (1-N)
    // ═══════════════════════════════════════════════════════════════════

    public function mandats(int $utiId): array
    {
        return $this->db->fetchAll(
            "SELECT ump.*, ucb.ucb_iban AS compte_iban
             FROM   sav_utilisateurs_mandats_prelevement ump
             LEFT JOIN sav_utilisateurs_comptes_bancaires ucb ON ucb.ucb_id = ump.ump_compte_bancaire_id
             WHERE  ump.ump_utilisateur_id = :id AND ump.ump_supprime_le IS NULL
             ORDER  BY ump.ump_est_defaut DESC, ump.ump_date_signature DESC",
            ['id' => $utiId]
        );
    }

    public function ajouterMandat(int $utiId, array $data, int $userId): int
    {
        if (!empty($data['ump_est_defaut'])) {
            $this->db->execute(
                'UPDATE sav_utilisateurs_mandats_prelevement SET ump_est_defaut = 0 WHERE ump_utilisateur_id = :id AND ump_supprime_le IS NULL',
                ['id' => $utiId]
            );
        }

        $this->db->execute(
            "INSERT INTO sav_utilisateurs_mandats_prelevement
             (ump_utilisateur_id, ump_compte_bancaire_id, ump_reference_unique, ump_type,
              ump_date_signature, ump_date_premier_prelevement, ump_date_fin_validite,
              ump_est_defaut, ump_est_actif, ump_cree_par_utilisateur_id)
             VALUES (:uti_id, :compte_id, :ref, :type,
                     :sig, :premier, :fin, :defaut, :actif, :cree_par)",
            [
                'uti_id'    => $utiId,
                'compte_id' => $this->int($data['ump_compte_bancaire_id'] ?? null),
                'ref'       => trim((string) ($data['ump_reference_unique'] ?? '')),
                'type'      => trim((string) ($data['ump_type'] ?? 'RECURRENT')),
                'sig'       => $this->date($data['ump_date_signature'] ?? null),
                'premier'   => $this->date($data['ump_date_premier_prelevement'] ?? null),
                'fin'       => $this->date($data['ump_date_fin_validite'] ?? null),
                'defaut'    => empty($data['ump_est_defaut']) ? 0 : 1,
                'actif'     => 1,
                'cree_par'  => $userId ?: null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function supprimerMandat(int $umpId, int $utiId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_utilisateurs_mandats_prelevement
             SET ump_supprime_le = NOW(), ump_supprime_par_utilisateur_id = :uid
             WHERE ump_id = :id AND ump_utilisateur_id = :uti_id AND ump_supprime_le IS NULL",
            ['id' => $umpId, 'uti_id' => $utiId, 'uid' => $userId ?: null]
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // PARC VÉHICULES (1-N)
    // ═══════════════════════════════════════════════════════════════════

    public function vehicules(int $utiId): array
    {
        return $this->db->fetchAll(
            "SELECT uve.*, s.soc_nom AS marque_nom
             FROM   sav_utilisateurs_vehicules uve
             LEFT JOIN sav_societes s ON s.soc_id = uve.uve_marque_societe_id
             WHERE  uve.uve_utilisateur_id = :id AND uve.uve_supprime_le IS NULL
             ORDER  BY uve.uve_immatriculation ASC, uve.uve_chassis ASC",
            ['id' => $utiId]
        );
    }

    public function ajouterVehicule(int $utiId, array $data, int $userId): int
    {
        $this->db->execute(
            "INSERT INTO sav_utilisateurs_vehicules
             (uve_utilisateur_id, uve_marque_societe_id, uve_code_marque, uve_type_modele,
              uve_chassis, uve_immatriculation, uve_numero_moteur, uve_date_mise_en_circulation,
              uve_cree_par_utilisateur_id)
             VALUES (:uti_id, :marque_soc_id, :code_marque, :type_modele,
                     :chassis, :immat, :moteur, :date_mec, :cree_par)",
            [
                'uti_id'        => $utiId,
                'marque_soc_id' => $this->int($data['uve_marque_societe_id'] ?? null),
                'code_marque'   => $this->str($data['uve_code_marque'] ?? null),
                'type_modele'   => $this->str($data['uve_type_modele'] ?? null),
                'chassis'       => $this->str($data['uve_chassis'] ?? null),
                'immat'         => $this->str($data['uve_immatriculation'] ?? null),
                'moteur'        => $this->str($data['uve_numero_moteur'] ?? null),
                'date_mec'      => $this->date($data['uve_date_mise_en_circulation'] ?? null),
                'cree_par'      => $userId ?: null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function supprimerVehicule(int $uveId, int $utiId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_utilisateurs_vehicules
             SET uve_supprime_le = NOW(), uve_supprime_par_utilisateur_id = :uid
             WHERE uve_id = :id AND uve_utilisateur_id = :uti_id AND uve_supprime_le IS NULL",
            ['id' => $uveId, 'uti_id' => $utiId, 'uid' => $userId ?: null]
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPERS INTERNES
    // ═══════════════════════════════════════════════════════════════════

    private function str(mixed $v): ?string
    {
        $s = trim((string) ($v ?? ''));
        return $s !== '' ? $s : null;
    }

    private function int(mixed $v): ?int
    {
        return ($v !== null && $v !== '') ? (int) $v : null;
    }

    private function decimal(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $v = str_replace([' ', ','], ['', '.'], (string) $v);
        return is_numeric($v) ? (float) $v : null;
    }

    private function date(mixed $v): ?string
    {
        if (!$v || $v === '' || $v === '0000-00-00') {
            return null;
        }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', (string) $v, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return preg_match('#^\d{4}-\d{2}-\d{2}#', (string) $v) ? substr((string) $v, 0, 10) : null;
    }

    private function bindAll(\PDOStatement $stmt, array $data): void
    {
        foreach ($data as $key => $val) {
            if (is_int($val)) {
                $stmt->bindValue(":{$key}", $val, PDO::PARAM_INT);
            } elseif ($val === null) {
                $stmt->bindValue(":{$key}", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":{$key}", $val, PDO::PARAM_STR);
            }
        }
    }
}
