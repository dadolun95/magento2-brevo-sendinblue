# Brevo Magento2 Module <img src="https://avatars.githubusercontent.com/u/168457?s=40&v=4" alt="magento" /> 

### Dadolun_SibOrderSync

[![Latest Stable Version](https://poser.pugx.org/dadolun95/magento2-sib-order-sync/v/stable)](https://packagist.org/packages/dadolun95/magento2-sib-order-sync)

## Features
Syncronization functionality for Brevo (Sendinblue previously) - Magento2 integration.

This module sync all your Magento2 orders (for newsletter subscribed users) to Brevo.

These are the default built in transactional attributes:
- ORDER_ID
- ORDER_DATE
- ORDER_PRICE
- ORDER_PRICE_INVOICED
- ORDER_STATUS


These are the default built in calculated attributes:
- MAGENTO_LAST_30_DAYS_AMOUNT
- MAGENTO_LAST_30_DAYS_AMOUNT_INVOICED
- MAGENTO_ORDER_TOTAL
- MAGENTO_ORDER_TOTAL_INVOICED
- MAGENTO_ORDER_AMOUNT
- MAGENTO_ORDER_AMOUNT_INVOICED

## Compatibility
Magento CE(EE) 2.4.4, 2.4.5, 2.4.6

## Installation
You can install this module adding it on app/code folder or with composer.
```
composer require dadolun95/magento2-sib-order-sync
```
##### MAGENTO
Then you'll need to enable the module and update your database:
```
php bin/magento module:enable Dadolun_SibCore
php bin/magento module:enable Dadolun_SibContactSync
php bin/magento module:enable Dadolun_SibOrderSync
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

##### CONFIGURATION
You must enable the order sync from "Stores > Configurations > Dadolun > Brevo > Order Sync" section.
The module provides a "Sync order" CTA on adminhtml that move all existing order (made from newsletter subscribed contacts) to Sendinblue (only new orders are synced on runtime).

## Contributing
Contributions are very welcome. In order to contribute, please fork this repository and submit a [pull request](https://docs.github.com/en/free-pro-team@latest/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request).
