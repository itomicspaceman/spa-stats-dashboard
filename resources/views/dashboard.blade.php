<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Squash Venues & Courts - Country Stats</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- MapLibre GL CSS -->
    <link href="https://unpkg.com/maplibre-gl@4.0.0/dist/maplibre-gl.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #map {
            height: 500px;
            border-radius: 0.5rem;
        }
        .chart-container {
            position: relative;
            height: 400px;
        }
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <h1 class="display-4 mb-2">Squash Venues & Courts</h1>
            <p class="lead mb-0">Global statistics and interactive map</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Summary Stats -->
        <div class="row g-4 mb-4" id="summary-stats">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-number text-primary" id="total-countries">-</div>
                        <div class="stat-label">Total countries & dependencies</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-number text-success" id="countries-with-venues">-</div>
                        <div class="stat-label">Countries with squash venues</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-number text-info" id="total-venues">-</div>
                        <div class="stat-label">Total squash venues</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="stat-number text-warning" id="total-courts">-</div>
                        <div class="stat-label">Total squash courts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interactive Map -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Global squash venue map</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 0: Continental & Timeline (immediately after map) -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Squash venues & courts by continent</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="regional-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Squash venues & courts by sub-continent</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="subcontinental-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Squash venues added over time</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="timeline-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Top 20 countries by squash venues</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="top-venues-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Squash courts per venue</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="court-dist-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Courts & Categories -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Top 20 countries by squash courts</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="top-courts-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Squash venues by category</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categories-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 3: Website Stats & Outdoor Courts -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Squash venues with websites on Google Maps</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="website-stats-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Top 20 countries by outdoor squash courts</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="top-outdoor-courts-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 Squash Players. Data updated every 3 hours.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@4.0.0/dist/maplibre-gl.js"></script>
    
    @vite(['resources/js/reports.js', 'resources/js/dashboard.js'])
</body>
</html>

