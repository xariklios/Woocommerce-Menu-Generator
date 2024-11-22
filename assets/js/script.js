(function ($) {
    $(document).ready(function () {
        /* Delete Menu */
        $('.delete-menu').on('click', function () {

            let menu_id = $(this).data('menu-id');

            jQuery.ajax({
                url: wmg_ajax.ajaxurl,
                type: "post",
                data: {
                    action: "delete_menu",
                    menu_id: menu_id,
                    nonce_ajax: wmg_ajax.nonce
                },
                dataType: "json",
                success: function (response) {
                    if (response.success === true) {
                        alert(response.data)
                        location.reload();
                    }
                },
                error: function (request, status, error) {
                    alert(request.responseJSON.data)
                }
            });
        });

        /* Update Menu */
        $('.update-menu').on('click', function () {

            let menu_id = $(this).data('menu-id');

            jQuery.ajax({
                url: wmg_ajax.ajaxurl,
                type: "post",
                data: {
                    action: "update_menu",
                    menu_id: menu_id,
                    nonce_ajax: wmg_ajax.nonce
                },
                dataType: "json",
                success: function (response) {
                    if (response.success === true) {
                        alert(response.data)
                        location.reload();
                    }
                },
                error: function (request, status, error) {
                    alert(request.responseJSON.data)
                }
            });
        });

        /* Generate Menu */
        $('#wmg_submit').on('click', function (e) {
            e.preventDefault();

            let menu_name = $('#wmg_menu_name').val();
            let skip_empty = $('#wmg_skip_empty').is(":checked")

            jQuery.ajax({
                url: wmg_ajax.ajaxurl,
                type: "post",
                data: {
                    action: "generate_menu",
                    menu_name: menu_name,
                    skip_empty: skip_empty,
                    nonce_ajax: wmg_ajax.nonce
                },
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        alert(response.data)
                        location.reload();
                    }
                },
                error: function (request, status, error) {
                    alert(request.responseJSON.data)
                }
            });
        });
    });
})(jQuery)