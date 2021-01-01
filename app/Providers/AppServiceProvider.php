<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Statamic\Statamic;
use Statamic\StaticSite\Generator;
use Statamic\Facades\Config;
use Statamic\Facades\Term;
use App\Acme\Pagination\StaticPagination;
use App\Acme\Pagination\YearPagination;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Generator $ssg)
    {
        // Statamic::script('app', 'cp');
        // Statamic::style('app', 'cp');

        // ssg before
        $ssg->before(function () {
            $urls = collect();

            // CALCULATE PAGINATION TOTALS
            // for each pagination listed in ssg config
            // push urls with the page numbers from the total amount of entries
            $entries = Config::get('statamic.ssg.pagination.entries');

            $pages = (new StaticPagination)->generateEntryUrls($entries);
            $urls = $urls->merge($pages);

            // CALCULATE PAGINATION FOR TAXONOMIES
            // for each taxonomy route in ssg config
            // push urls for each taxonomy type
            // with the page numbers from the total amount of entries
            $taxonomies = Config::get('statamic.ssg.pagination.taxonomies');

            foreach ($taxonomies as $taxonomy) {
                $handle = $taxonomy['handle'] ?? $taxonomy['collection'];
                $terms = Term::whereTaxonomy($handle)->map(function ($term) {
                    return $term->slug();
                });

                $pages = (new StaticPagination)->generateTaxonomyUrls($taxonomy, $terms);
                $urls = $urls->merge($pages);
            }

            // CALCULATE YEAR INDEXES
            $indexes = Config::get('statamic.ssg.pagination.years');

            foreach ($indexes as $index) {
                $years = (new YearPagination)
                  ->collection($index['collection'])
                  ->years()
                  ->all();

                $pages = (new StaticPagination)->generateYearUrls($index, $years);
                $urls = $urls->merge($pages);
            }

            $old = Config::get('statamic.ssg.urls', []);
            $new = array_merge($old, $urls->toArray());
            Config::set('statamic.ssg.urls', $new);
        });
    }
}
