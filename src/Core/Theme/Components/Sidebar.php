<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Components;

use Nenad\Autosav\Core\Theme\Support\Esc;
use Nenad\Autosav\Core\Theme\Support\Config;
use Nenad\Autosav\Core\Theme\Support\SecurityBridge;

final class Sidebar
{
    private function userLevel(): int
    {
        return (int)($_SESSION['user_level'] ?? $_SESSION['user']['level'] ?? 0);
    }

    public function render(): string
    {
        $brandCfg = Config::get('sidebar-brand', []);
        $concCfg  = Config::get('sidebar-concessions', []);
        $searchCfg = Config::get('sidebar-search', []);
        $items = Config::get('sidebar_items', []);
        $selects = Config::get('sidebar_selects', []);

        ob_start(); ?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="#" class="brand-link">
    <img src="../assets/img/AdminLTELogo.png" alt="Brand Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
    <span class="brand-text font-weight-light"><?= SecurityBridge::cleanHtml($this->resolvePlaceholder($brandCfg['data'] ?? 'AdminLTE')) ?></span>
  </a>

  <div class="sidebar">
    <?php if (is_array($selects) && !empty($selects)): ?>
      <div class="px-2 pt-2">
        <?php echo $this->renderSelects($selects); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($searchCfg) && is_array($searchCfg)): ?>
      <?php echo $this->renderSearch($searchCfg); ?>
    <?php endif; ?>

    <?php if (!empty($concCfg['enabled']) && $this->userLevel() >= (int)($concCfg['user_level'] ?? 0)): ?>
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image"><i class="fas fa-store text-white-50"></i></div>
        <div class="info text-truncate">
          <?= SecurityBridge::cleanHtml($this->resolvePlaceholder($concCfg['data'] ?? '')) ?>
        </div>
      </div>
    <?php endif; ?>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <?php foreach ($items as $node): ?>
          <?= $this->renderNode($node) ?>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</aside>

<?php echo $this->renderScripts($selects); ?>

<?php
        return (string)ob_get_clean();
    }

    private function renderSelects(array $selects): string
    {
        $output = '';
        
        foreach ($selects as $sel) {
            $id = (string)($sel['id'] ?? 'select');
            $label = (string)($sel['label'] ?? ucfirst($id));
            $sessionKey = (string)($sel['session_key'] ?? 'select_' . $id);
            $options = $sel['options'] ?? [];
            $defFromSession = !empty($sel['default_from_session']);
            
            $current = '';
            if ($defFromSession && isset($_SESSION[$sessionKey])) {
                $current = (string)$_SESSION[$sessionKey];
            } elseif (!empty($options)) {
                $current = (string)$options[0];
            }
            
            $output .= '<label for="sel-' . Esc::h($id) . '" class="small text-muted d-block mb-1">' 
                    . Esc::h($label) . '</label>';
            $output .= '<select id="sel-' . Esc::h($id) . '" class="form-control form-control-sm mb-2" '
                    . 'data-session-key="' . Esc::h($sessionKey) . '" '
                    . 'data-endpoint="' . Esc::h($sel['url_endpoint'] ?? '') . '" '
                    . 'data-method="' . Esc::h($sel['method'] ?? 'GET') . '" '
                    . 'data-value-key="' . Esc::h($sel['value_key'] ?? 'value') . '" '
                    . 'data-label-key="' . Esc::h($sel['label_key'] ?? 'label') . '" '
                    . 'data-csrf-param="' . Esc::h($sel['csrf_param'] ?? 'csrf') . '">';
            
            foreach ($options as $opt) {
                $opt = (string)$opt;
                $selected = ($opt === $current) ? ' selected' : '';
                $output .= '<option value="' . Esc::h($opt) . '"' . $selected . '>' 
                        . Esc::h($opt) . '</option>';
            }
            
            $output .= '</select>';
        }
        
        return $output;
    }

    private function renderSearch(array $searchCfg): string
    {
        $method = strtoupper($searchCfg['method'] ?? 'GET');
        $action = Esc::h($searchCfg['action'] ?? '#');
        $technologie = strtolower($searchCfg['technologie'] ?? 'php');
        
        $csrfToken = SecurityBridge::csrfToken('sidebar_search');
        
        $formId = 'sidebar-search-form';
        
        $output = '<div class="form-inline mt-2 mb-3">';
        $output .= '<form id="' . $formId . '" class="input-group" method="' . Esc::h($method) . '" action="' . $action . '"';
        
        if ($technologie === 'ajax') {
            $output .= ' data-ajax="true"';
        }
        
        $output .= '>';
        $output .= '<input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search" name="q">';
        $output .= '<div class="input-group-append">';
        $output .= '<button class="btn btn-sidebar" type="submit"><i class="fas fa-search fa-fw"></i></button>';
        $output .= '</div>';
        $output .= '<input type="hidden" name="csrf" value="' . Esc::h($csrfToken) . '">';
        $output .= '</form>';
        $output .= '</div>';
        
        return $output;
    }

    private function renderNode(array $node): string
    {
        $lvl = (int)($node['user_level'] ?? 0);
        if ($this->userLevel() < $lvl) {
            return '';
        }

        $label = Esc::h($node['label'] ?? '');
        $icon = Esc::h($node['iconClass'] ?? 'far fa-circle nav-icon');
        $url = Esc::h($node['url'] ?? '#');
        $children = $node['children'] ?? [];

        $hasChildren = !empty($children);
        $navItemClass = $hasChildren ? 'nav-item has-treeview' : 'nav-item';

        ob_start(); ?>
<li class="<?= $navItemClass ?>">
  <a href="<?= $url ?>" class="nav-link">
    <i class="<?= $icon ?>"></i>
    <p>
      <?= $label ?>
      <?php if ($hasChildren): ?>
        <i class="right fas fa-angle-left"></i>
      <?php endif; ?>
    </p>
  </a>
  <?php if ($hasChildren): ?>
  <ul class="nav nav-treeview">
    <?php foreach ($children as $child): ?>
      <?= $this->renderNode($child) ?>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</li>
<?php
        return (string)ob_get_clean();
    }

    private function resolvePlaceholder($expr): string
    {
        if (!is_string($expr)) {
            return (string)$expr;
        }

        if (preg_match('#^\$\{\s*\$_SESSION\[(.+)\]\s*\}$#', $expr, $m)) {
            $path = trim($m[1]);
            $keys = [];
            
            if (preg_match_all("#'([^']+)'#", $path, $mm)) {
                $keys = $mm[1];
            }
            
            $val = $_SESSION ?? [];
            foreach ($keys as $k) {
                if (is_array($val) && array_key_exists($k, $val)) {
                    $val = $val[$k];
                } else {
                    $val = '';
                    break;
                }
            }
            
            return is_scalar($val) ? (string)$val : '';
        }

        return (string)$expr;
    }

    private function renderScripts(array $selects): string
    {
        if (empty($selects)) {
            return '';
        }

        ob_start(); ?>
<script>
jQuery(function($){
  $(document).on('change', 'select[id^="sel-"]', function(){
    var $select = $(this);
    var sessionKey = $select.data('session-key');
    var value = $select.val();
    var endpoint = $select.data('endpoint');
    var method = ($select.data('method') || 'GET').toUpperCase();
    
    if (!sessionKey || !value) return;
    
    var sessionData = {};
    sessionData[sessionKey] = value;
    
    $.ajax({
      url: 'session.php',
      method: 'POST',
      data: {
        key: sessionKey,
        value: value,
        csrf: '<?= SecurityBridge::csrfToken('sidebar_select') ?>'
      },
      success: function() {
        if (endpoint) {
          loadSelectData($select, endpoint, method);
        } else {
          window.location.reload();
        }
      },
      error: function() {
        console.error('Erreur lors du stockage en session');
      }
    });
  });
  
  function loadSelectData($select, endpoint, method) {
    var valueKey = $select.data('value-key') || 'value';
    var labelKey = $select.data('label-key') || 'label';
    var csrfParam = $select.data('csrf-param') || 'csrf';
    
    var ajaxConfig = {
      url: endpoint,
      method: method,
      dataType: 'json',
      success: function(response) {
        if (response && Array.isArray(response.data)) {
          updateSelectOptions($select, response.data, valueKey, labelKey);
        }
      },
      error: function() {
        console.error('Erreur lors du chargement des données');
      }
    };
    
    if (method === 'POST') {
      ajaxConfig.data = {};
      ajaxConfig.data[csrfParam] = '<?= SecurityBridge::csrfToken('sidebar_select_load') ?>';
    }
    
    $.ajax(ajaxConfig);
  }
  
  function updateSelectOptions($select, data, valueKey, labelKey) {
    $select.empty();
    $.each(data, function(i, item) {
      var value = item[valueKey] || item;
      var label = item[labelKey] || item;
      $select.append($('<option></option>').attr('value', value).text(label));
    });
  }
  
  $('#sidebar-search-form[data-ajax="true"]').on('submit', function(e){
    e.preventDefault();
    var $form = $(this);
    var formData = $form.serialize();
    
    $.ajax({
      url: $form.attr('action'),
      method: $form.attr('method'),
      data: formData,
      dataType: 'json',
      success: function(response) {
        if (response && response.results) {
          console.log('Résultats de recherche:', response.results);
        }
      },
      error: function() {
        console.error('Erreur lors de la recherche');
      }
    });
  });
});
</script>
<?php
        return (string)ob_get_clean();
    }
}