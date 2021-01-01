<?php

namespace App\Tags;

use Illuminate\Support\Str;
use Statamic\Tags\Tags;
use App\Acme\Pagination\YearPagination;

class Years extends Tags
{
    /**
     * The {{ years }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        $collection = $this->params['collection'];

        return (new YearPagination)
          ->collection($collection)
          ->years()
          ->reverse()
          ->map(function ($year) {
              return ['value' => $year];
          });
    }

    /**
     * The {{ years:recent }} tag.
     *
     * @return string|array
     */
    public function recent()
    {
        $collection = $this->params['collection'];

        $years = (new YearPagination)
        ->collection($collection)
        ->years()
        ->reverse()
        ->slice(0, 7)
        ->map(function ($year) {
            return ['value' => $year];
        })->toArray();

        return [
          'first' => $years,
        ];
    }

    /**
     * The {{ years:segments }} tag.
     *
     * @return string|array
     */
    public function segments()
    {
        if (! isset($this->context['year'])) {
            return $this->recent();
        }

        $collection = $this->params['collection'];
        $currentYear = (int) $this->context['year'];

        $entries = (new YearPagination)->collection($collection);

        // optional filters
        foreach ($this->params as $key => $value) {
            if (Str::contains($key, ':')) {
                $filters = explode(':', $key);
                $entries = $entries->filter($filters[0], $value);
            }
        }

        // continue
        $entries = $entries->years()
          ->reverse()
          ->toArray();

        // find the index of the current year
        $index = array_search($currentYear, $entries);

        // get the first segments
        $first = collect($entries);
        // get the last segments, splice at index
        $last = $first->splice($index)->reject($currentYear);

        // prepare
        $current = ['value' => $currentYear];

        // return up to 6 values
        $first = $first->reverse()->map(function ($year) {
            return ['value' => $year];
        })->slice(0, 3)->reverse()->toArray();

        // create an overall limit of values to return
        $limit = 6 - count($first);

        // slice at the overall limit
        $last = $last->map(function ($year) {
            return ['value' => $year];
        })->slice(0, $limit)->toArray();

        // response
        return [
          'first' => $first,
          'current' => $current,
          'last' => $last,
        ];
    }
}
