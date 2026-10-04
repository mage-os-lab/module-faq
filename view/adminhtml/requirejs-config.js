/**
 * Adds the mageos-faq-directive-safe validation rule to the UI form validator, the extension point
 * Magento documents for UI form validation rules. See js/validation/directive-safe-mixin.js.
 */
var config = {
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'MageOS_Faq/js/validation/directive-safe-mixin': true
            }
        }
    }
};
