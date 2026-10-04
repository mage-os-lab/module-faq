/**
 * mageos-faq-directive-safe: refuses the characters a {{widget}} directive cannot carry in a value.
 *
 * The FAQ Page Builder content type stores its settings as a widget directive. In it, " ends a value
 * early (core's Tokenizer\Parameter splits on it, and the stage would not keep it escaped), } closes
 * the directive, and \ is kept or eaten by the tokenizer. So the form refuses them rather than store
 * a directive that renders something else.
 */
define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    var REFUSED = /["{}\\]/;

    return function (validator) {
        validator.addRule(
            'mageos-faq-directive-safe',
            function (value) {
                return !REFUSED.test(value || '');
            },
            $.mage.__('Leave out %1: the FAQ widget directive cannot carry them.').replace('%1', '" { } \\')
        );

        return validator;
    };
});
