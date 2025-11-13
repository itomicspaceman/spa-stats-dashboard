<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="fas fa-tombstone me-2"></i>Squash Court Graveyard
        </h5>
        <p class="text-muted">
            A memorial to squash venues that have closed, been removed, or never existed. 
            This data helps us track the evolution of squash infrastructure worldwide.
        </p>
        
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <label for="graveyard-country-filter" class="form-label">Filter by Country</label>
                <select id="graveyard-country-filter" class="form-select">
                    <option value="">All Countries</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="graveyard-reason-filter" class="form-label">Filter by Cause of Death</label>
                <select id="graveyard-reason-filter" class="form-select">
                    <option value="">All Reasons</option>
                </select>
            </div>
        </div>
        
        <!-- Stats Summary -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center p-3">
                        <h3 class="mb-0" id="graveyard-total-venues">-</h3>
                        <small class="text-muted">Total Venues</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center p-3">
                        <h3 class="mb-0" id="graveyard-total-countries">-</h3>
                        <small class="text-muted">Countries</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center p-3">
                        <h3 class="mb-0" id="graveyard-total-courts">-</h3>
                        <small class="text-muted">Courts Lost</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center p-3">
                        <h3 class="mb-0" id="graveyard-filtered-count">-</h3>
                        <small class="text-muted">Showing</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover" id="graveyard-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Country</th>
                        <th>Courts</th>
                        <th>Cause of Death</th>
                        <th>Death Date</th>
                    </tr>
                </thead>
                <tbody id="graveyard-table-body">
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Info -->
        <div class="mt-3 text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            Showing <span id="graveyard-showing-count">0</span> venues
        </div>
    </div>
</div>

