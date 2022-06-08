import $ from "jquery";
import ajaxModal from "../components/modal/ajax_modal";

$(document).ready(function () {

    $('.js-task-delete').on('click', function () {
        let target = this;
        ajaxModal(target, 'task__modal');
    });

});