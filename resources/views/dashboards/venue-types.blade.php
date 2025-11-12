<x-dashboard-layout 
    title="Squash Venues - Types & Categories" 
    :hero="['title' => 'Venue Types & Categories', 'subtitle' => 'Analysis of squash venue characteristics']">
    
    {{-- Summary Statistics --}}
    <x-charts.summary-stats />
    
    {{-- Charts Row 1: Categories & Court Distribution --}}
    <div class="row g-4 mb-4">
        <x-charts.venue-categories />
        <x-charts.court-distribution />
    </div>
    
    {{-- Charts Row 2: Website Stats & Outdoor Courts --}}
    <div class="row g-4 mb-4">
        <x-charts.website-stats />
        <x-charts.outdoor-courts />
    </div>
    
    {{-- Continental Breakdown --}}
    <x-charts.continental-breakdown />
    
    {{-- Subcontinental Breakdown --}}
    <x-charts.subcontinental-breakdown />
    
</x-dashboard-layout>

