<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Components;

use Nenad\Autosav\Core\Theme\Support\Esc;
use Nenad\Autosav\Core\Theme\Support\Config;
use Nenad\Autosav\Core\Theme\Support\SecurityBridge;

final class Navbar
{
    private function userLevel(): int
    {
        return (int)($_SESSION['user_level'] ?? $_SESSION['user']['level'] ?? 0);
    }

    public function render(): string
    {
        $leftLinks = Config::get('left_navbar_links', []);
        $rightLinks = Config::get('right_navbar_links', []);

        ob_start(); ?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <?php echo $this->renderLinks($leftLinks, 'left'); ?>
  </ul>
  <ul class="navbar-nav ml-auto">
    <?php echo $this->renderLinks($rightLinks, 'right'); ?>
  </ul>
</nav>

<script>
jQuery(function($){
  $('[data-toggle="dropdown"]').dropdown();

  $('#maximizeToggle').on('click', function(e){
    e.preventDefault();
    var $icon = $(this).find('i');
    var maxIcon = $icon.data('max-icon');
    var minIcon = $icon.data('min-icon');
    
    $('body').toggleClass('sidebar-collapse');
    
    if ($('body').hasClass('sidebar-collapse')) {
      $icon.removeClass(maxIcon).addClass(minIcon);
    } else {
      $icon.removeClass(minIcon).addClass(maxIcon);
    }
  });

  $('[data-widget="navbar-search"]').on('click', function(e) {
    e.preventDefault();
    $('.navbar-search-block').slideToggle();
  });
});
</script>
<?php
        return (string)ob_get_clean();
    }

    private function renderLinks(array $links, string $side): string
    {
        $output = '';
        
        foreach ($links as $link) {
            $requiredLevel = (int)($link['user_level'] ?? 0);
            if (!$link['enabled'] || $this->userLevel() < $requiredLevel) {
                continue;
            }

            if (isset($link['type']) && $link['type'] === 'user-menu') {
                $output .= $this->renderUserMenu($link);
                continue;
            }

            $iconClass = $link['iconClass'] ?? '';
            if ($iconClass === 'fas fa-bars') {
                $output .= $this->renderPushMenuLink();
                continue;
            }

            if ($iconClass === 'bi bi-search') {
                $output .= $this->renderSearchLink();
                continue;
            }

            if (is_array($iconClass) && in_array('maximize', $iconClass)) {
                $output .= $this->renderMaximizeLink($iconClass);
                continue;
            }

            if ($iconClass === 'bi bi-chat-text') {
                $output .= $this->renderMessagesDropdown($link);
                continue;
            }

            if ($iconClass === 'bi bi-bell-fill') {
                $output .= $this->renderNotificationsDropdown($link);
                continue;
            }

            if (strpos($iconClass, 'fi fi-') === 0) {
                $output .= $this->renderLanguageDropdown();
                continue;
            }

            $output .= $this->renderSimpleLink($link);
        }

        return $output;
    }

    private function renderPushMenuLink(): string
    {
        return '<li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button">
        <i class="fas fa-bars"></i>
      </a>
    </li>';
    }

    private function renderSearchLink(): string
    {
        return '<li class="nav-item">
      <a class="nav-link" data-widget="navbar-search" href="#" role="button">
        <i class="fas fa-search"></i>
      </a>
      <div class="navbar-search-block" style="display:none;">
        <form class="form-inline" action="#" method="GET">
          <div class="input-group input-group-sm">
            <input class="form-control form-control-navbar" type="search" name="q" placeholder="Search" aria-label="Search">
            <div class="input-group-append">
              <button class="btn btn-navbar" type="submit"><i class="fas fa-search"></i></button>
              <button class="btn btn-navbar" type="button" data-widget="navbar-search"><i class="fas fa-times"></i></button>
            </div>
          </div>
        </form>
      </div>
    </li>';
    }

    private function renderMaximizeLink(array $icons): string
    {
        $maxIcon = 'fas fa-expand-arrows-alt';
        $minIcon = 'fas fa-compress-arrows-alt';
        
        return '<li class="nav-item">
      <a class="nav-link" href="#" role="button" id="maximizeToggle">
        <i class="' . Esc::h($maxIcon) . '" data-max-icon="' . Esc::h($maxIcon) . '" data-min-icon="' . Esc::h($minIcon) . '"></i>
      </a>
    </li>';
    }

    private function renderMessagesDropdown(array $link): string
    {
        $badgeText = $link['badge']['text'] ?? '0';
        $badgeClass = $link['badge']['class'] ?? 'badge-warning';
        
        return '<li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="bi bi-chat-text"></i>
        <span class="badge ' . Esc::h($badgeClass) . ' navbar-badge">' . Esc::h($badgeText) . '</span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">' . Esc::h($badgeText) . ' Messages</span>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
          <i class="bi bi-chat-left-text mr-2"></i> Nouveau message
          <span class="float-right text-muted text-sm">3 mins</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item dropdown-footer">Voir tous les messages</a>
      </div>
    </li>';
    }

    private function renderNotificationsDropdown(array $link): string
    {
        $badgeText = $link['badge']['text'] ?? '0';
        $badgeClass = $link['badge']['class'] ?? 'badge-danger';
        
        return '<li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="bi bi-bell-fill"></i>
        <span class="badge ' . Esc::h($badgeClass) . ' navbar-badge">' . Esc::h($badgeText) . '</span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">' . Esc::h($badgeText) . ' Notifications</span>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
          <i class="bi bi-info-circle mr-2"></i> Notification 1
          <span class="float-right text-muted text-sm">2 hrs</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item dropdown-footer">Voir toutes les notifications</a>
      </div>
    </li>';
    }

    private function renderLanguageDropdown(): string
    {
        $i18n = Config::get('i18n', []);
        $locales = $i18n['locales'] ?? [];
        $sessionKey = $i18n['session_key'] ?? 'app_locale';
        $currentLocale = $_SESSION[$sessionKey] ?? $i18n['default_locale'] ?? 'fr-fr';
        
        $currentFlag = $locales[$currentLocale]['flag'] ?? 'fi fi-fr';
        
        $output = '<li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
        <span class="' . Esc::h($currentFlag) . '"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-right p-0">';
        
        foreach ($locales as $code => $locale) {
            $flag = $locale['flag'] ?? '';
            $label = $locale['label'] ?? $code;
            $param = $i18n['param'] ?? 'lang';
            
            $output .= '<a href="?' . Esc::h($param) . '=' . Esc::h($code) . '" class="dropdown-item">
          <span class="' . Esc::h($flag) . '"></span>
          <span class="ml-2">' . Esc::h($label) . '</span>
        </a>';
        }
        
        $output .= '</div>
    </li>';
        
        return $output;
    }

    private function renderUserMenu(array $menu): string
    {
        $name = Esc::h($menu['name'] ?? 'User');
        $avatar = Esc::h($menu['avatar'] ?? '../assets/img/user2-160x160.jpg');
        $role = Esc::h($menu['role'] ?? '');
        $memberSince = Esc::h($menu['member_since'] ?? '');
        
        $links = $menu['links'] ?? [];
        $actions = $menu['actions'] ?? [];
        
        return '<li class="nav-item dropdown user-menu">
      <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
        <img src="' . $avatar . '" class="user-image img-circle elevation-2" alt="User Image">
        <span class="d-none d-md-inline">' . $name . '</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <li class="user-header bg-primary">
          <img src="' . $avatar . '" class="img-circle elevation-2" alt="User Image">
          <p>' . $name . ($role ? ' - ' . $role : '') . '<small>' . $memberSince . '</small></p>
        </li>
        <li class="user-body">
          <div class="row">
            <div class="col-4 text-center"><a href="' . Esc::h($links['followers'] ?? '#') . '">Followers</a></div>
            <div class="col-4 text-center"><a href="' . Esc::h($links['sales'] ?? '#') . '">Sales</a></div>
            <div class="col-4 text-center"><a href="' . Esc::h($links['friends'] ?? '#') . '">Friends</a></div>
          </div>
        </li>
        <li class="user-footer">
          <a href="' . Esc::h($actions['profile'] ?? '#') . '" class="btn btn-default btn-flat">Profile</a>
          <a href="' . Esc::h($actions['signout'] ?? '#') . '" class="btn btn-default btn-flat float-right">Sign out</a>
        </li>
      </ul>
    </li>';
    }

    private function renderSimpleLink(array $link): string
    {
        $url = Esc::h($link['url'] ?? '#');
        $text = Esc::h($link['text'] ?? '');
        $iconClass = Esc::h($link['iconClass'] ?? '');
        
        $classes = 'nav-link';
        if (!empty($link['d-none'])) {
            $classes .= ' d-none d-sm-inline-block';
        }
        
        return '<li class="nav-item">
      <a href="' . $url . '" class="' . $classes . '">
        ' . ($iconClass ? '<i class="' . $iconClass . '"></i>' : '') . '
        ' . $text . '
      </a>
    </li>';
    }
}