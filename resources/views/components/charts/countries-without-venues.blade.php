<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3" id="countries-without-venues-title">
                    Countries & Dependencies without Squash Venues
                </h5>
                
                <!-- Legend -->
                <div class="mb-3 d-flex align-items-center gap-4">
                    <div class="d-flex align-items-center">
                        <div style="width: 20px; height: 20px; background-color: #10b981; border: 1px solid #059669; margin-right: 8px;"></div>
                        <span class="small">Has Venues</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div style="width: 20px; height: 20px; background-color: #ef4444; border: 1px solid #dc2626; margin-right: 8px;"></div>
                        <span class="small">No Venues (<span id="countries-without-count">-</span> countries)</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div style="width: 20px; height: 20px; background-color: #e5e7eb; border: 1px solid #d1d5db; margin-right: 8px;"></div>
                        <span class="small">No Data</span>
                    </div>
                </div>
                
                <!-- Map Container -->
                <div id="countries-without-map" style="height: 500px; border-radius: 0.5rem;"></div>
                
                <!-- Country List (collapsible) -->
                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#countryList" aria-expanded="false">
                        <i class="fas fa-list me-1"></i>View Full List
                    </button>
                    <div class="collapse mt-2" id="countryList">
                        <div class="card card-body">
                            <div id="country-list-content" class="row g-2">
                                <div class="col-12 text-center text-muted">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




