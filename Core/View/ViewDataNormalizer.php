<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\View;

/**
 * Normalise les donnees transmises aux vues.
 *
 * Objectif Phase 3 : eviter le melange historique com_* / soc_* dans les vues
 * tout en conservant la compatibilite avec les anciens modules Companies.
 */
final class ViewDataNormalizer
{
    /** @var array<string,string> */
    private const SOCIETE_ALIASES = [
        'com_id' => 'soc_id',
        'com_uuid' => 'soc_uuid',
        'com_code' => 'soc_code',
        'com_name' => 'soc_nom',
        'com_legal_name' => 'soc_nom_legal',
        'com_short_name' => 'soc_nom_court',
        'com_siret' => 'soc_siret',
        'com_vat_number' => 'soc_numero_tva',
        'com_address' => 'soc_adresse',
        'com_postal_code' => 'soc_code_postal',
        'com_zipcode' => 'soc_code_postal',
        'com_city' => 'soc_ville',
        'com_phone' => 'soc_telephone',
        'com_email' => 'soc_email',
        'com_website' => 'soc_site_web',
        'com_logo_url' => 'soc_logo_fichier_id',
        'com_is_holding' => 'soc_est_holding',
        'com_parent_id' => 'soc_societe_parente_id',
        'com_holding_id' => 'soc_holding_id',
        'com_created_by_company_id' => 'soc_cree_par_societe_id',
        'com_status' => 'soc_statut_code',
    ];

    public static function normalize(array $data): array
    {
        foreach (['societe', 'company'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = self::societe($data[$key]);
            }
        }

        foreach (['societes', 'companies', 'holdings', 'parents'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = array_map(
                    static fn($row) => is_array($row) ? self::societe($row) : $row,
                    $data[$key]
                );
            }
        }

        return $data;
    }

    public static function societe(array $row): array
    {
        foreach (self::SOCIETE_ALIASES as $ancien => $nouveau) {
            if (array_key_exists($ancien, $row) && !array_key_exists($nouveau, $row)) {
                $row[$nouveau] = $row[$ancien];
            }
            if (array_key_exists($nouveau, $row) && !array_key_exists($ancien, $row)) {
                $row[$ancien] = $row[$nouveau];
            }
        }
        return $row;
    }
}
