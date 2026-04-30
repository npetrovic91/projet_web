/**
 * Gestionnaire AJAX global de l'application Autosav
 * Fichier : public/assets/js/ajax.js
 *
 * Responsabilités :
 * 1. Lecture du token CSRF depuis la méta-balise (injectée dans layout_main)
 * 2. Classe AutosavAjax : wrapper fetch() avec injection CSRF, gestion 401/403/500
 * 3. Objet SidebarContext : changement entreprise/marque via les sélecteurs sidebar
 * 4. Gestion globale des erreurs AJAX + SweetAlert2
 *
 * Dépendances :
 * - SweetAlert2 (vendor local)
 * - window.AUTOSAV_DASHBOARD (injecté dans dashboard.php)
 * - Méta-balise <meta name="csrf-token" content="..."> dans le layout
 *
 * Activation : chargé uniquement sur les pages authentifiées (layout_main)
 */

(function () {
  'use strict';

  // ============================================================
  // 1. Lecture du token CSRF
  // ============================================================

  /**
   * Récupère le token CSRF depuis la méta-balise du layout
   * @returns {string}
   */
  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  // ============================================================
  // 2. Classe AutosavAjax
  // ============================================================

  /**
   * Wrapper fetch() pour tous les appels AJAX de l'application
   * Injecte automatiquement :
   *   - En-tête X-Requested-With: XMLHttpRequest
   *   - En-tête X-CSRF-Token: {token}
   *   - Content-Type: application/json pour les requêtes POST
   */
  class AutosavAjax {

    /**
     * Requête GET JSON
     * @param {string} url
     * @param {object} [params] Paramètres query string
     * @returns {Promise<object>} Corps JSON normalisé
     */
    static async get(url, params = {}) {
      const qs = new URLSearchParams(params).toString();
      const fullUrl = qs ? `${url}?${qs}` : url;
      return AutosavAjax._request('GET', fullUrl, null);
    }

    /**
     * Requête POST JSON
     * @param {string} url
     * @param {object} [body]  Corps JSON
     * @returns {Promise<object>}
     */
    static async post(url, body = {}) {
      return AutosavAjax._request('POST', url, body);
    }

    /**
     * Requête POST avec FormData (pour les formulaires)
     * @param {string} url
     * @param {FormData} formData
     * @returns {Promise<object>}
     */
    static async postForm(url, formData) {
      return AutosavAjax._requestForm(url, formData);
    }

    /** @private */
    static async _request(method, url, body) {
      const options = {
        method,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token':     getCsrfToken(),
          'Accept':           'application/json',
        },
      };

      if (body !== null) {
        options.headers['Content-Type'] = 'application/json;charset=utf-8';
        options.body = JSON.stringify(body);
      }

      return AutosavAjax._execute(url, options);
    }

    /** @private — FormData (pas de Content-Type manuel) */
    static async _requestForm(url, formData) {
      const options = {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token':     getCsrfToken(),
          'Accept':           'application/json',
        },
        body: formData,
      };
      return AutosavAjax._execute(url, options);
    }

    /** @private — fetch + gestion erreurs HTTP */
    static async _execute(url, options) {
      let response;
      try {
        response = await fetch(url, options);
      } catch (networkError) {
        AutosavAjax._handleNetworkError(networkError);
        throw networkError;
      }

      // Erreur authentification → redirection login
      if (response.status === 401) {
        AutosavAjax._handleUnauthorized();
        throw new Error('Unauthenticated');
      }

      // Accès refusé → SweetAlert
      if (response.status === 403) {
        const data = await AutosavAjax._parseJson(response);
        AutosavAjax._handleForbidden(data.message || 'Accès refusé.');
        throw new Error('Forbidden');
      }

      // Erreur serveur
      if (response.status === 500) {
        const data = await AutosavAjax._parseJson(response);
        AutosavAjax._handleServerError(data.message || 'Erreur serveur.');
        throw new Error('ServerError');
      }

      return AutosavAjax._parseJson(response);
    }

    /** @private */
    static async _parseJson(response) {
      try {
        return await response.json();
      } catch {
        return { success: false, code: response.status, message: 'Réponse invalide du serveur.' };
      }
    }

    // ---- Handlers d'erreurs centralisés ----

    static _handleUnauthorized() {
      Swal.fire({
        icon:              'warning',
        title:             'Session expirée',
        text:              'Votre session a expiré. Vous allez être redirigé vers la page de connexion.',
        timer:             3000,
        timerProgressBar:  true,
        showConfirmButton: false,
        allowOutsideClick: false,
      }).then(() => { window.location.href = '/'; });
    }

    static _handleForbidden(message) {
      Swal.fire({
        icon:  'error',
        title: 'Accès refusé',
        text:  message,
        confirmButtonColor: '#e3342f',
      });
    }

    static _handleServerError(message) {
      Swal.fire({
        icon:  'error',
        title: 'Erreur serveur',
        text:  message || 'Une erreur inattendue s\'est produite. Veuillez réessayer.',
        confirmButtonColor: '#3085d6',
      });
    }

    static _handleNetworkError(error) {
      console.error('[Autosav AJAX] Network error:', error);
      Swal.fire({
        icon:  'error',
        title: 'Erreur réseau',
        text:  'Impossible de contacter le serveur. Vérifiez votre connexion.',
        confirmButtonColor: '#3085d6',
      });
    }
  }

  // ============================================================
  // 3. Objet SidebarContext — Changement entreprise/marque
  // ============================================================

  const SidebarContext = {

    _companySelect : null,
    _brandSelect   : null,
    _brandWrapper  : null,
    _companySpinner: null,

    init() {
      this._companySelect  = document.getElementById('sidebar-company-select');
      this._brandSelect    = document.getElementById('sidebar-brand-select');
      this._brandWrapper   = document.getElementById('brand-selector-wrapper');
      this._companySpinner = document.getElementById('company-spinner');

      if (this._companySelect) {
        this._companySelect.addEventListener('change', (e) => {
          const id = parseInt(e.target.value, 10);
          if (id > 0) this._setCompany(id);
        });
      }

      if (this._brandSelect) {
        this._brandSelect.addEventListener('change', (e) => {
          const id = parseInt(e.target.value, 10);
          this._setBrand(id);
        });
      }
    },

    /** Envoie le changement d'entreprise active au serveur */
    async _setCompany(companyId) {
      if (this._companySpinner) this._companySpinner.style.display = 'block';

      try {
        const res = await AutosavAjax.post('/ajax/context/company', { company_id: companyId });

        if (res.success) {
          // Mise à jour du sélecteur de marques
          this._updateBrandSelector(res.data.brands || [], res.data.active_brand_id);

          // Feedback subtil sans bloquer l'UI
          AutosavHelper.toastSuccess(`Entreprise : ${res.data.company?.name || ''}`);

          // Recharger la page pour que les widgets se mettent à jour
          setTimeout(() => { window.location.reload(); }, 800);
        } else {
          AutosavHelper.toastError(res.message || 'Erreur lors du changement d\'entreprise.');
        }
      } catch (e) {
        // Erreur déjà gérée par AutosavAjax
      } finally {
        if (this._companySpinner) this._companySpinner.style.display = 'none';
      }
    },

    /** Envoie le changement de marque active */
    async _setBrand(brandId) {
      try {
        const res = await AutosavAjax.post('/ajax/context/brand', { brand_id: brandId });
        if (res.success) {
          AutosavHelper.toastSuccess(
            brandId > 0
              ? `Marque : ${res.data.brand?.name || ''}`
              : 'Toutes les marques'
          );
          // Pas de reload complet pour un simple changement de marque
          // Les widgets lourds se rechargent via dashboard.js si nécessaire
        } else {
          AutosavHelper.toastError(res.message || 'Erreur lors du changement de marque.');
        }
      } catch (e) { /* géré */ }
    },

    /** Met à jour le sélecteur de marques après changement d'entreprise */
    _updateBrandSelector(brands, activeBrandId) {
      if (!this._brandSelect || !this._brandWrapper) return;

      // Vider le select
      while (this._brandSelect.options.length > 1) {
        this._brandSelect.remove(1);
      }

      if (brands.length === 0) {
        this._brandWrapper.style.display = 'none';
        return;
      }

      brands.forEach((brand) => {
        const option    = document.createElement('option');
        option.value    = brand.brd_id;
        option.textContent = brand.brd_name;
        if (parseInt(brand.brd_id, 10) === parseInt(activeBrandId, 10)) {
          option.selected = true;
        }
        this._brandSelect.appendChild(option);
      });

      this._brandWrapper.style.display = 'block';

      // Réinitialiser Select2 si présent
      if (window.$ && $.fn.select2) {
        $(this._brandSelect).trigger('change.select2');
      }
    },
  };

  // ============================================================
  // 4. Helpers SweetAlert / Toast
  // ============================================================

  const AutosavHelper = {

    /** Toast de succès (non bloquant) */
    toastSuccess(message) {
      const Toast = Swal.mixin({
        toast:             true,
        position:          'bottom-end',
        showConfirmButton: false,
        timer:             2500,
        timerProgressBar:  true,
        icon:              'success',
      });
      Toast.fire({ title: message });
    },

    /** Toast d'erreur */
    toastError(message) {
      const Toast = Swal.mixin({
        toast:             true,
        position:          'bottom-end',
        showConfirmButton: false,
        timer:             3500,
        icon:              'error',
      });
      Toast.fire({ title: message });
    },

    /** Confirmation avant action destructive */
    async confirm(title, text, confirmText = 'Confirmer', cancelText = 'Annuler') {
      const result = await Swal.fire({
        title,
        text,
        icon:               'warning',
        showCancelButton:   true,
        confirmButtonColor: '#d33',
        cancelButtonColor:  '#3085d6',
        confirmButtonText:  confirmText,
        cancelButtonText:   cancelText,
      });
      return result.isConfirmed;
    },
  };

  // ============================================================
  // 5. Gestion des notifications (badge + liste)
  // ============================================================

  const NotifHandler = {

    init() {
      // Bouton "Tout marquer comme lu" dans le widget
      document.querySelectorAll('.js-mark-all-read').forEach((btn) => {
        btn.addEventListener('click', () => this.markAllRead());
      });

      // Bouton "Marquer lu" individuel
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-notif-read');
        if (btn) {
          const id = parseInt(btn.dataset.id, 10);
          if (id > 0) this.markRead(id, btn);
        }
      });

      // Mise à jour périodique du badge (toutes les 60s)
      this._updateBadge();
      setInterval(() => this._updateBadge(), 60000);
    },

    async markRead(notifId, btn) {
      try {
        const res = await AutosavAjax.post(`/ajax/notifications/mark-read/${notifId}`, {});
        if (res.success) {
          const li = btn.closest('li[data-notif-id]');
          if (li) li.remove();
          this._updateBadge();
        }
      } catch (e) { /* géré */ }
    },

    async markAllRead() {
      try {
        const res = await AutosavAjax.post('/ajax/notifications/mark-all-read', {});
        if (res.success) {
          const list = document.getElementById('notif-list');
          if (list) list.innerHTML =
            '<li class="text-center py-3 text-muted"><i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>Aucune notification non lue</li>';
          this._updateBadge();
        }
      } catch (e) { /* géré */ }
    },

    async _updateBadge() {
      try {
        const res = await AutosavAjax.get('/ajax/notifications/unread-count');
        if (res.success) {
          const badge = document.getElementById('notif-badge');
          if (badge) {
            badge.textContent = res.data.count;
            badge.style.display = res.data.count > 0 ? 'inline-block' : 'none';
          }
        }
      } catch (e) { /* silencieux */ }
    },
  };

  // ============================================================
  // 6. Exposition globale + initialisation DOM
  // ============================================================

  window.AutosavAjax   = AutosavAjax;
  window.AutosavHelper = AutosavHelper;

  document.addEventListener('DOMContentLoaded', function () {
    SidebarContext.init();
    NotifHandler.init();
  });

})();
