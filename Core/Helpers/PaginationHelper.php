<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Helpers;

class PaginationHelper
{
    public static function paginate(
        int    $total,
        int    $perPage = PAGINATION_DEFAULT,
        int    $currentPage = 1,
        string $baseUrl = ''
    ): array {
        $perPage     = min(max(1, $perPage), PAGINATION_MAX);
        $currentPage = max(1, $currentPage);
        $lastPage    = max(1, (int)ceil($total / $perPage));
        $currentPage = min($currentPage, $lastPage);
        $offset      = ($currentPage - 1) * $perPage;

        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $currentPage,
            'last_page'    => $lastPage,
            'offset'       => $offset,
            'has_prev'     => $currentPage > 1,
            'has_next'     => $currentPage < $lastPage,
            'prev_page'    => $currentPage - 1,
            'next_page'    => $currentPage + 1,
            'base_url'     => $baseUrl,
        ];
    }

    public static function renderLinks(array $pagination): string
    {
        if ($pagination['last_page'] <= 1) return '';

        $html  = '<nav><ul class="pagination pagination-sm">';
        $base  = $pagination['base_url'];
        $cur   = $pagination['current_page'];
        $last  = $pagination['last_page'];

        // Précédent
        $disabled = $cur === 1 ? ' disabled' : '';
        $html .= "<li class='page-item{$disabled}'><a class='page-link' href='{$base}?page=" . ($cur-1) . "'>«</a></li>";

        // Pages
        for ($i = max(1, $cur-2); $i <= min($last, $cur+2); $i++) {
            $active = $i === $cur ? ' active' : '';
            $html  .= "<li class='page-item{$active}'><a class='page-link' href='{$base}?page={$i}'>{$i}</a></li>";
        }

        // Suivant
        $disabled = $cur === $last ? ' disabled' : '';
        $html .= "<li class='page-item{$disabled}'><a class='page-link' href='{$base}?page=" . ($cur+1) . "'>»</a></li>";
        $html .= '</ul></nav>';
        return $html;
    }
}
