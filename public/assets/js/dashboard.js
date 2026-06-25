"use strict";

(function () {
    var config = window.AutoSAVDashboardConfig || {};
    var csrfToken = String(config.csrfToken || "");

    function headers(contentType) {
        var values = {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-Token": csrfToken
        };
        if (contentType) {
            values["Content-Type"] = contentType;
        }
        return values;
    }

    function request(url, options) {
        return fetch(url, Object.assign({
            credentials: "same-origin",
            headers: headers()
        }, options || {})).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || "Erreur de chargement.");
                }
                return payload.data || {};
            });
        });
    }

    function showWidgetError(container) {
        container.textContent = "";
        var alert = document.createElement("div");
        alert.className = "alert alert-warning mb-0";
        alert.textContent = "Impossible de charger ce widget.";
        container.appendChild(alert);
    }

    function loadWidget(code) {
        var container = Array.prototype.find.call(
            document.querySelectorAll("[data-widget-code]"),
            function (element) {
                return element.dataset.widgetCode === code;
            }
        );
        if (!container) {
            return;
        }
        request("/ajax/dashboard/widget/" + encodeURIComponent(code))
            .then(function (data) {
                container.innerHTML = String(data.html || "");
                container.classList.remove("dashboard-widget-async");
            })
            .catch(function () {
                showWidgetError(container);
            });
    }

    function updateContext(url, values) {
        return request(url, {
            method: "POST",
            headers: headers("application/x-www-form-urlencoded; charset=UTF-8"),
            body: new URLSearchParams(values).toString()
        }).then(function () {
            window.location.reload();
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        (Array.isArray(config.asyncWidgets) ? config.asyncWidgets : []).forEach(loadWidget);

        var company = document.getElementById("active-company-select");
        if (company) {
            company.addEventListener("change", function () {
                updateContext("/ajax/context/company", { company_id: company.value });
            });
        }

        var brand = document.getElementById("active-brand-select");
        if (brand) {
            brand.addEventListener("change", function () {
                updateContext("/ajax/context/brand", { brand_id: brand.value });
            });
        }
    });
}());
