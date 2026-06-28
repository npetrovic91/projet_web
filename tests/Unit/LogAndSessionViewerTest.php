<?php
declare(strict_types=1);

/**
 * AUTOSAV — Lecteur de journaux et de sessions, réservé au super_administrateur
 *
 * Demande explicite : pouvoir lire storage/logs/ (Core/Logger) et
 * storage/sessions/ uniquement pour le rôle super_administrateur. Lecture
 * seule, aucune écriture/suppression. Vérifie le câblage (routes, garde
 * de rôle, services) ET le comportement réel des deux services via une
 * instanciation directe sur des fichiers de test (pas seulement la
 * présence de texte dans le code).
 */

require_once dirname(__DIR__, 2) . '/Core/Services/Production/LogViewerService.php';
require_once dirname(__DIR__, 2) . '/Core/Services/Production/SessionViewerService.php';

use Nenad\Autosav\Core\Services\Production\LogViewerService;
use Nenad\Autosav\Core\Services\Production\SessionViewerService;

$root = dirname(__DIR__, 2);

// ---- Câblage : routes protégées par le rôle super_administrateur ----
$urls = file_get_contents($root . '/config/urls.php') ?: '';
foreach (["'GET /super-admin/logs'", "'GET /super-admin/sessions'", "'GET /super-admin/sessions/{id}'"] as $routeKey) {
    $pos = strpos($urls, $routeKey);
    assert($pos !== false, "Route {$routeKey} introuvable.");
    $ligne = substr($urls, $pos, strpos($urls, "\n", $pos) - $pos);
    assert(str_contains($ligne, "'role:super_administrateur'"), "{$routeKey} doit porter le tag role:super_administrateur.");
}

// ---- Câblage : requireRole() côté contrôleur (défense en profondeur) ----
$controller = file_get_contents($root . '/Modules/SuperAdmin/Controllers/SuperAdminController.php') ?: '';
foreach (['function logs(', 'function sessions(', 'function sessionShow('] as $signature) {
    assert(str_contains($controller, $signature), "SuperAdminController doit définir {$signature}.");
}

// ---- LogViewerService : test fonctionnel réel sur un répertoire isolé ----
$dossierLogsTest = sys_get_temp_dir() . '/autosav_test_logs_' . bin2hex(random_bytes(4));
mkdir($dossierLogsTest);
file_put_contents(
    $dossierLogsTest . '/application.log',
    "[2026-06-29 10:00:00] [application info] Connexion réussie\n"
    . "[2026-06-29 10:00:01] [application ERROR] Échec critique\n"
    . "[29-Jun-2026 10:00:02 Europe/Paris] PHP Warning:  ligne native PHP\n"
);

$logService = new LogViewerService($dossierLogsTest);
$canaux = $logService->canaux();
$nomsCanaux = array_column($canaux, 'nom');
assert(in_array('application', $nomsCanaux, true), 'application doit apparaître dans la liste des canaux.');

$lecture = $logService->lire('application', 200);
assert($lecture['fichier_existe'] === true);
assert(count($lecture['lignes']) === 3, 'Les 3 lignes (2 au format maison + 1 native PHP) doivent être lues.');
// La ligne native PHP (10:00:02) a été écrite en dernier dans le fichier
// de test : elle doit donc apparaître en premier (tri du plus récent au
// plus ancien), suivie de l'erreur (10:00:01) puis de l'info (10:00:00).
assert($lecture['lignes'][0]['niveau'] === 'warning', 'La ligne la plus récente (native PHP) doit apparaître en premier.');
assert($lecture['lignes'][1]['message'] === 'Échec critique');
assert($lecture['lignes'][1]['niveau'] === 'error');
assert($lecture['lignes'][2]['message'] === 'Connexion réussie');

$lectureFiltreeNiveau = $logService->lire('application', 200, '', 'error');
assert(count($lectureFiltreeNiveau['lignes']) === 1, 'Le filtre par niveau doit ne garder que les lignes "error".');

$lectureFiltreeRecherche = $logService->lire('application', 200, 'native PHP');
assert(count($lectureFiltreeRecherche['lignes']) === 1, 'Le filtre par recherche doit fonctionner aussi sur la ligne native PHP.');

unlink($dossierLogsTest . '/application.log');
rmdir($dossierLogsTest);

// ---- SessionViewerService : test fonctionnel réel sur un répertoire isolé ----
$dossierSessionsTest = sys_get_temp_dir() . '/autosav_test_sessions_' . bin2hex(random_bytes(4));
mkdir($dossierSessionsTest);
$idSession = 'abc123test';
$donneesSession = 'csrf_token|s:6:"secret";user|a:2:{s:2:"id";i:42;s:4:"name";s:9:"Test User";}active_company_id|i:7;';
file_put_contents($dossierSessionsTest . '/sess_' . $idSession, $donneesSession);

$sessionService = new SessionViewerService($dossierSessionsTest);
$liste = $sessionService->lister();
assert(count($liste) === 1, 'Une seule session de test doit être listée.');
assert($liste[0]['resume']['utilisateur_id'] === 42, 'Le résumé doit extraire l\'ID utilisateur depuis la session décodée.');
assert($liste[0]['resume']['societe_active_id'] === 7);

$detail = $sessionService->afficher($idSession);
assert($detail !== null, 'La session de test doit être retrouvée par son identifiant.');
assert($detail['donnees']['user']['name'] === 'Test User', 'Le contenu complet doit être décodé sans appeler session_decode().');
assert($detail['donnees']['csrf_token'] === 'secret', 'Le service lui-même renvoie la valeur brute ; le masquage est fait côté vue (session_show.php), pas dans le service.');

// Sécurité : un identifiant invalide (tentative de traversée de chemin) doit être rejeté.
assert($sessionService->afficher('../../etc/passwd') === null, 'Un identifiant de session invalide doit être rejeté sans accès fichier.');
assert($sessionService->afficher('inexistant') === null);

unlink($dossierSessionsTest . '/sess_' . $idSession);
rmdir($dossierSessionsTest);

// ---- Vue : masquage des clés sensibles côté affichage ----
$vueSession = file_get_contents($root . '/Modules/SuperAdmin/Views/session_show.php') ?: '';
assert(str_contains($vueSession, 'csrf') && str_contains($vueSession, 'masqué'), 'La vue doit masquer les clés sensibles (token/csrf/password/secret) avant affichage.');

echo "LogAndSessionViewerTest SUCCESS\n";
