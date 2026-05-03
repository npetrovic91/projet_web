<?php
declare(strict_types=1);

/**
 * AUTOSAV — Autoloader PSR-4 Manuel
 * Fichier : src/autoload.php
 * Rôle    : Charge automatiquement toutes les classes PHP du projet
 *           sans dépendance à Composer.
 *
 * Namespace racine : Nenad\Autosav
 * Répertoire racine : src/
 *
 * Exemples de mapping :
 *   Nenad\Autosav\Core\Router\Router        → src/Core/Router/Router.php
 *   Nenad\Autosav\Modules\Auth\Controllers\LoginController → src/Modules/Auth/Controllers/LoginController.php
 */

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(function (string $class): void {

    // Namespace racine du projet
    $rootNamespace = 'Nenad\\Autosav\\';
    $rootDir       = __DIR__ . '/';

    // Vérifier que la classe appartient à notre namespace
    if (strpos($class, $rootNamespace) !== 0) {
        return;
    }

    // Supprimer le namespace racine
    $relativeClass = substr($class, strlen($rootNamespace));

    // Convertir namespace en chemin de fichier
    $file = $rootDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }

}, true, false);

// ============================================================
// CHARGEMENT MANUEL DES BIBLIOTHÈQUES TIERCES
// (PHPMailer, TCPDF — pas de namespace PSR-4)
// ============================================================

/**
 * Charge PHPMailer si disponible.
 * Usage : dans les services qui envoient des emails.
 */
function autosav_load_phpmailer(): void {
    static $loaded = false;
    if ($loaded) return;

    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        $loaded = true;
        return;
    }

    $composerBase = ROOT_PATH . '/vendor/phpmailer/phpmailer/src/';
    $legacyBase = SRC_PATH . '/Vendor/PHPMailer/';
    foreach ([$composerBase, $legacyBase] as $base) {
        foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $file) {
            if (file_exists($base . $file)) {
                require_once $base . $file;
            }
        }
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            break;
        }
    }
    $loaded = true;
}

/**
 * Charge TCPDF si disponible.
 * Usage : dans les services qui génèrent des PDF.
 */
function autosav_load_tcpdf(): void {
    static $loaded = false;
    if ($loaded) return;

    if (class_exists(\TCPDF::class)) {
        $loaded = true;
        return;
    }

    foreach ([ROOT_PATH . '/vendor/tecnickcom/tcpdf/tcpdf.php', SRC_PATH . '/Vendor/TCPDF/tcpdf.php'] as $file) {
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
    $loaded = true;
}
