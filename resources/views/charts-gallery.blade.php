<x-dashboard-layout 
    title="Chart Gallery - Squash Stats" 
    :hero="['title' => 'Chart Gallery', 'subtitle' => 'Browse and embed charts on your website']">
    
    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="chart-search" class="form-control" placeholder="Search charts and dashboards...">
            </div>
        </div>
        <div class="col-md-6">
            <select id="category-filter" class="form-select">
                <option value="">All Categories</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    
    <!-- Full Dashboards Section -->
    <section class="mb-5">
        <h2 class="mb-3">Full Dashboards</h2>
        <p class="text-muted mb-4">Complete dashboard experiences with pre-configured charts</p>
        
        <div class="row g-4 dashboard-grid">
            @foreach($dashboards as $dashboard)
                <div class="col-md-6 col-lg-4 dashboard-card">
                    <div class="card h-100 shadow-sm">
                        <div class="card-img-top" style="height: 200px; overflow: hidden; position: relative;">
                            @if(file_exists(public_path('images/dashboards/' . $dashboard['id'] . '.png')))
                                {{-- Option 1: Static image if available --}}
                                <img src="/images/dashboards/{{ $dashboard['id'] }}.png" alt="{{ $dashboard['name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                {{-- Option 2: Live iframe preview --}}
                                <iframe 
                                    src="{{ $dashboard['url'] }}" 
                                    style="width: 400%; height: 400%; border: none; transform: scale(0.25); transform-origin: 0 0; pointer-events: none;"
                                    scrolling="no"
                                    loading="lazy">
                                </iframe>
                                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.1); pointer-events: none;"></div>
                            @endif
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">{{ $dashboard['name'] }}</h5>
                            <p class="card-text text-muted">{{ $dashboard['description'] }}</p>
                            <p class="mb-2">
                                <span class="badge bg-primary">{{ count($dashboard['charts']) }} charts</span>
                            </p>
                            <div class="mb-3">
                                <code class="d-block p-2 bg-light rounded shortcode-text">[squash_court_stats dashboard="{{ $dashboard['id'] }}"]</code>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary copy-shortcode" data-shortcode='[squash_court_stats dashboard="{{ $dashboard['id'] }}"]'>
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                                <a href="{{ $dashboard['url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-external-link-alt"></i> Preview
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    
    <!-- Individual Charts Section -->
    <section class="mb-5">
        <h2 class="mb-3">Individual Charts</h2>
        <p class="text-muted mb-4">Mix and match charts to create custom dashboards</p>
        
        <div class="row g-4 chart-grid">
            @foreach($charts as $chart)
                <div class="col-md-6 col-lg-4 chart-card" data-category="{{ $chart['category'] }}" data-name="{{ strtolower($chart['name']) }}">
                    <div class="card h-100 shadow-sm">
                        <div class="card-img-top" style="height: 180px; overflow: hidden; position: relative;">
                            @if(file_exists(public_path('images/charts/' . $chart['id'] . '.png')))
                                {{-- Option 1: Static image if available --}}
                                <img src="/images/charts/{{ $chart['id'] }}.png" alt="{{ $chart['name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                {{-- Option 2: Live iframe preview --}}
                                <iframe 
                                    src="/render?charts={{ $chart['id'] }}" 
                                    style="width: 400%; height: 400%; border: none; transform: scale(0.25); transform-origin: 0 0; pointer-events: none;"
                                    scrolling="no"
                                    loading="lazy">
                                </iframe>
                                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.1); pointer-events: none;"></div>
                            @endif
                        </div>
                        <div class="card-body">
                            <h6 class="card-title">{{ $chart['name'] }}</h6>
                            <p class="card-text text-muted small">{{ $chart['description'] }}</p>
                            <p class="mb-2">
                                <span class="badge bg-secondary">{{ $chart['category'] }}</span>
                            </p>
                            <div class="mb-3">
                                <code class="d-block p-2 bg-light rounded small shortcode-text">[squash_court_stats charts="{{ $chart['id'] }}"]</code>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <button class="btn btn-sm btn-primary copy-shortcode" data-shortcode='[squash_court_stats charts="{{ $chart['id'] }}"]'>
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                                <a href="/render?charts={{ $chart['id'] }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-external-link-alt"></i> Preview
                                </a>
                                <label class="mb-0">
                                    <input type="checkbox" class="chart-selector" value="{{ $chart['id'] }}">
                                    <small>Select</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    
    <!-- Custom Selection Builder -->
    <section class="mb-5" id="selection-builder" style="display: none;">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title">Custom Chart Selection</h3>
                <p class="text-muted">Selected charts: <span id="selected-count" class="fw-bold">0</span></p>
                <div id="selected-charts" class="mb-3"></div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Your Custom Shortcode:</label>
                    <code id="custom-shortcode" class="d-block p-3 bg-light rounded">[squash_court_stats charts=""]</code>
                </div>
                <div class="d-flex gap-2">
                    <button id="copy-custom" class="btn btn-primary">
                        <i class="fas fa-copy"></i> Copy Custom Shortcode
                    </button>
                    <button id="clear-selection" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear Selection
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Instructions -->
    <section class="mb-5">
        <div class="card bg-light">
            <div class="card-body">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> How to Use</h3>
                <ol class="mb-0">
                    <li>Browse the available dashboards and charts above</li>
                    <li>Click "Copy" to copy the shortcode for any dashboard or chart</li>
                    <li>Or select multiple charts to create a custom combination</li>
                    <li>Install the <a href="https://github.com/itomic/squash-court-stats" target="_blank">Squash Court Stats WordPress plugin</a></li>
                    <li>Paste the shortcode into any WordPress page or post</li>
                </ol>
            </div>
        </div>
    </section>
    
    @push('scripts')
    <script>
        // Search functionality
        document.getElementById('chart-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            // Search dashboards
            document.querySelectorAll('.dashboard-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchTerm) ? '' : 'none';
            });
            
            // Search charts
            document.querySelectorAll('.chart-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Category filter
        document.getElementById('category-filter').addEventListener('change', function(e) {
            const category = e.target.value;
            
            document.querySelectorAll('.chart-card').forEach(card => {
                if (!category || card.dataset.category === category) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Copy shortcode
        document.querySelectorAll('.copy-shortcode').forEach(button => {
            button.addEventListener('click', function() {
                const shortcode = this.dataset.shortcode;
                navigator.clipboard.writeText(shortcode).then(() => {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    this.classList.add('btn-success');
                    this.classList.remove('btn-primary');
                    
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-primary');
                    }, 2000);
                });
            });
        });
        
        // Chart selection
        let selectedCharts = [];
        
        document.querySelectorAll('.chart-selector').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    selectedCharts.push(this.value);
                } else {
                    selectedCharts = selectedCharts.filter(id => id !== this.value);
                }
                updateCustomShortcode();
            });
        });
        
        function updateCustomShortcode() {
            const count = selectedCharts.length;
            document.getElementById('selected-count').textContent = count;
            
            if (count > 0) {
                document.getElementById('selection-builder').style.display = '';
                const shortcode = `[squash_court_stats charts="${selectedCharts.join(',')}"]`;
                document.getElementById('custom-shortcode').textContent = shortcode;
                
                // Update selected charts display
                const selectedDiv = document.getElementById('selected-charts');
                selectedDiv.innerHTML = selectedCharts.map(id => {
                    return `<span class="badge bg-primary me-2 mb-2">${id} <button type="button" class="btn-close btn-close-white btn-sm ms-1" onclick="removeChart('${id}')"></button></span>`;
                }).join('');
            } else {
                document.getElementById('selection-builder').style.display = 'none';
            }
        }
        
        window.removeChart = function(chartId) {
            selectedCharts = selectedCharts.filter(id => id !== chartId);
            document.querySelector(`.chart-selector[value="${chartId}"]`).checked = false;
            updateCustomShortcode();
        };
        
        document.getElementById('copy-custom').addEventListener('click', function() {
            const shortcode = document.getElementById('custom-shortcode').textContent;
            navigator.clipboard.writeText(shortcode).then(() => {
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                this.classList.add('btn-success');
                this.classList.remove('btn-primary');
                
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-primary');
                }, 2000);
            });
        });
        
        document.getElementById('clear-selection').addEventListener('click', function() {
            selectedCharts = [];
            document.querySelectorAll('.chart-selector').forEach(cb => cb.checked = false);
            updateCustomShortcode();
        });
    </script>
    @endpush
    
</x-dashboard-layout>

