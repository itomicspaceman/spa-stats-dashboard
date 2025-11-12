<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GeographicAreasController extends Controller
{
    /**
     * Display all geographic areas with their unique identifiers.
     *
     * @return View
     */
    public function index(): View
    {
        // Cache for 24 hours (86400 seconds)
        $continents = Cache::remember('geographic_areas_continents', 86400, function () {
            return DB::connection('squash_remote')
                ->table('continents')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        });

        $regions = Cache::remember('geographic_areas_regions', 86400, function () {
            return DB::connection('squash_remote')
                ->table('regions')
                ->join('continents', 'regions.continent_id', '=', 'continents.id')
                ->select('regions.id', 'regions.name', 'regions.continent_id', 'continents.name as continent_name')
                ->orderBy('continents.name')
                ->orderBy('regions.name')
                ->get();
        });

        $countries = Cache::remember('geographic_areas_countries', 86400, function () {
            return DB::connection('squash_remote')
                ->table('countries')
                ->join('regions', 'countries.region_id', '=', 'regions.id')
                ->select(
                    'countries.id', 
                    'countries.name', 
                    'countries.alpha_2_code', 
                    'countries.alpha_3_code',
                    'countries.region_id',
                    'regions.name as region_name',
                    'regions.continent_id'
                )
                ->where('countries.api_display', true)
                ->orderBy('countries.name')
                ->get();
        });

        $states = Cache::remember('geographic_areas_states', 86400, function () {
            return DB::connection('squash_remote')
                ->table('states')
                ->join('countries', 'states.country_id', '=', 'countries.id')
                ->select(
                    'states.id', 
                    'states.name',
                    'states.country_id',
                    'countries.name as country_name',
                    'countries.alpha_2_code as country_code'
                )
                ->orderBy('countries.name')
                ->orderBy('states.name')
                ->get();
        });

        // Group data by continent for hierarchical display
        $organizedData = [];

        foreach ($continents as $continent) {
            $continentRegions = $regions->where('continent_id', $continent->id)->values();

            $regionsArray = [];
            foreach ($continentRegions as $region) {
                $regionCountries = $countries->where('region_id', $region->id)->values();

                $countriesArray = [];
                foreach ($regionCountries as $country) {
                    $countryStates = $states->where('country_id', $country->id)->values();

                    $countriesArray[] = [
                        'country' => $country,
                        'states' => $countryStates->all(),
                    ];
                }

                $regionsArray[] = [
                    'region' => $region,
                    'countries' => $countriesArray,
                ];
            }

            $organizedData[] = [
                'continent' => $continent,
                'regions' => $regionsArray,
            ];
        }

        return view('geographic-areas', [
            'organizedData' => $organizedData,
            'totalContinents' => $continents->count(),
            'totalRegions' => $regions->count(),
            'totalCountries' => $countries->count(),
            'totalStates' => $states->count(),
        ]);
    }
}

