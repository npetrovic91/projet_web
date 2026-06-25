<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Abonnements\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Abonnements\Models\AbonnementModel;

class AbonnementService implements ServiceInterface{
    private AbonnementModel $model;

    public function __construct(?AbonnementModel $model = null)
    {
        $this->model = $model ?? new AbonnementModel();
    }

    public function tableauDeBord(?int $societeId = null): array
    {
        return [
            'stats' => $this->model->stats($societeId),
            'abonnements' => $this->model->listerAbonnements(['societe_id' => $societeId]),
            'espaces' => $this->model->listerEspaces(['societe_id' => $societeId]),
            'modules_societes' => $this->model->listerModulesSocietes(['societe_id' => $societeId]),
            'refs' => $this->references(),
        ];
    }

    public function abonnements(array $filters = []): array { return $this->model->listerAbonnements($filters); }
    public function abonnement(int $id): ?array { return $this->model->trouverAbonnement($id); }
    public function formules(array $filters = []): array { return $this->model->listerFormules($filters); }
    public function formule(int $id): ?array { return $this->model->trouverFormule($id); }
    public function espaces(array $filters = []): array { return $this->model->listerEspaces($filters); }
    public function espace(int $id): ?array { return $this->model->trouverEspace($id); }
    public function modulesSocietes(array $filters = []): array { return $this->model->listerModulesSocietes($filters); }
    public function moduleSociete(int $id): ?array { return $this->model->trouverModuleSociete($id); }
    public function references(): array { return $this->model->references(); }
    public function export(?int $societeId = null): array { return $this->model->export($societeId); }

    /**
     * Contrat uniforme pour toutes les actions mutantes de ce service :
     * ['success' => bool, 'id' => ?int, 'message' => ?string, 'errors' => string[]].
     * Les erreurs de validation metier (champ manquant, JSON invalide...)
     * sont retournees, jamais levees en exception — les exceptions
     * restent reservees aux pannes systeme/infra (DB indisponible...),
     * qui remontent naturellement sans etre attrapees ici.
     */
    public function enregistrerAbonnement(array $data, ?int $id, ?int $userId, ?string $ip): array
    {
        $erreurs = $this->validerObligatoires($data, ['abo_societe_id'], 'abonnement');
        if ($erreurs !== []) {
            return ['success' => false, 'id' => $id, 'message' => null, 'errors' => $erreurs];
        }

        $savedId = $this->model->enregistrerAbonnement($data, $id, $userId);
        $this->model->audit($id ? 'abonnement.modifier' : 'abonnement.creer', 'sav_abonnements_societes', $savedId, $userId, $ip, [
            'societe_id' => (int)($data['abo_societe_id'] ?? 0),
            'formule_id' => (int)($data['abo_formule_abonnement_id'] ?? 0),
        ]);
        return ['success' => true, 'id' => $savedId, 'message' => 'Abonnement enregistré.', 'errors' => []];
    }

    /**
     * Cahier des charges (ACC-001) : fait passer une société non abonnée
     * à abonnée en une seule opération (abonnement + espace applicatif),
     * sans toucher à son historique préexistant (relations, contacts,
     * emails reçus, conservés via soc_id quel que soit le statut
     * d'abonnement).
     */
    public function souscrireSociete(int $societeId, int $formuleId, ?int $userId, ?string $ip): array
    {
        if ($societeId <= 0 || $formuleId <= 0) {
            return ['success' => false, 'espace_id' => null, 'historique' => null, 'errors' => ['Société et formule sont obligatoires.']];
        }

        $historiqueAvant = $this->model->historiqueConserve($societeId);
        $espaceId = $this->model->souscrireSociete($societeId, $formuleId, $userId);
        $historiqueApres = $this->model->historiqueConserve($societeId);

        $this->model->audit('societe.souscrire', 'sav_espaces_applicatifs', $espaceId, $userId, $ip, [
            'societe_id' => $societeId,
            'formule_id' => $formuleId,
            'historique_avant' => $historiqueAvant,
            'historique_apres' => $historiqueApres,
        ]);

        return ['success' => true, 'espace_id' => $espaceId, 'historique' => $historiqueApres, 'errors' => []];
    }

    public function historiqueConserve(int $societeId): array
    {
        return $this->model->historiqueConserve($societeId);
    }

    public function supprimerAbonnement(int $id, ?int $userId, ?string $ip): array
    {
        $this->model->supprimerAbonnement($id, $userId);
        $this->model->audit('abonnement.supprimer', 'sav_abonnements_societes', $id, $userId, $ip);
        return ['success' => true, 'message' => 'Abonnement supprimé logiquement.', 'errors' => []];
    }

    public function enregistrerFormule(array $data, ?int $id, ?int $userId, ?string $ip): array
    {
        $erreurs = $this->validerObligatoires($data, ['fab_code', 'fab_nom'], 'formule');
        if (!empty($data['fab_fonctions_json']) && json_decode((string)$data['fab_fonctions_json'], true) === null && json_last_error() !== JSON_ERROR_NONE) {
            $erreurs[] = 'Le JSON des fonctions/modules de la formule est invalide.';
        }
        if ($erreurs !== []) {
            return ['success' => false, 'id' => $id, 'message' => null, 'errors' => $erreurs];
        }

        $savedId = $this->model->enregistrerFormule($data, $id, $userId);
        $this->model->audit($id ? 'formule_abonnement.modifier' : 'formule_abonnement.creer', 'sav_formules_abonnement', $savedId, $userId, $ip, [
            'code' => (string)($data['fab_code'] ?? ''),
        ]);
        return ['success' => true, 'id' => $savedId, 'message' => 'Formule enregistrée.', 'errors' => []];
    }

    public function supprimerFormule(int $id, ?int $userId, ?string $ip): array
    {
        $this->model->supprimerFormule($id, $userId);
        $this->model->audit('formule_abonnement.supprimer', 'sav_formules_abonnement', $id, $userId, $ip);
        return ['success' => true, 'message' => 'Formule supprimée logiquement.', 'errors' => []];
    }

    public function enregistrerEspace(array $data, ?int $id, ?int $userId, ?string $ip): array
    {
        $erreurs = $this->validerObligatoires($data, ['eap_societe_id'], 'espace applicatif');
        if ($erreurs !== []) {
            return ['success' => false, 'id' => $id, 'message' => null, 'errors' => $erreurs];
        }

        $savedId = $this->model->enregistrerEspace($data, $id, $userId);
        $this->model->audit($id ? 'espace_applicatif.modifier' : 'espace_applicatif.creer', 'sav_espaces_applicatifs', $savedId, $userId, $ip, [
            'societe_id' => (int)($data['eap_societe_id'] ?? 0),
            'bloque' => !empty($data['eap_est_bloque']),
        ]);
        return ['success' => true, 'id' => $savedId, 'message' => 'Espace applicatif enregistré.', 'errors' => []];
    }

    public function supprimerEspace(int $id, ?int $userId, ?string $ip): array
    {
        $this->model->supprimerEspace($id, $userId);
        $this->model->audit('espace_applicatif.supprimer', 'sav_espaces_applicatifs', $id, $userId, $ip);
        return ['success' => true, 'message' => 'Espace applicatif supprimé logiquement.', 'errors' => []];
    }

    public function enregistrerModuleSociete(array $data, ?int $id, ?int $userId, ?string $ip): array
    {
        $erreurs = $this->validerObligatoires($data, ['mos_societe_id', 'mos_module_id'], 'module société');
        if ($erreurs !== []) {
            return ['success' => false, 'id' => $id, 'message' => null, 'errors' => $erreurs];
        }

        $savedId = $this->model->enregistrerModuleSociete($data, $id, $userId);
        $this->model->audit($id ? 'module_societe.modifier' : 'module_societe.activer', 'sav_modules_societes', $savedId, $userId, $ip, [
            'societe_id' => (int)($data['mos_societe_id'] ?? 0),
            'module_id' => (int)($data['mos_module_id'] ?? 0),
        ]);
        return ['success' => true, 'id' => $savedId, 'message' => 'Module société enregistré.', 'errors' => []];
    }

    public function supprimerModuleSociete(int $id, ?int $userId, ?string $ip): array
    {
        $this->model->supprimerModuleSociete($id, $userId);
        $this->model->audit('module_societe.supprimer', 'sav_modules_societes', $id, $userId, $ip);
        return ['success' => true, 'message' => 'Activation module/société supprimée logiquement.', 'errors' => []];
    }

    /** @return string[] */
    private function validerObligatoires(array $data, array $champs, string $objet): array
    {
        $erreurs = [];
        foreach ($champs as $champ) {
            if (!isset($data[$champ]) || trim((string)$data[$champ]) === '' || (str_ends_with($champ, '_id') && (int)$data[$champ] <= 0)) {
                $erreurs[] = 'Champ obligatoire manquant pour ' . $objet . ' : ' . $champ;
            }
        }
        return $erreurs;
    }
}
