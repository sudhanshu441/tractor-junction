/* Krishi Junction — shared front-end behaviour (Bootstrap 5 + jQuery) */
(function ($) {
    'use strict';

    // Every AJAX request carries the CSRF token; no per-call boilerplate.
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    });

    window.KJ = {
        /**
         * Standard envelope: {status, message, data, errors}
         * Always resolves so callers handle failure inline instead of in a catch.
         */
        request: function (options) {
            return $.ajax(options)
                .then(function (res) { return res; })
                .catch(function (xhr) {
                    var res = (xhr.responseJSON) || {};
                    return {
                        status: 'error',
                        message: res.message || 'Something went wrong. Please try again.',
                        errors: res.errors || {},
                        httpStatus: xhr.status,
                    };
                });
        },

        toast: function (message, variant) {
            var el = $(
                '<div class="toast align-items-center border-0 text-bg-' + (variant || 'dark') + '" role="status" aria-live="polite">' +
                '<div class="d-flex"><div class="toast-body"></div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                '</div></div>'
            );
            el.find('.toast-body').text(message);

            var host = $('#kj-toasts');
            if (!host.length) {
                host = $('<div id="kj-toasts" class="toast-container position-fixed bottom-0 end-0 p-3"></div>').appendTo('body');
            }
            host.append(el);
            new bootstrap.Toast(el[0], { delay: 4000 }).show();
        },

        /** Paints Laravel validation errors onto the matching fields. */
        showErrors: function (form, errors) {
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.invalid-feedback.js-error').remove();

            $.each(errors || {}, function (field, messages) {
                var input = $(form).find('[name="' + field + '"]').addClass('is-invalid');
                input.after('<div class="invalid-feedback js-error d-block">' + messages[0] + '</div>');
            });
        },

        /** state → district → city dependent selects. */
        bindGeoSelects: function (stateSel, districtSel, citySel) {
            var $state = $(stateSel), $district = $(districtSel), $city = $(citySel);

            function fill($select, rows, placeholder) {
                $select.empty().append($('<option>').val('').text(placeholder));
                $.each(rows, function (_, row) {
                    $select.append($('<option>').val(row.id).text(row.name));
                });
                $select.prop('disabled', rows.length === 0);
            }

            $state.on('change', function () {
                var id = $(this).val();
                fill($district, [], 'Loading…');
                fill($city, [], 'Select district first');
                if (!id) { return fill($district, [], 'Select state first'); }

                KJ.request({ url: '/ajax/geo/states/' + id + '/districts' }).then(function (res) {
                    fill($district, res.data || [], 'Select district');
                });
            });

            $district.on('change', function () {
                var id = $(this).val();
                if (!id) { return fill($city, [], 'Select district first'); }

                KJ.request({ url: '/ajax/geo/districts/' + id + '/cities' }).then(function (res) {
                    fill($city, res.data || [], 'Select city');
                });
            });
        },
    };

    $(function () {
        $('[data-bs-toggle="tooltip"]').each(function () { new bootstrap.Tooltip(this); });
        $('.js-geo').each(function () {
            KJ.bindGeoSelects($(this).find('.js-state'), $(this).find('.js-district'), $(this).find('.js-city'));
        });
    });
})(jQuery);
