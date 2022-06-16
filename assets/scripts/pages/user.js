import $ from "jquery";
import 'datatables.net-bs';
import ajaxModal from "../components/modal/ajax_modal";

$(document).ready(function () {

    // Table des utilisateurs
    $('#user-table').DataTable({
        "order": [[0, "asc"]],
        "search": [[1, 2]],
        "keys": true,
        "columnDefs": [
        {
            "targets": 4,
            "sortable": false
        },
        ],
        "columns": [
            { "searchable": false },
            null,
            null,
            { "searchable": false },
            { "searchable": false },
        ],
        "drawCallback": function () {
            $('.js-user-delete').on('click', function () {
                let target = this;
                ajaxModal(target, 'user__modal');
            });
        },
        "language": {
            "sEmptyTable": "Aucune donnée disponible dans le tableau",
            "sInfo": "Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",
            "sInfoEmpty": "Affichage de l'élément 0 à 0 sur 0 élément",
            "sInfoFiltered": "(filtré à partir de _MAX_ éléments au total)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sLengthMenu": "Afficher _MENU_ éléments",
            "sLoadingRecords": "Chargement...",
            "sProcessing": "Traitement...",
            "sSearch": "Rechercher :",
            "sZeroRecords": "Aucun élément correspondant trouvé",
            "oPaginate": {
                "sFirst": "Premier",
                "sLast": "Dernier",
                "sNext": "Suivant",
                "sPrevious": "Précédent"
            },
            "oAria": {
                "sSortAscending": ": activer pour trier la colonne par ordre croissant",
                "sSortDescending": ": activer pour trier la colonne par ordre décroissant"
            },
            "select": {
                "rows": {
                    "_": "%d lignes sélectionnées",
                    "0": "Aucune ligne sélectionnée",
                    "1": "1 ligne sélectionnée"
                }
            }
        }
    });
});