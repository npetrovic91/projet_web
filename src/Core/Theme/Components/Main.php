<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Components;

use Nenad\Autosav\Core\Theme\Support\Esc;

final class Main
{
    private $content;
    private array $data;

    public function __construct($content, array $data = [])
    {
        $this->content = $content;
        $this->data = $data;
    }

    public function render(string $title, array $breadcrumb): string
    {
        ob_start(); ?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1><?= Esc::h($title) ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <?php foreach ($breadcrumb as $bc): ?>
              <?php if (!empty($bc['href'])): ?>
                <li class="breadcrumb-item">
                  <a href="<?= Esc::attr($bc['href']) ?>">
                    <?= Esc::h($bc['label'] ?? '') ?>
                  </a>
                </li>
              <?php else: ?>
                <li class="breadcrumb-item active">
                  <?= Esc::h($bc['label'] ?? '') ?>
                </li>
              <?php endif; ?>
            <?php endforeach; ?>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <?php echo $this->renderContent(); ?>
    </div>
  </section>
</div>
<?php
        return (string)ob_get_clean();
    }

    private function renderContent(): string
    {
        if (is_callable($this->content)) {
            extract($this->data, EXTR_SKIP);
            ob_start();
            echo call_user_func($this->content, $this->data);
            return (string)ob_get_clean();
        }

        if (is_string($this->content) && is_file($this->content) && pathinfo($this->content, PATHINFO_EXTENSION) === 'php') {
            extract($this->data, EXTR_SKIP);
            ob_start();
            include $this->content;
            return (string)ob_get_clean();
        }

        return (string)$this->content;
    }
}