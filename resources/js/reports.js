/**
 * Squash Dashboard - Modular Report Components
 * Each report is a self-contained, reusable component that fetches data and renders itself
 */

// API Base URL
const API_BASE = '/api/squash';

// Chart.js default configuration
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
Chart.defaults.color = '#495057';

/**
 * Base Report Class
 */
class Report {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.options = options;
        this.data = null;
        this.chart = null;
    }

    async fetchData(endpoint) {
        try {
            const response = await fetch(`${API_BASE}${endpoint}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error(`Error fetching ${endpoint}:`, error);
            this.showError(error.message);
            return null;
        }
    }

    showError(message) {
        if (this.container) {
            this.container.innerHTML = `<div class="alert alert-danger">Error loading data: ${message}</div>`;
        }
    }

    showLoading() {
        if (this.container) {
            this.container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        }
    }

    async render() {
        throw new Error('render() must be implemented by subclass');
    }
}

/**
 * Summary Stat Card Report
 */
class SummaryStatReport extends Report {
    async render() {
        const data = await this.fetchData('/country-stats');
        if (!data) return;

        const value = this.options.getValue(data);
        if (this.container) {
            this.container.innerText = value.toLocaleString();
        }
    }
}

/**
 * Top Countries Bar Chart Report
 */
class TopCountriesReport extends Report {
    async render() {
        const metric = this.options.metric || 'venues';
        const limit = this.options.limit || 10;
        const data = await this.fetchData(`/top-countries?metric=${metric}&limit=${limit}`);
        if (!data) return;

        const canvas = this.container.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(c => c.name),
                datasets: [{
                    label: this.options.label || 'Total Venues',
                    data: data.map(c => c[`total_${metric}`]),
                    backgroundColor: this.options.color || 'rgba(102, 126, 234, 0.8)',
                    borderColor: this.options.borderColor || 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: this.options.horizontal ? 'y' : 'x',
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

/**
 * Venue Categories Doughnut Chart Report
 */
class VenueCategoriesReport extends Report {
    async render() {
        const data = await this.fetchData('/venue-types');
        if (!data) return;

        const canvas = this.container.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        this.chart = new Chart(ctx, {
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
                        'rgba(255, 159, 64, 0.8)'
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
                            boxWidth: 12
                        }
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }
}

/**
 * Court Distribution Bar Chart Report
 */
class CourtDistributionReport extends Report {
    async render() {
        const data = await this.fetchData('/court-distribution');
        if (!data) return;

        const canvas = this.container.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => `${d.no_of_courts} courts`),
                datasets: [{
                    label: 'Number of Venues',
                    data: data.map(d => d.venue_count),
                    backgroundColor: 'rgba(118, 75, 162, 0.8)',
                    borderColor: 'rgba(118, 75, 162, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

/**
 * Timeline Line Chart Report
 */
class TimelineReport extends Report {
    async render() {
        const data = await this.fetchData('/timeline');
        if (!data) return;

        const canvas = this.container.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: [{
                    label: 'New Venues',
                    data: data.map(d => d.venue_count),
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
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

/**
 * Regional Breakdown Report
 */
class RegionalBreakdownReport extends Report {
    async render() {
        const data = await this.fetchData('/regional-breakdown');
        if (!data) return;

        const canvas = this.container.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(r => r.name),
                datasets: [{
                    label: 'Venues',
                    data: data.map(r => r.venues),
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
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

/**
 * Court Types Breakdown Report
 */
class CourtTypesReport extends Report {
    async render() {
        const data = await this.fetchData('/court-types');
        if (!data) return;

        const canvas = this.container.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        this.chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Glass Courts', 'Non-Glass Courts', 'Outdoor Courts'],
                datasets: [{
                    data: [data.glass_courts, data.non_glass_courts, data.outdoor_courts],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
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
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }
}

/**
 * Map Report
 */
class MapReport extends Report {
    async render() {
        const geojsonData = await this.fetchData('/map');
        if (!geojsonData) return;

        const map = new maplibregl.Map({
            container: this.containerId,
            style: 'https://tiles.stadiamaps.com/styles/osm_bright.json',
            center: [0, 20],
            zoom: 1.5
        });

        map.on('load', () => {
            map.addSource('venues', {
                type: 'geojson',
                data: geojsonData,
                cluster: true,
                clusterMaxZoom: 14,
                clusterRadius: 50
            });

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
                        100,
                        '#f1f075',
                        750,
                        '#f28cb1'
                    ],
                    'circle-radius': [
                        'step',
                        ['get', 'point_count'],
                        20,
                        100,
                        30,
                        750,
                        40
                    ]
                }
            });

            map.addLayer({
                id: 'cluster-count',
                type: 'symbol',
                source: 'venues',
                filter: ['has', 'point_count'],
                layout: {
                    'text-field': ['get', 'point_count_abbreviated'],
                    'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
                    'text-size': 12
                },
                paint: {
                    'text-color': '#ffffff'
                }
            });

            map.addLayer({
                id: 'unclustered-point',
                type: 'circle',
                source: 'venues',
                filter: ['!', ['has', 'point_count']],
                paint: {
                    'circle-color': '#11b4da',
                    'circle-radius': 8,
                    'circle-stroke-width': 1,
                    'circle-stroke-color': '#fff'
                }
            });

            map.on('click', 'clusters', (e) => {
                const features = map.queryRenderedFeatures(e.point, {
                    layers: ['clusters']
                });
                const clusterId = features[0].properties.cluster_id;
                map.getSource('venues').getClusterExpansionZoom(
                    clusterId,
                    (err, zoom) => {
                        if (err) return;
                        map.easeTo({
                            center: features[0].geometry.coordinates,
                            zoom: zoom
                        });
                    }
                );
            });

            map.on('click', 'unclustered-point', (e) => {
                const coordinates = e.features[0].geometry.coordinates.slice();
                const properties = e.features[0].properties;
                const description = `
                    <strong>${properties.name}</strong><br>
                    Courts: ${properties.courts}<br>
                    Country: ${properties.country_code || 'N/A'}
                `;

                while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
                    coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
                }

                new maplibregl.Popup()
                    .setLngLat(coordinates)
                    .setHTML(description)
                    .addTo(map);
            });

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
}

// Export report classes
window.SquashReports = {
    SummaryStatReport,
    TopCountriesReport,
    VenueCategoriesReport,
    CourtDistributionReport,
    TimelineReport,
    RegionalBreakdownReport,
    CourtTypesReport,
    MapReport
};

