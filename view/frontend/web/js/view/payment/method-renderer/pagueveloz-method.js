/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Trezo_PagueVeloz/payment/pagueveloz'
            },

            getCode: function() {
                return 'pagueveloz';
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                return true;
            }
        });
    }
);
