<?php
declare(strict_types=1);

/**
 * AUTOSAV — Garde-fou « deny > allow » (ABAC)
 *
 * Contrainte non négociable du cahier des charges : un deny explicite doit
 * toujours l'emporter sur un allow, quel que soit l'ordre ou le nombre de
 * politiques. Ce test ne peut pas s'appuyer sur une vraie connexion DB
 * (AbacEngine charge ses politiques via Database::getInstance(), non
 * injectable sans refactor) ; il verrouille donc la structure du code par
 * inspection de source, comme les autres tests de ce dossier.
 *
 * Si ce test casse après une modification d'AbacEngine::evaluate(), c'est
 * le signal qu'il faut relire très attentivement la nouvelle logique avant
 * de mettre à jour ce test.
 */

$root = dirname(__DIR__, 2);
$abacEngine = file_get_contents($root . '/Core/Security/AbacEngine.php') ?: '';

assert($abacEngine !== '', 'AbacEngine.php introuvable.');

// 1) La méthode doit collecter TOUS les deny rencontrés (pas de "return" au
//    premier deny trouvé, sinon une politique allow de priorité plus haute
//    examinée plus tard ne serait jamais comparée — mais l'inverse doit
//    rester vrai : un deny de priorité plus basse doit quand même gagner).
assert(
    str_contains($abacEngine, '$denyReasons[] ='),
    'AbacEngine doit accumuler les deny rencontrés dans $denyReasons.'
);

// 2) La décision finale doit tester $denyReasons avant tout autre critère,
//    et retourner "denied" si non vide — sans condition supplémentaire qui
//    permettrait à un allow de l'emporter.
assert(
    (bool) preg_match(
        '/if\s*\(\s*\$denyReasons\s*!==\s*\[\]\s*\)\s*\{\s*return\s+AbacDecision::denied/s',
        $abacEngine
    ),
    'AbacEngine doit retourner AbacDecision::denied() dès que $denyReasons n\'est pas vide, sans condition additionnelle.'
);

// 3) Le retour "allowed" ne doit être atteignable que dans le code QUI SUIT
//    ce contrôle deny (donc après le "return denied" ci-dessus dans le texte
//    source) — on vérifie que le bloc allow apparaît après le bloc deny.
$denyPos  = strpos($abacEngine, 'AbacDecision::denied($permissionCode, $denyReasons)');
$allowPos = strpos($abacEngine, 'AbacDecision::allowed($permissionCode)');
assert($denyPos !== false && $allowPos !== false, 'Retours denied()/allowed() introuvables dans evaluate().');
assert($denyPos < $allowPos, 'Le retour allowed() ne doit apparaitre dans le code qu\'apres le controle deny (deny > allow).');

// 4) hasAccess() doit consulter le RBAC d'abord (court-circuit rapide), puis
//    refuser dès que la décision ABAC est deny, sans jamais inverser l'ordre.
assert(
    str_contains($abacEngine, 'if ($decision->isDenied())') && str_contains($abacEngine, 'return false;'),
    'hasAccess() doit refuser explicitement l\'accès quand isDenied() est vrai.'
);

echo "AbacDenyPrecedenceTest SUCCESS\n";
