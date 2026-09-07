/* Krishi Junction — used marketplace: sell wizard, contact reveal, reporting. */
(function ($) {
    'use strict';

    // ---------------- sell wizard ----------------
    KJ.initSellWizard = function (opts) {
        var state = { step: 1, categoryId: null, photos: 0 };

        function show(step) {
            state.step = step;
            $('.kj-wizard-step').addClass('d-none').filter('[data-step="' + step + '"]').removeClass('d-none');
            $('.kj-step').removeClass('is-current is-done');
            $('.kj-step').each(function () {
                var n = parseInt($(this).data('step'), 10);
                if (n < step) { $(this).addClass('is-done'); }
                if (n === step) { $(this).addClass('is-current'); }
            });
            $('html, body').animate({ scrollTop: 0 }, 200);
        }

        function saveStep(step, payload) {
            $('#draft-status').text('Saving…');

            return KJ.request({
                url: opts.stepUrl + '/' + step,
                method: 'POST',
                data: payload,
            }).then(function (res) {
                if (res.status !== 'ok') {
                    KJ.toast(res.message || 'Please check the highlighted fields.', 'danger');
                    $('#draft-status').text('Not saved — please try again.');
                    return null;
                }
                $('#draft-status').text('Saved as draft — you can come back to this.');
                return res.data;
            });
        }

        // Step 1
        $('.js-pick-category').on('click', function () {
            state.categoryId = $(this).data('id');
            $('.js-pick-category').removeClass('btn-primary').addClass('btn-outline-primary');
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');

            saveStep(1, { category_id: state.categoryId }).then(function (data) {
                if (data) { show(2); }
            });
        });

        // Step 2 — models depend on brand
        $('#brand_id').on('change', function () {
            var brandId = $(this).val();
            var $model = $('#product_id').prop('disabled', true).empty()
                .append($('<option>').val('').text('Loading…'));

            if (!brandId) { return $model.empty().append($('<option>').val('').text('Select brand first')); }

            KJ.request({ url: opts.modelsUrl, data: { brand_id: brandId, category_id: state.categoryId } })
                .then(function (res) {
                    $model.empty().append($('<option>').val('').text('Select model (optional)'));
                    (res.data || []).forEach(function (m) {
                        $model.append($('<option>').val(m.id).text(m.name + (m.hp_min ? ' — ' + m.hp_min + ' HP' : '')));
                    });
                    $model.prop('disabled', false);
                });
        });

        $('.js-pick-condition').on('click', function () {
            $('.js-pick-condition').removeClass('btn-primary').addClass('btn-outline-primary');
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        });

        $('.js-back').on('click', function () { show(Math.max(1, state.step - 1)); });

        $('.js-next').on('click', function () {
            var step = parseInt($(this).data('step'), 10);
            var payload = collect(step);

            if (payload === null) { return; }

            if (step === 4) {
                if (state.photos < opts.minPhotos) {
                    return KJ.toast('Add at least ' + opts.minPhotos + ' photos so buyers can see the machine.', 'warning');
                }
                return show(5);
            }

            saveStep(step, payload).then(function (data) {
                if (!data) { return; }
                if (data.valuation) { showValuation(data.valuation); }
                show(step + 1);
            });
        });

        function collect(step) {
            if (step === 2) {
                if (!$('#brand_id').val() || !$('#manufacturing_year').val()) {
                    KJ.toast('Choose a brand and enter the year.', 'warning');
                    return null;
                }
                return {
                    brand_id: $('#brand_id').val(),
                    product_id: $('#product_id').val() || null,
                    manufacturing_year: $('#manufacturing_year').val(),
                };
            }

            if (step === 3) {
                var condition = $('.js-pick-condition.btn-primary').data('value');
                if (!condition) {
                    KJ.toast('Tell buyers the overall condition.', 'warning');
                    return null;
                }
                return {
                    engine_hours: $('#engine_hours').val() || null,
                    condition: condition,
                    tyre_condition_front: $('#tyre_front').val() || null,
                    tyre_condition_rear: $('#tyre_rear').val() || null,
                    has_rc: $('#has_rc').is(':checked') ? 1 : 0,
                    has_insurance: $('#has_insurance').is(':checked') ? 1 : 0,
                    is_financed: $('#is_financed').is(':checked') ? 1 : 0,
                };
            }

            if (step === 5) {
                if (!$('#expected_price').val() || !$('#state_id').val() || !$('#district_id').val()) {
                    KJ.toast('Enter your price and where the machine is.', 'warning');
                    return null;
                }
                return {
                    expected_price: $('#expected_price').val(),
                    is_price_negotiable: $('#is_price_negotiable').is(':checked') ? 1 : 0,
                    description: $('#description').val() || null,
                    state_id: $('#state_id').val(),
                    district_id: $('#district_id').val(),
                    city_id: $('#city_id').val() || null,
                };
            }

            return {};
        }

        function showValuation(v) {
            $('#valuation-hint').html(
                'Similar machines sell for <b>₹' + Number(v.min).toLocaleString('en-IN') +
                ' – ₹' + Number(v.max).toLocaleString('en-IN') + '</b>.'
            );
        }

        // Step 4 — one photo per request
        $('#photo-input').on('change', function () {
            var file = this.files && this.files[0];
            if (!file) { return; }

            var form = new FormData();
            form.append('photo', file);
            form.append('angle', $('#photo-angle').val());

            $('#photo-count').text('Uploading…');

            $.ajax({
                url: opts.photoUrl, method: 'POST', data: form,
                processData: false, contentType: false,
            }).done(function (res) {
                state.photos = res.data.count;
                $('#photo-grid').append(
                    $('<div class="col-4 col-md-3">').append(
                        $('<img class="img-fluid rounded border">').attr('src', res.data.url)
                    )
                );
                updatePhotoCount();
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'That photo could not be uploaded.';
                KJ.toast(msg, 'danger');
                updatePhotoCount();
            }).always(function () {
                $('#photo-input').val('');
            });
        });

        function updatePhotoCount() {
            var need = Math.max(0, opts.minPhotos - state.photos);
            $('#photo-count').text(state.photos + ' of ' + opts.maxPhotos + ' added' +
                (need > 0 ? ' — ' + need + ' more needed' : ' — ready'));
        }

        // Step 6 — verify then submit
        $('#seller-send-otp').on('click', function () {
            var mobile = $('#seller_mobile').val();

            if (!/^[6-9]\d{9}$/.test(mobile)) {
                return KJ.toast('Enter a valid 10-digit mobile number.', 'warning');
            }

            KJ.request({ url: opts.otpUrl, method: 'POST', data: { mobile: mobile } }).then(function (res) {
                if (res.status !== 'ok') { return KJ.toast(res.message, 'danger'); }
                $('#seller-otp-wrap').removeClass('d-none');
                $('#seller-send-otp').addClass('d-none');
                $('#seller-submit').removeClass('d-none');
                $('#seller_otp').trigger('focus');
                KJ.toast(res.message, 'success');
            });
        });

        $('#seller-submit').on('click', function () {
            var $btn = $(this).prop('disabled', true);

            KJ.request({
                url: opts.submitUrl, method: 'POST',
                data: {
                    name: $('#seller_name').val(),
                    mobile: $('#seller_mobile').val(),
                    otp: $('#seller_otp').val(),
                },
            }).then(function (res) {
                if (res.status !== 'ok') {
                    $btn.prop('disabled', false);
                    return KJ.toast(res.message, 'danger');
                }
                window.location = res.data.redirect;
            });
        });

        show(1);
        updatePhotoCount();
    };

    // ---------------- listing detail ----------------
    KJ.initListingDetail = function (opts) {
        $('.js-listing-thumb').on('click', function () {
            $('#listing-main-image').attr('src', $(this).data('full'));
        });

        $('#lead-send-otp').on('click', function () {
            var mobile = $('#lead-mobile').val();

            if (!/^[6-9]\d{9}$/.test(mobile)) {
                return KJ.toast('Enter a valid 10-digit mobile number.', 'warning');
            }
            if (!$('#lead-name').val()) {
                return KJ.toast('Tell the seller your name.', 'warning');
            }

            KJ.request({ url: opts.otpUrl, method: 'POST', data: { mobile: mobile } }).then(function (res) {
                if (res.status !== 'ok') { return KJ.toast(res.message, 'danger'); }
                $('#lead-otp-wrap').removeClass('d-none');
                $('#lead-send-otp').addClass('d-none');
                $('#lead-verify').removeClass('d-none');
                $('#lead-otp').trigger('focus');
                KJ.toast(res.message, 'success');
            });
        });

        $('#lead-verify').on('click', function () {
            var $btn = $(this).prop('disabled', true);

            KJ.request({
                url: opts.revealUrl, method: 'POST',
                data: {
                    name: $('#lead-name').val(),
                    mobile: $('#lead-mobile').val(),
                    otp: $('#lead-otp').val(),
                },
            }).then(function (res) {
                $btn.prop('disabled', false);

                if (res.status !== 'ok') { return KJ.toast(res.message, 'danger'); }

                $('#seller-mobile').text(res.data.mobile || '—');
                $('#seller-name').text(res.data.seller_name || '');
                $('#reveal-form').addClass('d-none');
                $('#reveal-result').removeClass('d-none');
                KJ.toast(res.message, 'success');
            });
        });

        $('#report-submit').on('click', function () {
            KJ.request({
                url: opts.reportUrl, method: 'POST',
                data: { reason: $('#report-reason').val(), details: $('#report-details').val() },
            }).then(function (res) {
                bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
                KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            });
        });
    };
})(jQuery);
