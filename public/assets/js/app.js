"use strict";

(function () {
    function requireConfirmation(message, event) {
        if (message !== "" && !window.confirm(message)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }

    document.addEventListener("click", function (event) {
        if (!(event.target instanceof Element)) {
            return;
        }
        var target = event.target.closest("[data-confirm]");
        if (!target || target.tagName === "FORM") {
            return;
        }
        requireConfirmation(target.getAttribute("data-confirm") || "", event);
    });

    document.addEventListener("submit", function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches("form[data-confirm]")) {
            return;
        }
        requireConfirmation(form.getAttribute("data-confirm") || "", event);
    });
}());
