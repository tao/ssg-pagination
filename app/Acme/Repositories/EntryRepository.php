<?php

namespace App\Acme\Repositories;

use Illuminate\Support\Facades\Log;

class EntryRepository extends \Statamic\Stache\Repositories\EntryRepository
{
    /**
     * List of collections to ignore slug validation rules
     */
    protected array $override = [
        'transcripts',
        'notebooks',
    ];

    /**
     * List of collections which allow similar slugs per year
     */
    protected array $yearly = [
        'news',
        'events',
    ];

    /**
     * Allow keys of 4 digits with optional underscore numbering
     * e.g. 0104_01 => January 4th, Session 01
     */
    public const REGEX = '/^\d{4}(_\d{2})?$/i';

    public function createRules($collection, $site)
    {
        // Requires unique slug per year only
        if (in_array($collection, $this->yearly)) {
            return [
                'title' => 'required',
                'slug' => 'required',
            ];
        }

        if (in_array($collection, $this->override)) {
            return [
                'title' => 'required',
                'slug' => 'required|regex:' .self::REGEX,
            ];
        }

        return parent::createRules($collection, $site);
    }

    public function updateRules($collection, $entry)
    {
        // Requires unique slug per year only
        if (in_array($collection, $this->yearly)) {
            return [
                'title' => 'required',
                'slug' => 'required',
            ];
        }

        if (in_array($collection, $this->override)) {
            return [
                'title' => 'required',
                'slug' => 'required|regex:' .self::REGEX,
            ];
        }

        return parent::updateRules($collection, $entry);
    }
}
