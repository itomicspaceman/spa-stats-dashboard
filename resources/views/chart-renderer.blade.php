<x-dashboard-layout 
    title="Custom Dashboard" 
    :hero="false"
    :showFooter="false">
    
    @foreach($charts as $chart)
        {{-- Render each chart component dynamically --}}
        @if(in_array($chart['id'], ['top-venues', 'court-distribution', 'top-courts', 'venue-categories', 'website-stats', 'outdoor-courts']))
            {{-- These charts need to be wrapped in a row/col structure --}}
            <div class="row g-4 mb-4">
                <x-dynamic-component :component="$chart['component']" />
            </div>
        @else
            {{-- These charts are already full-width or have their own row structure --}}
            <x-dynamic-component :component="$chart['component']" />
        @endif
    @endforeach
    
</x-dashboard-layout>

