<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Controller;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Request\Request;
use Nenad\Autosav\Core\Security\SecurityManager;
use Nenad\Autosav\Core\Security\Class\CsrfProtection;
use Nenad\Autosav\Core\View\BaseView;

/**
 * AUTOSAV — Controleur de base
 * Fichier : src/Core/Controller/BaseController.php
 *
 * CORRECTION CRITIQUE :
 *   La propriete $db (PDO) est desormais chargee de facon LAZY via __get().
 *   Avant cette correction, le constructeur appelait Database::getInstance()
 *   immediatement, ce qui forcait une connexion MySQL meme pour des pages
 *   publiques n'en ayant pas besoin (ex : affichage du formulaire de login).
 *   En cas d'echec de connexion, cela produisait un 503 sur toutes les pages.
 *
 *   Compatibilite : Aucune modification requise dans les sous-classes.
 *   $this->db->query(...) continue de fonctionner exactement comme avant.
 *   La propriete n'est initialisee qu'au premier acces effectif.
 */
abstract class BaseController
{
    // ---------------------------------------------------------------
    // PROPRIETES INTERNES (connexion DB lazy)
    // La propriete publique $db est INTENTIONNELLEMENT non declaree
    // comme typed property pour permettre l'initialisation via __get().
    // ---------------------------------------------------------------

    /** Instance Database (conteneur lazy) — null jusqu'au premier acces */
    private ?Database $_dbInstance = null;

    /** Instance PDO reelle — null jusqu'au premier acces a $this->db */
    private ?\PDO $_pdo = null;

    // ---------------------------------------------------------------
    // PROPRIETES STANDARD (initialisees dans le constructeur)
    // ---------------------------------------------------------------

    /** @var SecurityManager Facade securite */
    protected SecurityManager $security;

    /** @var \Nenad\Autosav\Core\Logger\Class\Logger Logger applicatif */
    protected mixed $logger;

    /** @var Request Abstraction requete HTTP */
    protected Request $request;

    /**
     * Constructeur — n'ouvre PLUS la connexion DB.
     * La connexion s'etablit uniquement au premier acces a $this->db.
     *
     * @param Database|null $database Instance DB pre-construite (injection de dependance).
     *                                Si null, Database::getInstance() sera appele
     *                                au premier acces a $this->db.
     */
    public function __construct(?Database $database = null)
    {
        $this->_dbInstance = $database; // Stocke sans connecter
        $this->security    = SecurityManager::getInstance();
        $this->logger      = LogManager::getInstance()->channel('application');
        $this->request     = new Request();
    }

    // ---------------------------------------------------------------
    // LAZY LOADING de $this->db via __get()
    // PHP appelle __get() uniquement pour les proprietes non declarees.
    // Puisque $db n'est PAS declaree comme typed property, __get('db')
    // est appele a chaque $this->db->xxx() et retourne le PDO.
    // ---------------------------------------------------------------

    /**
     * Acces magique aux proprietes non declarees.
     * Gere uniquement 'db' : retourne le PDO en l'initialisant si besoin.
     *
     * @throws \RuntimeException Si la propriete demandee est inconnue.
     */
    public function __get(string $name): mixed
    {
        if ($name === 'db') {
            if ($this->_pdo === null) {
                $instance    = $this->_dbInstance ?? Database::getInstance();
                $this->_pdo  = $instance->getPdo();
            }
            return $this->_pdo;
        }
        throw new \RuntimeException(
            sprintf('Propriete "%s" non definie dans %s.', $name, static::class)
        );
    }

    /**
     * Permet l'ecriture de $this->db = ... (compatibilite si necessaire).
     */
    public function __set(string $name, mixed $value): void
    {
        if ($name === 'db' && $value instanceof \PDO) {
            $this->_pdo = $value;
            return;
        }
        throw new \RuntimeException(
            sprintf('Propriete "%s" non definie dans %s.', $name, static::class)
        );
    }

    /**
     * Permet isset($this->db) — retourne true si la connexion est etablie.
     */
    public function __isset(string $name): bool
    {
        return $name === 'db' && $this->_pdo !== null;
    }

    // ---------------------------------------------------------------
    // METHODES COMMUNES AUX CONTROLEURS
    // ---------------------------------------------------------------

    /**
     * Retourne les donnees de l'utilisateur courant depuis la session.
     */
    protected function getCurrentUser(): array
    {
        return [
            'use_id'                => $_SESSION['user_id']        ?? 0,
            'use_email'             => $_SESSION['user_email']     ?? '',
            'use_firstname'         => $_SESSION['user_firstname'] ?? '',
            'use_lastname'          => $_SESSION['user_lastname']  ?? '',
            'use_active_company_id' => $_SESSION['active_company_id'] ?? null,
            'use_active_brand_id'   => $_SESSION['active_brand_id']   ?? null,
            'roles'                 => $_SESSION['user_roles']       ?? [],
            'permissions'           => $_SESSION['user_permissions'] ?? [],
        ];
    }

    /**
     * Rend une vue avec les donnees fournies dans un layout.
     *
     * @param string $viewPath  Chemin de la vue relatif a src/Modules/
     *                          Ex: "Auth/Views/login"
     * @param array  $data      Donnees passees a la vue
     * @param string $layout    Nom du layout : 'main', 'public', 'minimal', 'none'
     * @param int    $httpCode  Code HTTP de la reponse
     */
    protected function render(
        string $viewPath,
        array  $data     = [],
        string $layout   = 'main',
        int    $httpCode = 200
    ): void {
        http_response_code($httpCode);
        BaseView::render($viewPath, $data, $layout);
    }

    /**
     * Redirige vers une URL et arrete l'execution.
     */
    protected function redirect(string $path, int $code = 302): never
    {
        header('Location: ' . url($path), true, $code);
        exit;
    }

    /**
     * Retourne une reponse JSON normalisee.
     */
    protected function json(
        bool   $success = true,
        mixed  $data    = null,
        string $message = '',
        int    $code    = 200,
        ?array $errors  = null,
        ?array $meta    = null
    ): never {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => $success,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors,
            'meta'    => $meta,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Retourne une erreur JSON normalisee.
     */
    protected function jsonError(
        string $message,
        int    $code   = 400,
        ?array $errors = null
    ): never {
        $this->json(false, null, $message, $code, $errors);
    }

    /**
     * Verifie que l'utilisateur est authentifie.
     * En cas d'echec, redirige vers le login ou retourne 401 en AJAX.
     */
    protected function requireAuth(): void
    {
        if (!is_authenticated()) {
            if ($this->isAjax()) {
                $this->jsonError('Session expiree. Veuillez vous reconnecter.', 401);
            }
            $this->redirect('/auth/login');
        }
    }

    /**
     * Verifie la validite du token CSRF.
     */
    protected function requireCsrf(): void
    {
        if (!CsrfProtection::validate()) {
            if ($this->isAjax()) {
                $this->jsonError('Token de securite invalide.', 403);
            }
            http_response_code(403);
            echo '<h1>403 &mdash; Token de securite invalide</h1>';
            echo '<p>Votre session a peut-etre expire. Veuillez recharger la page.</p>';
            exit;
        }
    }

    /**
     * Alias de requireCsrf() pour coherence de nommage.
     */
    protected function validateCsrf(): void
    {
        $this->requireCsrf();
    }

    /**
     * Verifie que l'utilisateur possede le role requis.
     */
    protected function requireRole(string|array $role): void
    {
        $allowed = false;
        foreach ((array) $role as $requiredRole) {
            if (has_role((string) $requiredRole)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            if ($this->isAjax()) {
                $this->jsonError('Acces refuse.', 403);
            }
            http_response_code(403);
            $errorView = SRC_PATH . '/Core/Theme/Views/errors/403.php';
            file_exists($errorView)
                ? include $errorView
                : print('<h1>403 &mdash; Acces refuse</h1>');
            exit;
        }
    }

    /**
     * Verifie qu'un utilisateur possede une permission specifique.
     */
    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();

        // SuperAdmin a toujours tous les droits
        if (has_role('SUPERADMIN')) {
            return;
        }

        $permissions = $_SESSION['user_permissions'] ?? [];
        if (!in_array($permission, $permissions, true)) {
            if ($this->isAjax()) {
                $this->jsonError('Permission insuffisante : ' . $permission, 403);
            }
            http_response_code(403);
            $errorView = SRC_PATH . '/Core/Theme/Views/errors/403.php';
            file_exists($errorView)
                ? include $errorView
                : print('<h1>403 &mdash; Acces refuse</h1>');
            exit;
        }
    }

    /**
     * Determine si la requete courante est AJAX.
     */
    protected function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Recupere un input POST.
     */
    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Recupere un input GET.
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Recupere le body JSON d'une requete AJAX.
     */
    protected function jsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Gestionnaire de messages flash en session.
     * Usage : $this->flash()->error('Message') ou $this->flash('error', 'Message')
     */
    protected function flash(?string $type = null, ?string $message = null): mixed
    {
        if ($type !== null && $message !== null) {
            $_SESSION['flash'][$type][] = $message;
            return null;
        }

        return new class {
            public function success(string $msg): void { $_SESSION['flash']['success'][] = $msg; }
            public function error(string $msg): void   { $_SESSION['flash']['error'][]   = $msg; }
            public function warning(string $msg): void { $_SESSION['flash']['warning'][] = $msg; }
            public function info(string $msg): void    { $_SESSION['flash']['info'][]    = $msg; }
            public function all(): array {
                $flash = $_SESSION['flash'] ?? [];
                unset($_SESSION['flash']);
                return $flash;
            }
        };
    }

    /**
     * Alias de flash() pour coherence de nommage.
     */
    protected function setFlash(string $type, string $message): void
    {
        $this->flash($type, $message);
    }

    protected function getRequest(): Request
    {
        return $this->request;
    }

    protected function isAuthenticated(): bool
    {
        return is_authenticated();
    }

    protected function csrfToken(): string
    {
        return CsrfProtection::getToken();
    }

    protected function verifyCsrf(?string $token): bool
    {
        return CsrfProtection::validateTokenValue((string) $token);
    }

    protected function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    protected function activeCompanyId(): ?int
    {
        return isset($_SESSION['active_company_id']) ? (int) $_SESSION['active_company_id'] : null;
    }

    protected function activeBrandId(): ?int
    {
        return isset($_SESSION['active_brand_id']) ? (int) $_SESSION['active_brand_id'] : null;
    }
}
