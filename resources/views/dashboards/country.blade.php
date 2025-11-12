<x-dashboard-layout 
    title="Squash Venues & Courts - Country Stats" 
    :hero="['title' => 'Country Statistics', 'subtitle' => 'Detailed breakdown by country']">
    
    {{-- Summary Statistics --}}
    <x-charts.summary-stats />
    
    {{-- Interactive Map (filtered by country) --}}
    <x-charts.venue-map />
    
    {{-- Charts Row 1: Top Venues & Court Distribution --}}
    <div class="row g-4 mb-4">
        <x-charts.top-venues />
        <x-charts.court-distribution />
    </div>
    
    {{-- Charts Row 2: Categories & Website Stats --}}
    <div class="row g-4 mb-4">
        <x-charts.venue-categories />
        <x-charts.website-stats />
    </div>
    
    {{-- Timeline --}}
    <x-charts.timeline />
    
</x-dashboard-layout>

