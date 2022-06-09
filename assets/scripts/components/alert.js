import $ from 'jquery';

$(document).ready(function () {
    $(".js-alert").fadeTo(10000, 0, function () {
        $(this).remove();
    });
});


