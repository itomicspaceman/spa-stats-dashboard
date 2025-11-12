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
                    center: [0, 20],
                    zoom: 1.5,
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
                    '#51bbd6',
                    10,
                    '#f1f075',
                    50,
                    '#f28cb1'
                ],
                'circle-radius': [
                    'step',
                    ['get', 'point_count'],
                    20,
                    10,
                    30,
                    50,
                    40
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
    
    // Build venue labels with name and physical address, add link icon
    const labels = data.map(venue => {
        // Use the actual physical address
        const address = venue.physical_address || '';
        const label = address ? `${venue.name} (${address})` : venue.name;
        return label + ' 🔗'; // Add link emoji to indicate clickability
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
                        color: '#0066cc',
                        cursor: 'pointer'
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

