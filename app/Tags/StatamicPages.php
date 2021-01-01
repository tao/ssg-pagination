<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use App\Acme\Pagination\LengthAwareStaticPaginator as StaticPaginator;
use App\Acme\Pagination\StaticPagination;

use Illuminate\Pagination\UrlWindow;

class StatamicPages extends Tags
{
    /**
     * The {{ statamic_pages }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        $items = $this->context->get('entries');
        $total = $this->context->get('total_results');
        $perPage = $this->getPaginationSize();
        $currentPage = $this->getCurrentPage();

        // clean the current page url
        $currentUri = $this->getCurrentUri();

        // construct paginator
        $this->paginator = new StaticPaginator($items, $total, $perPage, $currentPage);
        $this->paginator->setPath($currentUri);
        return $this->paginator->toArray();
    }

    /**
     * The {{ statamic_pages:results }} tag.
     *
     * @return string|array
     */
    public function results()
    {
        $collection = $this->context->get('collection');
        $entryKey = $this->params->get('as');
        $perPage = $this->getPaginationSize();
        $offset = $this->offset();

        $entries = $this->context->get($entryKey)->slice($offset, $perPage);

        return [
          $entryKey => $entries
        ];
    }

    /**
     * The {{ statamic_pages:root }} tag.
     *
     * @return string
     */
    public function root()
    {
        return $this->getCurrentUri();
    }

    /**
     * The {{ statamic_pages:page }} tag.
     *
     * @return string
     */
    public function page()
    {
        return $this->getCurrentPage();
    }

    /**
     * The {{ statamic_pages:offset }} tag.
     *
     * @return string
     */
    public function offset()
    {
        $page = $this->getCurrentPage();
        $perPage = $this->getPaginationSize();
        return (($page - 1) * $perPage);
    }

    /**
     * The {{ statamic_pages:limit }} tag.
     *
     * @return string
     */
    public function limit()
    {
        return $this->getPaginationSize();
    }

    /**
     * The {{ statamic_pages:pagination }} tag.
     *
     * @return string
     */
    public function pagination()
    {
        return $this->limit() <= $this->context->get('total_results');
    }

    /**
     * The {{ statamic_pages:next_page }} tag.
     *
     * @return bool
     */
    public function nextPage()
    {
        $total = $this->context->get('total_results');
        $perPage = $this->getPaginationSize();
        $currentPage = $this->getCurrentPage();

        // calculate if this is the last page
        $totalPages = (int) ceil($total / $perPage);
        $hasNextPage = (bool) ($currentPage < $totalPages);

        // if this is the last page: return no results
        if (!$hasNextPage) {
            return [];
        }

        // clean the current page url
        $currentUri = $this->getCurrentUri();

        // add the next_page to the root url
        $nextPage = $currentUri .'/'. ($currentPage + 1);

        return [
          'next_page' => $nextPage,
        ];
    }

    /**
     * The {{ statamic_pages:prev_page }} tag.
     *
     * @return bool
     */
    public function prevPage()
    {
        $total = $this->context->get('total_results');
        $perPage = $this->getPaginationSize();
        $currentPage = $this->getCurrentPage();

        // calculate if this is the first page
        $totalPages = (int) ceil($total / $perPage);
        $hasPrevPage = (bool) (($currentPage - 1) > 0);

        // if this is the first page: return no results
        if (!$hasPrevPage) {
            return [];
        }

        // clean the current page url
        $currentUri = $this->getCurrentUri();

        // clean the url
        // $currentUri = rtrim($currentUri, '/');
        $prevPage = $currentUri;

        // don't include page number '1' on the first page
        if (($currentPage - 1) > 2) {
            // add the prev_page to the root url
            $prevPage = $currentUri .'/'. ($currentPage - 1);
        }

        return [
          'prev_page' => $prevPage,
        ];
    }

    /**
     * The {{ statamic_pages:total }} tag.
     *
     * @return bool
     */
    public function total()
    {
        $total = $this->context->get('total_results');
        $perPage = $this->getPaginationSize();

        return (int) ceil($total / $perPage);
    }

    /**
     * The {{ statamic_pages:segments }} tag.
     *
     * @return bool
     */
    public function segments()
    {
        $items = $this->context->get('entries');
        $total = $this->context->get('total_results');
        $perPage = $this->getPaginationSize();
        $currentPage = $this->getCurrentPage();

        // clean the current page url
        $currentUri = $this->getCurrentUri();

        // reduce the number of pages shown on each side,
        // as the transcripts with three digit numbers don't fit
        $options = [];
        if ($total > 100) {
            $options['onEachSide'] = 1;
        }

        // construct paginator
        $this->paginator = new StaticPaginator($items, $total, $perPage, $currentPage, $options);
        $this->paginator->setPath($currentUri);

        // generate link:segments
        return UrlWindow::make($this->paginator);
    }

    /**
     * Get and clean the current uri, removing the pages and trailing slash.
     *
     * @return string
     */
    protected function getCurrentUri()
    {
        $currentPage = $this->getCurrentPage();
        $currentUri = $this->context->get('current_uri');
        $lastSegment = $this->context->get('last_segment');

        if (is_numeric($lastSegment) && $lastSegment == $currentPage) {
            $currentUri = rtrim($currentUri, $lastSegment);
        }

        return rtrim($currentUri, '/');
    }

    /**
     * Get the number of items to show on each paginated page.
     */
    protected function getPaginationSize()
    {
        $collection = $this->context['collection'];
        return (new StaticPagination)->getPaginationSize($collection);
    }

    /**
     * Get the current page number.
     */
    protected function getCurrentPage()
    {
        $currentPage = $this->context->get('current_page');
        $lastSegment = $this->context->get('last_segment');

        // default to last url segment
        $page = $currentPage ? $currentPage : $lastSegment;

        // ensure that page is a number, and not a year slug
        preg_match('/^[0-9]{4}$/', $page, $matches);
        if (empty($matches) && is_numeric($page)) {
            return (int) $page;
        }

        return 1;
    }
}
