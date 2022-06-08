import $ from 'jquery';

$(document).ready(function () {
    $(".js-alert").fadeTo(4000, 0, function () {
        $(this).remove();
    });
});


