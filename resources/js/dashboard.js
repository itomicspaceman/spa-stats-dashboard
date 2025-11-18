/**
 * Squash Dashboard - Main JavaScript Module
 * Handles data fetching, chart rendering, and map initialization
 * Updated to fix API data format issues
 */

// API Base URL - Dynamically determined based on environment
const API_BASE = window.location.hostname === 'spa.test' 
    ? 'https://spa.test/api/squash'
    : 'https://stats.squashplayers.app/api/squash';

/**
 * Geographic area name mappings for dynamic titles
 */
const GEOGRAPHIC_NAMES = {
    continents: {
        1: 'Africa',
        2: 'Antarctica',
        3: 'Asia',
        4: 'Europe',
        5: 'North America',
        6: 'Oceania',
        7: 'South America'
    },
    regions: {
        // Will be populated from API if needed
    },
    countries: {
        // Will be populated from API if needed
    }
};

/**
 * Adjust chart/map titles based on filter parameter
 * @param {string} filter - Filter string (e.g., "country:AU", "region:19", "continent:5")
 * @param {string} customTitle - Optional custom title override
 */
async function adjustTitles(filter, customTitle = null) {
    // If custom title provided, use it for all charts that exist
    if (customTitle) {
        document.querySelectorAll('[id$="-title"]').forEach(el => {
            if (el && !el.id.includes('venue-map')) {
                el.textContent = customTitle;
            }
        });
        // Special handling for map title
        const mapTitle = document.getElementById('venue-map-title');
        if (mapTitle) mapTitle.textContent = customTitle;
        return;
    }

    // If no filter, keep default generic titles
    if (!filter) return;

    // Fetch the actual area name from the API
    let areaName = '';
    try {
        const response = await fetch(`${API_BASE}/filter-details?filter=${encodeURIComponent(filter)}`);
        if (response.ok) {
            const data = await response.json();
            areaName = data.name || '';
        }
    } catch (error) {
        console.debug('Failed to fetch area name:', error);
        return; // If we can't get the area name, don't update titles
    }

    // Only proceed if we have an area name
    if (!areaName) return;

    // Update map title if it exists
    const mapTitle = document.getElementById('venue-map-title');
    if (mapTitle) {
        mapTitle.textContent = `Squash venue map: ${areaName}`;
    }

    // Update other chart titles only if they exist
    // Format: "Chart Name: Area Name"
    const titleMappings = {
        'timeline-title': `Squash venues added over time: ${areaName}`,
        'top-venues-title': `Top 20 countries by squash venues: ${areaName}`,
        'top-courts-title': `Top 20 countries by squash courts: ${areaName}`,
        'outdoor-courts-title': `Top 20 countries by outdoor squash courts: ${areaName}`,
        'categories-title': `Squash venues by category: ${areaName}`,
        'website-stats-title': `Squash venues with websites on Google Maps: ${areaName}`,
        'court-distribution-title': `Squash courts per venue: ${areaName}`,
        'continental-title': `Squash venues & courts by continent: ${areaName}`,
        'subcontinental-title': `Squash venues & courts by region: ${areaName}`,
        'state-title': `Squash venues & courts by state/county: ${areaName}`,
        'venues-by-state-pie-title': `Squash venues by state/county: ${areaName}`,
        'top-venues-by-courts-title': `Top 20 squash venues by courts: ${areaName}`
    };

    Object.entries(titleMappings).forEach(([id, title]) => {
        const el = document.getElementById(id);
        if (el) el.textContent = title;
    });
}

// Chart.js default configuration
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
Chart.defaults.color = '#495057';

// Register datalabels plugin but disable by default
Chart.register(ChartDataLabels);
Chart.defaults.set('plugins.datalabels', {
    display: false
});

/**
 * Get filter parameters from URL or data attributes
 * @returns {Object} Object containing filter and customTitle
 */
function getFilterParams() {
    // Try URL parameters first (for standalone pages)
    const urlParams = new URLSearchParams(window.location.search);
    const urlFilter = urlParams.get('filter');
    const urlTitle = urlParams.get('title');
    
    // Try data attributes (for embedded/shortcode usage)
    const container = document.querySelector('[data-filter], [data-title]');
    const dataFilter = container?.getAttribute('data-filter');
    const dataTitle = container?.getAttribute('data-title');
    
    return {
        filter: urlFilter || dataFilter || null,
        customTitle: urlTitle || dataTitle || null
    };
}

/**
 * Fetch data from API endpoint with optional filter
 * @param {string} endpoint - API endpoint path
 * @param {string|null} filter - Geographic filter (e.g., "country:US")
 */
async function fetchData(endpoint, filter = null) {
    try {
        let url = `${API_BASE}${endpoint}`;
        
        // Add filter parameter if provided
        if (filter) {
            const separator = endpoint.includes('?') ? '&' : '?';
            url += `${separator}filter=${encodeURIComponent(filter)}`;
        }
        
        const response = await fetch(url);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error(`Error fetching ${endpoint}:`, error);
        return null;
    }
}

/**
 * Update summary statistics cards
 * @param {string|null} filter - Geographic filter
 */
async function updateSummaryStats(filter = null) {
    const data = await fetchData('/country-stats', filter);
    if (!data) return;

    document.getElementById('total-venues').textContent = data.total_venues?.toLocaleString() || '-';
    document.getElementById('total-courts').textContent = data.total_courts?.toLocaleString() || '-';
}

/**
 * Add standard map controls (zoom +/-, reset globe button)
 * @param {maplibregl.Map} map - MapLibre map instance
 * @param {Object} resetOptions - Options for reset button {center, zoom}
 */
function addStandardMapControls(map, resetOptions = {center: [0, 20], zoom: 1.5}) {
    // Add navigation controls (zoom in/out only, no compass)
    map.addControl(new maplibregl.NavigationControl({
        showCompass: false
    }), 'top-right');
    
    // Add custom "Reset to Global View" button using Font Awesome globe icon
    class ResetControl {
        onAdd(map) {
            this._map = map;
            this._container = document.createElement('div');
            this._container.className = 'maplibregl-ctrl maplibregl-ctrl-group';
            this._container.innerHTML = `
                <button type="button" title="Reset to global view" aria-label="Reset to global view" style="font-size: 20px;">
                    <i class="fas fa-earth-americas" style="color: #333;"></i>
                </button>
            `;
            this._container.querySelector('button').addEventListener('click', () => {
                map.flyTo({
                    center: resetOptions.center,
                    zoom: resetOptions.zoom,
                    duration: 1000
                });
            });
            return this._container;
        }
        
        onRemove() {
            this._container.parentNode.removeChild(this._container);
            this._map = undefined;
        }
    }
    
    map.addControl(new ResetControl(), 'top-right');
}

/**
 * Initialize MapLibre GL map with venue markers
 * @param {string|null} filter - Geographic filter
 */
async function initMap(filter = null) {
    const mapData = await fetchData('/map', filter);
    if (!mapData) return;

    const map = new maplibregl.Map({
        container: 'map',
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'osm': {
                    type: 'raster',
                    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    tileSize: 256,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }
            },
            layers: [{
                id: 'osm',
                type: 'raster',
                source: 'osm',
                minzoom: 0,
                maxzoom: 19
            }]
        },
        center: [0, 20],
        zoom: 1.5
    });

    // Add standard map controls
    addStandardMapControls(map);

    map.on('load', () => {
        console.log('Map loaded! Adding layers...');
        console.log('Map data features:', mapData?.features?.length || 0);
        
        // Add venue points source
        map.addSource('venues', {
            type: 'geojson',
            data: mapData,
            cluster: true,
            clusterMaxZoom: 14,
            clusterRadius: 50
        });
        
        // Calculate bounds and fit map to venues if we have data
        if (mapData && mapData.features && mapData.features.length > 0) {
            const bounds = new maplibregl.LngLatBounds();
            
            mapData.features.forEach(feature => {
                if (feature.geometry && feature.geometry.coordinates) {
                    bounds.extend(feature.geometry.coordinates);
                }
            });
            
            // Fit the map to the bounds with some padding
            map.fitBounds(bounds, {
                padding: {top: 50, bottom: 50, left: 50, right: 50},
                maxZoom: 15, // Don't zoom in too close
                duration: 1000 // Smooth animation
            });
        }

        // Clustered circles
        map.addLayer({
            id: 'clusters',
            type: 'circle',
            source: 'venues',
            filter: ['has', 'point_count'],
            paint: {
                'circle-color': [
                    'step',
                    ['get', 'point_count'],
                    '#51bbd6',    // < 10: Light blue
                    10,
                    '#f1f075',    // 10-49: Yellow
                    50,
                    '#f28cb1',    // 50-999: Pink
                    1000,
                    '#ff6b6b'     // 1000+: Red
                ],
                'circle-radius': [
                    'step',
                    ['get', 'point_count'],
                    20,           // < 10: 20px
                    10,
                    30,           // 10-49: 30px
                    50,
                    40,           // 50-999: 40px
                    1000,
                    55            // 1000+: 55px
                ]
            }
        });

        // Cluster count labels
        map.addLayer({
            id: 'cluster-count',
            type: 'symbol',
            source: 'venues',
            filter: ['has', 'point_count'],
            layout: {
                'text-field': ['get', 'point_count_abbreviated'],
                'text-font': ['Klokantech Noto Sans Bold'],
                'text-size': 13
            },
            paint: {
                'text-color': '#ffffff'
            }
        });

        // Unclustered points
        map.addLayer({
            id: 'unclustered-point',
            type: 'circle',
            source: 'venues',
            filter: ['!', ['has', 'point_count']],
            paint: {
                'circle-color': '#11b4da',
                'circle-radius': 6,
                'circle-stroke-width': 2,
                'circle-stroke-color': '#fff'
            }
        });

        // Click handler for clusters - zoom in on click
        map.on('click', 'clusters', (e) => {
            const features = map.queryRenderedFeatures(e.point, {
                layers: ['clusters']
            });
            
            if (!features || features.length === 0) {
                return;
            }
            
            const coordinates = features[0].geometry.coordinates.slice();
            const pointCount = features[0].properties.point_count;
            
            console.log(`Cluster clicked with ${pointCount} venues, zooming in...`);
            
            // Simple and reliable: zoom in by 2 levels centered on the cluster
            map.easeTo({
                center: coordinates,
                zoom: map.getZoom() + 2,
                duration: 500
            });
        });

        // Click handler for individual points
        map.on('click', 'unclustered-point', (e) => {
            const coordinates = e.features[0].geometry.coordinates.slice();
            const { name, address, courts, telephone, website } = e.features[0].properties;

            // Build popup content with venue details
            let popupContent = `<div style="min-width: 200px;">`;
            
            // Venue name
            popupContent += `<strong style="font-size: 14px;">${name || 'Unknown'}</strong><br>`;
            
            // Address
            if (address && address !== 'Address not available') {
                popupContent += `<div style="margin: 8px 0; font-size: 12px; color: #666;">${address}</div>`;
            }
            
            // Number of courts
            const courtsDisplay = courts ? courts : 'unknown';
            popupContent += `<div style="margin: 8px 0; font-size: 13px;">No. of courts: <strong>${courtsDisplay}</strong></div>`;
            
            // Telephone
            if (telephone) {
                popupContent += `<div style="margin: 4px 0; font-size: 12px;">t: <a href="tel:${telephone}" style="color: #0066cc;">${telephone}</a></div>`;
            }
            
            // Website
            if (website) {
                const displayUrl = website.replace(/^https?:\/\//, '').replace(/\/$/, '');
                popupContent += `<div style="margin: 4px 0; font-size: 12px;">w: <a href="${website}" target="_blank" style="color: #0066cc;">${displayUrl}</a></div>`;
            }
            
            popupContent += `</div>`;

            new maplibregl.Popup()
                .setLngLat(coordinates)
                .setHTML(popupContent)
                .addTo(map);
        });

        // Change cursor on hover
        map.on('mouseenter', 'clusters', () => {
            map.getCanvas().style.cursor = 'pointer';
        });
        map.on('mouseleave', 'clusters', () => {
            map.getCanvas().style.cursor = '';
        });
        map.on('mouseenter', 'unclustered-point', () => {
            map.getCanvas().style.cursor = 'pointer';
        });
        map.on('mouseleave', 'unclustered-point', () => {
            map.getCanvas().style.cursor = '';
        });
    });
}

/**
 * Create Top Countries by Venues chart
 * @param {string|null} filter - Geographic filter
 */
async function createTopVenuesChart(filter = null) {
    const data = await fetchData('/top-countries?metric=venues&limit=20', filter);
    console.log('Top Venues Chart Data:', data);
    if (!data) {
        console.error('No data returned for top venues chart');
        return;
    }

    const canvas = document.getElementById('top-venues-chart');
    if (!canvas) {
        console.warn('Canvas element "top-venues-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(c => c.name),
            datasets: [{
                label: 'Venues',
                data: data.map(c => c.total_venues),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Create Court Distribution chart
 * @param {string|null} filter - Geographic filter
 */
async function createCourtDistributionChart(filter = null) {
    const data = await fetchData('/court-distribution', filter);
    if (!data) return;

    const canvas = document.getElementById('court-dist-chart');
    if (!canvas) {
        console.warn('Canvas element "court-dist-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                label: '# of courts',
                data: data.data,
                backgroundColor: [
                    'rgba(128, 128, 128, 0.8)', // Unknown - gray
                    'rgba(54, 162, 235, 0.8)',  // 1 - blue
                    'rgba(75, 192, 192, 0.8)',  // 2 - teal
                    'rgba(255, 99, 132, 0.8)',  // 3 - red
                    'rgba(255, 159, 64, 0.8)',  // 4 - orange
                    'rgba(153, 102, 255, 0.8)', // 5 - purple
                    'rgba(255, 205, 86, 0.8)',  // 6 - yellow
                    'rgba(201, 203, 207, 0.8)', // 7 - gray
                    'rgba(255, 99, 71, 0.8)',   // 8 - tomato
                    'rgba(144, 238, 144, 0.8)', // 9 - light green
                    'rgba(173, 216, 230, 0.8)', // 10 - light blue
                    'rgba(221, 160, 221, 0.8)', // 11+ - plum
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    return {
                                        text: `${label}    ${value}`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Website Stats chart
 * @param {string|null} filter - Geographic filter
 */
async function createWebsiteStatsChart(filter = null) {
    const data = await fetchData('/website-stats', filter);
    if (!data) return;

    const canvas = document.getElementById('website-stats-chart');
    if (!canvas) {
        console.warn('Canvas element "website-stats-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    
    // Calculate percentages
    const total = data.data.reduce((a, b) => a + b, 0);
    const percentages = data.data.map(value => ((value / total) * 100).toFixed(1));
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels.map((label, i) => `${label}    ${data.data[i]}`),
            datasets: [{
                data: data.data,
                backgroundColor: [
                    'rgba(102, 204, 204, 0.9)', // Teal for "Yes"
                    'rgba(99, 132, 255, 0.9)',  // Blue for "No"
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: {
                            size: 14
                        },
                        padding: 15,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    return {
                                        text: label,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = data.labels[context.dataIndex] || '';
                            const value = context.parsed || 0;
                            const percentage = percentages[context.dataIndex];
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                },
                // Add percentage labels inside the pie slices
                datalabels: {
                    display: true,
                    color: '#fff',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    formatter: function(value, context) {
                        return percentages[context.dataIndex] + '%';
                    }
                }
            }
        }
    });
}

/**
 * Create Venues by State/County Pie Chart
 * @param {string|null} filter - Geographic filter
 */
async function createVenuesByStatePieChart(filter = null) {
    const data = await fetchData('/venues-by-state', filter);
    if (!data) return;

    const canvas = document.getElementById('venues-by-state-pie-chart');
    if (!canvas) {
        console.warn('Canvas element "venues-by-state-pie-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    
    // Calculate percentages
    const total = data.reduce((sum, item) => sum + item.venues, 0);
    const percentages = data.map(item => ((item.venues / total) * 100).toFixed(1));
    
    // Generate colors for each state
    const colors = data.map((_, i) => {
        const hue = (i * 360 / data.length) % 360;
        return `hsla(${hue}, 70%, 60%, 0.9)`;
    });
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(item => `${item.name}    ${item.venues}`),
            datasets: [{
                data: data.map(item => item.venues),
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: {
                            size: 12
                        },
                        padding: 10,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    return {
                                        text: label,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const item = data[context.dataIndex];
                            const percentage = percentages[context.dataIndex];
                            return `${item.name}: ${item.venues} venues (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        size: 14,
                        weight: 'bold'
                    },
                    formatter: function(value, context) {
                        return percentages[context.dataIndex] + '%';
                    }
                }
            }
        }
    });
}

/**
 * Create Top Venues by Courts chart
 * @param {string|null} filter - Geographic filter
 */
async function createTopVenuesByCountsChart(filter = null) {
    const data = await fetchData('/top-venues-by-courts?limit=20', filter);
    if (!data) return;

    const canvas = document.getElementById('top-venues-by-courts-chart');
    if (!canvas) {
        console.warn('Canvas element "top-venues-by-courts-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    
    // Build venue labels with name and physical address
    const labels = data.map(venue => {
        // Use the actual physical address
        const address = venue.physical_address || '';
        const label = address ? `${venue.name} (${address})` : venue.name;
        return label;
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Courts',
                data: data.map(v => v.no_of_courts),
                backgroundColor: 'rgba(99, 132, 255, 0.9)',
                borderColor: 'rgba(99, 132, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            onHover: (event, activeElements) => {
                event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
            },
            onClick: (event, activeElements) => {
                // Also handle clicks on the canvas that might not hit an active element
                // by finding the closest data point
                const chart = event.chart;
                const canvasPosition = Chart.helpers.getRelativePosition(event, chart);
                
                // Get the data index from the y-axis (since it's a horizontal bar chart)
                const dataY = chart.scales.y.getValueForPixel(canvasPosition.y);
                const index = Math.round(dataY);
                
                if (index >= 0 && index < data.length) {
                    const venue = data[index];
                    console.log('Clicked venue:', venue); // Debug
                    if (venue.g_place_id) {
                        // Open Google Maps place page in new tab, breaking out of iframe if present
                        const url = `https://www.google.com/maps/place/?q=place_id:${venue.g_place_id}`;
                        console.log('Opening URL:', url); // Debug
                        if (window.top !== window.self) {
                            // We're in an iframe, open in parent window
                            window.top.open(url, '_blank');
                        } else {
                            // Not in iframe, open normally
                            window.open(url, '_blank');
                        }
                    } else {
                        console.log('No g_place_id found for venue'); // Debug
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    formatter: function(value) {
                        return value;
                    },
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    color: '#333'
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        stepSize: 1,
                        precision: 0
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
                        autoSkip: false,
                        color: '#333'
                    }
                }
            }
        }
    });
    
    // Add cursor pointer style to canvas for better UX
    canvas.style.cursor = 'pointer';
}

/**
 * Create Top Countries by Courts chart
 * @param {string|null} filter - Geographic filter
 */
async function createTopCourtsChart(filter = null) {
    const data = await fetchData('/top-countries?metric=courts&limit=20', filter);
    if (!data) return;

    const canvas = document.getElementById('top-courts-chart');
    if (!canvas) {
        console.warn('Canvas element "top-courts-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(c => c.name),
            datasets: [{
                label: 'Courts',
                data: data.map(c => c.total_courts),
                backgroundColor: 'rgba(255, 159, 64, 0.8)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Create Venue Categories chart
 * @param {string|null} filter - Geographic filter
 */
async function createCategoriesChart(filter = null) {
    const data = await fetchData('/venue-types', filter);
    if (!data) return;

    const canvas = document.getElementById('categories-chart');
    if (!canvas) {
        console.warn('Canvas element "categories-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(c => c.name),
            datasets: [{
                data: data.map(c => c.venue_count),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(201, 203, 207, 0.8)',
                    'rgba(255, 205, 210, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
}

/**
 * Create Regional Breakdown chart
 * Styled to match squash.players.app design
 * @param {string|null} filter - Geographic filter
 */
async function createRegionalChart(filter = null) {
    const data = await fetchData('/regional-breakdown', filter);
    if (!data) return;

    const canvas = document.getElementById('regional-chart');
    if (!canvas) {
        console.warn('Canvas element "regional-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(r => r.name),
            datasets: [
                {
                    label: 'venues',
                    data: data.map(r => r.venues),
                    backgroundColor: 'rgba(99, 132, 255, 0.9)', // Blue matching the reference
                    borderColor: 'rgba(99, 132, 255, 1)',
                    borderWidth: 1
                },
                {
                    label: 'courts',
                    data: data.map(r => r.courts),
                    backgroundColor: 'rgba(102, 204, 204, 0.9)', // Teal matching the reference
                    borderColor: 'rgba(102, 204, 204, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 15,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                        }
                    }
                },
                // Add data labels on top of bars
                datalabels: {
                    display: true, // Enable for this chart only
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) {
                        return value.toLocaleString();
                    },
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    color: '#333'
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Sub-Continental Breakdown chart
 * Styled to match squash.players.app design
 * @param {string|null} filter - Geographic filter
 */
async function createSubContinentalChart(filter = null) {
    const data = await fetchData('/subcontinental-breakdown', filter);
    if (!data) return;

    const canvas = document.getElementById('subcontinental-chart');
    if (!canvas) {
        console.warn('Canvas element "subcontinental-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(r => r.name),
            datasets: [
                {
                    label: 'venues',
                    data: data.map(r => r.venues),
                    backgroundColor: 'rgba(99, 132, 255, 0.9)', // Blue matching the reference
                    borderColor: 'rgba(99, 132, 255, 1)',
                    borderWidth: 1
                },
                {
                    label: 'courts',
                    data: data.map(r => r.courts),
                    backgroundColor: 'rgba(102, 204, 204, 0.9)', // Teal matching the reference
                    borderColor: 'rgba(102, 204, 204, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 15,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                        }
                    }
                },
                // Add data labels on top of bars
                datalabels: {
                    display: true, // Enable for this chart only
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) {
                        return value.toLocaleString();
                    },
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    color: '#333'
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create State/County Breakdown chart
 * @param {string|null} filter - Geographic filter
 */
async function createStateBreakdownChart(filter = null) {
    const data = await fetchData('/venues-by-state', filter);
    if (!data) return;

    const canvas = document.getElementById('state-chart');
    if (!canvas) {
        console.warn('Canvas element "state-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(r => r.name),
            datasets: [
                {
                    label: 'venues',
                    data: data.map(r => r.venues),
                    backgroundColor: 'rgba(99, 132, 255, 0.9)',
                    borderColor: 'rgba(99, 132, 255, 1)',
                    borderWidth: 1
                },
                {
                    label: 'courts',
                    data: data.map(r => r.courts),
                    backgroundColor: 'rgba(102, 204, 204, 0.9)',
                    borderColor: 'rgba(102, 204, 204, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 15,
                        padding: 15
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) {
                        return value.toLocaleString();
                    },
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    color: '#333'
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Court Types chart
 * REMOVED - Chart no longer displayed on dashboard
 */

/**
 * Create Top Glass Courts chart
 * REMOVED - Chart no longer displayed on dashboard
 */

/**
 * Create Top Outdoor Courts chart
 * @param {string|null} filter - Geographic filter
 */
async function createTopOutdoorCourtsChart(filter = null) {
    const data = await fetchData('/top-countries?metric=outdoor_courts&limit=20', filter);
    if (!data) return;

    const canvas = document.getElementById('top-outdoor-courts-chart');
    if (!canvas) {
        console.warn('Canvas element "top-outdoor-courts-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(c => c.name),
            datasets: [{
                label: 'Outdoor Courts',
                data: data.map(c => c.total_outdoor_courts),
                backgroundColor: 'rgba(153, 102, 255, 0.8)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Create Timeline chart
 * @param {string|null} filter - Geographic filter
 */
async function createTimelineChart(filter = null) {
    const data = await fetchData('/timeline', filter);
    if (!data) return;

    const canvas = document.getElementById('timeline-chart');
    if (!canvas) {
        console.warn('Canvas element "timeline-chart" not found');
        return;
    }
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.period),
            datasets: [{
                label: 'New Venues',
                data: data.map(d => d.count),
                borderColor: 'rgba(23, 162, 184, 1)',
                backgroundColor: 'rgba(23, 162, 184, 0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Initialize Countries Without Venues Choropleth Map
 */
async function initCountriesWithoutVenuesMap() {
    const mapContainer = document.getElementById('countries-without-map');
    if (!mapContainer) {
        console.warn('Countries without venues map container not found');
        return;
    }

    // Fetch countries without venues
    const countriesWithoutVenues = await fetchData('/countries-without-venues');
    if (!countriesWithoutVenues) return;

    // Update count
    const countElement = document.getElementById('countries-without-count');
    if (countElement) {
        countElement.textContent = countriesWithoutVenues.length;
    }

    // Create a Set of ISO alpha-2 codes for countries without venues
    const countriesWithoutSet = new Set(countriesWithoutVenues.map(c => c.alpha_2_code));

    // Initialize map
    const map = new maplibregl.Map({
        container: 'countries-without-map',
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'osm': {
                    type: 'raster',
                    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    tileSize: 256,
                    attribution: '© OpenStreetMap contributors'
                }
            },
            layers: [
                {
                    id: 'osm-tiles',
                    type: 'raster',
                    source: 'osm',
                    minzoom: 0,
                    maxzoom: 19
                }
            ]
        },
        center: [0, 20],
        zoom: 1.5,
        maxZoom: 6
    });

    // Add standard map controls
    addStandardMapControls(map);

    map.on('load', async () => {
        // Fetch GeoJSON country boundaries from Natural Earth
        const response = await fetch('https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_110m_admin_0_countries.geojson');
        const geojson = await response.json();

        // Add source
        map.addSource('countries-data', {
            type: 'geojson',
            data: geojson
        });

        // Add fill layer with color coding
        map.addLayer({
            id: 'countries-fill',
            type: 'fill',
            source: 'countries-data',
            paint: {
                'fill-color': [
                    'case',
                    ['in', ['get', 'ISO_A2'], ['literal', Array.from(countriesWithoutSet)]],
                    '#ef4444', // Red for countries without venues
                    '#10b981'  // Green for countries with venues
                ],
                'fill-opacity': 0.7
            }
        });

        // Add border layer
        map.addLayer({
            id: 'countries-border',
            type: 'line',
            source: 'countries-data',
            paint: {
                'line-color': '#ffffff',
                'line-width': 1
            }
        });

        // Add hover effect
        map.on('mousemove', 'countries-fill', (e) => {
            if (e.features.length > 0) {
                map.getCanvas().style.cursor = 'pointer';
                
                const country = e.features[0];
                const countryName = country.properties.NAME;
                const iso = country.properties.ISO_A2;
                const hasVenues = !countriesWithoutSet.has(iso);
                
                // Create popup
                new maplibregl.Popup({
                    closeButton: false,
                    closeOnClick: false
                })
                .setLngLat(e.lngLat)
                .setHTML(`
                    <div style="padding: 8px;">
                        <strong>${countryName}</strong><br>
                        <span style="color: ${hasVenues ? '#10b981' : '#ef4444'};">
                            ${hasVenues ? '✓ Has squash venues' : '✗ No squash venues'}
                        </span>
                    </div>
                `)
                .addTo(map);
            }
        });

        map.on('mouseleave', 'countries-fill', () => {
            map.getCanvas().style.cursor = '';
            // Remove all popups
            document.querySelectorAll('.maplibregl-popup').forEach(popup => popup.remove());
        });
    });

    // Populate country list
    const listContent = document.getElementById('country-list-content');
    if (listContent && countriesWithoutVenues.length > 0) {
        // Group by first letter
        const grouped = {};
        countriesWithoutVenues.forEach(country => {
            const firstLetter = country.name[0].toUpperCase();
            if (!grouped[firstLetter]) {
                grouped[firstLetter] = [];
            }
            grouped[firstLetter].push(country);
        });

        let html = '';
        Object.keys(grouped).sort().forEach(letter => {
            html += `<div class="col-12"><h6 class="text-muted mb-2">${letter}</h6></div>`;
            grouped[letter].forEach(country => {
                html += `<div class="col-md-4 col-sm-6"><span class="badge bg-light text-dark">${country.name}</span></div>`;
            });
        });

        listContent.innerHTML = html;
    }
}

/**
 * Initialize Highest Venues Map (Trivia)
 */
async function initHighestVenuesMap() {
    const mapContainer = document.getElementById('highest-venues-map');
    if (!mapContainer) {
        console.warn('Highest venues map container not found');
        return;
    }

    // Fetch venues with elevation
    const allVenues = await fetchData('/venues-with-elevation');
    if (!allVenues || allVenues.length === 0) return;

    // Filter to only show venues at 2000m or higher
    const venues = allVenues.filter(v => v.elevation >= 2000);

    // Update count
    const countElement = document.getElementById('venues-count');
    if (countElement) {
        countElement.textContent = `${venues.length} venues`;
    }

    // Function to get color based on elevation (simplified for 2000m+)
    function getElevationColor(elevation) {
        if (elevation < 3000) return '#fbbf24'; // Yellow (2000-3000m)
        if (elevation < 3500) return '#f97316'; // Orange (3000-3500m)
        return '#dc2626'; // Red (3500m+)
    }

    // Initialize map
    const map = new maplibregl.Map({
        container: 'highest-venues-map',
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'osm': {
                    type: 'raster',
                    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    tileSize: 256,
                    attribution: '© OpenStreetMap contributors'
                }
            },
            layers: [
                {
                    id: 'osm-tiles',
                    type: 'raster',
                    source: 'osm',
                    minzoom: 0,
                    maxzoom: 19
                }
            ]
        },
        center: [0, 20],
        zoom: 1.5
    });

    // Add standard map controls
    addStandardMapControls(map);

    // Store all markers for filtering
    let allMarkers = [];

    map.on('load', () => {
        // Calculate bounds to fit all venues
        const bounds = new maplibregl.LngLatBounds();
        venues.forEach(venue => {
            bounds.extend([venue.longitude, venue.latitude]);
        });
        
        // Fit map to show all venues with padding
        map.fitBounds(bounds, {
            padding: {top: 50, bottom: 50, left: 50, right: 50},
            maxZoom: 15,
            duration: 0
        });
        
        // Add markers for each venue
        venues.forEach(venue => {
            const el = document.createElement('div');
            el.className = 'elevation-marker';
            el.style.backgroundColor = getElevationColor(venue.elevation);
            el.style.width = '12px';
            el.style.height = '12px';
            el.style.borderRadius = '50%';
            el.style.border = '2px solid white';
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            el.style.cursor = 'pointer';

            const marker = new maplibregl.Marker({element: el})
                .setLngLat([venue.longitude, venue.latitude])
                .setPopup(
                    new maplibregl.Popup({offset: 15})
                        .setHTML(`
                            <div style="padding: 8px; min-width: 200px;">
                                <strong>${venue.name}</strong><br>
                                <span class="text-muted small">${venue.address || venue.suburb || ''}, ${venue.country}</span><br>
                                <div class="mt-2">
                                    <span style="color: ${getElevationColor(venue.elevation)}; font-weight: bold;">
                                        ⛰️ ${venue.elevation}m
                                    </span>
                                    <span class="text-muted small ms-2">
                                        🎾 ${venue.courts} court${venue.courts !== 1 && venue.courts !== 'Unknown' ? 's' : ''}
                                    </span>
                                </div>
                            </div>
                        `)
                )
                .addTo(map);

            allMarkers.push({marker, venue});
        });
    });

    // Populate top 10 list
    const listContent = document.getElementById('top-venues-list-content');
    if (listContent) {
        const top10 = venues.slice(0, 10);
        let html = '<ol class="list-group list-group-numbered">';
        top10.forEach(venue => {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">${venue.name}</div>
                        <small class="text-muted">${venue.suburb || venue.state || ''}, ${venue.country}</small>
                    </div>
                    <span class="badge" style="background-color: ${getElevationColor(venue.elevation)};">
                        ${venue.elevation}m
                    </span>
                </li>
            `;
        });
        html += '</ol>';
        listContent.innerHTML = html;
    }
}

/**
 * Initialize Extreme Latitude Venues Map (Trivia)
 */
async function initExtremeLatitudeMap() {
    const mapContainer = document.getElementById('extreme-latitude-map');
    if (!mapContainer) {
        console.warn('Extreme latitude map container not found');
        return;
    }

    // Fetch venues at extreme latitudes
    const data = await fetchData('/extreme-latitude-venues');
    if (!data || !data.northerly || !data.southerly) return;

    const allVenues = [...data.northerly, ...data.southerly];

    // Update count
    const countElement = document.getElementById('extreme-venues-count');
    if (countElement) {
        countElement.textContent = `${allVenues.length} venues`;
    }

    // Initialize map with satellite imagery
    const map = new maplibregl.Map({
        container: 'extreme-latitude-map',
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'satellite': {
                    type: 'raster',
                    tiles: [
                        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
                    ],
                    tileSize: 256,
                    attribution: '© Esri'
                },
                'labels': {
                    type: 'raster',
                    tiles: [
                        'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}'
                    ],
                    tileSize: 256
                }
            },
            layers: [
                {
                    id: 'satellite-tiles',
                    type: 'raster',
                    source: 'satellite',
                    minzoom: 0,
                    maxzoom: 19
                },
                {
                    id: 'labels-layer',
                    type: 'raster',
                    source: 'labels',
                    minzoom: 0,
                    maxzoom: 19
                }
            ]
        },
        center: [20, 35],
        zoom: 1.2
    });

    // Add standard map controls with custom reset position
    addStandardMapControls(map, {center: [20, 35], zoom: 1.2});

    map.on('load', () => {
        // Calculate bounds to fit all venues
        const bounds = new maplibregl.LngLatBounds();
        allVenues.forEach(venue => {
            bounds.extend([venue.longitude, venue.latitude]);
        });
        
        // Fit map to show all venues with padding
        map.fitBounds(bounds, {
            padding: {top: 50, bottom: 50, left: 50, right: 50},
            maxZoom: 15,
            duration: 0
        });
        
        // Add markers for northerly venues (blue)
        data.northerly.forEach(venue => {
            const el = document.createElement('div');
            el.style.backgroundColor = '#3b82f6';
            el.style.width = '12px';
            el.style.height = '12px';
            el.style.borderRadius = '50%';
            el.style.border = '2px solid white';
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            el.style.cursor = 'pointer';

            new maplibregl.Marker({element: el})
                .setLngLat([venue.longitude, venue.latitude])
                .setPopup(
                    new maplibregl.Popup({offset: 15})
                        .setHTML(`
                            <div style="padding: 8px; min-width: 200px;">
                                <strong>${venue.name}</strong><br>
                                <span class="text-muted small">${venue.address || venue.suburb || ''}, ${venue.country}</span><br>
                                <div class="mt-2">
                                    <span style="color: #3b82f6; font-weight: bold;">
                                        🧭 ${venue.latitude.toFixed(4)}°N
                                    </span>
                                    <span class="text-muted small ms-2">
                                        🎾 ${venue.courts} court${venue.courts !== 1 && venue.courts !== 'Unknown' ? 's' : ''}
                                    </span>
                                </div>
                            </div>
                        `)
                )
                .addTo(map);
        });

        // Add markers for southerly venues (red)
        data.southerly.forEach(venue => {
            const el = document.createElement('div');
            el.style.backgroundColor = '#ef4444';
            el.style.width = '12px';
            el.style.height = '12px';
            el.style.borderRadius = '50%';
            el.style.border = '2px solid white';
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            el.style.cursor = 'pointer';

            new maplibregl.Marker({element: el})
                .setLngLat([venue.longitude, venue.latitude])
                .setPopup(
                    new maplibregl.Popup({offset: 15})
                        .setHTML(`
                            <div style="padding: 8px; min-width: 200px;">
                                <strong>${venue.name}</strong><br>
                                <span class="text-muted small">${venue.address || venue.suburb || ''}, ${venue.country}</span><br>
                                <div class="mt-2">
                                    <span style="color: #ef4444; font-weight: bold;">
                                        🧭 ${Math.abs(venue.latitude).toFixed(4)}°S
                                    </span>
                                    <span class="text-muted small ms-2">
                                        🎾 ${venue.courts} court${venue.courts !== 1 && venue.courts !== 'Unknown' ? 's' : ''}
                                    </span>
                                </div>
                            </div>
                        `)
                )
                .addTo(map);
        });
    });

    // Populate northerly list
    const northerlyListContent = document.getElementById('northerly-list-content');
    if (northerlyListContent) {
        let html = '<ol class="list-group list-group-numbered">';
        data.northerly.forEach(venue => {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">${venue.name}</div>
                        <small class="text-muted">${venue.suburb || venue.state || ''}, ${venue.country}</small>
                    </div>
                    <span class="badge bg-primary">
                        ${venue.latitude.toFixed(4)}°N
                    </span>
                </li>
            `;
        });
        html += '</ol>';
        northerlyListContent.innerHTML = html;
    }

    // Populate southerly list
    const southerlyListContent = document.getElementById('southerly-list-content');
    if (southerlyListContent) {
        let html = '<ol class="list-group list-group-numbered">';
        data.southerly.forEach(venue => {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">${venue.name}</div>
                        <small class="text-muted">${venue.suburb || venue.state || ''}, ${venue.country}</small>
                    </div>
                    <span class="badge bg-danger">
                        ${Math.abs(venue.latitude).toFixed(4)}°S
                    </span>
                </li>
            `;
        });
        html += '</ol>';
        southerlyListContent.innerHTML = html;
    }
}

/**
 * Initialize Hotels & Resorts Map (Trivia)
 */
async function initHotelsResortsMap() {
    const mapContainer = document.getElementById('hotels-resorts-map');
    if (!mapContainer) {
        console.warn('Hotels & resorts map container not found');
        return;
    }

    // Fetch hotels and resorts
    const venues = await fetchData('/hotels-and-resorts');
    if (!venues || venues.length === 0) return;

    // Update count
    const countElement = document.getElementById('hotels-count');
    if (countElement) {
        countElement.textContent = `${venues.length} venues`;
    }

    // Initialize map with same style as High Altitude Venues (consistency)
    const map = new maplibregl.Map({
        container: 'hotels-resorts-map',
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'osm': {
                    type: 'raster',
                    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    tileSize: 256,
                    attribution: '© OpenStreetMap contributors'
                }
            },
            layers: [
                {
                    id: 'osm-tiles',
                    type: 'raster',
                    source: 'osm',
                    minzoom: 0,
                    maxzoom: 19
                }
            ]
        },
        center: [0, 20],
        zoom: 1.5
    });

    // Add standard map controls
    addStandardMapControls(map);

    // Store all markers for filtering
    let allMarkers = [];

    map.on('load', () => {
        // Calculate bounds to fit all venues
        const bounds = new maplibregl.LngLatBounds();
        venues.forEach(venue => {
            bounds.extend([venue.longitude, venue.latitude]);
        });
        
        // Fit map to show all venues with padding
        map.fitBounds(bounds, {
            padding: {top: 50, bottom: 50, left: 50, right: 50},
            maxZoom: 15,
            duration: 0
        });
        
        // Add markers for each hotel/resort
        venues.forEach(venue => {
            const el = document.createElement('div');
            el.style.backgroundColor = '#8b5cf6';
            el.style.width = '12px';
            el.style.height = '12px';
            el.style.borderRadius = '50%';
            el.style.border = '2px solid white';
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            el.style.cursor = 'pointer';
            el.dataset.continentId = venue.continent_id;

            const marker = new maplibregl.Marker({element: el})
                .setLngLat([venue.longitude, venue.latitude])
                .setPopup(
                    new maplibregl.Popup({offset: 15})
                        .setHTML(`
                            <div style="padding: 8px; min-width: 200px;">
                                <strong>${venue.name}</strong><br>
                                <span class="text-muted small">${venue.address || venue.suburb || ''}, ${venue.country}</span><br>
                                <div class="mt-2">
                                    <span class="badge bg-secondary">${venue.category}</span>
                                    <span class="text-muted small ms-2">
                                        🎾 ${venue.courts} court${venue.courts !== 1 && venue.courts !== 'Unknown' ? 's' : ''}
                                    </span>
                                </div>
                            </div>
                        `)
                )
                .addTo(map);

            allMarkers.push({marker, venue, element: el});
        });
    });

    // Continent filter functionality
    const continentFilter = document.getElementById('continent-filter');
    if (continentFilter) {
        continentFilter.addEventListener('change', function() {
            const selectedContinent = this.value;
            
            allMarkers.forEach(({marker, venue, element}) => {
                if (!selectedContinent || venue.continent_id == selectedContinent) {
                    element.style.display = 'block';
                } else {
                    element.style.display = 'none';
                }
            });

            // Update list
            updateHotelsList(selectedContinent ? venues.filter(v => v.continent_id == selectedContinent) : venues);
        });
    }

    // Populate initial list
    updateHotelsList(venues);

    function updateHotelsList(venuesToShow) {
        const listContent = document.getElementById('hotels-list-content');
        if (!listContent) return;

        if (venuesToShow.length === 0) {
            listContent.innerHTML = '<div class="text-center text-muted">No hotels or resorts found for this filter.</div>';
            return;
        }

        // Group by continent
        const grouped = {};
        venuesToShow.forEach(venue => {
            if (!grouped[venue.continent_name]) {
                grouped[venue.continent_name] = [];
            }
            grouped[venue.continent_name].push(venue);
        });

        let html = '';
        Object.keys(grouped).sort().forEach(continent => {
            html += `<div class="col-12 mt-3"><h6 class="text-primary">${continent}</h6></div>`;
            grouped[continent].forEach(venue => {
                html += `
                    <div class="col-md-6 mb-2">
                        <div class="card card-body py-2 px-3">
                            <div class="fw-bold small">${venue.name}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">${venue.suburb || venue.state || ''}, ${venue.country}</div>
                            <div class="mt-1">
                                <span class="badge bg-light text-dark" style="font-size: 0.7rem;">${venue.category}</span>
                                <span class="text-muted ms-2" style="font-size: 0.7rem;">🎾 ${venue.courts}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
        });

        listContent.innerHTML = `<div class="row">${html}</div>`;
    }
}

/**
 * Initialize Countries Stats Table (Trivia)
 */
async function initCountriesStatsTable() {
    const tableContainer = document.getElementById('countries-stats-container');
    const loadingIndicator = document.getElementById('countries-stats-loading');
    
    if (!tableContainer || !loadingIndicator) {
        console.warn('Countries stats table container not found');
        return;
    }

    // Fetch country statistics
    let countries = await fetchData('/countries-with-venues-stats');
    if (!countries || countries.length === 0) {
        loadingIndicator.innerHTML = '<div class="alert alert-warning">No data available</div>';
        return;
    }

    // Hide loading, show table
    loadingIndicator.classList.add('d-none');
    tableContainer.classList.remove('d-none');

    const tbody = document.getElementById('countries-stats-tbody');
    if (!tbody) return;

    // Sorting state
    let currentSort = { column: null, direction: 'asc' };

    // Function to render table
    function renderTable(data) {
        let html = '';
        data.forEach((country, index) => {
            html += `
                <tr>
                    <td class="text-center text-muted sticky-col">${index + 1}</td>
                    <td class="sticky-col sticky-col-country"><strong>${country.name}</strong></td>
                    <td class="text-end">${(country.population / 1000000).toFixed(3)}</td>
                    <td class="text-end">${(country.area_sq_km / 1000000).toFixed(5)}</td>
                    <td class="text-end">${country.venues}</td>
                    <td class="text-end">${country.courts}</td>
                    <td class="text-end">${country.venues_per_population.toFixed(2)}</td>
                    <td class="text-end">${country.courts_per_population.toFixed(2)}</td>
                    <td class="text-end">${country.venues_per_area.toFixed(2)}</td>
                    <td class="text-end">${country.courts_per_area.toFixed(2)}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    // Function to sort data
    function sortData(column) {
        // Toggle direction if same column, otherwise default to ascending
        if (currentSort.column === column) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.column = column;
            currentSort.direction = 'asc';
        }

        // Sort the data
        countries.sort((a, b) => {
            let aVal = a[column];
            let bVal = b[column];

            // Handle string comparison for country names
            if (column === 'name') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
                return currentSort.direction === 'asc' 
                    ? aVal.localeCompare(bVal)
                    : bVal.localeCompare(aVal);
            }

            // Numeric comparison
            return currentSort.direction === 'asc' 
                ? aVal - bVal
                : bVal - aVal;
        });

        // Update sort indicators
        document.querySelectorAll('.sortable').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        const activeHeader = document.querySelector(`.sortable[data-sort="${column}"]`);
        if (activeHeader) {
            activeHeader.classList.add(`sort-${currentSort.direction}`);
        }

        // Re-render table
        renderTable(countries);
    }

    // Initial render
    renderTable(countries);

    // Add click handlers to sortable headers
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.getAttribute('data-sort');
            sortData(column);
        });
    });
}

/**
 * Initialize Unknown Courts Map (Trivia)
 */
async function initUnknownCourtsMap() {
    const mapContainer = document.getElementById('unknown-courts-map');
    if (!mapContainer) {
        console.warn('Unknown courts map container not found');
        return;
    }

    // Fetch venues with unknown courts
    const venues = await fetchData('/venues-with-unknown-courts');
    if (!venues || venues.length === 0) return;

    // Update count
    const countElement = document.getElementById('unknown-courts-count');
    if (countElement) {
        countElement.textContent = `${venues.length} venues`;
    }

    // Initialize map
    const map = new maplibregl.Map({
        container: 'unknown-courts-map',
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'osm': {
                    type: 'raster',
                    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    tileSize: 256,
                    attribution: '© OpenStreetMap contributors'
                }
            },
            layers: [
                {
                    id: 'osm-tiles',
                    type: 'raster',
                    source: 'osm',
                    minzoom: 0,
                    maxzoom: 19
                }
            ]
        },
        center: [0, 20],
        zoom: 1.5
    });

    // Add standard map controls
    addStandardMapControls(map);

    // Store all markers for filtering
    let allMarkers = [];

    map.on('load', () => {
        // Calculate bounds to fit all venues
        const bounds = new maplibregl.LngLatBounds();
        venues.forEach(venue => {
            bounds.extend([venue.longitude, venue.latitude]);
        });
        
        // Fit map to show all venues with padding
        map.fitBounds(bounds, {
            padding: {top: 50, bottom: 50, left: 50, right: 50},
            maxZoom: 15,
            duration: 0
        });
        
        // Add markers for each venue
        venues.forEach(venue => {
            const el = document.createElement('div');
            el.style.backgroundColor = '#6c757d';
            el.style.width = '10px';
            el.style.height = '10px';
            el.style.borderRadius = '50%';
            el.style.border = '2px solid white';
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            el.style.cursor = 'pointer';
            el.dataset.continentId = venue.continent_id;

            const marker = new maplibregl.Marker({element: el})
                .setLngLat([venue.longitude, venue.latitude])
                .setPopup(
                    new maplibregl.Popup({offset: 15})
                        .setHTML(`
                            <div style="padding: 8px; min-width: 200px;">
                                <strong>${venue.name}</strong><br>
                                <span class="text-muted small">${venue.address || venue.suburb || ''}, ${venue.country}</span><br>
                                <div class="mt-2">
                                    <span class="badge bg-secondary">${venue.category}</span>
                                    <span class="text-muted small ms-2">
                                        ❓ Courts: Unknown
                                    </span>
                                </div>
                            </div>
                        `)
                )
                .addTo(map);

            allMarkers.push({marker, venue, element: el});
        });
    });

    // Continent filter functionality
    const continentFilter = document.getElementById('unknown-courts-continent-filter');
    if (continentFilter) {
        continentFilter.addEventListener('change', function() {
            const selectedContinent = this.value;
            
            allMarkers.forEach(({marker, venue, element}) => {
                if (!selectedContinent || venue.continent_id == selectedContinent) {
                    element.style.display = 'block';
                } else {
                    element.style.display = 'none';
                }
            });

            // Update list
            updateUnknownCourtsList(selectedContinent ? venues.filter(v => v.continent_id == selectedContinent) : venues);
        });
    }

    // Populate initial list
    updateUnknownCourtsList(venues);

    function updateUnknownCourtsList(venuesToShow) {
        const listContent = document.getElementById('unknown-courts-list-content');
        if (!listContent) return;

        if (venuesToShow.length === 0) {
            listContent.innerHTML = '<div class="text-center text-muted">No venues found for this filter.</div>';
            return;
        }

        // Group by continent
        const grouped = {};
        venuesToShow.forEach(venue => {
            if (!grouped[venue.continent_name]) {
                grouped[venue.continent_name] = [];
            }
            grouped[venue.continent_name].push(venue);
        });

        let html = '';
        Object.keys(grouped).sort().forEach(continent => {
            html += `<div class="col-12 mt-3"><h6 class="text-secondary">${continent}</h6></div>`;
            grouped[continent].forEach(venue => {
                html += `
                    <div class="col-md-6 mb-2">
                        <div class="card card-body py-2 px-3">
                            <div class="fw-bold small">${venue.name}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">${venue.suburb || venue.state || ''}, ${venue.country}</div>
                            <div class="mt-1">
                                <span class="badge bg-light text-dark" style="font-size: 0.7rem;">${venue.category}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
        });

        listContent.innerHTML = `<div class="row">${html}</div>`;
    }
}

/**
 * Initialize 100% Country Club Table (Trivia)
 */
async function initCountryClub100Table() {
    const tableContainer = document.getElementById('country-club-container');
    const loadingIndicator = document.getElementById('country-club-loading');
    
    if (!tableContainer || !loadingIndicator) {
        console.warn('Country club table container not found');
        return;
    }

    // Fetch country club data
    let countries = await fetchData('/country-club-100-percent');
    if (!countries || countries.length === 0) {
        loadingIndicator.innerHTML = '<div class="alert alert-warning">No data available</div>';
        return;
    }

    // Hide loading, show table
    loadingIndicator.classList.add('d-none');
    tableContainer.classList.remove('d-none');

    const tbody = document.getElementById('country-club-tbody');
    if (!tbody) return;

    // Store original data for filtering
    let allCountries = [...countries];
    let filteredCountries = [...countries];

    // Sorting state
    let currentSort = { column: 'percentage', direction: 'desc' };

    // Initial sort by percentage descending
    sortData('percentage', true);

    // Function to render table
    function renderTable(data) {
        let html = '';
        data.forEach((country, index) => {
            // Add a data attribute for 100% countries instead of a class
            const dataAttr = country.percentage === 100 ? 'data-hundred-percent="true"' : '';
            html += `
                <tr ${dataAttr}>
                    <td class="text-center text-muted sticky-col">${index + 1}</td>
                    <td class="sticky-col sticky-col-country"><strong>${country.name}</strong></td>
                    <td class="text-end">${country.total_venues}</td>
                    <td class="text-end">${country.venues_with_courts}</td>
                    <td class="text-end">${country.total_courts}</td>
                    <td class="text-end">${country.percentage}%</td>
                    <td class="text-end">${country.courts_per_venue}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        
        // Update totals
        updateTotals(data);
    }

    // Function to update grand summary totals
    function updateTotals(data) {
        const totalVenues = data.reduce((sum, c) => sum + c.total_venues, 0);
        const totalVenuesWithCourts = data.reduce((sum, c) => sum + c.venues_with_courts, 0);
        const totalCourts = data.reduce((sum, c) => sum + c.total_courts, 0);
        const overallPercentage = totalVenues > 0 ? ((totalVenuesWithCourts / totalVenues) * 100).toFixed(1) : 0;
        const avgCourtsPerVenue = totalVenuesWithCourts > 0 ? (totalCourts / totalVenuesWithCourts).toFixed(2) : 0;

        document.getElementById('total-venues').textContent = totalVenues;
        document.getElementById('total-venues-with-courts').textContent = totalVenuesWithCourts;
        document.getElementById('total-courts').textContent = totalCourts;
        document.getElementById('total-percentage').textContent = overallPercentage + '%';
        document.getElementById('avg-courts-per-venue').textContent = avgCourtsPerVenue;
    }

    // Function to sort data
    function sortData(column, initialSort = false) {
        // Toggle direction if same column, otherwise default to descending for percentage, ascending for others
        if (!initialSort) {
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = column === 'percentage' ? 'desc' : 'asc';
            }
        } else {
            currentSort.column = column;
            currentSort.direction = 'desc';
        }

        // Sort the data
        filteredCountries.sort((a, b) => {
            let aVal = a[column];
            let bVal = b[column];

            // Handle string comparison for country names
            if (column === 'name') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
                return currentSort.direction === 'asc' 
                    ? aVal.localeCompare(bVal)
                    : bVal.localeCompare(aVal);
            }

            // Numeric comparison
            return currentSort.direction === 'asc' 
                ? aVal - bVal
                : bVal - aVal;
        });

        // Update sort indicators
        document.querySelectorAll('.sortable').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        const activeHeader = document.querySelector(`.sortable[data-sort="${column}"]`);
        if (activeHeader) {
            activeHeader.classList.add(`sort-${currentSort.direction}`);
        }

        // Re-render table
        renderTable(filteredCountries);
    }

    // Initial render
    renderTable(filteredCountries);

    // Add click handlers to sortable headers
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.getAttribute('data-sort');
            sortData(column);
        });
    });

    // Continent filter (note: we don't have continent data in the API response yet)
    // This is a placeholder for future enhancement
    const continentFilter = document.getElementById('country-club-continent-filter');
    if (continentFilter) {
        continentFilter.addEventListener('change', function() {
            // For now, this filter won't work as we need to add continent data to the API
            // Placeholder for future implementation
            console.log('Continent filter selected:', this.value);
        });
    }
}

/**
 * Initialize Countries Word Cloud (Trivia)
 */
    async function initCountriesWordCloud() {
        const canvas = document.getElementById('countries-wordcloud-canvas');
        const loadingIndicator = document.getElementById('wordcloud-loading');
        const container = document.getElementById('wordcloud-container');
        const tooltip = document.getElementById('wordcloud-tooltip');

        if (!canvas || !loadingIndicator || !container || !tooltip) {
            console.warn('Word cloud elements not found');
            return;
        }

        // Fetch word cloud data
        let data = await fetchData('/countries-wordcloud');
        if (!data || data.length === 0) {
            loadingIndicator.innerHTML = '<div class="alert alert-warning">No data available</div>';
            return;
        }

        // Sort by value descending to ensure top countries are prioritized
        data = data.sort((a, b) => b.value - a.value);
        
        // Take as many countries as possible - let's try 100
        data = data.slice(0, 100);
        
        console.log('Word cloud data (top 10):', data.slice(0, 10));
        console.log('Total countries in word cloud:', data.length);

        // Hide loading, show chart
        loadingIndicator.classList.add('d-none');
        container.classList.remove('d-none');

        // Convert data to WordCloud2.js format: [[word, size], ...]
        // Ensure venue counts are integers - use Math.floor to guarantee whole numbers
        data = data.map(d => ({ key: d.key, value: Math.floor(Number(d.value)) }));
        
        // Create a lookup map for original venue counts BEFORE creating wordCloudData
        // This ensures we store the original integer values before any potential modifications
        const venueCountMap = {};
        data.forEach(d => {
            venueCountMap[d.key] = d.value;
        });
        
        // Now create the word cloud data array - WordCloud2.js may modify this array
        const wordCloudData = data.map(d => [d.key, d.value]);
        
        
        // Calculate max value for color scaling (after rounding)
        const maxValue = Math.max(...data.map(d => d.value));
        
        // Define color bands based on actual venue counts
        // These are meaningful thresholds that make sense for venue counts
        const colorBands = [
            { threshold: 1000, color: '#dc3545', label: '1,000+' },
            { threshold: 500, color: '#fd7e14', label: '500-999' },
            { threshold: 100, color: '#ffc107', label: '100-499' },
            { threshold: 50, color: '#28a745', label: '50-99' },
            { threshold: 10, color: '#20c997', label: '10-49' },
            { threshold: 0, color: '#0dcaf0', label: '1-9' }
        ];
        
        // Update the legend dynamically
        const legendContainer = document.querySelector('#countries-wordcloud-legend');
        if (legendContainer) {
            legendContainer.innerHTML = colorBands.map(band => 
                `<span class="badge" style="background-color: ${band.color}; color: ${band.color === '#ffc107' ? '#000' : '#fff'};">${band.label} venues</span>`
            ).join('');
        }
        
        // Create word cloud using WordCloud2.js with high-resolution canvas
        WordCloud(canvas, {
            list: wordCloudData,
            gridSize: 4, // Smaller grid for better precision
            weightFactor: function(size) {
                // Scale sizes appropriately - log scaling for better distribution with many countries
                return (Math.log(size + 1) / Math.log(maxValue + 1)) * 120;
            },
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            color: function(word, weight, fontSize, distance, theta) {
                // Color based on actual venue count - use meaningful thresholds
                const venueCount = venueCountMap[word];
                
                // Find the appropriate color band
                for (let band of colorBands) {
                    if (venueCount >= band.threshold) {
                        return band.color;
                    }
                }
                return '#0dcaf0'; // Default to lowest band
            },
            rotateRatio: 0, // No rotation for better readability
            backgroundColor: '#ffffff',
            minSize: 8, // Smaller minimum to fit more countries
            drawOutOfBound: false,
            shrinkToFit: true,
            hover: function(item, dimension, event) {
                if (item) {
                    // Show custom tooltip on hover
                    canvas.style.cursor = 'pointer';
                    const country = item[0];
                    // Use the original venue count from our lookup map instead of item[1]
                    const venues = venueCountMap[country];
                    
                    tooltip.textContent = `${country}: ${venues} venue${venues !== 1 ? 's' : ''}`;
                    tooltip.style.display = 'block';
                    tooltip.style.left = (event.offsetX + 10) + 'px';
                    tooltip.style.top = (event.offsetY + 10) + 'px';
                } else {
                    canvas.style.cursor = 'default';
                    tooltip.style.display = 'none';
                }
            },
            click: function(item, dimension, event) {
                if (item) {
                    const country = item[0];
                    // Use the original venue count from our lookup map
                    const venues = venueCountMap[country];
                    console.log(`Clicked: ${country} (${venues} venues)`);
                }
            }
        });
    }

/**
 * Initialize Loneliest Squash Courts Map (Trivia)
 */
async function initLoneliestCourtsMap() {
    const mapContainer = document.getElementById('loneliest-courts-map');
    if (!mapContainer) {
        console.warn('Loneliest courts map container not found');
        return;
    }

    // Get current filter context
    const { filter } = getFilterParams();
    
    // Fetch loneliest courts data (context-aware)
    const data = await fetchData('/loneliest-courts', filter);
    if (!data || data.length === 0) return;

    // Update title and description based on filter context
    const titleElement = document.getElementById('loneliest-courts-title');
    const descElement = document.getElementById('loneliest-courts-description');
    
    if (filter) {
        const parts = filter.split(':');
        const filterType = parts[0];
        
        if (filterType === 'country') {
            if (titleElement) titleElement.textContent = `Top ${data.length} Loneliest Squash Courts`;
            if (descElement) descElement.textContent = 'These are the squash venues in this country that are furthest from their nearest neighbor.';
        } else if (filterType === 'state') {
            if (titleElement) titleElement.textContent = `Top ${data.length} Loneliest Squash Courts`;
            if (descElement) descElement.textContent = 'These are the squash venues in this state/county that are furthest from their nearest neighbor.';
        } else if (filterType === 'continent') {
            if (titleElement) titleElement.textContent = 'Loneliest Squash Courts per Country';
            if (descElement) descElement.textContent = 'The loneliest squash venue in each country within this continent.';
        } else if (filterType === 'region') {
            if (titleElement) titleElement.textContent = 'Loneliest Squash Courts per Country';
            if (descElement) descElement.textContent = 'The loneliest squash venue in each country within this region.';
        }
    }

    // Update count
    const countElement = document.getElementById('loneliest-venues-count');
    if (countElement) {
        countElement.textContent = `${data.length} venues`;
    }

    // Initialize map
    const map = new maplibregl.Map({
        container: 'loneliest-courts-map',
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'osm': {
                    type: 'raster',
                    tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    tileSize: 256,
                    attribution: '© OpenStreetMap contributors'
                }
            },
            layers: [
                {
                    id: 'osm-tiles',
                    type: 'raster',
                    source: 'osm',
                    minzoom: 0,
                    maxzoom: 19
                }
            ]
        },
        center: [0, 20],
        zoom: 1.5
    });

    // Add standard map controls
    addStandardMapControls(map);

    map.on('load', () => {
        // Calculate bounds to fit all venues
        const bounds = new maplibregl.LngLatBounds();
        data.forEach(item => {
            bounds.extend([item.venue.longitude, item.venue.latitude]);
            bounds.extend([item.nearest.longitude, item.nearest.latitude]);
        });
        
        // Fit map to show all venues with padding
        map.fitBounds(bounds, {
            padding: {top: 50, bottom: 50, left: 50, right: 50},
            maxZoom: 10,
            duration: 0
        });
        
        // Add lines connecting venues to their nearest neighbor
        // Filter out lines that cross the international dateline
        const lineFeatures = data
            .filter(item => {
                // Calculate longitude difference
                const lngDiff = Math.abs(item.venue.longitude - item.nearest.longitude);
                // Don't draw lines that cross the dateline (difference > 180°)
                return lngDiff <= 180;
            })
            .map(item => ({
                type: 'Feature',
                geometry: {
                    type: 'LineString',
                    coordinates: [
                        [item.venue.longitude, item.venue.latitude],
                        [item.nearest.longitude, item.nearest.latitude]
                    ]
                },
                properties: {
                    distance: item.distance_km
                }
            }));

        map.addSource('connection-lines', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: lineFeatures
            }
        });

        map.addLayer({
            id: 'connection-lines',
            type: 'line',
            source: 'connection-lines',
            paint: {
                'line-color': '#9ca3af',
                'line-width': 2,
                'line-opacity': 0.6
            }
        });
        
        // Add markers for each venue (loneliest)
        data.forEach(item => {
            // Loneliest venue marker (red)
            const venueEl = document.createElement('div');
            venueEl.className = 'loneliest-venue-marker';
            venueEl.style.backgroundColor = '#dc2626';
            venueEl.style.width = '14px';
            venueEl.style.height = '14px';
            venueEl.style.borderRadius = '50%';
            venueEl.style.border = '2px solid white';
            venueEl.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            venueEl.style.cursor = 'pointer';

            new maplibregl.Marker({element: venueEl})
                .setLngLat([item.venue.longitude, item.venue.latitude])
                .setPopup(
                    new maplibregl.Popup({offset: 15})
                        .setHTML(`
                            <div style="padding: 8px; min-width: 250px;">
                                <strong style="color: #dc2626;">🏆 ${item.venue.name}</strong><br>
                                <span class="text-muted small">${item.venue.address || item.venue.suburb || ''}, ${item.venue.country}</span><br>
                                <div class="mt-2">
                                    <span class="text-muted small">
                                        🎾 ${item.venue.courts} court${item.venue.courts !== 1 && item.venue.courts !== 'Unknown' ? 's' : ''}
                                    </span>
                                </div>
                                <hr style="margin: 8px 0;">
                                <div class="small">
                                    <strong>Nearest venue:</strong><br>
                                    ${item.nearest.name}<br>
                                    <span class="text-muted">${item.nearest.country}</span><br>
                                    <strong style="color: #3b82f6;">📏 ${item.distance_km.toFixed(1)} km away</strong>
                                </div>
                            </div>
                        `)
                )
                .addTo(map);

            // Nearest venue marker (blue)
            const nearestEl = document.createElement('div');
            nearestEl.className = 'nearest-venue-marker';
            nearestEl.style.backgroundColor = '#3b82f6';
            nearestEl.style.width = '10px';
            nearestEl.style.height = '10px';
            nearestEl.style.borderRadius = '50%';
            nearestEl.style.border = '2px solid white';
            nearestEl.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            nearestEl.style.cursor = 'pointer';

            new maplibregl.Marker({element: nearestEl})
                .setLngLat([item.nearest.longitude, item.nearest.latitude])
                .setPopup(
                    new maplibregl.Popup({offset: 15})
                        .setHTML(`
                            <div style="padding: 8px; min-width: 200px;">
                                <strong style="color: #3b82f6;">📍 ${item.nearest.name}</strong><br>
                                <span class="text-muted small">${item.nearest.address || item.nearest.suburb || ''}, ${item.nearest.country}</span><br>
                                <div class="mt-2">
                                    <span class="text-muted small">
                                        🎾 ${item.nearest.courts} court${item.nearest.courts !== 1 && item.nearest.courts !== 'Unknown' ? 's' : ''}
                                    </span>
                                </div>
                            </div>
                        `)
                )
                .addTo(map);
        });
    });

    // Populate top 10 list
    const listContent = document.getElementById('loneliest-venues-list-content');
    if (listContent) {
        const top10 = data.slice(0, 10);
        let html = '<ol class="list-group list-group-numbered">';
        top10.forEach(item => {
            html += `
                <li class="list-group-item">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="me-auto">
                            <div class="fw-bold">${item.venue.name}</div>
                            <small class="text-muted">${item.venue.suburb || item.venue.state || ''}, ${item.venue.country}</small>
                            <div class="small mt-1">
                                <span class="text-muted">Nearest: ${item.nearest.name}, ${item.nearest.country}</span>
                            </div>
                        </div>
                        <span class="badge bg-danger ms-2">
                            ${item.distance_km.toFixed(1)} km
                        </span>
                    </div>
                </li>
            `;
        });
        html += '</ol>';
        listContent.innerHTML = html;
    }
}

/**
 * Initialize Court Graveyard Table (Trivia)
 */
async function initCourtGraveyard() {
    const tableBody = document.getElementById('graveyard-table-body');
    const countryFilter = document.getElementById('graveyard-country-filter');
    const reasonFilter = document.getElementById('graveyard-reason-filter');
    
    if (!tableBody || !countryFilter || !reasonFilter) {
        return;
    }
    
    let allData = [];
    let deletionReasons = [];
    let filteredData = [];
    
    // Fetch deletion reasons
    try {
        deletionReasons = await fetchData('/deletion-reasons');
        
        // Populate reason filter
        reasonFilter.innerHTML = '<option value="">All Reasons</option>';
        deletionReasons.forEach(reason => {
            const option = document.createElement('option');
            option.value = reason.id;
            option.textContent = reason.name;
            reasonFilter.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading deletion reasons:', error);
    }
    
    // Fetch graveyard data
    try {
        allData = await fetchData('/court-graveyard');
        filteredData = allData;
        
        // Extract unique countries and populate country filter (sorted A-Z by country name)
        const countryMap = new Map();
        allData.forEach(venue => {
            if (!countryMap.has(venue.country_code)) {
                countryMap.set(venue.country_code, venue.country);
            }
        });
        
        // Convert to array and sort by country name
        const sortedCountries = Array.from(countryMap.entries())
            .sort((a, b) => a[1].localeCompare(b[1])); // Sort by country name
        
        countryFilter.innerHTML = '<option value="">All Countries</option>';
        sortedCountries.forEach(([code, countryName]) => {
            const option = document.createElement('option');
            option.value = code;
            option.textContent = countryName;
            countryFilter.appendChild(option);
        });
        
        // Render initial table
        renderGraveyardTable(filteredData);
        updateGraveyardStats(allData, filteredData);
        
    } catch (error) {
        console.error('Error loading graveyard data:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading data</td></tr>';
    }
    
    // Filter handlers
    const applyFilters = () => {
        const selectedCountry = countryFilter.value;
        const selectedReason = reasonFilter.value;
        
        filteredData = allData.filter(venue => {
            const matchesCountry = !selectedCountry || venue.country_code === selectedCountry;
            const matchesReason = !selectedReason || venue.delete_reason_id == selectedReason;
            return matchesCountry && matchesReason;
        });
        
        renderGraveyardTable(filteredData);
        updateGraveyardStats(allData, filteredData);
    };
    
    countryFilter.addEventListener('change', applyFilters);
    reasonFilter.addEventListener('change', applyFilters);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Render graveyard table
 */
function renderGraveyardTable(data) {
    const tableBody = document.getElementById('graveyard-table-body');
    
    if (data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No venues found</td></tr>';
        return;
    }
    
    let html = '';
    data.forEach(venue => {
        const address = [venue.address, venue.suburb, venue.state].filter(Boolean).join(', ');
        const courts = venue.courts ? venue.courts : '?';
        const date = new Date(venue.date_deleted).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
        
        // Truncate reason details to 180 characters
        let reasonDisplay = '';
        let reasonFull = '';
        let isTruncated = false;
        
        // Get reason text - check both reason_details and more_details for compatibility
        const reasonText = venue.reason_details || venue.more_details || '';
        
        if (reasonText) {
            reasonFull = String(reasonText); // Ensure it's a string
            if (reasonFull.length > 180) {
                reasonDisplay = reasonFull.substring(0, 180) + '...';
                isTruncated = true;
            } else {
                reasonDisplay = reasonFull;
            }
        }
        
        // Generate unique ID for this row's reason
        const reasonId = `reason-${venue.id || Math.random().toString(36).substr(2, 9)}`;
        
        html += `
            <tr>
                <td>
                    <strong>${venue.name}</strong>
                    ${reasonDisplay ? `
                        <br><small class="text-muted graveyard-reason-text" 
                                   data-reason-id="${reasonId}"
                                   data-full-reason="${escapeHtml(reasonFull)}"
                                   data-is-truncated="${isTruncated}"
                                   title="${isTruncated ? 'Click to view full reason' : ''}"
                                   style="cursor: ${isTruncated ? 'pointer' : 'default'};">
                            <span class="reason-display">${escapeHtml(reasonDisplay)}</span>
                            ${isTruncated ? '<span class="reason-full d-none">' + escapeHtml(reasonFull) + '</span>' : ''}
                        </small>
                    ` : ''}
                </td>
                <td><small>${address || '-'}</small></td>
                <td>
                    <span class="badge bg-secondary">${venue.country}</span>
                </td>
                <td class="text-center">${courts}</td>
                <td><small>${venue.delete_reason}</small></td>
                <td style="white-space: nowrap;"><small>${date}</small></td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    
    // Add click handlers for truncated reasons
    tableBody.querySelectorAll('.graveyard-reason-text[data-is-truncated="true"]').forEach(element => {
        element.addEventListener('click', function() {
            const displaySpan = this.querySelector('.reason-display');
            const fullSpan = this.querySelector('.reason-full');
            
            if (displaySpan && fullSpan) {
                if (displaySpan.classList.contains('d-none')) {
                    // Collapse: show truncated
                    displaySpan.classList.remove('d-none');
                    fullSpan.classList.add('d-none');
                    this.title = 'Click to expand full reason';
                } else {
                    // Expand: show full
                    displaySpan.classList.add('d-none');
                    fullSpan.classList.remove('d-none');
                    this.title = 'Click to collapse';
                }
            }
        });
    });
}

/**
 * Update graveyard statistics
 */
function updateGraveyardStats(allData, filteredData) {
    // Total venues
    document.getElementById('graveyard-total-venues').textContent = allData.length;
    
    // Total countries
    const uniqueCountries = new Set(allData.map(v => v.country_code));
    document.getElementById('graveyard-total-countries').textContent = uniqueCountries.size;
    
    // Total courts lost (sum of all courts, treating null as 0)
    const totalCourts = allData.reduce((sum, v) => sum + (v.courts || 0), 0);
    document.getElementById('graveyard-total-courts').textContent = totalCourts;
    
    // Filtered count
    document.getElementById('graveyard-filtered-count').textContent = filteredData.length;
    document.getElementById('graveyard-showing-count').textContent = filteredData.length;
}

/**
 * Initialize dashboard - only load components that exist on the page
 */
document.addEventListener('DOMContentLoaded', async () => {
    console.log('Initializing Squash Dashboard...');
    
    // Get filter parameters
    const { filter, customTitle } = getFilterParams();
    
    // Adjust titles based on filter/custom title
    if (filter || customTitle) {
        await adjustTitles(filter, customTitle);
    }
    
    // Build array of initialization functions for components that exist on the page
    const initFunctions = [];
    
    // Check which components exist and add their init functions (passing filter)
    if (document.getElementById('summary-stats')) {
        initFunctions.push(updateSummaryStats(filter));
    }
    
    if (document.getElementById('map')) {
        initFunctions.push(initMap(filter));
    }
    
    if (document.getElementById('top-venues-chart')) {
        initFunctions.push(createTopVenuesChart(filter));
    }
    
    if (document.getElementById('court-dist-chart')) {
        initFunctions.push(createCourtDistributionChart(filter));
    }
    
    if (document.getElementById('website-stats-chart')) {
        initFunctions.push(createWebsiteStatsChart(filter));
    }
    
    if (document.getElementById('top-courts-chart')) {
        initFunctions.push(createTopCourtsChart(filter));
    }
    
    if (document.getElementById('categories-chart')) {
        initFunctions.push(createCategoriesChart(filter));
    }
    
    if (document.getElementById('regional-chart')) {
        initFunctions.push(createRegionalChart(filter));
    }
    
    if (document.getElementById('subcontinental-chart')) {
        initFunctions.push(createSubContinentalChart(filter));
    }
    
    if (document.getElementById('top-outdoor-courts-chart')) {
        initFunctions.push(createTopOutdoorCourtsChart(filter));
    }
    
    if (document.getElementById('timeline-chart')) {
        initFunctions.push(createTimelineChart(filter));
    }
    
    if (document.getElementById('state-chart')) {
        initFunctions.push(createStateBreakdownChart(filter));
    }
    
    if (document.getElementById('venues-by-state-pie-chart')) {
        initFunctions.push(createVenuesByStatePieChart(filter));
    }
    
    if (document.getElementById('top-venues-by-courts-chart')) {
        initFunctions.push(createTopVenuesByCountsChart(filter));
    }
    
    if (document.getElementById('countries-without-map')) {
        initFunctions.push(initCountriesWithoutVenuesMap());
    }
    
    if (document.getElementById('highest-venues-map')) {
        initFunctions.push(initHighestVenuesMap());
    }
    
    if (document.getElementById('extreme-latitude-map')) {
        initFunctions.push(initExtremeLatitudeMap());
    }
    
    if (document.getElementById('hotels-resorts-map')) {
        initFunctions.push(initHotelsResortsMap());
    }
    
    if (document.getElementById('countries-stats-table')) {
        initFunctions.push(initCountriesStatsTable());
    }
    
    if (document.getElementById('unknown-courts-map')) {
        initFunctions.push(initUnknownCourtsMap());
    }
    
    if (document.getElementById('country-club-table')) {
        initFunctions.push(initCountryClub100Table());
    }
    
    if (document.getElementById('countries-wordcloud-canvas')) {
        initFunctions.push(initCountriesWordCloud());
    }
    
    if (document.getElementById('loneliest-courts-map')) {
        initFunctions.push(initLoneliestCourtsMap());
    }
    
    if (document.getElementById('graveyard-table-body')) {
        initFunctions.push(initCourtGraveyard());
    }
    
    // Load only the components that exist on the page
    if (initFunctions.length > 0) {
        await Promise.all(initFunctions);
    }
    
    console.log('Dashboard initialized successfully!');
});

/**
 * Geographic Search Component
 * Handles predictive search for geographic areas
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('geographic-search-input');
    const searchResults = document.getElementById('geographic-search-results');
    const clearButton = document.getElementById('clear-filter-button');
    
    if (!searchInput || !searchResults || !clearButton) {
        return; // Component not on this page
    }
    
    let searchTimeout = null;
    let currentFilter = null;
    
    // Get current filter from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentFilter = urlParams.get('filter');
    
    // Search input handler
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Hide results if query is empty
        if (query.length === 0) {
            searchResults.style.display = 'none';
            searchResults.innerHTML = '';
            return;
        }
        
        // Debounce search - wait 300ms after user stops typing
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Clear filter button handler
    clearButton.addEventListener('click', function() {
        // Remove filter from URL and reload
        const url = new URL(window.location.href);
        url.searchParams.delete('filter');
        url.searchParams.delete('title');
        window.location.href = url.toString();
    });
    
    // Click outside to close results
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    /**
     * Perform geographic area search
     */
    async function performSearch(query) {
        try {
            const response = await fetch(`${API_BASE}/search-areas?query=${encodeURIComponent(query)}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const results = await response.json();
            displaySearchResults(results);
        } catch (error) {
            console.debug('Search error:', error);
            searchResults.innerHTML = '<div class="list-group-item text-danger">Search failed. Please try again.</div>';
            searchResults.style.display = 'block';
        }
    }
    
    /**
     * Display search results in dropdown
     */
    function displaySearchResults(results) {
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
            searchResults.style.display = 'block';
            return;
        }
        
        searchResults.innerHTML = results.map(result => `
            <a href="#" class="list-group-item list-group-item-action" data-filter="${result.filter}">
                <strong>${result.name}</strong> <span class="text-muted">(${result.type})</span>
            </a>
        `).join('');
        
        searchResults.style.display = 'block';
        
        // Add click handlers to results
        searchResults.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter');
                applyFilter(filter);
            });
        });
    }
    
    /**
     * Apply selected filter
     */
    function applyFilter(filter) {
        const url = new URL(window.location.href);
        url.searchParams.set('filter', filter);
        window.location.href = url.toString();
    }
});

