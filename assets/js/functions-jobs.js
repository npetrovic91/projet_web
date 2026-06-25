/**
 * AUTOSAV — functions-jobs.js
 * Fichier : public/assets/js/functions-jobs.js
 * Rôle    : Gestion AJAX des sélecteurs dynamiques fonctions/métiers.
 *           Utilisé dans les formulaires utilisateur (L6/L8) et l'affectation.
 *
 * Dépendances : ajax.js (AUTOSAV), jQuery, Select2
 */

'use strict';

const AutosavFJ = (function () {

    // ============================================================
    // CHARGEMENT DES FONCTIONS
    // ============================================================

    /**
     * Charge la liste des fonctions actives pour un sélecteur.
     *
     * @param {string|HTMLElement} selector  Sélecteur CSS ou élément DOM
     * @param {object}             opts      Options : companyId, format
     * @returns {Promise<Array>}
     */
    async function loadFunctions(selector, opts = {}) {
        const params = new URLSearchParams();
        if (opts.companyId) params.append('company_id', opts.companyId);
        if (opts.format)    params.append('format', opts.format);

        const url = '/ajax/functions/list?' + params.toString();

        try {
            const resp = await AutosavAjax.get(url);
            if (!resp.success) {
                console.warn('[AutosavFJ] loadFunctions: ' + resp.message);
                return [];
            }
            _populateSelect(selector, resp.data, 'fnc_id', 'fnc_label');
            return resp.data;
        } catch (e) {
            console.error('[AutosavFJ] loadFunctions error:', e);
            return [];
        }
    }

    /**
     * Initialise Select2 sur un champ fonctions avec recherche AJAX.
     *
     * @param {string} selector  Sélecteur CSS du <select>
     * @param {object} opts      companyId, placeholder, multiple
     */
    function initFunctionSelect2(selector, opts = {}) {
        const $el = $(selector);
        if (!$el.length || typeof $.fn.select2 === 'undefined') return;

        $el.select2({
            placeholder: opts.placeholder || 'Sélectionner une ou plusieurs fonctions…',
            allowClear: true,
            multiple:   opts.multiple !== false,
            language:   'fr',
            ajax: {
                url: '/ajax/functions/search',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q:          params.term || '',
                        company_id: opts.companyId || '',
                    };
                },
                processResults: function (data) {
                    if (!data.success) return { results: [] };
                    return { results: data.data.results };
                },
            },
        });
    }

    // ============================================================
    // CHARGEMENT DES MÉTIERS
    // ============================================================

    /**
     * Charge la liste des métiers actifs filtrés par type d'entreprise.
     *
     * @param {string|HTMLElement} selector
     * @param {object}             opts     companyTypeId, companyId, format
     * @returns {Promise<Array>}
     */
    async function loadJobs(selector, opts = {}) {
        const params = new URLSearchParams();
        if (opts.companyTypeId) params.append('company_type_id', opts.companyTypeId);
        if (opts.companyId)     params.append('company_id', opts.companyId);
        if (opts.format)        params.append('format', opts.format);

        const url = '/ajax/jobs/list?' + params.toString();

        try {
            const resp = await AutosavAjax.get(url);
            if (!resp.success) {
                console.warn('[AutosavFJ] loadJobs: ' + resp.message);
                return [];
            }
            _populateSelect(selector, resp.data, 'job_id', 'job_label');
            return resp.data;
        } catch (e) {
            console.error('[AutosavFJ] loadJobs error:', e);
            return [];
        }
    }

    /**
     * Recharge les métiers quand l'entreprise active change.
     * À connecter sur l'événement de changement de contexte.
     *
     * @param {number} companyId     ID de la nouvelle entreprise active
     * @param {string} targetSelect  Sélecteur CSS du <select> métiers
     */
    async function reloadJobsForCompany(companyId, targetSelect) {
        const params = new URLSearchParams({ company_id: companyId });
        const url    = '/ajax/jobs/by-company-type?' + params.toString();

        try {
            const resp = await AutosavAjax.get(url);
            if (!resp.success) return;

            _populateSelect(targetSelect, resp.data.results, 'id', 'text');
        } catch (e) {
            console.error('[AutosavFJ] reloadJobsForCompany error:', e);
        }
    }

    /**
     * Initialise Select2 sur un champ métiers avec recherche AJAX.
     *
     * @param {string} selector
     * @param {object} opts     companyTypeId, placeholder, multiple
     */
    function initJobSelect2(selector, opts = {}) {
        const $el = $(selector);
        if (!$el.length || typeof $.fn.select2 === 'undefined') return;

        $el.select2({
            placeholder: opts.placeholder || 'Sélectionner un ou plusieurs métiers…',
            allowClear: true,
            multiple:   opts.multiple !== false,
            language:   'fr',
            ajax: {
                url: '/ajax/jobs/search',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q:               params.term || '',
                        company_type_id: opts.companyTypeId || '',
                    };
                },
                processResults: function (data) {
                    if (!data.success) return { results: [] };
                    return { results: data.data.results };
                },
            },
        });
    }

    // ============================================================
    // ASSIGNATION AJAX (dans les fiches utilisateurs)
    // ============================================================

    /**
     * Assigne une fonction à un utilisateur via AJAX.
     *
     * @param {number} userId
     * @param {number} functionId
     * @param {object} opts     isPrimary, companyId
     * @returns {Promise<boolean>}
     */
    async function assignFunction(userId, functionId, opts = {}) {
        const body = {
            function_id: functionId,
            is_primary:  opts.isPrimary ? 1 : 0,
        };
        if (opts.companyId) body.company_id = opts.companyId;

        try {
            const resp = await AutosavAjax.post(`/ajax/users/${userId}/assign-function`, body);
            if (!resp.success) {
                AutosavAlert.error(resp.message || 'Erreur d\'assignation.');
                return false;
            }
            return true;
        } catch (e) {
            console.error('[AutosavFJ] assignFunction error:', e);
            return false;
        }
    }

    /**
     * Retire une fonction d'un utilisateur via AJAX.
     */
    async function unassignFunction(userId, functionId) {
        try {
            const resp = await AutosavAjax.post(`/ajax/users/${userId}/unassign-function`, {
                function_id: functionId,
            });
            if (!resp.success) {
                AutosavAlert.error(resp.message || 'Erreur.');
                return false;
            }
            return true;
        } catch (e) {
            console.error('[AutosavFJ] unassignFunction error:', e);
            return false;
        }
    }

    /**
     * Synchronise les fonctions d'un utilisateur (remplace tout).
     *
     * @param {number}   userId
     * @param {number[]} functionIds
     * @param {number}   primaryFunctionId
     */
    async function syncFunctions(userId, functionIds, primaryFunctionId) {
        const body = {
            function_ids:       functionIds,
            primary_function_id: primaryFunctionId,
        };
        try {
            const resp = await AutosavAjax.post(`/ajax/users/${userId}/sync-functions`, body);
            if (!resp.success) {
                AutosavAlert.error(resp.message || 'Erreur de synchronisation.');
                return false;
            }
            return true;
        } catch (e) {
            console.error('[AutosavFJ] syncFunctions error:', e);
            return false;
        }
    }

    /**
     * Synchronise les métiers d'un utilisateur.
     */
    async function syncJobs(userId, jobIds, primaryJobId) {
        const body = {
            job_ids:      jobIds,
            primary_job_id: primaryJobId,
        };
        try {
            const resp = await AutosavAjax.post(`/ajax/users/${userId}/sync-jobs`, body);
            if (!resp.success) {
                AutosavAlert.error(resp.message || 'Erreur de synchronisation.');
                return false;
            }
            return true;
        } catch (e) {
            console.error('[AutosavFJ] syncJobs error:', e);
            return false;
        }
    }

    // ============================================================
    // UTILITAIRES INTERNES
    // ============================================================

    /**
     * Remplit un <select> avec des données.
     *
     * @param {string|HTMLElement} selector
     * @param {Array}  data       Tableau d'objets
     * @param {string} valueKey   Clé pour la valeur
     * @param {string} textKey    Clé pour le texte
     */
    function _populateSelect(selector, data, valueKey, textKey) {
        const el = typeof selector === 'string'
            ? document.querySelector(selector)
            : selector;

        if (!el) return;

        // Conserver la première option vide si présente
        const firstOpt  = el.options.length > 0 && el.options[0].value === '' ? el.options[0] : null;
        el.innerHTML = '';

        if (firstOpt) el.appendChild(firstOpt.cloneNode(true));

        data.forEach(function (item) {
            const opt   = document.createElement('option');
            opt.value   = item[valueKey];
            opt.textContent = item[textKey];
            el.appendChild(opt);
        });
    }

    // ============================================================
    // API PUBLIQUE
    // ============================================================
    return {
        loadFunctions,
        loadJobs,
        reloadJobsForCompany,
        initFunctionSelect2,
        initJobSelect2,
        assignFunction,
        unassignFunction,
        syncFunctions,
        syncJobs,
    };

})();
