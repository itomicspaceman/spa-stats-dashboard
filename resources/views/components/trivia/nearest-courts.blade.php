<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="fas fa-walking me-2"></i>Nearest Squash Venues
        </h5>
        <p class="text-muted">
            Squash venues that are within 300 meters of each other. These are the closest pairs of squash facilities worldwide.
        </p>
        
        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover table-striped" id="nearest-courts-table">
                <thead>
                    <tr>
                        <th class="text-end">SPA ID</th>
                        <th>Venue</th>
                        <th class="text-end">SPA ID</th>
                        <th>Nearest Venue</th>
                        <th>Country</th>
                        <th class="text-end">Distance (m)</th>
                        <th class="text-center">Walking Directions</th>
                    </tr>
                </thead>
                <tbody id="nearest-courts-table-body">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Info -->
        <div class="mt-3 text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            Showing <span id="nearest-courts-count">0</span> venue pairs within 300 meters of each other
        </div>
    </div>
</div>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE = '/api/squash';
    
    // Fetch data
    fetch(`${API_BASE}/nearest-courts`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('nearest-courts-table-body');
            const countSpan = document.getElementById('nearest-courts-count');
            
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No nearby venues found</td></tr>';
                countSpan.textContent = '0';
                return;
            }
            
            countSpan.textContent = data.length.toLocaleString();
            
            tbody.innerHTML = data.map(pair => {
                const directionsUrl = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(pair.source_name)}&origin_place_id=${pair.source_g_place_id}&destination=${encodeURIComponent(pair.target_name)}&destination_place_id=${pair.target_g_place_id}&travelmode=walking`;
                
                return `
                    <tr>
                        <td class="text-end">${pair.source_id}</td>
                        <td>
                            <a href="${pair.source_g_map_url || '#'}" target="_blank" rel="noopener noreferrer">
                                ${escapeHtml(pair.source_name)}
                            </a>
                            ${pair.source_no_of_courts > 0 ? ` <span class="text-muted">(${pair.source_no_of_courts})</span>` : ''}
                        </td>
                        <td class="text-end">${pair.target_id}</td>
                        <td>
                            <a href="${pair.target_g_map_url || '#'}" target="_blank" rel="noopener noreferrer">
                                ${escapeHtml(pair.target_name)}
                            </a>
                            ${pair.target_no_of_courts > 0 ? ` <span class="text-muted">(${pair.target_no_of_courts})</span>` : ''}
                        </td>
                        <td>${escapeHtml(pair.country)}</td>
                        <td class="text-end">${pair.distance.toLocaleString()}</td>
                        <td class="text-center">
                            <a href="${directionsUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-directions me-1"></i>Map
                            </a>
                        </td>
                    </tr>
                `;
            }).join('');
            
            // Initialize DataTables if available
            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                $('#nearest-courts-table').DataTable({
                    paging: true,
                    pageLength: 25,
                    order: [[5, 'asc']], // Sort by distance ascending
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                    }
                });
            } else {
                // Fallback: simple table without DataTables
                console.log('DataTables not available, using basic table');
            }
        })
        .catch(error => {
            console.error('Error loading nearest courts:', error);
            document.getElementById('nearest-courts-table-body').innerHTML = 
                '<tr><td colspan="7" class="text-center text-danger">Error loading data. Please try again later.</td></tr>';
        });
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

