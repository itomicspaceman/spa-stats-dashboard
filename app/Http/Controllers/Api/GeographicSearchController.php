<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GeographicSearchController extends Controller
{
    /**
     * Search for geographic areas (continents, regions, countries, states).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        
        // Minimum 2 characters to search
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $cacheKey = "geographic_search:" . strtolower($query);
        
        $results = Cache::remember($cacheKey, 3600, function () use ($query) {
            $results = [];
            
            // Search continents
            $continents = DB::connection('squash_remote')
                ->table('continents')
                ->where('name', 'LIKE', "%{$query}%")
                ->select('id', 'name')
                ->get();
            
            foreach ($continents as $continent) {
                $results[] = [
                    'type' => 'continent',
                    'id' => $continent->id,
                    'name' => $continent->name,
                    'display' => $continent->name . ' (Continent)',
                    'filter' => "continent:{$continent->id}",
                    'hierarchy' => [$continent->name],
                ];
            }
            
            // Search regions
            $regions = DB::connection('squash_remote')
                ->table('regions')
                ->join('continents', 'regions.continent_id', '=', 'continents.id')
                ->where('regions.name', 'LIKE', "%{$query}%")
                ->select('regions.id', 'regions.name', 'continents.name as continent_name')
                ->get();
            
            foreach ($regions as $region) {
                $results[] = [
                    'type' => 'region',
                    'id' => $region->id,
                    'name' => $region->name,
                    'display' => $region->name . ' (Region)',
                    'filter' => "region:{$region->id}",
                    'hierarchy' => [$region->continent_name, $region->name],
                ];
            }
            
            // Search countries
            $countries = DB::connection('squash_remote')
                ->table('countries')
                ->join('regions', 'countries.region_id', '=', 'regions.id')
                ->join('continents', 'regions.continent_id', '=', 'continents.id')
                ->where(function ($q) use ($query) {
                    $q->where('countries.name', 'LIKE', "%{$query}%")
                      ->orWhere('countries.alpha_2_code', 'LIKE', "%{$query}%")
                      ->orWhere('countries.alpha_3_code', 'LIKE', "%{$query}%");
                })
                ->select(
                    'countries.id',
                    'countries.name',
                    'countries.alpha_2_code',
                    'regions.name as region_name',
                    'continents.name as continent_name'
                )
                ->limit(50)
                ->get();
            
            foreach ($countries as $country) {
                $results[] = [
                    'type' => 'country',
                    'id' => $country->id,
                    'name' => $country->name,
                    'code' => $country->alpha_2_code,
                    'display' => $country->name . ' (Country)',
                    'filter' => "country:{$country->alpha_2_code}",
                    'hierarchy' => [$country->continent_name, $country->region_name, $country->name],
                ];
            }
            
            // Search states
            $states = DB::connection('squash_remote')
                ->table('states')
                ->join('countries', 'states.country_id', '=', 'countries.id')
                ->where('states.name', 'LIKE', "%{$query}%")
                ->select(
                    'states.id',
                    'states.name',
                    'countries.name as country_name',
                    'countries.alpha_2_code'
                )
                ->limit(50)
                ->get();
            
            foreach ($states as $state) {
                $results[] = [
                    'type' => 'state',
                    'id' => $state->id,
                    'name' => $state->name,
                    'display' => $state->name . ', ' . $state->country_name . ' (State/Province)',
                    'filter' => "state:{$state->id}",
                    'hierarchy' => [$state->country_name, $state->name],
                ];
            }
            
            return $results;
        });

        return response()->json($results);
    }

    /**
     * Get details about a specific filter.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterDetails(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        
        if (!$filter) {
            return response()->json([
                'level' => 'world',
                'name' => 'World',
                'filter' => null,
            ]);
        }

        $parts = explode(':', $filter, 2);
        if (count($parts) !== 2) {
            return response()->json(['error' => 'Invalid filter format'], 400);
        }

        [$type, $code] = $parts;

        $cacheKey = "filter_details:{$filter}";
        
        $details = Cache::remember($cacheKey, 3600, function () use ($type, $code) {
            switch ($type) {
                case 'continent':
                    $continent = DB::connection('squash_remote')
                        ->table('continents')
                        ->where('id', $code)
                        ->first();
                    
                    return $continent ? [
                        'level' => 'continent',
                        'name' => $continent->name,
                        'id' => $continent->id,
                    ] : null;

                case 'region':
                    $region = DB::connection('squash_remote')
                        ->table('regions')
                        ->join('continents', 'regions.continent_id', '=', 'continents.id')
                        ->where('regions.id', $code)
                        ->select('regions.id', 'regions.name', 'continents.name as continent_name')
                        ->first();
                    
                    return $region ? [
                        'level' => 'region',
                        'name' => $region->name,
                        'id' => $region->id,
                        'parent' => $region->continent_name,
                    ] : null;

                case 'country':
                    $query = DB::connection('squash_remote')
                        ->table('countries')
                        ->join('regions', 'countries.region_id', '=', 'regions.id')
                        ->select('countries.id', 'countries.name', 'countries.alpha_2_code', 'regions.name as region_name');
                    
                    if (is_numeric($code)) {
                        $query->where('countries.id', $code);
                    } elseif (strlen($code) === 2) {
                        $query->where('countries.alpha_2_code', strtoupper($code));
                    } elseif (strlen($code) === 3) {
                        $query->where('countries.alpha_3_code', strtoupper($code));
                    }
                    
                    $country = $query->first();
                    
                    return $country ? [
                        'level' => 'country',
                        'name' => $country->name,
                        'id' => $country->id,
                        'code' => $country->alpha_2_code,
                        'parent' => $country->region_name,
                    ] : null;

                case 'state':
                    $state = DB::connection('squash_remote')
                        ->table('states')
                        ->join('countries', 'states.country_id', '=', 'countries.id')
                        ->where('states.id', $code)
                        ->select('states.id', 'states.name', 'countries.name as country_name')
                        ->first();
                    
                    return $state ? [
                        'level' => 'state',
                        'name' => $state->name,
                        'id' => $state->id,
                        'parent' => $state->country_name,
                    ] : null;

                default:
                    return null;
            }
        });

        if (!$details) {
            return response()->json(['error' => 'Area not found'], 404);
        }

        return response()->json($details);
    }
}

