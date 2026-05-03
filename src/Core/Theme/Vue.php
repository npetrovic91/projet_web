<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme;

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
            session_start();
        }
        SecurityBridge::init();

        if (!Config::isLoaded()) {
            $this->loadConfig();
        }

        $this->applyDevSettings();
        $this->handleI18n();

        $this->title = $title ?: (string)Config::get('default_page_settings.title', 'Application');
        $this->content = $content;
        $this->data = $data;
        
        $this->breadcrumb = $breadcrumb ?: [
            ['label' => 'Home', 'href' => '/'],
            ['label' => $this->title]
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

        $configLoaded = false;
        foreach ($possiblePaths as $path) {
            if (is_file($path)) {
                try {
                    Config::load($path);
                    $configLoaded = true;
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        if (!$configLoaded) {
            throw new \RuntimeException('Fichier config.json introuvable.');
        }
    }

    private function applyDevSettings(): void
    {
        $displayErrors = Config::get('dev.display_errors', false);
        
        if ($displayErrors === true) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            error_reporting(0);
        }
    }

    private function handleI18n(): void
    {
        $i18n = Config::get('i18n', []);
        $sessionKey = $i18n['session_key'] ?? 'app_locale';
        $param = $i18n['param'] ?? 'lang';
        
        if (isset($_GET[$param]) && !empty($_GET[$param])) {
            $requestedLocale = (string)$_GET[$param];
            $availableLocales = array_keys($i18n['locales'] ?? []);
            
            if (in_array($requestedLocale, $availableLocales, true)) {
                $_SESSION[$sessionKey] = $requestedLocale;
            }
        }
        
        if (empty($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = $i18n['default_locale'] ?? 'fr-fr';
        }
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
        $metaRefresh = (int)Config::get('default_page_settings.meta_refresh_content', 0);
        
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
        echo "</body>\n</html>";
    }

    private function renderHead(string $lang, string $dir, int $metaRefresh, array $css, array $jsHead): string
    {
        ob_start();
        ?><!doctype html>
<html lang="<?= Esc::h($lang) ?>" dir="<?= Esc::h($dir) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= Esc::h($this->title) ?></title>
  <?php if ($metaRefresh > 0): ?>
  <meta http-equiv="refresh" content="<?= (int)$metaRefresh ?>"/>
  <?php endif; ?>
  <?php foreach ($css as $href): ?>
  <link rel="stylesheet" href="<?= Esc::attr($href) ?>"/>
  <?php endforeach; ?>
  <?php foreach ($jsHead as $src): ?>
  <script src="<?= Esc::attr($src) ?>"></script>
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
        foreach ($jsFooter as $src) {
            $output .= '  <script src="' . Esc::attr($src) . '"></script>' . "\n";
        }
        return $output;
    }
}