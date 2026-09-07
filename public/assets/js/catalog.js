/* Krishi Junction — catalogue behaviour: filters, compare, gallery, type-ahead.
   Everything here is enhancement: each page already renders its results server-side. */
(function ($) {
    'use strict';

    var listing = { endpoint: null, type: 'tractor', request: null };

    function serialiseFilters($form) {
        // Only send fields that carry a value, so the URL stays readable and shareable.
        return $form.serializeArray().filter(function (field) {
            return field.value !== '' && field.value !== null;
        });
    }

    function applyFilters(push) {
        var $form = $('#filter-form');
        var params = serialiseFilters($form);

        if (listing.request) { listing.request.abort(); }

        $('#result-grid').addClass('kj-loading');

        listing.request = $.ajax({
            url: listing.endpoint,
            data: params,
            dataType: 'json',
        });

        listing.request
            .done(function (res) {
                if (res.status !== 'ok') { return; }

                $('#result-grid').html(res.data.grid).removeClass('kj-loading');
                $('#result-pagination').html(res.data.pagination);
                $('#result-summary').text(res.data.summary);

                // Repaint the facets so counts reflect the filters now applied,
                // preserving whether the panel was scrolled or expanded.
                if (res.data.facets_html) {
                    var scroll = $('#facet-panel').scrollTop();
                    $('#facet-panel').html(res.data.facets_html).scrollTop(scroll);
                }

                if (push !== false && window.history && window.history.pushState) {
                    var url = window.location.pathname + (res.data.query ? '?' + res.data.query : '');
                    window.history.pushState({ filters: res.data.query }, '', url);
                }
            })
            .fail(function (xhr, status) {
                $('#result-grid').removeClass('kj-loading');
                if (status !== 'abort') {
                    KJ.toast('Could not load results. Please try again.', 'danger');
                }
            });
    }

    var debounceTimer = null;
    function debouncedApply() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyFilters, 350);
    }

    KJ.initListing = function (options) {
        listing.endpoint = options.endpoint;
        listing.type = options.type;

        // Checkboxes and selects apply at once; typed numbers wait for a pause.
        $(document).on('change', '.js-filter-input', function () {
            if ($(this).is('input[type=number]')) { return debouncedApply(); }
            applyFilters();
        });
        $(document).on('input', 'input[type=number].js-filter-input', debouncedApply);

        $(document).on('click', '.js-band', function () {
            var $btn = $(this);
            $('#' + $btn.data('min-field')).val($btn.data('min'));
            $('#' + $btn.data('max-field')).val($btn.data('max'));
            $btn.siblings().removeClass('active');
            $btn.addClass('active');
            applyFilters();
        });

        $(document).on('click', '.js-clear-filters', function () {
            var $form = $('#filter-form');
            $form.find('input[type=checkbox]').prop('checked', false);
            $form.find('input[type=number], input[type=hidden]').not('[name=type]').val('');
            $('.js-band').removeClass('active');
            applyFilters();
        });

        $(document).on('click', '#active-chips .btn-close', function () {
            var key = $(this).data('filter-key');
            $('#filter-form').find('[name="' + key + '"], [name="' + key + '[]"]')
                .prop('checked', false).filter('input[type=number],input[type=hidden]').val('');
            $(this).parent().remove();
            applyFilters();
        });

        $(document).on('click', '.js-toggle-more', function () {
            var group = $(this).data('more-group');
            $('[data-more-group="' + group + '"].js-more').toggleClass('d-none');
            $(this).text($(this).text() === 'Show all' ? 'Show fewer' : 'Show all');
        });

        // Paginate over AJAX but keep the links real for crawlers and no-JS users.
        $(document).on('click', '#result-pagination a', function (e) {
            e.preventDefault();
            var page = new URL(this.href, window.location.origin).searchParams.get('page');
            if (!page) { return; }

            $('#filter-form').find('input[name=page]').remove();
            $('#filter-form').append($('<input type="hidden" name="page">').val(page));
            applyFilters();
            $('html, body').animate({ scrollTop: $('#result-grid').offset().top - 90 }, 250);
        });

        // Back/forward through filter states.
        window.addEventListener('popstate', function () { window.location.reload(); });

        KJ.initCompare();
    };

    // ---------- comparison ----------
    KJ.initCompare = function () {
        $(document).off('click.kjcompare').on('click.kjcompare', '.js-compare-toggle', function () {
            var $btn = $(this);
            var pressed = $btn.attr('aria-pressed') === 'true';
            var url = pressed ? '/ajax/compare/remove' : '/ajax/compare/add';

            KJ.request({ url: url, method: 'POST', data: { product_id: $btn.data('product-id') } })
                .then(function (res) {
                    if (res.status !== 'ok') { return KJ.toast(res.message, 'warning'); }

                    $btn.attr('aria-pressed', pressed ? 'false' : 'true')
                        .toggleClass('btn-primary', !pressed)
                        .toggleClass('btn-outline-primary', pressed)
                        .text(pressed ? 'Compare' : 'Added');

                    $('#compare-bar-host').html(res.data.html);
                    KJ.toast(res.message, 'success');
                });
        });

        $(document).off('click.kjcompareremove').on('click.kjcompareremove', '.js-compare-remove', function () {
            var id = $(this).data('product-id');

            KJ.request({ url: '/ajax/compare/remove', method: 'POST', data: { product_id: id } })
                .then(function (res) {
                    $('#compare-bar-host').html(res.data.html);
                    $('.js-compare-toggle[data-product-id="' + id + '"]')
                        .attr('aria-pressed', 'false')
                        .removeClass('btn-primary').addClass('btn-outline-primary')
                        .text('Compare');

                    if (window.location.pathname.indexOf('/compare/') === 0) { window.location.reload(); }
                });
        });

        $(document).off('click.kjcompareclear').on('click.kjcompareclear', '.js-compare-clear', function () {
            KJ.request({ url: '/ajax/compare/clear', method: 'POST' }).then(function () {
                window.location.reload();
            });
        });
    };

    // ---------- detail gallery ----------
    KJ.initDetail = function () {
        $(document).on('click', '.js-gallery-thumb', function () {
            $('.kj-thumb img').first().attr('src', $(this).data('full'));
        });

        KJ.initCompare();
    };

    // ---------- header type-ahead ----------
    $(function () {
        var $input = $('#kj-search-input');
        if (!$input.length) { return; }

        var $results = $('#kj-search-results');
        var timer = null;

        $input.on('input', function () {
            var term = $(this).val();
            clearTimeout(timer);

            if (term.length < 2) { return $results.addClass('d-none').empty(); }

            timer = setTimeout(function () {
                KJ.request({ url: '/ajax/search/suggest', data: { q: term } }).then(function (res) {
                    if (res.status !== 'ok') { return; }

                    var items = (res.data.brands || []).concat(res.data.products || []);
                    if (!items.length) { return $results.addClass('d-none').empty(); }

                    $results.empty().removeClass('d-none');
                    items.forEach(function (item) {
                        $results.append(
                            $('<a class="dropdown-item d-flex justify-content-between gap-2"></a>')
                                .attr('href', item.url)
                                .append($('<span></span>').text(item.label))
                                .append($('<span class="text-muted-2 small"></span>').text(item.meta || ''))
                        );
                    });
                });
            }, 250);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.kj-search-wrap').length) { $results.addClass('d-none'); }
        });
    });
})(jQuery);
