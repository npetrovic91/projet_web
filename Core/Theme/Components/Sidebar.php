<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Components;

use Nenad\Autosav\Core\Security\Class\CspNonce;
use Nenad\Autosav\Core\Theme\Support\Esc;
use Nenad\Autosav\Core\Theme\Support\Config;

final class Sidebar
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

    public function render(): string
    {
        $items = Config::get('sidebar_items', []);
        $searchCfg = Config::get('sidebar-search', []);

        ob_start(); ?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="/" class="brand-link">
    <span class="brand-image img-circle elevation-3 bg-primary d-inline-flex align-items-center justify-content-center" style="opacity:.9;width:34px;height:34px;">AS</span>
    <span class="brand-text font-weight-light"><?= Esc::h((string)Config::get('brand.name', 'AutoSAV')) ?></span>
  </a>

  <div class="sidebar">
    <?= $this->renderUserPanel() ?>
    <?= $this->renderSocietyPanel() ?>
    <?= $this->renderContextSelectors() ?>
    <?= $this->renderSearch($searchCfg) ?>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <?php foreach ($items as $node): ?>
          <?= $this->renderNode($node) ?>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</aside>
<?= $this->renderContextScript() ?>
<?php
        return (string)ob_get_clean();
    }

    private function renderUserPanel(): string
    {
        $user = $this->utilisateur();
        $nom = (string)($user['nom_complet'] ?? $user['name'] ?? 'Utilisateur');
        $role = (string)($user['role_label'] ?? '');
        $initiales = (string)($user['initiales'] ?? 'U');

        return '<div class="user-panel mt-3 pb-3 mb-3 d-flex"><div class="image"><span class="img-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white" style="width:34px;height:34px;">' . Esc::h($initiales) . '</span></div><div class="info text-truncate"><a href="/profile" class="d-block">' . Esc::h($nom) . '</a>' . ($role !== '' ? '<small class="text-muted d-block">' . Esc::h($role) . '</small>' : '') . '</div></div>';
    }

    private function renderSocietyPanel(): string
    {
        $society = $this->societe();
        $nom = trim((string)($society['name'] ?? $society['nom'] ?? ''));
        $marques = trim((string)($society['marques_label'] ?? ''));
        $groupe = $society['group'] ?? $society['groupe'] ?? null;
        $groupeNom = is_array($groupe) ? (string)($groupe['name'] ?? $groupe['nom'] ?? '') : '';

        if ($nom === '' && $marques === '' && $groupeNom === '') {
            return '';
        }

        $html = '<div class="user-panel pb-3 mb-3"><div class="info d-block text-white-50">';
        if ($nom !== '') {
            $html .= '<div class="text-white text-truncate"><i class="fas fa-store mr-1"></i>' . Esc::h($nom) . '</div>';
        }
        if ($marques !== '') {
            $html .= '<small class="d-block text-truncate"><i class="fas fa-tags mr-1"></i>' . Esc::h($marques) . '</small>';
        }
        if ($groupeNom !== '') {
            $html .= '<small class="d-block text-truncate"><i class="fas fa-sitemap mr-1"></i>' . Esc::h($groupeNom) . '</small>';
        }
        return $html . '</div></div>';
    }

    private function renderSearch(array $searchCfg): string
    {
        if (($searchCfg['enabled'] ?? true) === false) {
            return '';
        }

        $action = Esc::attr((string)($searchCfg['action'] ?? '/search'));
        $placeholder = Esc::attr((string)($searchCfg['placeholder'] ?? 'Rechercher'));

        return '<div class="form-inline mt-2 mb-3"><form class="input-group" method="GET" action="' . $action . '"><input class="form-control form-control-sidebar" type="search" placeholder="' . $placeholder . '" aria-label="Rechercher" name="q"><div class="input-group-append"><button class="btn btn-sidebar" type="submit"><i class="fas fa-search fa-fw"></i></button></div></form></div>';
    }

    private function renderContextSelectors(): string
    {
        $companies = is_array($_SESSION['available_companies'] ?? null) ? $_SESSION['available_companies'] : [];
        $brands = is_array($_SESSION['available_brands'] ?? null) ? $_SESSION['available_brands'] : [];
        $activeCompanyId = $this->activeCompanyId();
        $activeBrandId = $this->activeBrandId();

        ob_start(); ?>
<div class="pb-3 mb-3 border-bottom border-secondary px-2" id="sidebar-context-selectors">
  <label class="small text-white-50 mb-1" for="sel-concessions">Societe / concession active</label>
  <select class="form-control form-control-sidebar mb-2" id="sel-concessions" name="active_concession_id" data-session-key="active_concession_id" data-selected-value="<?= $activeCompanyId ?>" <?= $companies === [] ? 'disabled' : '' ?>>
    <?php if ($companies === []): ?>
      <option value="">Aucune societe accessible</option>
    <?php else: ?>
      <?php foreach ($companies as $company): ?>
        <?php
        $id = (int)($company['soc_id'] ?? $company['com_id'] ?? 0);
        $label = (string)($company['soc_nom'] ?? $company['com_name'] ?? ('Societe #' . $id));
        ?>
        <option value="<?= $id ?>" <?= $id === $activeCompanyId ? 'selected' : '' ?>><?= Esc::h($label) ?></option>
      <?php endforeach; ?>
    <?php endif; ?>
  </select>

  <label class="small text-white-50 mb-1" for="sel-marques">Marque active</label>
  <select class="form-control form-control-sidebar" id="sel-marques" name="active_brand_id" data-session-key="active_brand_id" data-selected-value="<?= $activeBrandId ?>" <?= $companies === [] ? 'disabled' : '' ?>>
    <option value="">Toutes les marques</option>
    <?php foreach ($brands as $brand): ?>
      <?php
      $id = (int)($brand['soc_id'] ?? $brand['brd_id'] ?? 0);
      $label = (string)($brand['soc_nom'] ?? $brand['brd_name'] ?? ('Marque #' . $id));
      ?>
      <option value="<?= $id ?>" <?= $id === $activeBrandId ? 'selected' : '' ?>><?= Esc::h($label) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php
        return (string)ob_get_clean();
    }

    private function renderContextScript(): string
    {
        ob_start(); ?>
<script<?= CspNonce::attribute() ?>>
document.addEventListener('DOMContentLoaded', function () {
  var csrfToken = <?= json_encode(function_exists('csrf_token') ? csrf_token() : '', JSON_UNESCAPED_SLASHES) ?>;
  function updateContext(url, values) {
    if (csrfToken) {
      values[<?= json_encode(defined('AJAX_CSRF_FIELD') ? AJAX_CSRF_FIELD : '_csrf_token') ?>] = csrfToken;
    }
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken
      },
      body: new URLSearchParams(values).toString()
    }).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'Mise a jour du contexte impossible.');
        }
        window.location.reload();
      });
    }).catch(function (error) {
      window.alert(error.message);
    });
  }

  var company = document.getElementById('sel-concessions');
  if (company) {
    company.addEventListener('change', function () {
      if (company.value) updateContext('/ajax/context/company', {company_id: company.value});
    });
  }

  var brand = document.getElementById('sel-marques');
  if (brand) {
    brand.addEventListener('change', function () {
      updateContext('/ajax/context/brand', {brand_id: brand.value});
    });
  }
});
</script>
<?php
        return (string)ob_get_clean();
    }

    private function renderNode(array $node): string
    {
        if (!$this->nodeAutorise($node)) {
            return '';
        }

        $children = [];
        foreach (($node['children'] ?? []) as $child) {
            if (is_array($child)) {
                $rendered = $this->renderNode($child);
                if ($rendered !== '') {
                    $children[] = $rendered;
                }
            }
        }

        $label = Esc::h((string)($node['label'] ?? ''));
        $icon = Esc::h((string)($node['iconClass'] ?? 'far fa-circle nav-icon'));
        $url = Esc::attr((string)($node['url'] ?? '#'));
        $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $isActive = $url !== '#' && ($currentUri === $url || str_starts_with($currentUri, rtrim($url, '/') . '/'));
        $hasChildren = $children !== [];
        $itemClass = 'nav-item' . ($hasChildren ? ' has-treeview' : '') . ($isActive && $hasChildren ? ' menu-open' : '');
        $linkClass = 'nav-link' . ($isActive ? ' active' : '');

        ob_start(); ?>
<li class="<?= $itemClass ?>">
  <a href="<?= $url ?>" class="<?= $linkClass ?>">
    <i class="<?= $icon ?>"></i>
    <p><?= $label ?><?php if ($hasChildren): ?><i class="right fas fa-angle-left"></i><?php endif; ?></p>
  </a>
  <?php if ($hasChildren): ?>
  <ul class="nav nav-treeview">
    <?= implode('', $children) ?>
  </ul>
  <?php endif; ?>
</li>
<?php
        return (string)ob_get_clean();
    }

    private function activeCompanyId(): int
    {
        return (int)(
            $_SESSION['active_concession_id']
            ?? $_SESSION['user']['actual_concession_id']
            ?? $_SESSION['concession']['id']
            ?? $_SESSION['concession']['soc_id']
            ?? $_SESSION['active_company_id']
            ?? $_SESSION['user']['active_company_id']
            ?? $_SESSION['user']['actual_society_id']
            ?? $_SESSION['society']['id']
            ?? $_SESSION['society']['soc_id']
            ?? 0
        );
    }

    private function activeBrandId(): int
    {
        return (int)(
            $_SESSION['active_brand_id']
            ?? $_SESSION['user']['active_brand_id']
            ?? $_SESSION['user']['actual_brand']
            ?? $_SESSION['marque']['id']
            ?? $_SESSION['marque']['soc_id']
            ?? 0
        );
    }

    private function nodeAutorise(array $node): bool
    {
        if (array_key_exists('enabled', $node) && !$node['enabled']) {
            return false;
        }

        if ($this->estSuperAdministrateur()) {
            return true;
        }

        if ($this->niveauUtilisateur() < (int)($node['user_level'] ?? 0)) {
            return false;
        }

        $requiredRoles = (array)($node['roles'] ?? []);
        if ($requiredRoles !== [] && !array_intersect($requiredRoles, $this->rolesUtilisateur())) {
            return false;
        }

        $requiredPermissions = (array)($node['permissions'] ?? []);
        $permissions = $this->permissionsUtilisateur();
        if ($requiredPermissions !== [] && !in_array('*', $permissions, true)) {
            $allowed = false;
            foreach ($requiredPermissions as $permission) {
                $permission = (string) $permission;
                if (in_array($permission, $permissions, true) || (function_exists('has_permission') && has_permission($permission))) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return false;
            }
        }

        return true;
    }

    private function estSuperAdministrateur(): bool
    {
        return array_intersect(['super_administrateur', 'super_admin', 'SUPERADMIN'], $this->rolesUtilisateur()) !== [];
    }
}
