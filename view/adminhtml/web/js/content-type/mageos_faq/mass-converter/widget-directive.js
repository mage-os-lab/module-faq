/**
 * Stores the FAQ content type's settings as a {{widget}} directive, the way core's Block and Products
 * content types do, so the storefront renders it with core's widget filter and nothing else.
 *
 * toDom() writes {{widget type="MageOS\Faq\Block\Widget\FaqList" identifier="…" heading="…"}} into the
 * element's html; fromDom() reads the settings back out of it when the element is opened again.
 *
 * What a directive cannot carry in a value — " { } \ — is refused by the form's
 * mageos-faq-directive-safe rule. What the stage would otherwise parse as markup — & < > — is
 * escaped here and unescaped on the way back. The storefront shows it as typed: the widget's template
 * escapes the heading with Escaper::escapeHtml(), which does not double-encode.
 */
define([
    'Magento_PageBuilder/js/mass-converter/widget-directive-abstract',
    'Magento_PageBuilder/js/utils/object'
], function (WidgetDirectiveAbstract, objectUtils) {
    'use strict';

    var WIDGET_TYPE = 'MageOS\\Faq\\Block\\Widget\\FaqList';

    /**
     * Escape the characters the stage would parse as markup.
     *
     * @param {String} value
     * @returns {String}
     */
    function encode(value) {
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /**
     * Undo encode().
     *
     * @param {String} value
     * @returns {String}
     */
    function decode(value) {
        return String(value).replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
    }

    /**
     * @constructor
     */
    function WidgetDirective() {
        WidgetDirectiveAbstract.apply(this, arguments);
    }

    WidgetDirective.prototype = Object.create(WidgetDirectiveAbstract.prototype);
    WidgetDirective.prototype.constructor = WidgetDirective;

    /**
     * Read the settings out of the stored directive.
     *
     * @param {Object} data
     * @param {Object} config
     * @returns {Object}
     */
    WidgetDirective.prototype.fromDom = function (data, config) {
        var attributes = WidgetDirectiveAbstract.prototype.fromDom.call(this, data, config);

        data.identifier = attributes.identifier ? decode(attributes.identifier) : '';
        data.heading = attributes.heading ? decode(attributes.heading) : '';

        return data;
    };

    /**
     * Write the settings as the directive; with no identifier there is nothing to render.
     *
     * @param {Object} data
     * @param {Object} config
     * @returns {Object}
     */
    WidgetDirective.prototype.toDom = function (data, config) {
        var attributes;

        if (!data.identifier) {
            return data;
        }

        attributes = {
            type: WIDGET_TYPE,
            identifier: encode(data.identifier)
        };
        if (data.heading) {
            attributes.heading = encode(data.heading);
        }

        objectUtils.set(data, config.html_variable, this.buildDirective(attributes));

        return data;
    };

    return WidgetDirective;
});
