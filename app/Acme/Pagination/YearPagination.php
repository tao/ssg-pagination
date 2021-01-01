<?php

namespace App\Acme\Pagination;

use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Statamic\Facades\Config;
use Statamic\Facades\Collection;
use Statamic\Facades\YAML;

class YearPagination
{
    /**
     * Collection entries.
     */
    protected $entries;

    /**
     * Earliest year.
     */
    protected $earliest;

    /**
     * Most recent year.
     */
    protected $latest;

    /**
     * Get a list of valid years from the collection.
     */
    public function collection($collection)
    {
        // fetch the entries in the collection
        $this->entries = Entry::query()
        ->where('collection', $collection)
        ->where('published', true)
        ->get()
        ->map(function ($item) {
            return $item->date()->year;
        })
        ->unique()
        ->sort();

        $this->earliest = $this->entries->first();
        $this->latest = $this->entries->last();

        return $this;
    }

    /**
     * Optionally filter collections in a year for better results
     */
    public function filter($type, $filters)
    {
        switch ($type) {
          case 'is': {
            // filter is
            $this->entries = $this->entries->whereIn($type, explode('|', $filters));
            break;
          }
          case 'isnt': {
            // filter isnt
            $this->entries = $this->entries->whereNotIn($type, explode('|', $filters));
            break;
          }
        }

        return $this;
    }

    public function count()
    {
        return $this->entries->count();
    }

    public function reverse()
    {
        $this->entries = $this->entries->sortDesc();

        return $this;
    }

    public function years()
    {
        return $this->entries;
    }

    public function first()
    {
        return $this->earlist;
    }

    public function last()
    {
        return $this->latest;
    }

    /**
     * Find all years between earliest and latest,
     * even if they might not have collection entries.
     */
    public function between()
    {
        return range($this->earlist, $this->latest);
    }
}
