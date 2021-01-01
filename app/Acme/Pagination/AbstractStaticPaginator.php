<?php

namespace App\Acme\Pagination;

use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class AbstractStaticPaginator extends AbstractPaginator
{
    /**
       * Get the URL for a given page number.
       *
       * @param  int  $page
       * @return string
       */
    public function url($page)
    {
        if ($page <= 0) {
            $page = 1;
        }

        // remove page number from the first page
        $url = ($page > 1) ? $this->path().'/'.$page : $this->path;

        return ['page' => $page, 'url' => $url];
    }
}
