(function () {
    'use strict';

    var cfg = window.AutoSAVDashboardConfig || {};
    var csrf = cfg.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function request(url, options) {
        options = options || {};
        options.headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrf
        }, options.headers || {});
        return fetch(url, options).then(function (response) { return response.json(); });
    }

    function formBody(data) {
        var params = new URLSearchParams();
        Object.keys(data).forEach(function (key) { params.append(key, data[key]); });
        return params;
    }

    function loadAsyncWidgets() {
        (cfg.asyncWidgets || []).forEach(function (code) {
            request('/ajax/dashboard/widget/' + encodeURIComponent(code))
                .then(function (json) {
                    if (!json.success) return;
                    var target = document.querySelector('[data-widget-code="' + code + '"]');
                    if (target) target.innerHTML = json.data.html;
                });
        });
    }

    function refreshBrands(companyId) {
        return request('/ajax/context/brands-for-company?company_id=' + encodeURIComponent(companyId))
            .then(function (json) {
                if (!json.success) return;
                var select = document.getElementById('active-brand-select');
                select.innerHTML = '<option value="">Toutes les marques</option>';
                (json.data.brands || []).forEach(function (brand) {
                    var option = document.createElement('option');
                    option.value = brand.brd_id;
                    option.textContent = brand.brd_name;
                    select.appendChild(option);
                });
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadAsyncWidgets();

        var companySelect = document.getElementById('active-company-select');
        var brandSelect = document.getElementById('active-brand-select');

        if (companySelect) {
            companySelect.addEventListener('change', function () {
                request('/ajax/context/company', {
                    method: 'POST',
                    body: formBody({company_id: this.value})
                }).then(function (json) {
                    if (json.success) {
                        refreshBrands(companySelect.value).then(loadAsyncWidgets);
                    }
                });
            });
        }

        if (brandSelect) {
            brandSelect.addEventListener('change', function () {
                request('/ajax/context/brand', {
                    method: 'POST',
                    body: formBody({brand_id: this.value})
                }).then(function (json) {
                    if (json.success) loadAsyncWidgets();
                });
            });
        }
    });
})();
