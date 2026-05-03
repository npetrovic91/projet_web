/**
 * Helpers SweetAlert2 centralises.
 * Fichier : public/assets/js/sweetalert-helper.js
 *
 * Usage : inclus dans layout_main, disponible globalement via window.AutoSavAlert.
 * Dependance : SweetAlert2 local.
 */
(function () {
  'use strict';

  const hasSwal = function () {
    return typeof window.Swal !== 'undefined';
  };

  window.AutoSavAlert = {
    success(title, text = '') {
      if (!hasSwal()) return Promise.resolve(false);
      return Swal.fire({ icon: 'success', title, text, confirmButtonColor: '#28a745' });
    },

    error(title, text = '') {
      if (!hasSwal()) return Promise.resolve(false);
      return Swal.fire({ icon: 'error', title, text, confirmButtonColor: '#dc3545' });
    },

    info(title, text = '') {
      if (!hasSwal()) return Promise.resolve(false);
      return Swal.fire({ icon: 'info', title, text, confirmButtonColor: '#17a2b8' });
    },

    warning(title, text = '') {
      if (!hasSwal()) return Promise.resolve(false);
      return Swal.fire({ icon: 'warning', title, text, confirmButtonColor: '#ffc107' });
    },

    toast(message, icon = 'success', timer = 3000) {
      if (!hasSwal()) return Promise.resolve(false);
      return Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer,
        timerProgressBar: true,
        icon,
      }).fire({ title: message });
    },

    async confirm(title, text = '', options = {}) {
      if (!hasSwal()) return window.confirm(title + (text ? '\n' + text : ''));
      const defaults = {
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: options.confirmText || 'Confirmer',
        cancelButtonText: options.cancelText || 'Annuler',
      };
      const result = await Swal.fire({ ...defaults, title, text });
      return result.isConfirmed;
    },

    loading(message = 'Traitement en cours...') {
      if (!hasSwal()) return;
      Swal.fire({
        title: message,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
      });
    },

    close() {
      if (hasSwal()) Swal.close();
    },
  };

  window.SA = window.AutoSavAlert;
})();
