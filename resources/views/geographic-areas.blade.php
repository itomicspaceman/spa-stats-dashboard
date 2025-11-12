<x-dashboard-layout 
    title="Geographic Areas - Squash Stats" 
    :hero="['title' => 'Geographic Area Codes', 'subtitle' => 'Unique identifiers for filtering squash statistics']">
    
    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> About These Codes</h5>
                <p class="mb-2">These are the unique identifiers used in the Squash Players database for geographic filtering. Use these codes when filtering dashboards and charts.</p>
                <p class="mb-2">
                    <strong>Total Areas:</strong>
                    {{ $totalContinents }} Continents,
                    {{ $totalRegions }} Regions,
                    {{ $totalCountries }} Countries,
                    {{ $totalStates }} States/Territories
                </p>
                <p class="mb-0">
                    <strong>Usage:</strong>
                    Use the filter codes shown next to each area name in your shortcode. For example: <code>[squash_stats charts="top-venues" filter="state:810"]</code>
                </p>
            </div>
        </div>
    </div>

    <!-- Search/Filter Box -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="areaSearch" placeholder="Search by name, ID, or code...">
                <button class="btn btn-outline-secondary" type="button" id="clearSearch">Clear</button>
            </div>
        </div>
        <div class="col-md-4">
            <select class="form-select" id="levelFilter">
                <option value="">All Levels</option>
                <option value="continent">Continents Only</option>
                <option value="region">Regions Only</option>
                <option value="country">Countries Only</option>
                <option value="state">States Only</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Geographic Hierarchy</h4>
                </div>
                <div class="card-body">
                    @forelse($organizedData as $continentData)
                        <div class="mb-4 border-bottom pb-3 continent-item" data-level="continent" data-search="{{ strtolower($continentData['continent']->name . ' ' . $continentData['continent']->id) }}">
                            <h4 class="mb-0">
                                <i class="fas fa-globe"></i>
                                {{ $continentData['continent']->name }}
                                <span class="badge bg-light text-dark ms-2">
                                    <code>filter="continent:{{ $continentData['continent']->id }}"</code>
                                </span>
                            </h4>
                            <div class="ms-4 mt-2">
                                @forelse($continentData['regions'] as $regionData)
                                    <div class="mb-3 border-start ps-3 region-item" data-level="region" data-search="{{ strtolower($regionData['region']->name . ' ' . $regionData['region']->id) }}">
                                        <h5 class="mb-1">
                                            <i class="fas fa-map"></i>
                                            {{ $regionData['region']->name }}
                                            <span class="badge bg-info text-dark ms-2">
                                                <code>filter="region:{{ $regionData['region']->id }}"</code>
                                            </span>
                                        </h5>
                                        <div class="ms-4 mt-2">
                                            @forelse($regionData['countries'] as $countryData)
                                                <div class="mb-2 border-start ps-3 country-item" data-level="country" data-search="{{ strtolower($countryData['country']->name . ' ' . $countryData['country']->id . ' ' . $countryData['country']->alpha_2_code) }}">
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-flag"></i>
                                                        {{ $countryData['country']->name }}
                                                        <span class="badge bg-success text-white ms-2">
                                                            <code>filter="country:{{ $countryData['country']->alpha_2_code }}"</code>
                                                        </span>
                                                    </h6>
                                                    <div class="ms-4 mt-2">
                                                        @forelse($countryData['states'] as $state)
                                                            <div class="mb-1 border-start ps-3 state-item" data-level="state" data-search="{{ strtolower($state->name . ' ' . $state->id) }}">
                                                                <div class="small">
                                                                    <strong>{{ $state->name }}</strong>
                                                                    <span class="badge bg-warning text-dark ms-2">
                                                                        <code>filter="state:{{ $state->id }}"</code>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <p class="text-muted small">No states/territories listed.</p>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-muted">No countries listed for this region.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted">No regions listed for this continent.</p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No continents found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Search/Filter JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('areaSearch');
        const levelFilter = document.getElementById('levelFilter');
        const clearButton = document.getElementById('clearSearch');
        
        function filterAreas() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedLevel = levelFilter.value;
            
            // Get all items
            const continents = document.querySelectorAll('.continent-item');
            const regions = document.querySelectorAll('.region-item');
            const countries = document.querySelectorAll('.country-item');
            const states = document.querySelectorAll('.state-item');
            
            // Reset visibility
            continents.forEach(item => item.style.display = '');
            regions.forEach(item => item.style.display = '');
            countries.forEach(item => item.style.display = '');
            states.forEach(item => item.style.display = '');
            
            // Apply level filter
            if (selectedLevel) {
                if (selectedLevel !== 'continent') continents.forEach(item => item.style.display = 'none');
                if (selectedLevel !== 'region') regions.forEach(item => item.style.display = 'none');
                if (selectedLevel !== 'country') countries.forEach(item => item.style.display = 'none');
                if (selectedLevel !== 'state') states.forEach(item => item.style.display = 'none');
            }
            
            // Apply search filter
            if (searchTerm) {
                const allItems = [...continents, ...regions, ...countries, ...states];
                allItems.forEach(item => {
                    const searchData = item.getAttribute('data-search') || '';
                    if (!searchData.includes(searchTerm)) {
                        item.style.display = 'none';
                    }
                });
            }
            
            // Show parent items if children are visible
            states.forEach(state => {
                if (state.style.display !== 'none') {
                    const country = state.closest('.country-item');
                    const region = state.closest('.region-item');
                    const continent = state.closest('.continent-item');
                    if (country) country.style.display = '';
                    if (region) region.style.display = '';
                    if (continent) continent.style.display = '';
                }
            });
            
            countries.forEach(country => {
                if (country.style.display !== 'none') {
                    const region = country.closest('.region-item');
                    const continent = country.closest('.continent-item');
                    if (region) region.style.display = '';
                    if (continent) continent.style.display = '';
                }
            });
            
            regions.forEach(region => {
                if (region.style.display !== 'none') {
                    const continent = region.closest('.continent-item');
                    if (continent) continent.style.display = '';
                }
            });
        }
        
        searchInput.addEventListener('input', filterAreas);
        levelFilter.addEventListener('change', filterAreas);
        
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            levelFilter.value = '';
            filterAreas();
        });
    });
    </script>
</x-dashboard-layout>
