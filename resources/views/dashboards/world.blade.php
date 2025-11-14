<x-dashboard-layout 
    title="Squash Venues & Courts - World Stats" 
    :hero="['title' => 'Squash Venues & Courts', 'subtitle' => 'Global statistics and interactive map']">
    
    {{-- Summary Statistics --}}
    @chartRelevant('summary-stats')
        <x-charts.summary-stats />
    @endchartRelevant
    
    {{-- Interactive Map --}}
    @chartRelevant('venue-map')
        <x-charts.venue-map />
    @endchartRelevant
    
    {{-- Loneliest Courts Map --}}
    @chartRelevant('loneliest-courts')
        <x-charts.loneliest-courts />
    @endchartRelevant
    
    {{-- Continental Breakdown --}}
    @chartRelevant('continental-breakdown')
        <x-charts.continental-breakdown />
    @endchartRelevant
    
    {{-- Subcontinental Breakdown --}}
    @chartRelevant('subcontinental-breakdown')
        <x-charts.subcontinental-breakdown />
    @endchartRelevant
    
    {{-- Timeline --}}
    @chartRelevant('timeline')
        <x-charts.timeline />
    @endchartRelevant
    
    {{-- State/County Breakdown --}}
    @chartRelevant('state-breakdown')
        <x-charts.state-breakdown />
    @endchartRelevant
    
    {{-- Top Venues by Courts --}}
    @chartRelevant('top-venues-by-courts')
        <x-charts.top-venues-by-courts />
    @endchartRelevant
    
    {{-- Fluid Grid Layout: All charts flow naturally without fixed rows --}}
    <div class="row g-4 mb-4">
        @chartRelevant('top-venues')
            <x-charts.top-venues />
        @endchartRelevant
        @chartRelevant('court-distribution')
            <x-charts.court-distribution />
        @endchartRelevant
        @chartRelevant('top-courts')
            <x-charts.top-courts />
        @endchartRelevant
        @chartRelevant('venue-categories')
            <x-charts.venue-categories />
        @endchartRelevant
        @chartRelevant('venues-by-state-pie')
            <x-charts.venues-by-state-pie />
        @endchartRelevant
        @chartRelevant('website-stats')
            <x-charts.website-stats />
        @endchartRelevant
        @chartRelevant('outdoor-courts')
            <x-charts.outdoor-courts />
        @endchartRelevant
    </div>
    
</x-dashboard-layout>

