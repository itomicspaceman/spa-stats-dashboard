/**
 * Admin Scripts for Squash Stats Dashboard Plugin
 */

(function($) {
    'use strict';
    
    let charts = {};
    let dashboards = {};
    let categories = {};
    let selectedCharts = [];
    let selectedDashboard = '';
    
    $(document).ready(function() {
        // Load data from API
        loadData();
        
        // Tab switching
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.squash-tab-content').hide();
            $(target).show();
            
            // Reset selection when switching tabs
            selectedCharts = [];
            selectedDashboard = '';
            updateShortcode();
        });
        
        // Category filter
        $('#chart-category').on('change', function() {
            const category = $(this).val();
            filterCharts(category);
        });
        
        // Clear selection
        $(document).on('click', '#clear-selection', function() {
            selectedCharts = [];
            $('.squash-card').removeClass('selected');
            $('.chart-checkbox').prop('checked', false);
            updateShortcode();
        });
        
        // Copy shortcode
        $(document).on('click', '#copy-shortcode', function() {
            const shortcode = $('#generated-shortcode').text();
            copyToClipboard(shortcode);
            
            // Show success message
            const $button = $(this);
            const originalText = $button.html();
            $button.html('<span class="dashicons dashicons-yes"></span> Copied!');
            
            setTimeout(function() {
                $button.html(originalText);
            }, 2000);
        });
        
        // Update shortcode when optional parameters change
        $('#shortcode-filter, #shortcode-title').on('input', function() {
            updateShortcode();
        });
    });
    
    /**
     * Load charts and dashboards from API
     */
    function loadData() {
        const apiBase = squashStatsAdmin.apiBase;
        
        Promise.all([
            $.get(apiBase + '/charts'),
            $.get(apiBase + '/dashboards')
        ]).then(function(responses) {
            charts = responses[0];
            dashboards = responses[1];
            
            // Extract categories from charts
            categories = {};
            Object.values(charts).forEach(function(chart) {
                if (chart.category && !categories[chart.category]) {
                    categories[chart.category] = chart.category.charAt(0).toUpperCase() + chart.category.slice(1);
                }
            });
            
            renderDashboards();
            renderCharts();
            renderCategoryFilter();
            
            // Show content, hide loading
            $('#squash-loading').hide();
            $('#squash-error').hide();
            $('#squash-tabs').show();
            $('#tab-dashboards').show();
            
        }).catch(function(error) {
            console.error('Failed to load data:', error);
            $('#squash-loading').hide();
            $('#squash-error').show();
        });
    }
    
    /**
     * Render dashboards grid
     */
    function renderDashboards() {
        const $grid = $('#dashboards-grid');
        $grid.empty();
        
        Object.values(dashboards).forEach(function(dashboard) {
            const $card = $('<div class="squash-card dashboard-card">')
                .attr('data-dashboard-id', dashboard.id)
                .html(`
                    <div class="squash-card-thumbnail">
                        <span>Dashboard Preview</span>
                    </div>
                    <h4 class="squash-card-title">${dashboard.name}</h4>
                    <p class="squash-card-description">${dashboard.description}</p>
                    <p><span class="squash-card-badge">${dashboard.charts.length} charts</span></p>
                    <div class="squash-card-radio">
                        <label>
                            <input type="radio" name="dashboard" value="${dashboard.id}" class="dashboard-radio">
                            Select this dashboard
                        </label>
                    </div>
                `);
            
            $card.on('click', function(e) {
                if (!$(e.target).is('input')) {
                    $(this).find('.dashboard-radio').prop('checked', true).trigger('change');
                }
            });
            
            $card.find('.dashboard-radio').on('change', function() {
                selectedDashboard = $(this).val();
                $('.dashboard-card').removeClass('selected');
                $(this).closest('.squash-card').addClass('selected');
                updateShortcode();
            });
            
            $grid.append($card);
        });
    }
    
    /**
     * Render charts grid
     */
    function renderCharts() {
        const $grid = $('#charts-grid');
        $grid.empty();
        
        Object.values(charts).forEach(function(chart) {
            const $card = $('<div class="squash-card chart-card">')
                .attr('data-chart-id', chart.id)
                .attr('data-category', chart.category)
                .html(`
                    <div class="squash-card-thumbnail">
                        <span>Chart Preview</span>
                    </div>
                    <h4 class="squash-card-title">${chart.name}</h4>
                    <p class="squash-card-description">${chart.description}</p>
                    <p><span class="squash-card-badge">${chart.category}</span></p>
                    <div class="squash-card-checkbox">
                        <label>
                            <input type="checkbox" value="${chart.id}" class="chart-checkbox">
                            Add to selection
                        </label>
                    </div>
                `);
            
            $card.on('click', function(e) {
                if (!$(e.target).is('input')) {
                    const $checkbox = $(this).find('.chart-checkbox');
                    $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
                }
            });
            
            $card.find('.chart-checkbox').on('change', function() {
                const chartId = $(this).val();
                const isChecked = $(this).prop('checked');
                
                if (isChecked) {
                    if (!selectedCharts.includes(chartId)) {
                        selectedCharts.push(chartId);
                    }
                    $(this).closest('.squash-card').addClass('selected');
                } else {
                    selectedCharts = selectedCharts.filter(id => id !== chartId);
                    $(this).closest('.squash-card').removeClass('selected');
                }
                
                updateShortcode();
            });
            
            $grid.append($card);
        });
    }
    
    /**
     * Render category filter
     */
    function renderCategoryFilter() {
        const $select = $('#chart-category');
        $select.find('option:not(:first)').remove();
        
        Object.entries(categories).forEach(function([key, label]) {
            $select.append(`<option value="${key}">${label}</option>`);
        });
    }
    
    /**
     * Filter charts by category
     */
    function filterCharts(category) {
        if (!category) {
            $('.chart-card').show();
        } else {
            $('.chart-card').each(function() {
                if ($(this).attr('data-category') === category) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    }
    
    /**
     * Update shortcode based on selection
     */
    function updateShortcode() {
        let shortcode = '[squash_court_stats';
        
        if (selectedDashboard) {
            shortcode += ` dashboard="${selectedDashboard}"`;
        } else if (selectedCharts.length > 0) {
            shortcode += ` charts="${selectedCharts.join(',')}"`;
        }
        
        // Add optional parameters
        const filter = $('#shortcode-filter').val().trim();
        if (filter) {
            shortcode += ` filter="${filter}"`;
        }
        
        const title = $('#shortcode-title').val().trim();
        if (title) {
            shortcode += ` title="${title}"`;
        }
        
        shortcode += ']';
        
        $('#generated-shortcode').text(shortcode);
        
        // Show/hide shortcode output
        if (selectedDashboard || selectedCharts.length > 0) {
            $('#shortcode-output').show();
        } else {
            $('#shortcode-output').hide();
        }
        
        // Update selected summary for charts
        if (selectedCharts.length > 0) {
            updateSelectedSummary();
            $('#selected-summary').show();
        } else {
            $('#selected-summary').hide();
        }
    }
    
    /**
     * Update selected charts summary
     */
    function updateSelectedSummary() {
        $('#selected-count').text(selectedCharts.length);
        
        const $list = $('#selected-list');
        $list.empty();
        
        selectedCharts.forEach(function(chartId) {
            const chart = charts[chartId];
            if (chart) {
                const $tag = $('<span class="selected-chart-tag">')
                    .html(`
                        ${chart.name}
                        <span class="remove" data-chart-id="${chartId}">&times;</span>
                    `);
                
                $tag.find('.remove').on('click', function() {
                    const id = $(this).attr('data-chart-id');
                    selectedCharts = selectedCharts.filter(cid => cid !== id);
                    $(`.chart-checkbox[value="${id}"]`).prop('checked', false).trigger('change');
                });
                
                $list.append($tag);
            }
        });
    }
    
    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        }
    }
    
})(jQuery);

