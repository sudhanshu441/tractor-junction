/* Krishi Junction — finance: EMI calculator and the loan application wizard. */
(function ($) {
    'use strict';

    function rupees(value) {
        return '₹' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
    }

    // ---------------- EMI calculator ----------------
    KJ.initEmi = function (opts) {
        var timer = null;

        function recalc() {
            var price = parseFloat($('#price').val()) || 0;
            var down = parseFloat($('#down_payment').val()) || 0;

            if (down > price) {
                down = price;
                $('#down_payment').val(down);
            }

            KJ.request({
                url: opts.url, method: 'POST',
                data: {
                    price: price,
                    down_payment: down,
                    rate: $('#rate').val(),
                    tenure: $('#tenure').val(),
                    frequency: $('#frequency').val(),
                    product_id: $('#product_id').val() || null,
                },
            }).then(function (res) {
                if (res.status !== 'ok') {
                    return KJ.toast(res.message || 'Check the values and try again.', 'warning');
                }

                var d = res.data;
                $('#emi-value').text(rupees(d.emi));
                $('#emi-caption').text('over ' + d.instalments + ' instalment' + (d.instalments === 1 ? '' : 's'));
                $('#loan-amount').text(rupees(d.loan_amount));
                $('#total-interest').text(rupees(d.total_interest));
                $('#total-payable').text(rupees(d.total_payable));
                $('#down-shown').text(rupees(down));

                var rows = (d.schedule || []).map(function (r) {
                    return '<tr><td class="mono">' + r.year + '</td>' +
                        '<td class="num mono">' + Number(r.principal).toLocaleString('en-IN', { maximumFractionDigits: 0 }) + '</td>' +
                        '<td class="num mono">' + Number(r.interest).toLocaleString('en-IN', { maximumFractionDigits: 0 }) + '</td>' +
                        '<td class="num mono">' + Number(r.balance).toLocaleString('en-IN', { maximumFractionDigits: 0 }) + '</td></tr>';
                }).join('');

                $('#schedule-table tbody').html(rows);
                $('#share-emi').data('url', d.share_url);
            });
        }

        function debounced() {
            clearTimeout(timer);
            timer = setTimeout(recalc, 350);
        }

        $('.js-emi-input').on('input', function () {
            // Keep the percentage slider in step when the rupee amount is typed.
            if (this.id === 'down_payment' || this.id === 'price') {
                var price = parseFloat($('#price').val()) || 1;
                var down = parseFloat($('#down_payment').val()) || 0;
                $('#down_range').val(Math.min(100, Math.round(down / price * 100)));
            }
            debounced();
        });

        $('#down_range').on('input', function () {
            var price = parseFloat($('#price').val()) || 0;
            $('#down_payment').val(Math.round(price * $(this).val() / 100));
            debounced();
        });

        $('.js-tenure').on('click', function () {
            $('.js-tenure').removeClass('btn-primary').addClass('btn-outline-primary');
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            $('#tenure').val($(this).data('value'));
            recalc();
        });

        $('.js-frequency').on('click', function () {
            $('.js-frequency').removeClass('btn-primary').addClass('btn-outline-primary');
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            $('#frequency').val($(this).data('value'));
            recalc();
        });

        $('#share-emi').on('click', function () {
            var url = $(this).data('url') || window.location.href;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    KJ.toast('Link copied — share it on WhatsApp.', 'success');
                });
            } else {
                window.prompt('Copy this link', url);
            }
        });
    };

    // ---------------- loan application wizard ----------------
    KJ.initLoanWizard = function (opts) {
        var step = 1;

        function show(n) {
            step = n;
            $('.kj-wizard-step').addClass('d-none').filter('[data-step="' + n + '"]').removeClass('d-none');
            $('.kj-step').removeClass('is-current is-done').each(function () {
                var i = parseInt($(this).data('step'), 10);
                if (i < n) { $(this).addClass('is-done'); }
                if (i === n) { $(this).addClass('is-current'); }
            });
            $('html, body').animate({ scrollTop: 0 }, 200);
        }

        function save(n, payload) {
            $('#draft-status').text('Saving…');

            return KJ.request({ url: opts.stepUrl + '/' + n, method: 'POST', data: payload })
                .then(function (res) {
                    if (res.status !== 'ok') {
                        KJ.showErrors($('#loan-form'), res.errors);
                        KJ.toast(res.message || 'Please check the highlighted fields.', 'danger');
                        return null;
                    }
                    $('#draft-status').text('Saved — you can come back to this.');
                    return res.data;
                });
        }

        $('#check-eligibility').on('click', function () {
            KJ.request({
                url: opts.eligibilityUrl, method: 'POST',
                data: {
                    annual_income: $('#annual_income_quick').val(),
                    machinery_price: $('#machinery_price_quick').val(),
                    down_payment: $('#down_payment_quick').val(),
                },
            }).then(function (res) {
                if (res.status !== 'ok') { return KJ.toast(res.message, 'danger'); }

                var d = res.data;
                var html = d.eligible
                    ? '<div class="alert alert-secondary small mb-0"><b>Looks workable.</b> On this income, lenders would typically consider up to ' + rupees(d.max_loan) + '.</div>'
                    : '<div class="alert alert-warning small mb-0"><b>This may be difficult:</b><ul class="mb-0">' +
                        d.reasons.map(function (r) { return '<li>' + r + '</li>'; }).join('') + '</ul></div>';

                $('#eligibility-result').html(html);
            });
        });

        $('.js-loan-next').on('click', function () {
            var n = parseInt($(this).data('step'), 10);
            var payload = collect(n);

            if (payload === null) { return; }

            save(n, payload).then(function (data) {
                if (!data) { return; }
                if (data.emi) { $('#running-emi').text(rupees(data.emi)); }
                if (data.loan_amount) { $('#running-loan').text(rupees(data.loan_amount)); }
                show(n + 1);
            });
        });

        $('.js-loan-back').on('click', function () { show(Math.max(1, step - 1)); });

        function collect(n) {
            var form = $('#loan-form');

            if (n === 1) {
                return {
                    applicant_name: $('#applicant_name').val(),
                    mobile: $('#mobile').val(),
                    email: $('#email').val() || null,
                    date_of_birth: $('#date_of_birth').val() || null,
                    purpose: $('#purpose').val(),
                };
            }

            if (n === 2) {
                return {
                    machinery_price: $('#machinery_price').val(),
                    down_payment: $('#down_payment2').val(),
                    tenure_months: $('#tenure_months').val(),
                    expected_interest_rate: $('#expected_interest_rate').val(),
                };
            }

            if (n === 3) {
                return {
                    annual_income: $('#annual_income').val(),
                    income_source: $('#income_source').val(),
                    land_holding_acres: $('#land_holding_acres').val() || null,
                    state_id: $('#state_id').val(),
                    district_id: $('#district_id').val(),
                    city_id: $('#city_id').val() || null,
                    address: $('#address').val() || null,
                    pan: $('#pan').val() || null,
                    aadhaar: $('#aadhaar').val() || null,
                };
            }

            return {};
        }

        // Documents upload one at a time, like the sell wizard's photos.
        $('#document-input').on('change', function () {
            var file = this.files && this.files[0];
            if (!file) { return; }

            var form = new FormData();
            form.append('document', file);
            form.append('doc_type', $('#doc-type').val());

            $('#doc-status').text('Uploading…');

            $.ajax({ url: opts.documentUrl, method: 'POST', data: form, processData: false, contentType: false })
                .done(function (res) {
                    $('#doc-' + $('#doc-type').val()).removeClass('badge-muted').addClass('badge-ok').text('Uploaded');
                    renderMissing(res.data.missing_documents);
                    KJ.toast(res.message, 'success');
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'That file could not be uploaded.';
                    KJ.toast(msg, 'danger');
                })
                .always(function () {
                    $('#document-input').val('');
                    $('#doc-status').text('');
                });
        });

        function renderMissing(missing) {
            if (!missing || !missing.length) {
                return $('#missing-docs').html('<span class="badge badge-ok">All required documents uploaded</span>');
            }
            $('#missing-docs').html('<span class="badge badge-warn">Still needed: ' +
                missing.join(', ').replace(/_/g, ' ') + '</span>');
        }

        $('#loan-send-otp').on('click', function () {
            var mobile = $('#confirm_mobile').val();

            if (!/^[6-9]\d{9}$/.test(mobile)) {
                return KJ.toast('Enter a valid 10-digit mobile number.', 'warning');
            }

            KJ.request({ url: opts.otpUrl, method: 'POST', data: { mobile: mobile } }).then(function (res) {
                if (res.status !== 'ok') { return KJ.toast(res.message, 'danger'); }
                $('#loan-otp-wrap').removeClass('d-none');
                $('#loan-send-otp').addClass('d-none');
                $('#loan-submit').removeClass('d-none');
                $('#loan_otp').trigger('focus');
                KJ.toast(res.message, 'success');
            });
        });

        $('#loan-submit').on('click', function () {
            var $btn = $(this).prop('disabled', true);

            KJ.request({
                url: opts.submitUrl, method: 'POST',
                data: {
                    name: $('#confirm_name').val(),
                    mobile: $('#confirm_mobile').val(),
                    otp: $('#loan_otp').val(),
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
    };
})(jQuery);
