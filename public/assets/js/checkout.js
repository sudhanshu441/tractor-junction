/* Krishi Junction — plan and boost checkout.
 *
 * The server opens the order and tells us how to collect the money. In test
 * mode (no gateway keys) there is no widget to open, so the confirm call is
 * made straight away and the flow can be exercised end to end without keys.
 */
(function ($) {
    'use strict';

    /**
     * @param {string} selector buttons carrying data-url (checkout) and data-name
     * @param {string} description shown inside the gateway widget
     */
    KJ.initCheckout = function (selector, description) {
        $(selector).on('click', async function () {
            var button = $(this).prop('disabled', true);

            var response = await KJ.request({
                url: button.data('url'),
                method: 'POST',
            });

            if (response.status !== 'ok') {
                button.prop('disabled', false);
                return KJ.toast(response.message, 'danger');
            }

            // A free plan is granted server-side; there is nothing to pay.
            if (response.data && response.data.paid) {
                KJ.toast(response.message, 'success');
                return setTimeout(function () { window.location.reload(); }, 700);
            }

            var data = response.data || {};
            var checkout = data.checkout || {};

            if (checkout.test_mode || !window.Razorpay || !data.key_id) {
                // No live gateway configured: confirm directly against our own
                // endpoint, which the Log driver accepts.
                return confirmPayment(data.confirm_url, {
                    payment_id: checkout.order_id,
                    order_id: checkout.order_id,
                }, button);
            }

            var rzp = new window.Razorpay({
                key: data.key_id,
                order_id: checkout.order_id,
                amount: Math.round((checkout.amount || 0) * 100),
                currency: checkout.currency || 'INR',
                name: 'Krishi Junction',
                description: description,
                handler: function (result) {
                    confirmPayment(data.confirm_url, result, button);
                },
                modal: {
                    ondismiss: function () {
                        button.prop('disabled', false);
                        KJ.toast('Payment cancelled. Nothing has been charged.', 'dark');
                    },
                },
            });

            rzp.open();
        });
    };

    async function confirmPayment(url, payload, button) {
        var response = await KJ.request({ url: url, method: 'POST', data: payload });

        if (response.status === 'ok') {
            KJ.toast(response.message, 'success');
            return setTimeout(function () { window.location.reload(); }, 700);
        }

        button.prop('disabled', false);
        KJ.toast(response.message, 'danger');
    }
})(jQuery);
