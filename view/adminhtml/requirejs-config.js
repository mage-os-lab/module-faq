/**
 * Adds the mageos-faq-identifier and mageos-faq-directive-safe validation rules to the UI form
 * validator, the extension point Magento documents for UI form validation rules. See
 * js/validation/identifier-mixin.js and js/validation/directive-safe-mixin.js.
 */
var config = {
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'MageOS_Faq/js/validation/identifier-mixin': true,
                'MageOS_Faq/js/validation/directive-safe-mixin': true
            }
        }
    }
};
