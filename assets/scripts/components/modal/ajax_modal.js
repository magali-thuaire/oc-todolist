import $ from "jquery";
import empty_modal from "./empty_modal";

// Appel AJAX et intégration d'une modale
export default function (target, modal_div) {
    // Récupère l'url depuis la propriété "data-href" de la balise html a
    let url = $(target).data('href');
    // Appel ajax vers l'action symfony qui nous renvoie la vue
    return $.ajax({
        method: 'GET',
        url: url
    }).done(function (data) {
        // Injecte le html dans la modale
        $('#' + modal_div).html(data);
        // Récupère l'id de la modale
        let modal_id = $('.modal').attr('id');
        // Ouvre la modale
        $('#' + modal_id).modal('show');
        empty_modal(modal_div);
    });
}