<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="fas fa-hotel me-2"></i>Hotels & Resorts with Squash Courts
        </h5>
        
        <p class="text-muted mb-3">
            Discover hotels and resorts around the world that offer squash facilities for their guests.
        </p>
        
        <!-- Legend -->
        <div class="mb-3 d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center">
                <div style="width: 20px; height: 20px; background-color: #8b5cf6; border: 2px solid white; border-radius: 50%; margin-right: 8px;"></div>
                <span class="small">Hotel/Resort with Squash Courts</span>
            </div>
            <div class="ms-auto">
                <span class="badge bg-primary" id="hotels-count">Loading...</span>
            </div>
        </div>
        
        <!-- Map Container -->
        <div id="hotels-resorts-map" style="height: 600px; border-radius: 0.5rem;"></div>
        
        <!-- Filter Options -->
        <div class="mt-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#hotelsList" aria-expanded="false">
                        <i class="fas fa-list me-1"></i>View All Hotels & Resorts
                    </button>
                </div>
                <div class="col-md-6">
                    <select class="form-select form-select-sm" id="continent-filter">
                        <option value="">All Continents</option>
                        <option value="1">Africa</option>
                        <option value="3">Asia</option>
                        <option value="4">Europe</option>
                        <option value="5">North America</option>
                        <option value="6">Oceania</option>
                        <option value="7">South America</option>
                    </select>
                </div>
            </div>
            
            <div class="collapse mt-2" id="hotelsList">
                <div class="card card-body">
                    <div id="hotels-list-content">
                        <div class="text-center text-muted">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




