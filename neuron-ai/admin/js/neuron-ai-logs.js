/**
 * JavaScript for Neuron AI logs page
 */
(function($) {
    'use strict';

    /**
     * Initialize the logs functionality
     */
    function init() {
        // Load initial logs
        loadLogs();
        
        // Set up filter button
        $('#neuron-ai-filter-logs').on('click', function(e) {
            e.preventDefault();
            loadLogs();
        });
        
        // Set up clear filters button
        $('#neuron-ai-clear-filters').on('click', function(e) {
            e.preventDefault();
            clearFilters();
        });
        
        // Set up clear logs button
        $('#neuron-ai-clear-logs').on('click', function(e) {
            e.preventDefault();
            confirmClearLogs();
        });
    }

    /**
     * Load logs using current filters
     */
    function loadLogs(page = 1) {
        const $container = $('.neuron-ai-log-table-container');
        const $loading = $('<p class="neuron-ai-log-loading">' + wp.i18n.__('Loading logs...', 'neuron-ai') + '</p>');
        
        // Show loading
        $container.html($loading);
        
        // Get filter values
        const filters = {
            provider: $('#neuron-ai-filter-provider').val(),
            request_type: $('#neuron-ai-filter-type').val(),
            status: $('#neuron-ai-filter-status').val(),
            date_range: $('#neuron-ai-filter-date').val(),
            page: page,
            limit: 50,
        };
        
        // Make AJAX request
        $.ajax({
            url: neuronAI.ajaxUrl,
            type: 'POST',
            data: {
                action: 'neuron_ai_get_logs',
                _wpnonce: neuronAI.nonce,
                ...filters
            },
            success: function(response) {
                if (response.success) {
                    renderLogs(response.data.logs, response.data.pagination);
                } else {
                    showError(response.data.message || 'Error loading logs');
                }
            },
            error: function() {
                showError('Connection error while loading logs');
            }
        });
    }

    /**
     * Render logs in the table
     */
    function renderLogs(logs, pagination) {
        const $container = $('.neuron-ai-log-table-container');
        
        // If no logs, show empty message
        if (logs.length === 0) {
            $container.html(
                '<div class="neuron-ai-log-empty">' +
                '<p>' + wp.i18n.__('No logs found matching your criteria.', 'neuron-ai') + '</p>' +
                '</div>'
            );
            return;
        }
        
        // Create table
        let html = '<table class="neuron-ai-log-table">';
        
        // Add header
        html += '<thead>' +
            '<tr>' +
            '<th>' + wp.i18n.__('Time', 'neuron-ai') + '</th>' +
            '<th>' + wp.i18n.__('Type', 'neuron-ai') + '</th>' +
            '<th>' + wp.i18n.__('Provider', 'neuron-ai') + '</th>' +
            '<th>' + wp.i18n.__('Status', 'neuron-ai') + '</th>' +
            '<th>' + wp.i18n.__('Details', 'neuron-ai') + '</th>' +
            '</tr>' +
            '</thead>';
        
        // Add body
        html += '<tbody>';
        
        logs.forEach(function(log) {
            const statusClass = log.status_code >= 200 && log.status_code < 300 ? 'success' : 'error';
            const statusText = log.status_code >= 200 && log.status_code < 300 ? wp.i18n.__('Success', 'neuron-ai') : wp.i18n.__('Error', 'neuron-ai');
            
            let details = '';
            
            // Format details based on type
            if (log.request_type === 'error' && log.details && log.details.error_message) {
                details = escapeHtml(log.details.error_message);
            } else if (log.details) {
                // Show different details based on the log type
                if (log.request_type === 'enhance') {
                    details = wp.i18n.__('Text enhancement', 'neuron-ai');
                    if (log.details.prompt_length) {
                        details += ' (' + log.details.prompt_length + ' ' + wp.i18n.__('chars', 'neuron-ai') + ')';
                    }
                } else if (log.request_type === 'search') {
                    details = wp.i18n.__('Search query', 'neuron-ai');
                    if (log.details.query) {
                        details += ': ' + escapeHtml(log.details.query);
                    }
                } else if (log.request_type === 'chat') {
                    details = wp.i18n.__('Chat message', 'neuron-ai');
                    if (log.details.prompt_length) {
                        details += ' (' + log.details.prompt_length + ' ' + wp.i18n.__('chars', 'neuron-ai') + ')';
                    }
                } else if (log.request_type === 'response') {
                    details = wp.i18n.__('API Response', 'neuron-ai');
                    if (log.details.response_length) {
                        details += ' (' + log.details.response_length + ' ' + wp.i18n.__('chars', 'neuron-ai') + ')';
                    }
                }
            }
            
            // Format timestamp
            const timestamp = new Date(log.timestamp);
            const formattedTime = timestamp.toLocaleString();
            
            html += '<tr data-log-id="' + log.id + '">' +
                '<td>' + formattedTime + '</td>' +
                '<td>' + formatRequestType(log.request_type) + '</td>' +
                '<td>' + (log.provider ? log.provider : '-') + '</td>' +
                '<td><span class="neuron-ai-log-status ' + statusClass + '">' + 
                    (log.status_code ? log.status_code : '-') + ' ' + statusText + 
                '</span></td>' +
                '<td>' + details + '</td>' +
                '</tr>';
        });
        
        html += '</tbody></table>';
        
        // Add pagination if needed
        if (pagination.total_pages > 1) {
            html += renderPagination(pagination);
        }
        
        // Add log actions
        html += '<div class="neuron-ai-log-actions">' +
            '<button type="button" class="button neuron-ai-clear-logs-button" id="neuron-ai-clear-logs">' +
            wp.i18n.__('Clear Logs', 'neuron-ai') +
            '</button>' +
            '</div>';
        
        // Update container
        $container.html(html);
        
        // Add event handlers for pagination
        $('.neuron-ai-pagination a').on('click', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            loadLogs(page);
        });
        
        // Re-attach event handler for clear logs button
        $('#neuron-ai-clear-logs').on('click', function(e) {
            e.preventDefault();
            confirmClearLogs();
        });
        
        // Add click handler for expandable log rows
        $('.neuron-ai-log-table tbody tr').on('click', function() {
            const logId = $(this).data('log-id');
            toggleLogDetails(logId, $(this));
        });
    }

    /**
     * Toggle detailed view for a log entry
     */
    function toggleLogDetails(logId, $row) {
        // Close any existing detailed rows
        const $existingDetail = $row.next('.neuron-ai-log-detail-row');
        if ($existingDetail.length) {
            $existingDetail.remove();
            $row.removeClass('expanded');
            return;
        }
        
        // Remove any other expanded details
        $('.neuron-ai-log-detail-row').remove();
        $('.neuron-ai-log-table tbody tr').removeClass('expanded');
        
        // Mark this row as expanded
        $row.addClass('expanded');
        
        // TODO: If we want to load additional details via AJAX, do it here
        // For now, we'll just show what we already have
        
        // Get log data from the row
        const timestamp = $row.find('td:eq(0)').text();
        const type = $row.find('td:eq(1)').text();
        const provider = $row.find('td:eq(2)').text();
        const status = $row.find('td:eq(3)').text();
        
        // Create detail row
        const $detailRow = $('<tr class="neuron-ai-log-detail-row"></tr>');
        const $detailCell = $('<td colspan="5"></td>');
        
        $detailCell.html(
            '<div class="neuron-ai-log-details">' +
            '<p><strong>' + wp.i18n.__('Log ID', 'neuron-ai') + ':</strong> ' + logId + '</p>' +
            '<p><strong>' + wp.i18n.__('Time', 'neuron-ai') + ':</strong> ' + timestamp + '</p>' +
            '<p><strong>' + wp.i18n.__('Type', 'neuron-ai') + ':</strong> ' + type + '</p>' +
            '<p><strong>' + wp.i18n.__('Provider', 'neuron-ai') + ':</strong> ' + provider + '</p>' +
            '<p><strong>' + wp.i18n.__('Status', 'neuron-ai') + ':</strong> ' + status + '</p>' +
            '</div>'
        );
        
        $detailRow.append($detailCell);
        $row.after($detailRow);
    }

    /**
     * Render pagination controls
     */
    function renderPagination(pagination) {
        const currentPage = pagination.page;
        const totalPages = pagination.total_pages;
        
        let html = '<div class="neuron-ai-pagination">';
        
        // Previous button
        if (currentPage > 1) {
            html += '<a href="#" class="button" data-page="' + (currentPage - 1) + '">' +
                '&laquo; ' + wp.i18n.__('Previous', 'neuron-ai') +
                '</a>';
        }
        
        // Page info
        html += '<span class="neuron-ai-pagination-info">' +
            wp.i18n.__('Page', 'neuron-ai') + ' ' + currentPage + ' ' +
            wp.i18n.__('of', 'neuron-ai') + ' ' + totalPages +
            '</span>';
        
        // Next button
        if (currentPage < totalPages) {
            html += '<a href="#" class="button" data-page="' + (currentPage + 1) + '">' +
                wp.i18n.__('Next', 'neuron-ai') + ' &raquo;' +
                '</a>';
        }
        
        html += '</div>';
        
        return html;
    }

    /**
     * Format request type for display
     */
    function formatRequestType(type) {
        switch (type) {
            case 'enhance':
                return wp.i18n.__('Enhance', 'neuron-ai');
            case 'search':
                return wp.i18n.__('Search', 'neuron-ai');
            case 'chat':
                return wp.i18n.__('Chat', 'neuron-ai');
            case 'error':
                return wp.i18n.__('Error', 'neuron-ai');
            case 'response':
                return wp.i18n.__('Response', 'neuron-ai');
            default:
                return type;
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        const $container = $('.neuron-ai-log-table-container');
        
        $container.html(
            '<div class="notice notice-error">' +
            '<p>' + message + '</p>' +
            '</div>'
        );
    }

    /**
     * Clear all filters
     */
    function clearFilters() {
        $('#neuron-ai-filter-provider').val('');
        $('#neuron-ai-filter-type').val('');
        $('#neuron-ai-filter-status').val('');
        $('#neuron-ai-filter-date').val('24h');
        
        // Reload logs
        loadLogs();
    }

    /**
     * Confirm and clear all logs
     */
    function confirmClearLogs() {
        if (confirm(wp.i18n.__('Are you sure you want to clear all logs? This action cannot be undone.', 'neuron-ai'))) {
            clearLogs();
        }
    }

    /**
     * Clear all logs
     */
    function clearLogs() {
        const $container = $('.neuron-ai-log-table-container');
        const $loading = $('<p class="neuron-ai-log-loading">' + wp.i18n.__('Clearing logs...', 'neuron-ai') + '</p>');
        
        // Show loading
        $container.html($loading);
        
        // Make AJAX request
        $.ajax({
            url: neuronAI.ajaxUrl,
            type: 'POST',
            data: {
                action: 'neuron_ai_clear_logs',
                _wpnonce: neuronAI.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload logs
                    loadLogs();
                } else {
                    showError(response.data.message || 'Error clearing logs');
                }
            },
            error: function() {
                showError('Connection error while clearing logs');
            }
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);