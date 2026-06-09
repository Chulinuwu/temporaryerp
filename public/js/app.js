/**
 * PEGASUS ERP - Common JavaScript
 * Version 3.0
 */

(function () {
    'use strict';

    /* ==========================================================
       1. Sidebar submenu toggle
       ========================================================== */
    window.toggleSubmenu = function (el) {
        var arrow = el.querySelector('.sidebar-item-arrow');
        var submenu = el.nextElementSibling;
        if (!submenu || !submenu.classList.contains('sidebar-submenu')) return;

        var isOpen = submenu.classList.contains('open');
        submenu.classList.toggle('open', !isOpen);
        if (arrow) arrow.classList.toggle('expanded', !isOpen);
    };

    /* ==========================================================
       2. Mobile hamburger menu toggle
       ========================================================== */
    document.addEventListener('DOMContentLoaded', function () {
        var hamburger = document.getElementById('hamburgerBtn');
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebarOverlay');

        if (hamburger && sidebar) {
            hamburger.addEventListener('click', function () {
                sidebar.classList.toggle('open');
                if (overlay) overlay.classList.toggle('active', sidebar.classList.contains('open'));
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function () {
                if (sidebar) sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }

        // Auto-dismiss flash messages after 5 seconds
        autoDismissAlerts();

        // Init table sorting
        initTableSort();

        // Init table search/filter
        initTableSearch();
    });

    /* ==========================================================
       3. Modal open / close
       ========================================================== */
    window.openModal = function (id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function (id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close modal when clicking overlay
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-overlay') && e.target.classList.contains('active')) {
            e.target.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var openModals = document.querySelectorAll('.modal-overlay.active');
            openModals.forEach(function (m) {
                m.classList.remove('active');
            });
            document.body.style.overflow = '';
        }
    });

    /* ==========================================================
       4. CSRF token handling for AJAX
       ========================================================== */
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /* ==========================================================
       5. Flash message auto-dismiss (5 seconds)
       ========================================================== */
    function autoDismissAlerts() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.3s, transform 0.3s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-8px)';
                setTimeout(function () {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    }

    /* ==========================================================
       6. Confirm delete dialogs
       ========================================================== */
    window.confirmDelete = function (message, formOrCallback) {
        var msg = message || 'Are you sure you want to delete this item?';
        if (!confirm(msg)) return false;

        if (typeof formOrCallback === 'function') {
            formOrCallback();
        } else if (formOrCallback && formOrCallback.submit) {
            formOrCallback.submit();
        }
        return true;
    };

    /* ==========================================================
       7. Number formatting
       ========================================================== */
    window.formatNumber = function (num, decimals, locale) {
        decimals = decimals !== undefined ? decimals : 2;
        locale = locale || 'en-US';
        var n = parseFloat(num);
        if (isNaN(n)) return '0';
        return n.toLocaleString(locale, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    };

    window.formatCurrency = function (num, currency, locale) {
        currency = currency || 'THB';
        locale = locale || 'th-TH';
        var n = parseFloat(num);
        if (isNaN(n)) return '0';
        return n.toLocaleString(locale, {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    /* ==========================================================
       8. Date formatting helpers
       ========================================================== */
    window.formatDate = function (dateStr, format) {
        if (!dateStr) return '';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;

        var yyyy = d.getFullYear();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');

        format = format || 'YYYY-MM-DD';
        return format
            .replace('YYYY', yyyy)
            .replace('MM', mm)
            .replace('DD', dd);
    };

    window.formatDateTime = function (dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0') + ' ' +
            String(d.getHours()).padStart(2, '0') + ':' +
            String(d.getMinutes()).padStart(2, '0');
    };

    /* ==========================================================
       9. Table sorting (click headers)
       ========================================================== */
    function initTableSort() {
        document.querySelectorAll('.data-table thead th.sortable').forEach(function (th) {
            th.addEventListener('click', function () {
                var table = th.closest('table');
                var tbody = table.querySelector('tbody');
                if (!tbody) return;

                var colIndex = Array.from(th.parentNode.children).indexOf(th);
                var rows = Array.from(tbody.querySelectorAll('tr'));
                var isAsc = th.classList.contains('sort-asc');

                // Reset all sort indicators in this table
                table.querySelectorAll('thead th').forEach(function (h) {
                    h.classList.remove('sort-asc', 'sort-desc');
                });

                var direction = isAsc ? 'desc' : 'asc';
                th.classList.add('sort-' + direction);

                rows.sort(function (a, b) {
                    var aVal = (a.children[colIndex] ? a.children[colIndex].textContent : '').trim();
                    var bVal = (b.children[colIndex] ? b.children[colIndex].textContent : '').trim();

                    // Try numeric comparison
                    var aNum = parseFloat(aVal.replace(/[^0-9.\-]/g, ''));
                    var bNum = parseFloat(bVal.replace(/[^0-9.\-]/g, ''));

                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return direction === 'asc' ? aNum - bNum : bNum - aNum;
                    }

                    // String comparison
                    var cmp = aVal.localeCompare(bVal, undefined, { sensitivity: 'base' });
                    return direction === 'asc' ? cmp : -cmp;
                });

                rows.forEach(function (row) {
                    tbody.appendChild(row);
                });
            });
        });
    }

    /* ==========================================================
       10. Search / filter functionality for tables
       ========================================================== */
    function initTableSearch() {
        document.querySelectorAll('[data-table-search]').forEach(function (input) {
            var targetId = input.getAttribute('data-table-search');
            var table = document.getElementById(targetId);
            if (!table) return;

            input.addEventListener('input', function () {
                var query = input.value.toLowerCase().trim();
                var rows = table.querySelectorAll('tbody tr');

                rows.forEach(function (row) {
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(query) !== -1 ? '' : 'none';
                });
            });
        });
    }

    /* ==========================================================
       11. Form validation helpers
       ========================================================== */
    window.validateForm = function (formEl) {
        var isValid = true;
        var firstInvalid = null;

        // Clear previous errors
        formEl.querySelectorAll('.form-input.error, .form-select.error, .form-textarea.error').forEach(function (el) {
            el.classList.remove('error');
        });
        formEl.querySelectorAll('.form-error').forEach(function (el) {
            el.remove();
        });

        // Check required fields
        formEl.querySelectorAll('[required]').forEach(function (field) {
            var val = field.value.trim();
            if (!val) {
                isValid = false;
                field.classList.add('error');

                var errDiv = document.createElement('div');
                errDiv.className = 'form-error';
                errDiv.textContent = 'This field is required';
                field.parentNode.appendChild(errDiv);

                if (!firstInvalid) firstInvalid = field;
            }
        });

        // Check email fields
        formEl.querySelectorAll('input[type="email"]').forEach(function (field) {
            var val = field.value.trim();
            if (val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                isValid = false;
                field.classList.add('error');

                var errDiv = document.createElement('div');
                errDiv.className = 'form-error';
                errDiv.textContent = 'Please enter a valid email address';
                field.parentNode.appendChild(errDiv);

                if (!firstInvalid) firstInvalid = field;
            }
        });

        if (firstInvalid) {
            firstInvalid.focus();
        }

        return isValid;
    };

    /* ==========================================================
       12. AJAX helper function
       ========================================================== */
    window.ajax = function (options) {
        var method = (options.method || 'GET').toUpperCase();
        var url = options.url;
        var data = options.data || null;
        var headers = options.headers || {};

        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open(method, url, true);

            // Default headers
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            // Add CSRF token for non-GET requests
            if (method !== 'GET') {
                xhr.setRequestHeader('X-CSRF-Token', getCsrfToken());
            }

            // Set content type if data is object
            if (data && typeof data === 'object' && !(data instanceof FormData)) {
                xhr.setRequestHeader('Content-Type', 'application/json');
                data = JSON.stringify(data);
            }

            // Custom headers
            Object.keys(headers).forEach(function (key) {
                xhr.setRequestHeader(key, headers[key]);
            });

            xhr.onload = function () {
                var response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    response = xhr.responseText;
                }

                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(response);
                } else {
                    reject({ status: xhr.status, response: response });
                }
            };

            xhr.onerror = function () {
                reject({ status: 0, response: 'Network error' });
            };

            xhr.send(data);
        });
    };

    /* ==========================================================
       13. Language switcher
       ========================================================== */
    window.switchLang = function (lang) {
        ajax({
            method: 'POST',
            url: '/settings/language',
            data: { lang: lang }
        }).then(function () {
            location.reload();
        }).catch(function () {
            // Fallback: set via query parameter
            var url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        });
    };

    /* ==========================================================
       14. Utility: Show toast notification
       ========================================================== */
    window.showToast = function (message, type) {
        type = type || 'info';
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.style.cssText = 'position:fixed;top:60px;right:16px;z-index:3000;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'alert alert-' + type;
        toast.style.minWidth = '280px';
        toast.innerHTML = '<span>' + message + '</span><button class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
        container.appendChild(toast);

        setTimeout(function () {
            toast.style.transition = 'opacity 0.3s';
            toast.style.opacity = '0';
            setTimeout(function () {
                toast.remove();
            }, 300);
        }, 5000);
    };

    /* ==========================================================
       15. Tab navigation
       ========================================================== */
    window.switchTab = function (tabGroup, tabName) {
        // Deactivate all tabs in group
        document.querySelectorAll('[data-tab-group="' + tabGroup + '"]').forEach(function (el) {
            el.classList.remove('active');
        });
        document.querySelectorAll('[data-tab-content="' + tabGroup + '"]').forEach(function (el) {
            el.classList.remove('active');
        });

        // Activate selected tab
        var tabBtn = document.querySelector('[data-tab-group="' + tabGroup + '"][data-tab="' + tabName + '"]');
        var tabContent = document.querySelector('[data-tab-content="' + tabGroup + '"][data-tab="' + tabName + '"]');
        if (tabBtn) tabBtn.classList.add('active');
        if (tabContent) tabContent.classList.add('active');
    };

})();
