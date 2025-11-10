/**
 * Squash Dashboard - Main JavaScript Module
 * Handles data fetching, chart rendering, and map initialization
 * Updated to fix API data format issues
 */

// API Base URL
const API_BASE = '/api/squash';

// Chart.js default configuration
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
Chart.defaults.color = '#495057';

// Register datalabels plugin but disable by default
Chart.register(ChartDataLabels);
Chart.defaults.set('plugins.datalabels', {
    display: false
});

/**
 * Fetch data from API endpoint
 */
async function fetchData(endpoint) {
    try {
        const response = await fetch(`${API_BASE}${endpoint}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error(`Error fetching ${endpoint}:`, error);
        return null;
    }
}

/**
 * Update summary statistics cards
 */
async function updateSummaryStats() {
    const data = await fetchData('/country-stats');
    if (!data) return;

    document.getElementById('total-countries').textContent = data.total_countries?.toLocaleString() || '-';
    document.getElementById('countries-with-venues').textContent = data.countries_with_venues?.toLocaleString() || '-';
    document.getElementById('total-venues').textContent = data.total_venues?.toLocaleString() || '-';
    document.getElementById('total-courts').textContent = data.total_courts?.toLocaleString() || '-';
}

/**
 * Initialize MapLibre GL map with venue markers
 */
async function initMap() {
    const mapData = await fetchData('/map');
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
    
    // Add geolocate control (find user's location)
    map.addControl(
        new maplibregl.GeolocateControl({
            positionOptions: {
                enableHighAccuracy: true
            },
            trackUserLocation: true,
            showUserHeading: true
        }),
        'top-right'
    );
    
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
 */
async function createTopVenuesChart() {
    const data = await fetchData('/top-countries?metric=venues&limit=20');
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
 */
async function createCourtDistributionChart() {
    const data = await fetchData('/court-distribution');
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
 */
async function createWebsiteStatsChart() {
    const data = await fetchData('/website-stats');
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
 * Create Top Countries by Courts chart
 */
async function createTopCourtsChart() {
    const data = await fetchData('/top-countries?metric=courts&limit=20');
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
 */
async function createCategoriesChart() {
    const data = await fetchData('/venue-types');
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
 */
async function createRegionalChart() {
    const data = await fetchData('/regional-breakdown');
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
 */
async function createSubContinentalChart() {
    const data = await fetchData('/subcontinental-breakdown');
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
 * Create Court Types chart
 * REMOVED - Chart no longer displayed on dashboard
 */

/**
 * Create Top Glass Courts chart
 * REMOVED - Chart no longer displayed on dashboard
 */

/**
 * Create Top Outdoor Courts chart
 */
async function createTopOutdoorCourtsChart() {
    const data = await fetchData('/top-countries?metric=outdoor_courts&limit=20');
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
 */
async function createTimelineChart() {
    const data = await fetchData('/timeline');
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
 * Initialize dashboard
 */
document.addEventListener('DOMContentLoaded', async () => {
    console.log('Initializing Squash Dashboard...');
    
    // Load all components in parallel
    await Promise.all([
        updateSummaryStats(),
        initMap(),
        createTopVenuesChart(),
        createCourtDistributionChart(),
        createWebsiteStatsChart(),
        createTopCourtsChart(),
        createCategoriesChart(),
        createRegionalChart(),
        createSubContinentalChart(),
        createTopOutdoorCourtsChart(),
        createTimelineChart()
    ]);
    
    console.log('Dashboard initialized successfully!');
});

