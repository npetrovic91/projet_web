<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Components;

use Nenad\Autosav\Core\Theme\Support\Esc;
use Nenad\Autosav\Core\Theme\Support\Config;

final class Footer
{
    public function render(): string
    {
        $text = Config::get('footer.text', '');
        $copy = Config::get('footer.copyright', '');
        
        ob_start(); ?>
<footer class="main-footer">
  <div class="float-right d-none d-sm-inline"><?= Esc::h($text) ?></div>
  <strong><?= Esc::h($copy) ?></strong>
</footer>
<?php
        return (string)ob_get_clean();
    }
}