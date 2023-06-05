/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

define(
    ["underscore", 'Magento_Customer/js/customer-data']
    ,function(_, customerData) {
        return function(config, element)
        {
            let sibData = customerData.get('sib_quote_data')();
            if (!_.isEmpty(sibData) && !_.isEmpty(JSON.parse(sibData["sib_quote_data"]))) {
                _(JSON.parse(sibData["sib_quote_data"])).each(function (event, key) {
                    window.sendinblue.track(
                        event.event,
                        event.properties,
                        event.eventdata
                    );
                });
                customerData.set('sib_quote_data', {});
            }
        };
    }
);
