<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services\Concerns;

/**
 * AUTOSAV — Convention de retour pour les méthodes de mutation des Services
 *
 * CORRECTIF 2.3 (convergence gestion d'erreurs, 2026-06-27) : ~11 des 44
 * Services du projet utilisaient déjà ce shape (AbonnementService,
 * AuthService, UserService, BrandService, JobService...) sans qu'il soit
 * formalisé nulle part — chaque nouveau Service le redécouvrait par copie.
 * Ce trait ne change AUCUN comportement existant : il documente et
 * factorise la construction du tableau déjà dominant pour les nouvelles
 * méthodes de mutation.
 *
 * Convention (mutations uniquement — créer/modifier/supprimer/décider) :
 *   - succès  : ok(['id' => $id])              -> ['success'=>true,  'message'=>'', 'errors'=>[], 'id'=>$id]
 *   - échec   : fail('Code invalide.', $errors) -> ['success'=>false, 'message'=>'Code invalide.', 'errors'=>$errors]
 *
 * Hors de ce périmètre (volontairement PAS converti à ce shape) :
 *   - Lectures (find/lister/...) : retournent les données directement, ou
 *     `null`/`?array` pour "introuvable". Ne jamais mélanger avec ['success'=>...].
 *   - Gardes booléennes (peutXxx(), estYyy()) : retournent un bool brut —
 *     c'est un contrat oui/non, pas un résultat d'opération. Les convertir
 *     en tableau structuré n'ajouterait rien et casserait l'usage en
 *     condition (if ($service->peutModifier(...))).
 *   - Erreurs systèmes/infrastructure imprévues : laisser remonter
 *     l'exception jusqu'au contrôleur ou au filet de sécurité global
 *     (Core/Error/GlobalErrorHandler.php) plutôt que de l'avaler dans un
 *     ['success'=>false] générique qui masquerait la vraie cause.
 */
trait ServiceResponse
{
    /**
     * @param array<string, mixed> $extra Fusionné dans la réponse (ex. ['id' => $id]).
     */
    protected function ok(array $extra = [], string $message = ''): array
    {
        return array_merge(
            ['success' => true, 'message' => $message, 'errors' => []],
            $extra
        );
    }

    /**
     * @param array<string, string>|string[] $errors
     */
    protected function fail(string $message, array $errors = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors === [] ? [$message] : $errors,
        ];
    }
}
