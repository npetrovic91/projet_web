<?php
declare(strict_types=1);

/**
 * AUTOSAV — Unification des noms CSRF (section 3 du roadmap production)
 *
 * BaseController exposait deux méthodes identiques pour la même garde
 * CSRF : requireCsrf() (implémentation, 3 appelants) et validateCsrf()
 * (simple alias, 31 appelants). validateCsrf() devient la méthode
 * canonique (déjà la plus utilisée en pratique) ; requireCsrf() reste un
 * alias rétro-compatible plutôt que d'être supprimée (pas de suppression
 * d'API publique sans nécessité).
 */

$root = dirname(__DIR__, 2);

$base = file_get_contents($root . '/Core/Controller/BaseController.php') ?: '';
assert($base !== '', 'BaseController.php introuvable.');
assert(
    str_contains($base, 'protected function validateCsrf(): void') && str_contains($base, 'CsrfProtection::validate()'),
    'validateCsrf() doit porter l\'implémentation réelle de la vérification CSRF.'
);
assert(
    preg_match('/protected function requireCsrf\(\): void\s*\{\s*\$this->validateCsrf\(\);/', $base) === 1,
    'requireCsrf() doit être un alias de validateCsrf(), pas une seconde implémentation.'
);

$verrous = file_get_contents($root . '/Modules/Verrous/Controllers/VerrousController.php') ?: '';
assert($verrous !== '', 'VerrousController.php introuvable.');
assert(
    !str_contains($verrous, '$this->requireCsrf()'),
    'VerrousController doit utiliser le nom canonique validateCsrf(), pas l\'ancien alias requireCsrf().'
);

echo "CsrfNamingUnificationTest SUCCESS\n";
