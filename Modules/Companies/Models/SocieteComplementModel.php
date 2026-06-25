<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Models;

use Nenad\Autosav\Core\Database\Database;
use PDO;

/**
 * Gestion des 4 tables d'extension Société / Marque issues des
 * écrans de référence CLIENTS du DMS :
 *
 *  - sav_societes_infos_complementaires (1-1) : conditions commerciales, drapeaux
 *  - sav_societes_comptes_bancaires     (1-N) : RIB / IBAN / BIC
 *  - sav_societes_mandats_prelevement   (1-N) : mandats SEPA
 *  - sav_societes_vehicules             (1-N) : parc SAV
 *
 * "Marque" est une sav_societes de type "marque" : elle bénéficie
 * automatiquement de toutes ces extensions via soc_id.
 */
class SocieteComplementModel
{
    private Database $db;
    private PDO $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db  = $db ?? Database::getInstance();
        $this->pdo = $this->db->getPdo();
    }

    // ═══════════════════════════════════════════════════════════════════
    // LECTURE GLOBALE — chargée par ficheComplete()
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Retourne toutes les données complémentaires pour une société.
     * Appelé par ServiceSocietes::ficheComplete().
     */
    public function toutPourSociete(int $socId): array
    {
        return [
            'infos_complementaires' => $this->infosComplementaires($socId),
            'comptes_bancaires'     => $this->comptesBancaires($socId),
            'mandats'               => $this->mandats($socId),
            'vehicules'             => $this->vehicules($socId),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // INFOS COMPLÉMENTAIRES (1-1)
    // ═══════════════════════════════════════════════════════════════════

    public function infosComplementaires(int $socId): array
    {
        return $this->db->fetch(
            "SELECT sic.*,
                    nac.nac_code AS nature_client_code, nac.nac_libelle AS nature_client_libelle,
                    mrg.mrg_code AS mode_reglement_code, mrg.mrg_libelle AS mode_reglement_libelle,
                    cre.cre_code AS code_remise_code,   cre.cre_libelle AS code_remise_libelle,
                    tva.tva_code AS tva_specifique_code, tva.tva_nom AS tva_specifique_nom, tva.tva_taux AS tva_specifique_taux
             FROM   sav_societes_infos_complementaires sic
             LEFT JOIN sav_natures_clients nac ON nac.nac_id = sic.sic_nature_client_id
             LEFT JOIN sav_modes_reglement mrg ON mrg.mrg_id = sic.sic_mode_reglement_id
             LEFT JOIN sav_codes_remise    cre ON cre.cre_id = sic.sic_code_remise_id
             LEFT JOIN sav_taux_tva        tva ON tva.tva_id = sic.sic_tva_specifique_id
             WHERE  sic.sic_societe_id = :id
               AND  sic.sic_supprime_le IS NULL
             LIMIT 1",
            ['id' => $socId]
        ) ?: [];
    }

    public function sauvegarderInfosComplementaires(int $socId, array $data, int $userId): bool
    {
        $existant = $this->db->fetchColumn(
            'SELECT sic_id FROM sav_societes_infos_complementaires WHERE sic_societe_id = :id AND sic_supprime_le IS NULL LIMIT 1',
            ['id' => $socId]
        );

        $payload = [
            'sic_code_client'                  => $this->str($data['sic_code_client'] ?? null),
            'sic_complement_nom'               => $this->str($data['sic_complement_nom'] ?? null),
            'sic_nature_client_id'             => $this->int($data['sic_nature_client_id'] ?? null),
            'sic_compte_collectif'             => $this->str($data['sic_compte_collectif'] ?? null),
            'sic_partenaire_vgf'               => empty($data['sic_partenaire_vgf']) ? 0 : 1,
            'sic_kvps_mra_cld'                 => $this->str($data['sic_kvps_mra_cld'] ?? null),
            'sic_flag_garantie'                => empty($data['sic_flag_garantie']) ? 0 : 1,
            'sic_flag_passager'                => empty($data['sic_flag_passager']) ? 0 : 1,
            'sic_flag_releve'                  => empty($data['sic_flag_releve']) ? 0 : 1,
            'sic_flag_assureur'                => empty($data['sic_flag_assureur']) ? 0 : 1,
            'sic_flag_blocage_facture'         => empty($data['sic_flag_blocage_facture']) ? 0 : 1,
            'sic_flag_douteux'                 => empty($data['sic_flag_douteux']) ? 0 : 1,
            'sic_mode_reglement_id'            => $this->int($data['sic_mode_reglement_id'] ?? null),
            'sic_tva_specifique_id'            => $this->int($data['sic_tva_specifique_id'] ?? null),
            'sic_banque_remise'                => $this->str($data['sic_banque_remise'] ?? null),
            'sic_plafond_credit'               => $this->decimal($data['sic_plafond_credit'] ?? null),
            'sic_delai_paiement_mois'          => $this->int($data['sic_delai_paiement_mois'] ?? null),
            'sic_delai_paiement_jours'         => $this->int($data['sic_delai_paiement_jours'] ?? null),
            'sic_code_remise_id'               => $this->int($data['sic_code_remise_id'] ?? null),
            'sic_compte_debiteur_cession_interne' => $this->str($data['sic_compte_debiteur_cession_interne'] ?? null),
            'sic_zone_libre'                   => $this->str($data['sic_zone_libre'] ?? null),
        ];

        if ($existant) {
            $sets = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($payload)));
            $payload['sic_modifie_par_utilisateur_id'] = $userId ?: null;
            $payload['sic_id'] = (int) $existant;
            $stmt = $this->pdo->prepare(
                "UPDATE sav_societes_infos_complementaires
                 SET {$sets}, sic_modifie_par_utilisateur_id = :sic_modifie_par_utilisateur_id
                 WHERE sic_id = :sic_id"
            );
        } else {
            $payload['sic_societe_id']             = $socId;
            $payload['sic_cree_par_utilisateur_id'] = $userId ?: null;
            $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($payload)));
            $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($payload)));
            $stmt = $this->pdo->prepare("INSERT INTO sav_societes_infos_complementaires ({$cols}) VALUES ({$vals})");
        }

        $this->bindAll($stmt, $payload);
        return $stmt->execute();
    }

    // ═══════════════════════════════════════════════════════════════════
    // COMPTES BANCAIRES (1-N)
    // ═══════════════════════════════════════════════════════════════════

    public function comptesBancaires(int $socId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM sav_societes_comptes_bancaires
             WHERE scb_societe_id = :id AND scb_supprime_le IS NULL
             ORDER BY scb_est_principal DESC, scb_id ASC",
            ['id' => $socId]
        );
    }

    public function ajouterCompteBancaire(int $socId, array $data, int $userId): int
    {
        // Si ce compte est principal, dépromouvoir les autres
        if (!empty($data['scb_est_principal'])) {
            $this->db->execute(
                'UPDATE sav_societes_comptes_bancaires SET scb_est_principal = 0 WHERE scb_societe_id = :id AND scb_supprime_le IS NULL',
                ['id' => $socId]
            );
        }

        $this->db->execute(
            "INSERT INTO sav_societes_comptes_bancaires
             (scb_societe_id, scb_code_banque, scb_code_guichet, scb_numero_compte, scb_cle_rib,
              scb_iban, scb_bic, scb_est_principal, scb_est_actif, scb_cree_par_utilisateur_id)
             VALUES (:soc_id, :code_banque, :code_guichet, :numero_compte, :cle_rib,
                     :iban, :bic, :principal, :actif, :cree_par)",
            [
                'soc_id'        => $socId,
                'code_banque'   => $this->str($data['scb_code_banque'] ?? null),
                'code_guichet'  => $this->str($data['scb_code_guichet'] ?? null),
                'numero_compte' => $this->str($data['scb_numero_compte'] ?? null),
                'cle_rib'       => $this->str($data['scb_cle_rib'] ?? null),
                'iban'          => $this->str($data['scb_iban'] ?? null),
                'bic'           => $this->str($data['scb_bic'] ?? null),
                'principal'     => empty($data['scb_est_principal']) ? 0 : 1,
                'actif'         => 1,
                'cree_par'      => $userId ?: null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function supprimerCompteBancaire(int $scbId, int $socId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_societes_comptes_bancaires
             SET scb_supprime_le = NOW(), scb_supprime_par_utilisateur_id = :uid
             WHERE scb_id = :id AND scb_societe_id = :soc_id AND scb_supprime_le IS NULL",
            ['id' => $scbId, 'soc_id' => $socId, 'uid' => $userId ?: null]
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // MANDATS SEPA (1-N)
    // ═══════════════════════════════════════════════════════════════════

    public function mandats(int $socId): array
    {
        return $this->db->fetchAll(
            "SELECT smp.*, scb.scb_iban AS compte_iban
             FROM   sav_societes_mandats_prelevement smp
             LEFT JOIN sav_societes_comptes_bancaires scb ON scb.scb_id = smp.smp_compte_bancaire_id
             WHERE  smp.smp_societe_id = :id AND smp.smp_supprime_le IS NULL
             ORDER  BY smp.smp_est_defaut DESC, smp.smp_date_signature DESC",
            ['id' => $socId]
        );
    }

    public function ajouterMandat(int $socId, array $data, int $userId): int
    {
        if (!empty($data['smp_est_defaut'])) {
            $this->db->execute(
                'UPDATE sav_societes_mandats_prelevement SET smp_est_defaut = 0 WHERE smp_societe_id = :id AND smp_supprime_le IS NULL',
                ['id' => $socId]
            );
        }

        $this->db->execute(
            "INSERT INTO sav_societes_mandats_prelevement
             (smp_societe_id, smp_compte_bancaire_id, smp_reference_unique, smp_type,
              smp_date_signature, smp_date_premier_prelevement, smp_date_fin_validite,
              smp_est_defaut, smp_est_actif, smp_cree_par_utilisateur_id)
             VALUES (:soc_id, :compte_id, :ref, :type,
                     :sig, :premier, :fin, :defaut, :actif, :cree_par)",
            [
                'soc_id'    => $socId,
                'compte_id' => $this->int($data['smp_compte_bancaire_id'] ?? null),
                'ref'       => trim((string) ($data['smp_reference_unique'] ?? '')),
                'type'      => trim((string) ($data['smp_type'] ?? 'RECURRENT')),
                'sig'       => $this->date($data['smp_date_signature'] ?? null),
                'premier'   => $this->date($data['smp_date_premier_prelevement'] ?? null),
                'fin'       => $this->date($data['smp_date_fin_validite'] ?? null),
                'defaut'    => empty($data['smp_est_defaut']) ? 0 : 1,
                'actif'     => 1,
                'cree_par'  => $userId ?: null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function mettreAJourMandat(int $smpId, int $socId, array $data, int $userId): bool
    {
        if (!empty($data['smp_est_defaut'])) {
            $this->db->execute(
                'UPDATE sav_societes_mandats_prelevement SET smp_est_defaut = 0 WHERE smp_societe_id = :id AND smp_supprime_le IS NULL',
                ['id' => $socId]
            );
        }
        return $this->db->execute(
            "UPDATE sav_societes_mandats_prelevement
             SET smp_type = :type, smp_date_signature = :sig, smp_date_premier_prelevement = :premier,
                 smp_date_fin_validite = :fin, smp_date_derniere_utilisation = :derniere,
                 smp_est_defaut = :defaut, smp_est_actif = :actif,
                 smp_modifie_par_utilisateur_id = :uid
             WHERE smp_id = :id AND smp_societe_id = :soc_id AND smp_supprime_le IS NULL",
            [
                'type'     => trim((string) ($data['smp_type'] ?? 'RECURRENT')),
                'sig'      => $this->date($data['smp_date_signature'] ?? null),
                'premier'  => $this->date($data['smp_date_premier_prelevement'] ?? null),
                'fin'      => $this->date($data['smp_date_fin_validite'] ?? null),
                'derniere' => $this->date($data['smp_date_derniere_utilisation'] ?? null),
                'defaut'   => empty($data['smp_est_defaut']) ? 0 : 1,
                'actif'    => empty($data['smp_est_actif']) ? 0 : 1,
                'uid'      => $userId ?: null,
                'id'       => $smpId,
                'soc_id'   => $socId,
            ]
        );
    }

    public function supprimerMandat(int $smpId, int $socId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_societes_mandats_prelevement
             SET smp_supprime_le = NOW(), smp_supprime_par_utilisateur_id = :uid
             WHERE smp_id = :id AND smp_societe_id = :soc_id AND smp_supprime_le IS NULL",
            ['id' => $smpId, 'soc_id' => $socId, 'uid' => $userId ?: null]
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // PARC VÉHICULES (1-N)
    // ═══════════════════════════════════════════════════════════════════

    public function vehicules(int $socId): array
    {
        return $this->db->fetchAll(
            "SELECT sve.*, s.soc_nom AS marque_nom
             FROM   sav_societes_vehicules sve
             LEFT JOIN sav_societes s ON s.soc_id = sve.sve_marque_societe_id
             WHERE  sve.sve_societe_id = :id AND sve.sve_supprime_le IS NULL
             ORDER  BY sve.sve_immatriculation ASC, sve.sve_chassis ASC",
            ['id' => $socId]
        );
    }

    public function ajouterVehicule(int $socId, array $data, int $userId): int
    {
        $this->db->execute(
            "INSERT INTO sav_societes_vehicules
             (sve_societe_id, sve_marque_societe_id, sve_code_marque, sve_type_modele,
              sve_chassis, sve_immatriculation, sve_numero_moteur, sve_date_mise_en_circulation,
              sve_cree_par_utilisateur_id)
             VALUES (:soc_id, :marque_soc_id, :code_marque, :type_modele,
                     :chassis, :immat, :moteur, :date_mec, :cree_par)",
            [
                'soc_id'       => $socId,
                'marque_soc_id'=> $this->int($data['sve_marque_societe_id'] ?? null),
                'code_marque'  => $this->str($data['sve_code_marque'] ?? null),
                'type_modele'  => $this->str($data['sve_type_modele'] ?? null),
                'chassis'      => $this->str($data['sve_chassis'] ?? null),
                'immat'        => $this->str($data['sve_immatriculation'] ?? null),
                'moteur'       => $this->str($data['sve_numero_moteur'] ?? null),
                'date_mec'     => $this->date($data['sve_date_mise_en_circulation'] ?? null),
                'cree_par'     => $userId ?: null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function supprimerVehicule(int $sveId, int $socId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_societes_vehicules
             SET sve_supprime_le = NOW(), sve_supprime_par_utilisateur_id = :uid
             WHERE sve_id = :id AND sve_societe_id = :soc_id AND sve_supprime_le IS NULL",
            ['id' => $sveId, 'soc_id' => $socId, 'uid' => $userId ?: null]
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
        // Accepte d/m/Y et Y-m-d
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
