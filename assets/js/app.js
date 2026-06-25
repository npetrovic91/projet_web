/**
 * AUTOSAV — JS personnalisé global
 * Fichier : public/assets/js/app.js
 */
'use strict';

$(document).ready(function() {
    // Initialisation DataTables globale
    if ($.fn.DataTable) {
        $('.datatable-auto').DataTable({
            language: {
                url: '', // Pas de CDN — fallback textes anglais
                search: 'Rechercher :',
                lengthMenu: 'Afficher _MENU_ éléments',
                info: 'Affichage de _START_ à _END_ sur _TOTAL_ éléments',
                paginate: { first:'«', last:'»', next:'›', previous:'‹' },
                zeroRecords: 'Aucun résultat trouvé.',
                emptyTable: 'Aucune donnée disponible.',
            },
            pageLength: 25,
            responsive: true,
        });
    }

    // Confirmation de suppression globale
    $(document).on('submit', 'form[data-confirm]', function(e) {
        e.preventDefault();
        const form = this;
        const msg  = form.dataset.confirm || 'Êtes-vous sûr(e) de vouloir effectuer cette action ?';
        Swal.fire({
            title: 'Confirmation',
            text:  msg,
            icon:  'warning',
            showCancelButton:  true,
            confirmButtonText: 'Confirmer',
            cancelButtonText:  'Annuler',
            confirmButtonColor:'#d33',
        }).then(result => {
            if (result.isConfirmed) form.submit();
        });
    });

    // Auto-dismiss alerts Bootstrap après 5s
    setTimeout(() => {
        $('.alert-dismissible:not(.alert-permanent)').fadeOut(500);
    }, 5000);
});
