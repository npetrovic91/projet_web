<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Society\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

class ServiceValidationSocietes implements ServiceInterface{
    /** @var int[] Identifiants sav_types_societes considérés comme "concession" */
    private const TYPES_CONCESSION = [7];

    public function valider(array $donnees): array
    {
        $erreurs = [];
        $typesIds = $this->typesIds($donnees);
        if ($typesIds === []) {
            $erreurs['types_ids'] = 'Au moins un type de société est obligatoire.';
        }

        // Cahier des charges : "Chaque concession doit avoir un
        // importateur_id obligatoire." Vérifié ici même si le champ est
        // absent ou vide côté formulaire (le backend ne fait pas confiance
        // à l'UI).
        if (array_intersect($typesIds, self::TYPES_CONCESSION) !== []) {
            $importateurId = (int) ($donnees['soc_importateur_id'] ?? $donnees['importateur_id'] ?? 0);
            if ($importateurId <= 0) {
                $erreurs['soc_importateur_id'] = 'Une concession doit obligatoirement être rattachée à un importateur.';
            }
        }
        if (trim((string) ($donnees['soc_nom'] ?? $donnees['com_name'] ?? '')) === '') {
            $erreurs['soc_nom'] = 'Le nom de la société est obligatoire.';
        }
        $email = (string) ($donnees['soc_email'] ?? $donnees['com_email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs['soc_email'] = 'Adresse email invalide.';
        }
        $siret = (string) ($donnees['soc_siret'] ?? $donnees['com_siret'] ?? '');
        if ($siret !== '') {
            $digits = preg_replace('/\D+/', '', $siret);
            if (strlen((string) $digits) !== 14) {
                $erreurs['soc_siret'] = 'Le SIRET doit contenir 14 chiffres.';
            }
        }
        return $erreurs;
    }

    /**
     * @return array<int>
     */
    private function typesIds(array $donnees): array
    {
        $types = $donnees['soc_types_ids'] ?? $donnees['com_types_ids'] ?? $donnees['types_ids'] ?? null;
        if ($types === null) {
            $types = [$donnees['soc_type_id'] ?? $donnees['com_type_id'] ?? $donnees['type_id'] ?? null];
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
