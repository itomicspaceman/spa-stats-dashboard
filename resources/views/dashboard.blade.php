<x-dashboard-layout 
    title="Squash Venues & Courts - World Stats" 
    :hero="['title' => 'Squash Venues & Courts', 'subtitle' => 'Global statistics and interactive map']">
    
    {{-- Summary Statistics --}}
    <x-charts.summary-stats />
    
    {{-- Interactive Map --}}
    <x-charts.venue-map />
    
    {{-- Continental Breakdown --}}
    <x-charts.continental-breakdown />
    
    {{-- Subcontinental Breakdown --}}
    <x-charts.subcontinental-breakdown />
    
    {{-- Timeline --}}
    <x-charts.timeline />
    
    {{-- Charts Row 1: Top Venues & Court Distribution --}}
    <div class="row g-4 mb-4">
        <x-charts.top-venues />
        <x-charts.court-distribution />
    </div>
    
    {{-- Charts Row 2: Top Courts & Categories --}}
    <div class="row g-4 mb-4">
        <x-charts.top-courts />
        <x-charts.venue-categories />
    </div>
    
    {{-- Charts Row 3: Website Stats & Outdoor Courts --}}
    <div class="row g-4 mb-4">
        <x-charts.website-stats />
        <x-charts.outdoor-courts />
    </div>
    
</x-dashboard-layout>
