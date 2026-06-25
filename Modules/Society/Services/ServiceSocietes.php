<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Society\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Modules\Companies\Models\SocieteComplementModel;
use Nenad\Autosav\Modules\Society\Models\ModeleMarqueSociete;
use Nenad\Autosav\Modules\Society\Models\ModeleRelationSociete;
use Nenad\Autosav\Modules\Society\Models\ModeleSociete;
use Nenad\Autosav\Modules\Society\Models\ModeleTypeSociete;

class ServiceSocietes implements ServiceInterface{
    private ModeleSociete $societes;
    private ModeleTypeSociete $types;
    private ModeleMarqueSociete $marques;
    private ModeleRelationSociete $relations;
    private ServiceValidationSocietes $validation;
    private ServiceAccesSocietes $acces;
    private SocieteComplementModel $complement;

    public function __construct()
    {
        $this->societes    = new ModeleSociete();
        $this->types       = new ModeleTypeSociete();
        $this->marques     = new ModeleMarqueSociete();
        $this->relations   = new ModeleRelationSociete();
        $this->validation  = new ServiceValidationSocietes();
        $this->acces       = new ServiceAccesSocietes();
        $this->complement  = new SocieteComplementModel();
    }

    public function lister(array $filtres = [], int $page = 1, int $parPage = 25): array
    {
        $perimetre = $this->acces->filtresDePerimetre();
        if (!empty($perimetre['ids_societes'])) {
            $resultat = $this->societes->paginer($filtres, 1, 500);
            $ids = array_flip(array_map('intval', $perimetre['ids_societes']));
            $lignes = array_values(array_filter($resultat['lignes'], static fn($s) => isset($ids[(int) ($s['soc_id'] ?? $s['com_id'] ?? 0)])));
            $resultat['lignes'] = $lignes;
            $resultat['rows'] = $lignes;
            $resultat['total'] = count($lignes);
            return $resultat;
        }
        return $this->societes->paginer($filtres, $page, $parPage);
    }

    public function ficheComplete(int $idSociete): ?array
    {
        $societe = $this->societes->ficheComplete($idSociete);
        if (!$societe) {
            return null;
        }
        $marques = $this->marques->marquesDeSociete($idSociete);
        $complement = $this->complement->toutPourSociete($idSociete);
        return [
            'societe'    => $societe,
            'company'    => $societe,
            'marques'    => $marques,
            'brands'     => $marques,
            'relations'  => $this->relations->relationsDeSociete($idSociete),
            'enfants'    => $this->relations->enfants($idSociete),
            // Extensions lot40
            'infos_complementaires' => $complement['infos_complementaires'],
            'comptes_bancaires'     => $complement['comptes_bancaires'],
            'mandats'               => $complement['mandats'],
            'vehicules'             => $complement['vehicules'],
        ];
    }

    public function sauvegarderInfosComplementaires(int $idSociete, array $input, int $idUtilisateur): bool
    {
        return $this->complement->sauvegarderInfosComplementaires($idSociete, $input, $idUtilisateur);
    }

    public function ajouterCompteBancaire(int $idSociete, array $input, int $idUtilisateur): int
    {
        return $this->complement->ajouterCompteBancaire($idSociete, $input, $idUtilisateur);
    }

    public function supprimerCompteBancaire(int $scbId, int $idSociete, int $idUtilisateur): bool
    {
        return $this->complement->supprimerCompteBancaire($scbId, $idSociete, $idUtilisateur);
    }

    public function ajouterMandat(int $idSociete, array $input, int $idUtilisateur): int
    {
        return $this->complement->ajouterMandat($idSociete, $input, $idUtilisateur);
    }

    public function mettreAJourMandat(int $smpId, int $idSociete, array $input, int $idUtilisateur): bool
    {
        return $this->complement->mettreAJourMandat($smpId, $idSociete, $input, $idUtilisateur);
    }

    public function supprimerMandat(int $smpId, int $idSociete, int $idUtilisateur): bool
    {
        return $this->complement->supprimerMandat($smpId, $idSociete, $idUtilisateur);
    }

    public function ajouterVehicule(int $idSociete, array $input, int $idUtilisateur): int
    {
        return $this->complement->ajouterVehicule($idSociete, $input, $idUtilisateur);
    }

    public function supprimerVehicule(int $sveId, int $idSociete, int $idUtilisateur): bool
    {
        return $this->complement->supprimerVehicule($sveId, $idSociete, $idUtilisateur);
    }

    public function enregistrer(?int $idSociete, array $input, int $idUtilisateur): array
    {
        $erreurs = $this->validation->valider($input);
        if ($erreurs !== []) {
            return ['success' => false, 'id' => $idSociete, 'errors' => $erreurs, 'erreurs' => $erreurs];
        }

        $statutId = $this->statutGeneralId((string) ($input['soc_statut_code'] ?? $input['com_status'] ?? 'actif'));
        if (isset($input['com_is_active']) && (int) $input['com_is_active'] === 0) {
            $statutId = 2;
        }

        $nom = trim((string) ($input['soc_nom'] ?? $input['com_name']));
        $code = trim((string) ($input['soc_code'] ?? $input['com_code'] ?? ''));
        if ($code === '') {
            $code = $this->codeDepuisNom($nom);
        }

        $donnees = [
            'uuid' => $this->uuidV4(),
            'code' => $code,
            'nom' => $nom,
            'raison_sociale' => trim((string) ($input['soc_nom_legal'] ?? $input['com_legal_name'] ?? '')) ?: null,
            'nom_court' => trim((string) ($input['soc_nom_court'] ?? $input['com_short_name'] ?? '')) ?: null,
            'types_ids' => $this->typesIds($input),
            'siret' => preg_replace('/\D+/', '', (string) ($input['soc_siret'] ?? $input['com_siret'] ?? '')) ?: null,
            'tva' => trim((string) ($input['soc_numero_tva'] ?? $input['com_vat_number'] ?? '')) ?: null,
            'adresse' => trim((string) ($input['soc_adresse'] ?? $input['com_address'] ?? '')) ?: null,
            'code_postal' => trim((string) ($input['soc_code_postal'] ?? $input['com_postal_code'] ?? $input['com_zipcode'] ?? '')) ?: null,
            'ville' => trim((string) ($input['soc_ville'] ?? $input['com_city'] ?? '')) ?: null,
            'pays_id' => $this->paysId((string) ($input['soc_pays'] ?? $input['com_country'] ?? 'France')),
            'telephone' => trim((string) ($input['soc_telephone'] ?? $input['com_phone'] ?? '')) ?: null,
            'email' => trim((string) ($input['soc_email'] ?? $input['com_email'] ?? '')) ?: null,
            'site_web' => trim((string) ($input['soc_site_web'] ?? $input['com_website'] ?? '')) ?: null,
            'logo_fichier_id' => !empty($input['soc_logo_fichier_id']) ? (int) $input['soc_logo_fichier_id'] : null,
            'est_holding' => !empty($input['soc_est_holding']) || !empty($input['com_is_holding']) ? 1 : 0,
            'parent_id' => !empty($input['soc_societe_parente_id'] ?? $input['com_parent_id'] ?? null) ? (int) ($input['soc_societe_parente_id'] ?? $input['com_parent_id']) : null,
            'holding_id' => !empty($input['soc_holding_id'] ?? $input['com_holding_id'] ?? null) ? (int) ($input['soc_holding_id'] ?? $input['com_holding_id']) : null,
            'cree_par_societe_id' => !empty($input['soc_cree_par_societe_id']) ? (int) $input['soc_cree_par_societe_id'] : null,
            'statut_id' => $statutId,
            'cree_par' => $idUtilisateur ?: null,
            'modifie_par' => $idUtilisateur ?: null,
        ];

        if ($idSociete !== null) {
            unset($donnees['uuid'], $donnees['cree_par'], $donnees['cree_par_societe_id']);
            $this->societes->modifier($idSociete, $donnees);
            return ['success' => true, 'id' => $idSociete, 'errors' => [], 'erreurs' => []];
        }

        unset($donnees['modifie_par']);
        $id = $this->societes->creer($donnees);
        return ['success' => true, 'id' => $id, 'errors' => [], 'erreurs' => []];
    }

    public function desactiver(int $idSociete, int $idUtilisateur): bool
    {
        return $this->societes->desactiver($idSociete, $idUtilisateur);
    }

    public function reactiver(int $idSociete, int $idUtilisateur): bool
    {
        return $this->societes->reactiver($idSociete, $idUtilisateur);
    }

    public function supprimer(int $idSociete, int $idUtilisateur): bool
    {
        return $this->societes->supprimerLogiquement($idSociete, $idUtilisateur);
    }

    public function types(): array { return $this->types->tousActifs(); }
    public function typesAdministrables(): array { return $this->types->tousAdministrables(); }
    public function typeAdministrable(int $id): ?array { return $this->types->trouver($id); }
    public function enregistrerType(array $input, ?int $id, int $idUtilisateur): int { return $this->types->enregistrer($input, $id, $idUtilisateur); }
    public function supprimerType(int $id, int $idUtilisateur): void { $this->types->supprimerLogiquement($id, $idUtilisateur); }
    public function holdings(): array { return $this->societes->holdings(); }
    public function societesActives(?string $type = null): array { return $this->societes->toutesActives($type); }
    public function marquesDisponibles(): array { return $this->marques->marquesDisponibles(); }

    public function attacherMarque(int $idSociete, int $idMarque, int $idUtilisateur, bool $principale = false): bool
    {
        return $this->marques->attacher($idSociete, $idMarque, $idUtilisateur, $principale);
    }

    public function detacherMarque(int $idSociete, int $idMarque, int $idUtilisateur): bool
    {
        return $this->marques->detacher($idSociete, $idMarque, $idUtilisateur);
    }

    public function ajouterRelation(int $parent, int $enfant, string $type, int $idUtilisateur): array
    {
        if ($parent <= 0 || $enfant <= 0) {
            return ['success' => false, 'message' => 'Les deux sociétés doivent être renseignées.'];
        }
        if ($parent === $enfant) {
            return ['success' => false, 'message' => 'Une société ne peut pas être reliée à elle-même.'];
        }
        $type = trim($type) !== '' ? trim($type) : 'relation';
        if ($this->relations->existe($parent, $enfant, $type)) {
            return ['success' => false, 'message' => 'Cette relation existe déjà.'];
        }
        $this->relations->creer($parent, $enfant, $type, $idUtilisateur);
        return ['success' => true, 'message' => 'Relation créée.'];
    }

    public function retirerRelation(int $idRelation, int $idUtilisateur): bool
    {
        return $this->relations->desactiver($idRelation, $idUtilisateur);
    }

    private function statutGeneralId(string $code): int
    {
        $normalise = strtolower(trim($code));
        $mapping = [
            'active' => 'actif',
            'actif' => 'actif',
            'inactive' => 'inactif',
            'inactif' => 'inactif',
            'blocked' => 'bloque',
            'bloque' => 'bloque',
            'archived' => 'archive',
            'archive' => 'archive',
            'deleted' => 'supprime',
            'supprime' => 'supprime',
        ];
        $codeStatut = $mapping[$normalise] ?? 'actif';
        $row = Database::getInstance()->fetch(
            "SELECT sta_id FROM sav_statuts WHERE sta_domaine = 'general' AND sta_code = :code LIMIT 1",
            ['code' => $codeStatut]
        );
        return (int) ($row['sta_id'] ?? 1);
    }

    private function paysId(string $pays): int
    {
        $pays = trim($pays) ?: 'France';
        $row = Database::getInstance()->fetch(
            "SELECT pay_id FROM sav_pays
             WHERE LOWER(pay_nom) = LOWER(:pays)
                OR LOWER(pay_code_iso2) = LOWER(:pays)
                OR LOWER(pay_code_iso3) = LOWER(:pays)
             LIMIT 1",
            ['pays' => $pays]
        );
        return (int) ($row['pay_id'] ?? 1);
    }

    private function uuidV4(): string
    {
        if (function_exists('generate_uuid')) {
            return (string) generate_uuid();
        }
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function codeDepuisNom(string $nom): string
    {
        $code = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom) ?: $nom;
        $code = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', $code) ?? 'SOC');
        $code = trim($code, '_') ?: 'SOC';
        return substr($code, 0, 30);
    }

    /**
     * @return array<int>
     */
    private function typesIds(array $input): array
    {
        $types = $input['soc_types_ids'] ?? $input['com_types_ids'] ?? $input['types_ids'] ?? null;
        if ($types === null) {
            $types = [$input['soc_type_id'] ?? $input['com_type_id'] ?? $input['type_id'] ?? null];
        }
        if (!is_array($types)) {
            $types = [$types];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $types),
            static fn(int $id): bool => $id > 0
        )));
    }
}