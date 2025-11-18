<x-dashboard-layout 
    title="Squash Trivia - Fun Facts & Maps" 
    :hero="['title' => 'Squash Trivia', 'subtitle' => 'Fun facts, interesting maps, and quirky statistics about squash worldwide']"
    :showSearch="false">
    
    <div class="row">
        @unless(isset($hideSidebar) && $hideSidebar)
        {{-- Side Navigation --}}
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card shadow-sm sticky-top" style="top: {{ (isset($isEmbedded) && $isEmbedded) ? '0' : '20' }}px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Trivia Maps</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('trivia.countries-without-venues') }}" class="list-group-item list-group-item-action {{ (!isset($activeMap) || $activeMap === 'countries-without-venues') ? 'active' : '' }}" data-trivia-link data-map-id="countries-without-venues">
                        <i class="fas fa-globe-americas me-2"></i>Countries Without Venues
                    </a>
                    <a href="{{ route('trivia.high-altitude-venues') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'highest-venues') ? 'active' : '' }}" data-trivia-link data-map-id="highest-venues">
                        <i class="fas fa-mountain me-2"></i>High Altitude Venues
                    </a>
                    <a href="{{ route('trivia.extreme-latitude-venues') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'extreme-latitude') ? 'active' : '' }}" data-trivia-link data-map-id="extreme-latitude">
                        <i class="fas fa-compass me-2"></i>Most Northerly/Southerly
                    </a>
                    <a href="{{ route('trivia.hotels-resorts') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'hotels-resorts') ? 'active' : '' }}" data-trivia-link data-map-id="hotels-resorts">
                        <i class="fas fa-hotel me-2"></i>Hotels & Resorts
                    </a>
                    <a href="{{ route('trivia.countries-stats') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'countries-stats') ? 'active' : '' }}" data-trivia-link data-map-id="countries-stats">
                        <i class="fas fa-table me-2"></i>Countries by Pop. & Area
                    </a>
                    <a href="{{ route('trivia.unknown-courts') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'unknown-courts') ? 'active' : '' }}" data-trivia-link data-map-id="unknown-courts">
                        <i class="fas fa-question-circle me-2"></i>Unknown # of Courts
                    </a>
                    <a href="{{ route('trivia.country-club-100') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'country-club-100') ? 'active' : '' }}" data-trivia-link data-map-id="country-club-100">
                        <i class="fas fa-trophy me-2"></i>The 100% Country Club
                    </a>
                    <a href="{{ route('trivia.countries-wordcloud') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'countries-wordcloud') ? 'active' : '' }}" data-trivia-link data-map-id="countries-wordcloud">
                        <i class="fas fa-cloud me-2"></i>Countries Word Cloud
                    </a>
                    <a href="{{ route('trivia.loneliest-courts') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'loneliest-courts') ? 'active' : '' }}" data-trivia-link data-map-id="loneliest-courts">
                        <i class="fas fa-map-marker-alt me-2"></i>Loneliest Squash Courts
                    </a>
                    <a href="{{ route('trivia.court-graveyard') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'court-graveyard') ? 'active' : '' }}" data-trivia-link data-map-id="court-graveyard">
                        <i class="fas fa-skull-crossbones me-2"></i>Squash Court Graveyard
                    </a>
                    <a href="{{ route('trivia.nearest-courts') }}" class="list-group-item list-group-item-action {{ (isset($activeMap) && $activeMap === 'nearest-courts') ? 'active' : '' }}" data-trivia-link data-map-id="nearest-courts">
                        <i class="fas fa-walking me-2"></i>Nearest Squash Venues
                    </a>
                </div>
                <div class="card-footer text-muted small">
                    <i class="fas fa-info-circle me-1"></i>More trivia maps coming soon!
                </div>
            </div>
        </div>
        @endunless
        
        {{-- Main Content Area --}}
        <div class="{{ (isset($hideSidebar) && $hideSidebar) ? 'col-12' : 'col-lg-9 col-md-8' }}">
            {{-- Countries Without Venues --}}
            <div id="countries-without-venues" class="trivia-section {{ (isset($activeMap) && $activeMap !== 'countries-without-venues') ? 'd-none' : '' }}">
                <x-charts.countries-without-venues />
            </div>
            
            {{-- Highest Venues --}}
            <div id="highest-venues" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'highest-venues') ? 'd-none' : '' }}">
                <x-trivia.highest-venues />
            </div>
            
            {{-- Extreme Latitude Venues --}}
            <div id="extreme-latitude" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'extreme-latitude') ? 'd-none' : '' }}">
                <x-trivia.extreme-latitude-venues />
            </div>
            
            {{-- Hotels & Resorts --}}
            <div id="hotels-resorts" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'hotels-resorts') ? 'd-none' : '' }}">
                <x-trivia.hotels-resorts />
            </div>
            
            {{-- Countries Stats Table --}}
            <div id="countries-stats" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'countries-stats') ? 'd-none' : '' }}">
                <x-trivia.countries-stats-table />
            </div>
            
            {{-- Unknown Courts Map --}}
            <div id="unknown-courts" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'unknown-courts') ? 'd-none' : '' }}">
                <x-trivia.unknown-courts />
            </div>
            
            {{-- 100% Country Club Table --}}
            <div id="country-club-100" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'country-club-100') ? 'd-none' : '' }}">
                <x-trivia.country-club-100 />
            </div>
            
            {{-- Countries Word Cloud --}}
            <div id="countries-wordcloud" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'countries-wordcloud') ? 'd-none' : '' }}">
                <x-trivia.countries-wordcloud />
            </div>
            
            {{-- Loneliest Squash Courts --}}
            <div id="loneliest-courts" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'loneliest-courts') ? 'd-none' : '' }}">
                <x-trivia.loneliest-courts />
            </div>
            
            {{-- Squash Court Graveyard --}}
            <div id="court-graveyard" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'court-graveyard') ? 'd-none' : '' }}">
                <x-trivia.court-graveyard />
            </div>
            
            {{-- Nearest Squash Venues --}}
            <div id="nearest-courts" class="trivia-section {{ (!isset($activeMap) || $activeMap !== 'nearest-courts') ? 'd-none' : '' }}">
                <x-trivia.nearest-courts />
            </div>
            
            {{-- Placeholder for future trivia maps --}}
            <div id="coming-soon-2" class="trivia-section d-none">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-mountain fa-3x text-muted mb-3"></i>
                        <h5>Highest Squash Courts in the World</h5>
                        <p class="text-muted">Coming soon! We'll map the highest elevation squash courts.</p>
                    </div>
                </div>
            </div>
            
            <div id="coming-soon-2" class="trivia-section d-none">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hotel fa-3x text-muted mb-3"></i>
                        <h5>Hotels & Resorts with Squash Courts</h5>
                        <p class="text-muted">Coming soon! Find squash courts at hotels and resorts worldwide.</p>
                    </div>
                </div>
            </div>
            
            <div id="coming-soon-3" class="trivia-section d-none">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-compass fa-3x text-muted mb-3"></i>
                        <h5>Most Northerly & Most Southerly Squash Courts</h5>
                        <p class="text-muted">Coming soon! Discover the extreme latitude squash courts.</p>
                    </div>
                </div>
            </div>
            
            <div id="coming-soon-4" class="trivia-section d-none">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-umbrella-beach fa-3x text-muted mb-3"></i>
                        <h5>Squash Courts of the Caribbean</h5>
                        <p class="text-muted">Coming soon! Explore squash venues in the Caribbean islands.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- JavaScript for side menu navigation --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('[data-trivia-link]');
            
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Don't navigate if disabled
                    if (this.classList.contains('disabled')) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Let the browser handle the navigation to the new URL
                    // The server will render the correct active map
                });
            });
        });
    </script>
    
</x-dashboard-layout>

