<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="fas fa-question-circle me-2"></i>Squash Venues with Unknown # of Courts
        </h5>
        
        <p class="text-muted mb-3">
            These venues are in our database but we don't yet know how many courts they have. Can you help us complete this data?
        </p>
        
        <!-- Legend -->
        <div class="mb-3 d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center">
                <div style="width: 20px; height: 20px; background-color: #6c757d; border: 2px solid white; border-radius: 50%; margin-right: 8px;"></div>
                <span class="small">Unknown Court Count</span>
            </div>
            <div class="ms-auto">
                <span class="badge bg-secondary" id="unknown-courts-count">Loading...</span>
            </div>
        </div>
        
        <!-- Map Container -->
        <div id="unknown-courts-map" style="height: 600px; border-radius: 0.5rem;"></div>
        
        <!-- Filter Options -->
        <div class="mt-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <select class="form-select form-select-sm" id="unknown-courts-continent-filter">
                        <option value="">All Continents</option>
                        <option value="1">Africa</option>
                        <option value="3">Asia</option>
                        <option value="4">Europe</option>
                        <option value="5">North America</option>
                        <option value="6">Oceania</option>
                        <option value="7">South America</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#unknownCourtsList" aria-expanded="false">
                        <i class="fas fa-list me-1"></i>View All Venues
                    </button>
                </div>
            </div>
            
            <div class="collapse mt-2" id="unknownCourtsList">
                <div class="card card-body">
                    <div id="unknown-courts-list-content">
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




