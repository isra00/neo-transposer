<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Manipulates the page_title before rendering, adding a suffix if short enough.
 */
class PageTitleComposer
{
    /** Defined by SEO rules */
    private const PAGE_TITLE_MAX_LENGTH = 55;

    public function compose(View $view): void
    {
        $data = $view->getData();
        $suffix = __((string) config('nt.seo_title_suffix'));

        if (isset($data['page_title'])) {
            if (strlen((string) $data['page_title']) < self::PAGE_TITLE_MAX_LENGTH - strlen($suffix)) {
                $view->with('page_title', $data['page_title'] . " · $suffix");
            }
        } else {
            $view->with('page_title', config('app.name'));
        }
    }
}
