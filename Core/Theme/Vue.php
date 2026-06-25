<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme;

use Nenad\Autosav\Core\Alertes\SweetAlertGenerator;
use Nenad\Autosav\Core\Security\Class\CspNonce;
use Nenad\Autosav\Core\Security\Class\SessionHandler;
use Nenad\Autosav\Core\Theme\Support\Config;
use Nenad\Autosav\Core\Theme\Support\Esc;
use Nenad\Autosav\Core\Theme\Support\SecurityBridge;
use Nenad\Autosav\Core\Theme\Components\Navbar;
use Nenad\Autosav\Core\Theme\Components\Sidebar;
use Nenad\Autosav\Core\Theme\Components\Main;
use Nenad\Autosav\Core\Theme\Components\Footer;

final class Vue
{
    private string $title;
    private $content;
    private array $data;
    private array $breadcrumb;

    public function __construct(string $title, $content, array $data = [], array $breadcrumb = [])
    {
        if (session_status() === PHP_SESSION_NONE) {
            SessionHandler::init();
        }

        SecurityBridge::init();

        if (!Config::isLoaded()) {
            $this->loadConfig();
        }

        $this->applyDevSettings();
        $this->handleI18n();
        $this->normaliserSessionTheme();

        $this->title = $title ?: (string) Config::get('default_page_settings.title', 'Application');
        $this->content = $content;
        $this->data = $data;
        $this->breadcrumb = $breadcrumb ?: [
            ['label' => 'Accueil', 'href' => '/'],
            ['label' => $this->title],
        ];
    }

    private function loadConfig(): void
    {
        $possiblePaths = [
            __DIR__ . '/config.json',
            __DIR__ . '/../config.json',
            __DIR__ . '/../../config.json',
            getcwd() . '/config.json',
        ];

        foreach ($possiblePaths as $path) {
            if (is_file($path)) {
                Config::load($path);
                return;
            }
        }

        throw new \RuntimeException('Fichier Theme config.json introuvable.');
    }

    private function applyDevSettings(): void
    {
        if (Config::get('dev.display_errors', false) === true) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
            return;
        }

        ini_set('display_errors', '0');
        error_reporting(0);
    }

    private function handleI18n(): void
    {
        $i18n = Config::get('i18n', []);
        $sessionKey = $i18n['session_key'] ?? 'app_locale';
        $param = $i18n['param'] ?? 'lang';

        if (isset($_GET[$param]) && is_string($_GET[$param])) {
            $requestedLocale = $_GET[$param];
            $availableLocales = array_keys($i18n['locales'] ?? []);

            if (in_array($requestedLocale, $availableLocales, true)) {
                $_SESSION[$sessionKey] = $requestedLocale;
            }
        }

        if (empty($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = $i18n['default_locale'] ?? 'fr-fr';
        }
    }

    /**
     * Prépare des libellés d'affichage sûrs pour le Theme sans modifier la vérité métier.
     * La vérité métier reste $_SESSION['user'] et $_SESSION['society'].
     */
    private function normaliserSessionTheme(): void
    {
        $_SESSION['user'] = $_SESSION['user'] ?? [];
        $_SESSION['society'] = $_SESSION['society'] ?? [];

        $user = &$_SESSION['user'];
        $society = &$_SESSION['society'];

        $prenom = trim((string)($user['prenom'] ?? $user['firstname'] ?? $_SESSION['user_firstname'] ?? ''));
        $nom = trim((string)($user['nom'] ?? $user['lastname'] ?? $_SESSION['user_lastname'] ?? ''));
        $nomComplet = trim((string)($user['name'] ?? $prenom . ' ' . $nom));

        if ($nomComplet === '') {
            $nomComplet = (string)($user['email'] ?? $_SESSION['user_email'] ?? 'Utilisateur');
        }

        $user['name'] = $nomComplet;
        $user['nom_complet'] = $nomComplet;
        $user['initiales'] = $this->initiales($nomComplet);
        $user['role_label'] = $this->premierRoleLabel($user['roles'] ?? [], $user['role_codes'] ?? $_SESSION['user_roles'] ?? []);
        $user['marques_label'] = $this->listeLibelles($user['marques'] ?? [], ['name', 'nom', 'brd_name', 'label']);

        $society['name'] = (string)($society['name'] ?? $society['nom'] ?? $_SESSION['concession']['name'] ?? '');
        $society['nom'] = (string)($society['nom'] ?? $society['name'] ?? '');
        $society['marques_label'] = $this->listeLibelles($society['marques'] ?? $_SESSION['concession']['brands'] ?? [], ['name', 'nom', 'brd_name', 'label']);

        if (!isset($society['group']) && isset($society['groupe'])) {
            $society['group'] = $society['groupe'];
        }
        if (!isset($society['groupe']) && isset($society['group'])) {
            $society['groupe'] = $society['group'];
        }

        $_SESSION['concession'] = $_SESSION['concession'] ?? [
            'id' => $society['id'] ?? null,
            'name' => $society['name'] ?? null,
            'brands' => $society['marques'] ?? [],
        ];
        $_SESSION['group_concession'] = $_SESSION['group_concession'] ?? ($society['group'] ?? null);
    }

    private function initiales(string $nom): string
    {
        $parts = preg_split('/\s+/', trim($nom)) ?: [];
        $initiales = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $initiales .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
            }
        }
        return $initiales !== '' ? $initiales : 'U';
    }

    private function premierRoleLabel(array $roles, array $roleCodes): string
    {
        foreach ($roles as $role) {
            if (is_array($role)) {
                $label = (string)($role['label'] ?? $role['rol_nom'] ?? $role['name'] ?? '');
                if ($label !== '') {
                    return $label;
                }
            }
        }

        $code = (string)($roleCodes[0] ?? '');
        return $code !== '' ? str_replace('_', ' ', ucfirst($code)) : '';
    }

    private function listeLibelles(array $items, array $keys): string
    {
        $labels = [];
        foreach ($items as $item) {
            if (is_scalar($item)) {
                $labels[] = (string)$item;
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            foreach ($keys as $key) {
                if (!empty($item[$key])) {
                    $labels[] = (string)$item[$key];
                    break;
                }
            }
        }

        $labels = array_values(array_unique(array_filter(array_map('trim', $labels))));
        return implode(', ', $labels);
    }

    private function bodyClasses(): string
    {
        $classes = ['sidebar-mini'];
        $layout = Config::get('layout', []);

        if (!empty($layout['collapsed'])) {
            $classes[] = 'sidebar-collapse';
        }
        if (!empty($layout['fixed-header'])) {
            $classes[] = 'layout-navbar-fixed';
        }
        if (!empty($layout['fixed-footer'])) {
            $classes[] = 'layout-footer-fixed';
        }

        return implode(' ', $classes);
    }

    public function setBreadcrumb(array $breadcrumb): self
    {
        $this->breadcrumb = $breadcrumb;
        return $this;
    }

    public function addBreadcrumb(string $label, ?string $href = null): self
    {
        $item = ['label' => $label];
        if ($href !== null) {
            $item['href'] = $href;
        }
        $this->breadcrumb[] = $item;
        return $this;
    }

    public function render(): void
    {
        $lang = Config::get('default_page_settings.langue', 'fr');
        $dir = 'ltr';
        $metaRefresh = (int) Config::get('default_page_settings.meta_refresh_content', 0);
        $css = Config::get('base_css', []);
        $jsHead = Config::get('base_js_head', []);
        $jsFooter = Config::get('base_js_footer', []);

        $navbar = new Navbar();
        $sidebar = new Sidebar();
        $main = new Main($this->content, $this->data);
        $footer = new Footer();

        echo $this->renderHead($lang, $dir, $metaRefresh, $css, $jsHead);
        echo $this->renderBody($navbar, $sidebar, $main, $footer);
        echo $this->renderFooterScripts($jsFooter);
        echo $this->renderFlashAlerts();
        echo "</body>\n</html>";
    }

    private function renderHead(string $lang, string $dir, int $metaRefresh, array $css, array $jsHead): string
    {
        $nonceAttr = CspNonce::attribute();
        $csrf = '';
        if (function_exists('csrf_token')) {
            $csrf = (string) csrf_token();
        } else {
            try {
                $csrf = (string) SecurityBridge::csrfToken('theme');
            } catch (\Throwable $e) {
                $csrf = '';
            }
        }

        ob_start();
        ?><!doctype html>
<html lang="<?= Esc::h($lang) ?>" dir="<?= Esc::h($dir) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <meta name="csrf-token" content="<?= Esc::attr($csrf) ?>"/>
  <title><?= Esc::h($this->title) ?></title>
  <?php if ($metaRefresh > 0): ?>
  <meta http-equiv="refresh" content="<?= (int)$metaRefresh ?>"/>
  <?php endif; ?>
  <?php foreach ($css as $href): ?>
  <link rel="stylesheet" href="<?= Esc::attr((string)$href) ?>"/>
  <?php endforeach; ?>
  <?php foreach ($jsHead as $src): ?>
  <script<?= $nonceAttr ?> src="<?= Esc::attr((string)$src) ?>"></script>
  <?php endforeach; ?>
</head>
<?php
        return (string)ob_get_clean();
    }

    private function renderBody(Navbar $navbar, Sidebar $sidebar, Main $main, Footer $footer): string
    {
        ob_start();
        ?>
<body class="<?= Esc::h($this->bodyClasses()) ?>">
  <div class="wrapper">
    <?= $navbar->render() ?>
    <?= $sidebar->render() ?>
    <?= $main->render($this->title, $this->breadcrumb) ?>
    <?= $footer->render() ?>
  </div>
<?php
        return (string)ob_get_clean();
    }

    private function renderFooterScripts(array $jsFooter): string
    {
        $output = '';
        $nonceAttr = CspNonce::attribute();
        foreach ($jsFooter as $src) {
            $output .= '  <script' . $nonceAttr . ' src="' . Esc::attr((string)$src) . '"></script>' . "\n";
        }
        return $output;
    }

    /**
     * Affiche les messages flash (succès/erreur/avertissement) déposés en
     * session par les contrôleurs (ex: ProfileController::changePassword)
     * sous forme d'alertes SweetAlert2. Sans cet appel, les flashs restent
     * en session sans jamais être montrés à l'utilisateur.
     */
    private function renderFlashAlerts(): string
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return SweetAlertGenerator::renderFromFlash($flash);
    }
}
