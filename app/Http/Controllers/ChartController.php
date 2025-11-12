<?php

namespace App\Http\Controllers;

use App\Services\ChartRegistry;
use App\Services\DashboardRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartController extends Controller
{
    /**
     * Render charts dynamically based on request parameters.
     *
     * Supports two modes:
     * 1. Dashboard mode: ?dashboard=world (renders entire dashboard)
     * 2. Custom charts mode: ?charts=venue-map,top-venues (renders specific charts)
     *
     * @param Request $request
     * @return View
     */
    public function render(Request $request): View
    {
        $dashboard = $request->get('dashboard');
        $chartsParam = $request->get('charts');
        
        // Mode 1: Render entire dashboard
        if ($dashboard) {
            $dashboardData = DashboardRegistry::get($dashboard);
            
            if (!$dashboardData) {
                abort(404, "Dashboard '{$dashboard}' not found");
            }
            
            // Redirect to the dashboard's route
            return redirect()->route($dashboardData['route']);
        }
        
        // Mode 2: Render specific charts
        if ($chartsParam) {
            $chartIds = array_map('trim', explode(',', $chartsParam));
            $charts = [];
            
            foreach ($chartIds as $chartId) {
                $chart = ChartRegistry::get($chartId);
                if ($chart) {
                    $charts[] = $chart;
                } else {
                    // Log warning but continue with valid charts
                    \Log::warning("Chart '{$chartId}' not found in registry");
                }
            }
            
            if (empty($charts)) {
                abort(404, 'No valid charts specified');
            }
            
            return view('chart-renderer', compact('charts'));
        }
        
        // No parameters provided - show default world dashboard
        return redirect()->route('dashboard.world');
    }
    
    /**
     * Display the chart gallery page.
     *
     * @return View
     */
    public function gallery(): View
    {
        $charts = ChartRegistry::all();
        $dashboards = DashboardRegistry::all();
        $categories = ChartRegistry::categories();
        
        return view('charts-gallery', compact('charts', 'dashboards', 'categories'));
    }
}

