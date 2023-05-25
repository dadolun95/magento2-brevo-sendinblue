# Brevo Magento2 Module <img src="https://avatars.githubusercontent.com/u/168457?s=40&v=4" alt="magento" /> 

### Dadolun_SibOrderSync

[![Latest Stable Version](https://poser.pugx.org/dadolun95/magento2-sib-order-sync/v/stable)](https://packagist.org/packages/dadolun95/magento2-sib-order-sync)

## Features
Syncronization functionality for Brevo (Sendinblue previously) - Magento2 integration.

This module sync all your Magento2 orders (for newsletter subscribed users) to Brevo.

These are the default built in transactional attributes:
- QUOTE_ID
- QUOTE_DATE
- QUOTE_PRICE
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
The module provides a "Sync order" CTA on adminhtml that move all existing order (made from newsletter subscribed contacts) to Brevo (only new orders are synced on runtime).
Pay attention to the "Sync Type" configuration, you must choose between "Async" and "Sync" mode.
- "Sync" mode (not recommended) will create or update order data on Brevo synchronously at each magento2 event (subscription update / order update) making an API call to Brevo
- "Async" mode (recommended) use Magento2 message queue system with a dedicated MySQL-operated queue ([See here message queue configuration guide](https://experienceleague.adobe.com/docs/commerce-operations/configuration-guide/message-queues/manage-message-queues.html?lang=en)) so you need to configure also magento to use consumer properly updating your app/etc/env.php file (something like that):
```
...
    'cron_consumers_runner' => [
        'cron_run' => true,
        'max_messages' => 1000,
        'consumers' => [
            'sibContactProcessor',
            'sibOrderProcessor',
        ]
    ],
...
```
The "Sync order" CTA use Magento2 message queue system. Clicking "Sync Order" you'll only add a complete order synchronization request on queue. So, if you set up "Sync" as "Sync Type" and you've not configured message queue system on your Magento installation, you will need to run this command from your cli each time you want to perform an "Order Sync" request from adminhtml:
```
php bin/magento queue:consumers:start sibOrderProcessor
```

## Contributing
Contributions are very welcome. In order to contribute, please fork this repository and submit a [pull request](https://docs.github.com/en/free-pro-team@latest/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request).
