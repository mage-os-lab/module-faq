/**
 * mageos-faq-identifier: a FAQ group identifier is lowercase letters, digits, - and _, up to 128.
 *
 * The rule MageOS\Faq\Model\Faq\Identifier enforces on save, given here so the form says so before
 * submitting. Used by the FAQ entry form and the FAQ Page Builder content type, whose identifier has
 * to name a group that can exist. An empty value passes: required-entry reports that.
 */
define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    var VALID = /^[a-z0-9_-]{1,128}$/;

    return function (validator) {
        validator.addRule(
            'mageos-faq-identifier',
            function (value) {
                return !value || VALID.test(value);
            },
            $.mage.__('Use lowercase letters (a-z), digits (0-9), hyphens (-) and underscores (_) only, up to 128 characters.')
        );

        return validator;
    };
});
