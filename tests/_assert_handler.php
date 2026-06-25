<?php
declare(strict_types=1);

/**
 * AUTOSAV — Handler de visibilité pour les tests en CLI.
 *
 * En production, config/environment.php force display_errors=0 (APP_DEBUG
 * suit APP_ENV). Sans ce fichier, un AssertionError non capturé dans un
 * test ne produit AUCUNE sortie (ni stdout ni stderr) : le test échoue
 * silencieusement avec le seul indice d'un exit code 255. Ce handler,
 * chargé en auto_prepend_file par bin/run_tests.php, intercepte toute
 * exception/erreur non capturée et l'affiche explicitement avant de
 * sortir en code 1, indépendamment du réglage display_errors de l'app.
 */
set_exception_handler(static function (\Throwable $e): void {
    fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getFile() . ':' . $e->getLine() . "\n");
    exit(1);
});
