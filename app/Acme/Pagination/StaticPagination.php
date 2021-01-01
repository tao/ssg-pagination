<?php

namespace App\Acme\Pagination;

use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Statamic\Facades\Config;
use Statamic\Facades\Collection;
use Statamic\Facades\YAML;

class StaticPagination
{
    /**
     * Collection entries.
     */
    protected $entries;

    /**
     * Pagination size.
     */
    protected $perPages;

    /**
     * Determine the amount of pages for a collection.
     *
     * @return bool
     */
    public function pages($collection)
    {
        $this->perPage = $this->getPaginationSize($collection);

        // fetch the entries in the collection
        $this->entries = Entry::query()
          ->where('collection', $collection)
          ->where('published', true);

        return $this;
    }

    /**
     * Determine the amount of pages for a taxonomy.
     *
     * @return bool
     */
    public function pagesForTaxonomy($collection, $terms)
    {
        $this->perPage = $this->getPaginationSize($collection);

        // fetch the entries in the collection
        $this->entries = Entry::query()
          ->where('collection', $collection)
          ->whereIn('tags', $terms)
          ->where('published', true);

        return $this;
    }

    /**
     * Optionally filter entries
     */
    public function filter($filters)
    {
        foreach ($filters as $filter => $options) {
            switch ($filter) {
              case 'is': {
                // filter is
                $this->entries = $this->entries->whereIn($options[0], explode('|', $options[1]));
                break;
              }
              case 'isnt': {
                // filter isnt
                $this->entries = $this->entries->whereNotIn($options[0], explode('|', $options[1]));
                break;
              }
              case 'contains': {
                // filter contains
                $this->entries = $this->entries->get()->filter(function ($entry) use ($options) {
                    $tags = $entry->tags ?? [];
                    return in_array($options[1], $tags);
                });
                break;
              }
              case 'date_contains': {
                // filter date contains
                $this->entries = $this->entries->get()->filter(function ($entry) use ($options) {
                    return Str::contains($entry->date()->toString(), $options[1]);
                });
                break;
              }
            }
        }

        return $this;
    }

    /**
     * Calculate the amount of pages required
     */
    public function calculate()
    {
        return (int) ceil($this->entries->count() / $this->perPage);
    }

    /**
     * Return a collection of page numbers
     */
    public function get()
    {
        $total = $this->calculate();
        return ($total > 1) ? collect(range(2, $this->calculate())) : collect([]);
    }

    /**
     * Retieve the pagination size from the collection file
     */
    public function getPaginationSize($collection)
    {
        $defaultPerPage = Config::get('statamic.ssg.pagination_size', 10);

        $data = YAML::file("content/collections/{$collection}.yaml")->parse();
        if (array_key_exists('pagination_size', $data)) {
            return (int) $data['pagination_size'];
        }

        return $defaultPerPage;
    }

    /**
     * Generate URLs for static entry pages
     */
    public function generateEntryUrls($entries)
    {
        $urls = collect();

        foreach ($entries as $entry) {
            $this->pages($entry['collection'])
              ->filter($entry['filters'] ?? [])
              ->get()
              ->each(function ($page) use ($entry, $urls) {
                  $urls->push("{$entry['url']}/{$page}");
              });
        }

        return $urls;
    }

    /**
     * Generate URLs for static taxonomy pages
     */
    public function generateTaxonomyUrls($taxonomy, $terms)
    {
        $urls = collect();

        foreach ($terms as $term) {
            $defaultFilter = ['contains' => ['tags', $term]];
            $filterByTerm = $taxonomy['filters'][$term] ?? $defaultFilter;

            // add first page
            $urls->push("{$taxonomy['url']}/{$term}");

            // add paginated pages
            $this->pages($taxonomy['collection'])
              ->filter($filterByTerm)
              ->get()
              ->each(function ($page) use ($taxonomy, $term, $urls) {
                  $urls->push("{$taxonomy['url']}/{$term}/{$page}");
              });
        }

        return $urls;
    }

    /**
     * Generate URLs for static year indexes
     */
    public function generateYearUrls($index, $years)
    {
        $urls = collect();

        foreach ($years as $year) {
            // push the root year index
            $urls = $urls->push("{$index['url']}/{$year}");

            // create pagination if required
            if (array_key_exists('paginate', $index)) {
                // optional filters
                $filterByYear = array_merge(
                    ($index['filters'] ?? []),
                    ['date_contains' => ['date', $year]],
                );

                // paginate each year index
                $this->pages($index['collection'])
                ->filter($filterByYear)
                ->get()
                ->each(function ($page) use ($index, $year, $urls) {
                    $urls->push("{$index['url']}/{$year}/{$page}");
                });
            }
        }

        return $urls;
    }
}
