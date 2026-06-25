<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Components;

use Nenad\Autosav\Core\Security\Class\CspNonce;
use Nenad\Autosav\Core\Theme\Support\Esc;
use Nenad\Autosav\Core\Theme\Support\Config;

final class Navbar
{
    private function utilisateur(): array
    {
        return $_SESSION['user'] ?? [];
    }

    private function societe(): array
    {
        return $_SESSION['society'] ?? [];
    }

    private function niveauUtilisateur(): int
    {
        return (int)($_SESSION['user']['level'] ?? $_SESSION['user_level'] ?? 0);
    }

    private function rolesUtilisateur(): array
    {
        return array_values(array_unique(array_map('strval', $_SESSION['user']['role_codes'] ?? $_SESSION['user_roles'] ?? [])));
    }

    private function permissionsUtilisateur(): array
    {
        return array_values(array_unique(array_map('strval', $_SESSION['user']['permissions'] ?? $_SESSION['user_permissions'] ?? [])));
    }

    private function lienAutorise(array $link): bool
    {
        if (array_key_exists('enabled', $link) && !$link['enabled']) {
            return false;
        }

        if ($this->estSuperAdministrateur()) {
            return true;
        }

        if ($this->niveauUtilisateur() < (int)($link['user_level'] ?? 0)) {
            return false;
        }

        $requiredRoles = (array)($link['roles'] ?? []);
        if ($requiredRoles !== [] && !array_intersect($requiredRoles, $this->rolesUtilisateur())) {
            return false;
        }

        $requiredPermissions = (array)($link['permissions'] ?? []);
        $permissions = $this->permissionsUtilisateur();
        if ($requiredPermissions !== [] && !in_array('*', $permissions, true) && !array_intersect($requiredPermissions, $permissions)) {
            return false;
        }

        return true;
    }

    private function estSuperAdministrateur(): bool
    {
        return array_intersect(['super_administrateur', 'super_admin', 'SUPERADMIN'], $this->rolesUtilisateur()) !== [];
    }

    public function render(): string
    {
        $leftLinks = Config::get('left_navbar_links', []);
        $rightLinks = Config::get('right_navbar_links', []);

        ob_start(); ?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <?= $this->renderLinks($leftLinks) ?>
  </ul>
  <ul class="navbar-nav ml-auto">
    <?= $this->renderContextBadge() ?>
    <?= $this->renderLinks($rightLinks) ?>
  </ul>
</nav>

<script<?= CspNonce::attribute() ?>>
document.addEventListener('DOMContentLoaded', function () {
  var maximizeToggle = document.getElementById('maximizeToggle');
  if (maximizeToggle) {
    maximizeToggle.addEventListener('click', function (event) {
      event.preventDefault();
      document.body.classList.toggle('sidebar-collapse');
      var icon = maximizeToggle.querySelector('i');
      if (!icon) return;
      var maxIcon = icon.dataset.maxIcon || 'fas fa-expand-arrows-alt';
      var minIcon = icon.dataset.minIcon || 'fas fa-compress-arrows-alt';
      icon.className = document.body.classList.contains('sidebar-collapse') ? minIcon : maxIcon;
    });
  }
});
</script>
<?php
        return (string)ob_get_clean();
    }

    private function renderLinks(array $links): string
    {
        $output = '';
        foreach ($links as $link) {
            if (!is_array($link) || !$this->lienAutorise($link)) {
                continue;
            }

            $type = (string)($link['type'] ?? 'link');
            $iconClass = $link['iconClass'] ?? '';

            if ($type === 'pushmenu' || $iconClass === 'fas fa-bars') {
                $output .= $this->renderPushMenuLink();
                continue;
            }

            if ($type === 'language') {
                $output .= $this->renderLanguageDropdown();
                continue;
            }

            if ($type === 'fullscreen' || (is_array($iconClass) && in_array('maximize', $iconClass, true))) {
                $output .= $this->renderMaximizeLink();
                continue;
            }

            if ($type === 'notifications') {
                $output .= $this->renderNotificationsDropdown();
                continue;
            }

            if ($type === 'user-menu') {
                $output .= $this->renderUserMenu();
                continue;
            }

            $output .= $this->renderSimpleLink($link);
        }
        return $output;
    }

    private function renderPushMenuLink(): string
    {
        return '<li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>';
    }

    private function renderMaximizeLink(): string
    {
        $maxIcon = 'fas fa-expand-arrows-alt';
        $minIcon = 'fas fa-compress-arrows-alt';
        return '<li class="nav-item"><a class="nav-link" href="#" role="button" id="maximizeToggle"><i class="' . Esc::h($maxIcon) . '" data-max-icon="' . Esc::h($maxIcon) . '" data-min-icon="' . Esc::h($minIcon) . '"></i></a></li>';
    }

    private function renderContextBadge(): string
    {
        $societe = $this->societe();
        $nom = trim((string)($societe['name'] ?? $societe['nom'] ?? ''));
        $marques = trim((string)($societe['marques_label'] ?? ''));

        if ($nom === '' && $marques === '') {
            return '';
        }

        $texte = $nom;
        if ($marques !== '') {
            $texte .= $texte !== '' ? ' · ' . $marques : $marques;
        }

        return '<li class="nav-item d-none d-md-flex align-items-center mr-2"><span class="badge badge-light border"><i class="fas fa-store mr-1"></i>' . Esc::h($texte) . '</span></li>';
    }

    private function renderNotificationsDropdown(): string
    {
        $notifications = $_SESSION['notifications'] ?? [];
        $count = (int)($notifications['unread_count'] ?? $notifications['non_lues'] ?? 0);
        $items = is_array($notifications['items'] ?? null) ? $notifications['items'] : [];

        $badge = $count > 0 ? '<span class="badge badge-danger navbar-badge">' . Esc::h((string)$count) . '</span>' : '';
        $output = '<li class="nav-item dropdown"><a class="nav-link" data-toggle="dropdown" href="#"><i class="far fa-bell"></i>' . $badge . '</a><div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">';
        $output .= '<span class="dropdown-item dropdown-header">' . Esc::h($count . ' notification' . ($count > 1 ? 's' : '')) . '</span>';

        if ($items === []) {
            $output .= '<div class="dropdown-divider"></div><span class="dropdown-item text-muted">Aucune notification</span>';
        } else {
            foreach (array_slice($items, 0, 5) as $item) {
                $label = (string)($item['label'] ?? $item['title'] ?? $item['message'] ?? 'Notification');
                $url = (string)($item['url'] ?? '/notifications');
                $output .= '<div class="dropdown-divider"></div><a href="' . Esc::attr($url) . '" class="dropdown-item"><i class="far fa-bell mr-2"></i>' . Esc::h($label) . '</a>';
            }
        }

        $output .= '<div class="dropdown-divider"></div><a href="/notifications" class="dropdown-item dropdown-footer">Voir les notifications</a></div></li>';
        return $output;
    }

    private function renderLanguageDropdown(): string
    {
        $i18n = Config::get('i18n', []);
        $locales = $i18n['locales'] ?? [];
        if ($locales === []) {
            return '';
        }

        $sessionKey = $i18n['session_key'] ?? 'app_locale';
        $param = $i18n['param'] ?? 'lang';
        $currentLocale = $_SESSION[$sessionKey] ?? $i18n['default_locale'] ?? 'fr-fr';
        $currentFlag = $locales[$currentLocale]['flag'] ?? 'fi fi-fr';

        $output = '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#"><span class="' . Esc::h($currentFlag) . '"></span></a><div class="dropdown-menu dropdown-menu-right p-0">';
        foreach ($locales as $code => $locale) {
            $flag = (string)($locale['flag'] ?? '');
            $label = (string)($locale['label'] ?? $code);
            $output .= '<a href="?' . Esc::attr($param) . '=' . Esc::attr((string)$code) . '" class="dropdown-item"><span class="' . Esc::h($flag) . '"></span><span class="ml-2">' . Esc::h($label) . '</span></a>';
        }
        return $output . '</div></li>';
    }

    private function renderUserMenu(): string
    {
        $user = $this->utilisateur();
        $name = (string)($user['nom_complet'] ?? $user['name'] ?? 'Utilisateur');
        $email = (string)($user['email'] ?? '');
        $role = (string)($user['role_label'] ?? '');
        $initiales = (string)($user['initiales'] ?? 'U');
        $avatar = (string)($user['avatar'] ?? $user['photo_url'] ?? '');

        $image = $avatar !== ''
            ? '<img src="' . Esc::attr($avatar) . '" class="user-image img-circle elevation-2" alt="Utilisateur">'
            : '<span class="user-image img-circle elevation-2 bg-secondary d-inline-flex align-items-center justify-content-center" style="width:2.1rem;height:2.1rem;">' . Esc::h($initiales) . '</span>';

        $headerImage = $avatar !== ''
            ? '<img src="' . Esc::attr($avatar) . '" class="img-circle elevation-2" alt="Utilisateur">'
            : '<div class="img-circle elevation-2 bg-secondary d-inline-flex align-items-center justify-content-center" style="width:90px;height:90px;font-size:2rem;">' . Esc::h($initiales) . '</div>';

        return '<li class="nav-item dropdown user-menu">
      <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">' . $image . '<span class="d-none d-md-inline">' . Esc::h($name) . '</span></a>
      <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <li class="user-header bg-primary">' . $headerImage . '<p>' . Esc::h($name) . ($role !== '' ? '<small>' . Esc::h($role) . '</small>' : '') . ($email !== '' ? '<small>' . Esc::h($email) . '</small>' : '') . '</p></li>
        <li class="user-footer">
          <a href="/profile" class="btn btn-default btn-flat">Mon profil</a>
          <a href="/auth/logout" class="btn btn-default btn-flat float-right">Déconnexion</a>
        </li>
      </ul>
    </li>';
    }

    private function renderSimpleLink(array $link): string
    {
        $url = Esc::attr((string)($link['url'] ?? '#'));
        $text = Esc::h((string)($link['text'] ?? ''));
        $iconClass = Esc::h((string)($link['iconClass'] ?? ''));
        $classes = 'nav-link' . (!empty($link['d-none']) ? ' d-none d-sm-inline-block' : '');

        return '<li class="nav-item"><a href="' . $url . '" class="' . $classes . '">' . ($iconClass !== '' ? '<i class="' . $iconClass . '"></i> ' : '') . $text . '</a></li>';
    }
}
