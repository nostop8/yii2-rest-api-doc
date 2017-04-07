(function () {
    'use strict';

    function prettify(value) {
        try {
            var object;
            eval('object = ' + value);
            return JSON.stringify(object, undefined, 2);
        } catch (e) {
            console.log(e);
            return value;
        }
    }

    function escapeHtml(text) {
        return text
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/(?:\r\n|\r|\n)/g, '<br/>')
            .replace(/\s/g, "&nbsp;");
    }

    $(document).ready(function () {
        $('.endpoint-toggle').click(function () {
            $(this).toggleClass('active');
        });

        $('.prettify').click(function (e) {
            e.preventDefault();
            var $bodyField = $(this).closest('form').find('[name="body"]');
            $bodyField.val(prettify($bodyField.val()));
        });

        $('.sample').each(function () {
            $(this).html(escapeHtml(prettify($(this).text())));
        }).click(function (e) {
            e.preventDefault();
            var $bodyField = $(this).closest('form').find('[name="body"]');
            $bodyField.val(prettify($(this).text()));
        });

        $('#token, #base_url').each(function () {
            $(this).val(localStorage.getItem('rest_api_doc_' + $(this).attr('id')));
        });

        $('#token, #base_url').change(function () {
            localStorage.setItem('rest_api_doc_' + $(this).attr('id'), $(this).val());
        });

        $('.docs-index form').submit(function (e) {
            e.preventDefault();
            var form = this;
            var url = $('[name="url"]', form).val();
            var method = $('[name="method"]', form).val();
            var body;
            var $bodyField = $('[name="body"]', form);
            if ($bodyField.length) {
                $bodyField.val(prettify($bodyField.val()));
                body = $bodyField.val();
            }
            $('.params input', form).each(function () {
                url = url.replace($(this).attr('data-key'), $(this).val());
            });

            var urlParams = [];
            $('.filters input', form).each(function () {
                urlParams.push($(this).attr('data-key') + '=' + $(this).val());
            });

            var expand = [];
            $('.expand input:checked', form).each(function () {
                expand.push($(this).val());
            });
            if (expand.length) {
                urlParams.push('expand=' + expand.join(','));
            }
            if (urlParams.length) {
                url += '?' + urlParams.join('&');
            }
            var response = $('.response', form).get(0);
            $('.loader', response).removeClass('hidden');
            $('.data', response).addClass('hidden');
            $('.data .element', response).text('');

            var formData = {};
            if ($('.files', form).length) {
                formData = {
                    data: new FormData(form),
                    processData: false,
                    contentType: false,
                    cache: false
                };
            }

            var ajaxParams = $.extend({
                url: $('#base_url').val() + url,
                method: method,
                data: body,
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    Authorization: 'Bearer ' + $('#token').val()
                }
            }, formData);

            var ajax = $.ajax(ajaxParams).done(function (data, textStatus, jqXHR) {
                outputResponse(jqXHR, response);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                outputResponse(jqXHR, response);
            }).always(function () {
                $('.loader', response).addClass('hidden');
            });

            function outputResponse(jqXHR, response) {
                $('.data', response).removeClass('hidden');
                $('.code', response).text(jqXHR.status);
                $('.final-url', response).text(url);
                $('.text', response).text(jqXHR.statusText);
                if (jqXHR.responseText) {
                    $('.body', response).JSONView(prettify(jqXHR.responseText), {collapsed: jqXHR.status.toString().indexOf('20') === 0 ? true : false});
                } else {
                    $('.body', response).html('<div class="text-danger">Empty</div>');
                }
                $('.headers', response).html(escapeHtml(ajax.getAllResponseHeaders()));
            }
        });
    });

})();