/**
 * CIRAS Main JavaScript
 * Cybercrime Incident Reporting & Analysis System
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initSidebar();
        initNotifications();
        initDataTables();
        initFormValidation();
        initFileUpload();
        initTooltips();
    });

    /**
     * Sidebar Toggle for Mobile
     */
    function initSidebar() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
            });
            
            // Close sidebar on outside click (mobile)
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 1024) {
                    if (!sidebar.contains(event.target) && 
                        !sidebarToggle.contains(event.target) && 
                        !sidebar.classList.contains('-translate-x-full')) {
                        sidebar.classList.add('-translate-x-full');
                    }
                }
            });
        }
    }

    /**
     * Notifications Dropdown
     */
    function initNotifications() {
        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsDropdown = document.getElementById('notifications-dropdown');
        
        if (notificationsBtn) {
            notificationsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (notificationsDropdown) {
                    notificationsDropdown.classList.toggle('hidden');
                }
            });
        }
        
        // Close dropdown on outside click
        document.addEventListener('click', function() {
            if (notificationsDropdown) {
                notificationsDropdown.classList.add('hidden');
            }
        });
    }

    /**
     * Initialize DataTables
     */
    function initDataTables() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            // Auto-initialize DataTables on tables with data-table class
            $('.data-table').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[0, 'desc']],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    }

    /**
     * Form Validation
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Show validation errors
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                form.classList.add('was-validated');
            });
        });
    }

    /**
     * File Upload with Drag & Drop
     */
    function initFileUpload() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(function(input) {
            const container = input.closest('.file-upload-container');
            
            if (container) {
                const dropZone = container.querySelector('.file-upload-area');
                
                if (dropZone) {
                    // Drag and drop
                    dropZone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        dropZone.classList.add('dragover');
                    });
                    
                    dropZone.addEventListener('dragleave', function() {
                        dropZone.classList.remove('dragover');
                    });
                    
                    dropZone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        dropZone.classList.remove('dragover');
                        
                        const files = e.dataTransfer.files;
                        if (files.length > 0) {
                            input.files = files;
                            updateFileDisplay(input, files[0]);
                        }
                    });
                    
                    dropZone.addEventListener('click', function() {
                        input.click();
                    });
                }
                
                // File selection
                input.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        updateFileDisplay(this, this.files[0]);
                    }
                });
            }
        });
    }

    /**
     * Update File Display
     */
    function updateFileDisplay(input, file) {
        const container = input.closest('.file-upload-container');
        if (container) {
            const display = container.querySelector('.file-name-display');
            if (display) {
                display.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
            }
        }
    }

    /**
     * Format File Size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Initialize Tooltips
     */
    function initTooltips() {
        // Simple tooltip implementation
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(function(element) {
            element.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.getAttribute('data-tooltip');
                tooltip.style.cssText = `
                    position: absolute;
                    background: #1f2937;
                    color: white;
                    padding: 0.5rem 0.75rem;
                    border-radius: 0.25rem;
                    font-size: 0.875rem;
                    z-index: 1000;
                    pointer-events: none;
                    white-space: nowrap;
                `;
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            });
            
            element.addEventListener('mouseleave', function() {
                const tooltip = document.querySelector('.tooltip');
                if (tooltip) {
                    tooltip.remove();
                }
            });
        });
    }

    /**
     * Confirm Delete
     */
    window.confirmDelete = function(message) {
        return confirm(message || 'Are you sure you want to delete this item?');
    };

    /**
     * Show Loading Spinner
     */
    window.showLoading = function() {
        const spinner = document.createElement('div');
        spinner.id = 'loading-spinner';
        spinner.className = 'spinner';
        spinner.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        `;
        document.body.appendChild(spinner);
    };

    /**
     * Hide Loading Spinner
     */
    window.hideLoading = function() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    };

    /**
     * Export to Excel (CSV)
     */
    window.exportToCSV = function(data, filename) {
        const csv = convertToCSV(data);
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename || 'export.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    };

    /**
     * Convert Data to CSV
     */
    function convertToCSV(data) {
        if (!data || data.length === 0) return '';
        
        const headers = Object.keys(data[0]);
        const rows = data.map(row => 
            headers.map(header => {
                const value = row[header];
                return typeof value === 'string' && value.includes(',') 
                    ? '"' + value.replace(/"/g, '""') + '"' 
                    : value;
            }).join(',')
        );
        
        return [headers.join(','), ...rows].join('\n');
    }

    /**
     * Auto-save form data to localStorage
     */
    window.autoSaveForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        localStorage.setItem('form_autosave_' + formId, JSON.stringify(data));
    };

    /**
     * Restore form data from localStorage
     */
    window.restoreForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        const saved = localStorage.getItem('form_autosave_' + formId);
        if (saved) {
            const data = JSON.parse(saved);
            Object.keys(data).forEach(key => {
                const input = form.querySelector('[name="' + key + '"]');
                if (input) {
                    input.value = data[key];
                }
            });
        }
    };

    /**
     * Clear auto-saved form data
     */
    window.clearAutoSave = function(formId) {
        localStorage.removeItem('form_autosave_' + formId);
    };

})();

