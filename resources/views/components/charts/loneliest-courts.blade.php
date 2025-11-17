{{-- Loneliest Courts Map Component --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0" id="loneliest-courts-title">Loneliest Squash Courts</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small" id="loneliest-courts-description">
                    These are the squash venues that are furthest from their nearest neighbor.
                    Lines connect each venue to its closest squash court.
                </p>
                <!-- Legend -->
                <div class="mb-3 d-flex align-items-center gap-3 flex-wrap">
                    <div class="small text-muted">
                        <span style="color: #dc2626;">●</span> Loneliest venue
                        <span style="color: #3b82f6;">●</span> Nearest neighbor
                        <span style="color: #9ca3af;">━</span> Distance
                    </div>
                    <div class="ms-auto">
                        <span class="badge bg-primary" id="loneliest-venues-count">Loading...</span>
                    </div>
                </div>
                <div id="loneliest-courts-map" style="width: 100%; height: 600px;"></div>
            </div>
        </div>
    </div>
</div>

