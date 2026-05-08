/**
 * AntiGravity Payroll PIT - Global JS Helpers
 */
const App = {
    /**
     * Show a consistent alert message
     * @param {string} type - 'success', 'danger', 'warning', 'info'
     * @param {string} message - The message to display
     */
    showAlert: function(type, message) {
        const placeholder = document.getElementById('alertPlaceholder');
        if (!placeholder) {
            alert(message); // Fallback if no placeholder exists
            return;
        }

        const iconMap = {
            'success': 'fa-check-circle',
            'danger': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        const icon = iconMap[type] || 'fa-info-circle';

        placeholder.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show border-0 shadow-sm mb-4 border-start border-5 border-${type}">
                <div class="d-flex align-items-center">
                    <i class="fas ${icon} me-3 fs-4"></i>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    /**
     * Wrapper for fetch API with button loading state
     * Supports both:
     * 1. App.ajaxRequest(action, data, button)
     * 2. App.ajaxRequest({ action, formData/data, button, method, url, success, error })
     * @returns {Promise}
     */
    ajaxRequest: async function(arg1, arg2 = null, arg3 = null) {
        let config = {};

        // Handle different calling conventions
        if (typeof arg1 === 'object' && arg1 !== null && !(arg1 instanceof FormData)) {
            config = arg1;
        } else {
            config = {
                action: arg1,
                data: arg2,
                button: arg3
            };
        }

        const action = config.action;
        const data = config.data || config.formData;
        const button = config.button || null;
        const method = config.method || 'POST';
        const url = config.url || (typeof BASE_URL !== 'undefined' ? BASE_URL + 'pages/ajax_handler.php' : 'ajax_handler.php');


        let originalContent = '';
        if (button) {
            originalContent = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        }

        let formData;
        if (data instanceof FormData) {
            formData = data;
        } else if (data && typeof data === 'object') {
            formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }
        } else {
            formData = new FormData();
        }
        
        if (action && !formData.has('action')) {
            formData.append('action', action);
        }

        try {
            const fetchOptions = {
                method: method
            };

            if (method === 'POST') {
                fetchOptions.body = formData;
            } else if (method === 'GET' && action) {
                // If it's a GET request and we have an action, we might need to append it to the URL
                // but usually the URL passed in config.url already contains the query string.
            }

            const response = await fetch(url, fetchOptions);
            const result = await response.json();

            if (button) {
                button.disabled = false;
                button.innerHTML = originalContent;
            }

            // Support success/error callbacks if provided in config
            if (result.success && typeof config.success === 'function') {
                config.success(result);
            } else if (!result.success && typeof config.error === 'function') {
                config.error(result.message || 'An error occurred');
            }

            return result;
        } catch (error) {
            console.error('App.ajaxRequest Error:', error);
            if (button) {
                button.disabled = false;
                button.innerHTML = originalContent;
            }
            const errorResult = { success: false, message: 'Network error or server-side failure.' };
            if (typeof config.error === 'function') {
                config.error(errorResult.message);
            }
            return errorResult;
        }
    },

    /**
     * Helper to confirm actions
     * @param {string} message 
     * @returns {boolean}
     */
    confirm: function(message) {
        return confirm(message);
    }
};
