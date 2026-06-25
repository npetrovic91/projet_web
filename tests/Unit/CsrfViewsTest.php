<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$racine = SRC_PATH . '/Modules';
$erreurs = [];
$nbVues = 0;
$nbFormulairesPost = 0;
$iterateur = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racine));

foreach ($iterateur as $fichier) {
    if (!$fichier->isFile() || $fichier->getExtension() !== 'php') {
        continue;
    }
    $chemin = $fichier->getPathname();
    if (!str_contains($chemin, DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $nbVues++;
    $contenu = (string) file_get_contents($chemin);
    $relatif = str_replace(SRC_PATH . DIRECTORY_SEPARATOR, '', $chemin);

    if (preg_match('/name=["\']csrf_token["\']/', $contenu)) {
        $erreurs[] = $relatif . ' : ancien champ csrf_token détecté';
    }

    if (preg_match_all('/<form\b(?=[^>]*method=["\']?post["\']?)[^>]*>(.*?)<\/form>/is', $contenu, $formulaires)) {
        foreach ($formulaires[0] as $formulaire) {
            $nbFormulairesPost++;
            $ok = str_contains($formulaire, 'csrfField')
                || str_contains($formulaire, 'csrf_field')
                || str_contains($formulaire, 'CSRF_FORM_FIELD')
                || str_contains($formulaire, '_csrf_token')
                || str_contains($formulaire, 'AJAX_CSRF_FIELD');
            if (!$ok) {
                $erreurs[] = $relatif . ' : formulaire POST sans champ CSRF standardisé';
            }
        }
    }
}

assert($nbVues > 0, 'Aucune vue détectée.');
assert($nbFormulairesPost > 0, 'Aucun formulaire POST détecté.');
assert($erreurs === [], implode("\n", $erreurs));

echo "CsrfViewsTest SUCCESS — {$nbVues} vues contrôlées, {$nbFormulairesPost} formulaires POST protégés\n";
