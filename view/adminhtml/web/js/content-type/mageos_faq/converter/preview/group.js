/**
 * Shows the FAQ group on the Page Builder stage in place of the stored {{widget}} directive.
 *
 * The element's html holds the directive for the storefront; on the stage the preview template
 * prints this converter's value instead: the group identifier, or '' while none is set.
 */
define([], function () {
    'use strict';

    /**
     * @constructor
     */
    function Group() {}

    /**
     * @param {String} value
     * @returns {String}
     */
    Group.prototype.fromDom = function (value) {
        return value;
    };

    /**
     * @param {String} name
     * @param {Object} data
     * @returns {String}
     */
    Group.prototype.toDom = function (name, data) {
        return typeof data.identifier === 'string' ? data.identifier : '';
    };

    return Group;
});
