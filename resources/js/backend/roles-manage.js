import $ from 'jquery';

$(document).ready(function () {
    $('.select2').select2({
        placeholder: 'Select options',
        allowClear: true
    });

    const $userSelect = $('#user_id');
    const routeUrl = $userSelect.data('route-users');

    $userSelect.on('change', function () {
        const userId = $(this).val();
        if (userId) {
            $.ajax({
                url: routeUrl + '/' + userId + '/roles-permissions',
                type: 'GET',
                success: function (data) {
                    $('#roles').val(data.roles).trigger('change');
                    $('#permissions').val(data.permissions).trigger('change');
                },
                error: function () {
                    alert('Failed to load user roles and permissions.');
                }
            });
        } else {
            $('#roles').val([]).trigger('change');
            $('#permissions').val([]).trigger('change');
        }
    });
});
