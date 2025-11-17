@props(['title' => 'Squash Stats Dashboard', 'hero' => null, 'showFooter' => true, 'showSearch' => true])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    
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
            height: 600px;
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
    @php
        // Check if page is embedded (via iframe)
        $isEmbedded = request()->has('embed') || request()->has('embedded');
    @endphp

    @unless($isEmbedded)
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard.world') }}">
                <i class="fas fa-chart-line me-2"></i>Squash Venue & Court Stats
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard.world') ? 'active' : '' }}" href="{{ route('dashboard.world') }}">
                            <i class="fas fa-globe me-1"></i>Stats
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('trivia.index') ? 'active' : '' }}" href="{{ route('trivia.index') }}">
                            <i class="fas fa-lightbulb me-1"></i>Trivia
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('charts.gallery') ? 'active' : '' }}" href="{{ route('charts.gallery') }}">
                            <i class="fas fa-th me-1"></i>Chart Gallery
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    @if($showSearch)
    <!-- Geographic Search -->
    <x-geographic-search />
    @endif

    @if($hero)
    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            @if(is_array($hero))
                <h1 class="display-4 mb-2">{{ $hero['title'] ?? 'Squash Stats' }}</h1>
                <p class="lead mb-0">{{ $hero['subtitle'] ?? '' }}</p>
            @else
                <h1 class="display-4 mb-2">{{ $title }}</h1>
                <p class="lead mb-0">{{ $hero }}</p>
            @endif
        </div>
    </div>
    @endif
    @endunless

    <!-- Main Content -->
    <div class="container mb-5">
        {{ $slot }}
    </div>

    @if($showFooter)
    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 Squash Players. Data updated every 3 hours.</p>
        </div>
    </footer>
    @endif

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/wordcloud@1.2.2/src/wordcloud2.js"></script>
    <script src="https://unpkg.com/maplibre-gl@4.0.0/dist/maplibre-gl.js"></script>
    
    @vite(['resources/js/reports.js', 'resources/js/dashboard.js'])
    
    <!-- iframe Height Communication (for WordPress embedding) -->
    <script>
    (function() {
        // Only send messages if we're in an iframe
        if (window.parent === window) return;
        
        function sendHeightToParent() {
            const height = Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.clientHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight
            );
            
            window.parent.postMessage({
                type: 'squash-dashboard-height',
                height: height
            }, '*');
            
            console.log('Sent height to parent:', height);
        }
        
        // Send initial height when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', sendHeightToParent);
        } else {
            sendHeightToParent();
        }
        
        // Send height when everything is loaded (images, charts, etc.)
        window.addEventListener('load', function() {
            sendHeightToParent();
            // Send again after a delay to catch dynamic content
            setTimeout(sendHeightToParent, 1000);
            setTimeout(sendHeightToParent, 3000);
        });
        
        // Send height when window resizes
        window.addEventListener('resize', sendHeightToParent);
        
        // Observe DOM changes (for dynamically loaded content)
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function() {
                sendHeightToParent();
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true
            });
        }
    })();
    </script>
</body>
</html>

