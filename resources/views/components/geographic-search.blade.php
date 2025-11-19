@props(['embedded' => false])

<div class="geographic-search-container bg-white border-bottom shadow-sm py-3 {{ $embedded ? 'not-sticky' : '' }}">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="position-relative">
                    <input 
                        type="text" 
                        id="geographic-search-input" 
                        class="form-control form-control-lg" 
                        placeholder="Search for any area (continent, region, country, state)..."
                        autocomplete="off"
                    >
                    <div id="geographic-search-results" class="position-absolute w-100 bg-white border rounded shadow-lg mt-1" style="display: none; max-height: 400px; overflow-y: auto; z-index: 1000;"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div id="current-area-display" class="d-flex align-items-center">
                    <i class="fas fa-globe text-primary me-2"></i>
                    <span class="fw-bold">Viewing: <span id="current-area-name">World</span></span>
                    <button id="clear-filter-btn" class="btn btn-sm btn-outline-secondary ms-3" style="display: none;">
                        <i class="fas fa-times me-1"></i>Clear Filter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .geographic-search-container {
        position: sticky;
        top: 56px; /* Height of navbar */
        z-index: 999;
    }
    
    /* When embedded, don't use sticky positioning to avoid overlaying content */
    .geographic-search-container.not-sticky {
        position: relative !important;
        top: 0 !important;
        z-index: auto !important;
    }
    
    .search-result-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #e9ecef;
        transition: background-color 0.2s;
    }
    
    .search-result-item:hover {
        background-color: #f8f9fa;
    }
    
    .search-result-item:last-child {
        border-bottom: none;
    }
    
    .search-result-hierarchy {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .search-result-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
</style>

<script>
    // Geographic Search Component
    (function() {
        // Fallback: Detect if we're in an iframe and apply not-sticky class if needed
        // This ensures the search bar doesn't float when embedded, even if embed param isn't detected
        function applyNotStickyIfEmbedded() {
            const searchContainer = document.querySelector('.geographic-search-container');
            if (searchContainer && !searchContainer.classList.contains('not-sticky')) {
                // Check if we're in an iframe OR if embed parameter is in URL
                const urlParams = new URLSearchParams(window.location.search);
                const isEmbedded = window.self !== window.top || urlParams.has('embed') || urlParams.has('embedded');
                if (isEmbedded) {
                    searchContainer.classList.add('not-sticky');
                }
            }
        }
        
        // Run immediately if DOM is ready, otherwise wait
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyNotStickyIfEmbedded);
        } else {
            applyNotStickyIfEmbedded();
        }
        
        // Dynamically determine API base URL
        const API_BASE = window.location.hostname === 'spa.test' 
            ? 'https://spa.test/api/squash'
            : 'https://stats.squashplayers.app/api/squash';
        
        const searchInput = document.getElementById('geographic-search-input');
        const searchResults = document.getElementById('geographic-search-results');
        const currentAreaName = document.getElementById('current-area-name');
        const clearFilterBtn = document.getElementById('clear-filter-btn');
        
        let searchTimeout = null;
        let currentFilter = null;
        
        // Initialize from URL
        const urlParams = new URLSearchParams(window.location.search);
        currentFilter = urlParams.get('filter');
        
        if (currentFilter) {
            loadFilterDetails(currentFilter);
        }
        
        // Search input handler
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => performSearch(query), 300);
        });
        
        // Clear filter button
        clearFilterBtn.addEventListener('click', function() {
            applyFilter(null, 'World');
        });
        
        // Click outside to close results
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
        
        async function performSearch(query) {
            try {
                const response = await fetch(`${API_BASE}/search-areas?query=${encodeURIComponent(query)}`);
                const results = await response.json();
                
                displayResults(results);
            } catch (error) {
                console.error('Search error:', error);
            }
        }
        
        function displayResults(results) {
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="p-3 text-muted">No results found</div>';
                searchResults.style.display = 'block';
                return;
            }
            
            const html = results.map(result => `
                <div class="search-result-item" data-filter="${result.filter}" data-name="${result.name}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold">${result.name}</div>
                            <div class="search-result-hierarchy">${result.hierarchy.join(' › ')}</div>
                        </div>
                        <span class="badge bg-secondary search-result-type-badge">${result.type}</span>
                    </div>
                </div>
            `).join('');
            
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
            
            // Add click handlers
            searchResults.querySelectorAll('.search-result-item').forEach(item => {
                item.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    const name = this.dataset.name;
                    applyFilter(filter, name);
                });
            });
        }
        
        async function loadFilterDetails(filter) {
            try {
                const response = await fetch(`${API_BASE}/filter-details?filter=${encodeURIComponent(filter)}`);
                const details = await response.json();
                
                if (details.name) {
                    currentAreaName.textContent = details.name;
                    clearFilterBtn.style.display = 'inline-block';
                }
            } catch (error) {
                console.error('Error loading filter details:', error);
            }
        }
        
        function applyFilter(filter, name) {
            currentFilter = filter;
            currentAreaName.textContent = name;
            searchInput.value = '';
            searchResults.style.display = 'none';
            
            if (filter) {
                clearFilterBtn.style.display = 'inline-block';
            } else {
                clearFilterBtn.style.display = 'none';
            }
            
            // Update URL and reload page
            const url = new URL(window.location);
            if (filter) {
                url.searchParams.set('filter', filter);
            } else {
                url.searchParams.delete('filter');
            }
            window.location.href = url.toString();
        }
        
        // Expose for external use
        window.GeographicSearch = {
            getCurrentFilter: () => currentFilter,
            applyFilter: applyFilter
        };
    })();
</script>

