<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="fas fa-compass me-2"></i>Most Northerly and Southerly Squash Venues
        </h5>
        
        <p class="text-muted mb-3">
            Discover the squash venues at the extreme northern and southern latitudes of the world.
        </p>
        
        <!-- Legend -->
        <div class="mb-3 d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center">
                <div style="width: 20px; height: 20px; background-color: #3b82f6; border: 2px solid white; border-radius: 50%; margin-right: 8px;"></div>
                <span class="small">Most Northerly (Top 20)</span>
            </div>
            <div class="d-flex align-items-center">
                <div style="width: 20px; height: 20px; background-color: #ef4444; border: 2px solid white; border-radius: 50%; margin-right: 8px;"></div>
                <span class="small">Most Southerly (Top 20)</span>
            </div>
            <div class="ms-auto">
                <span class="badge bg-primary" id="extreme-venues-count">Loading...</span>
            </div>
        </div>
        
        <!-- Map Container with satellite imagery -->
        <div id="extreme-latitude-map" style="height: 600px; border-radius: 0.5rem;"></div>
        
        <!-- Toggle Lists -->
        <div class="mt-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <button class="btn btn-sm btn-outline-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#northerlyList" aria-expanded="false">
                        <i class="fas fa-arrow-up me-1"></i>View Top 20 Northerly Venues
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-sm btn-outline-danger w-100" type="button" data-bs-toggle="collapse" data-bs-target="#southerlyList" aria-expanded="false">
                        <i class="fas fa-arrow-down me-1"></i>View Top 20 Southerly Venues
                    </button>
                </div>
            </div>
            
            <!-- Northerly List -->
            <div class="collapse mt-2" id="northerlyList">
                <div class="card card-body">
                    <h6 class="text-primary mb-3"><i class="fas fa-arrow-up me-2"></i>Most Northerly Squash Venues</h6>
                    <div id="northerly-list-content">
                        <div class="text-center text-muted">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Loading...
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Southerly List -->
            <div class="collapse mt-2" id="southerlyList">
                <div class="card card-body">
                    <h6 class="text-danger mb-3"><i class="fas fa-arrow-down me-2"></i>Most Southerly Squash Venues</h6>
                    <div id="southerly-list-content">
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




