# Dadolun_SibOrderSync

## Features
Order Syncronization functionality for Sendinblue - Magento2 integration.

This module sync all your Magento2 orders (for newsletter subscribed users) to Sendinblue.

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

## Installation
You can install this module adding it on app/code folder or with composer.
##### COMPOSER
You need to update your composer.json "repositories" node:
```
{
    "type": "vcs",
    "url":  "git@github.com:dadolun95/magento2-sib-order-sync.git"
}
```
Then execute this command:
```
composer require dadolun95/magento2-sib-order-sync
```
Then you'll need to enable the module and update your database:
```
php bin/magento module:enable Dadolun_SibOrderSync
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```
##### SOURCE CODE
If you choose to add the module from source code instead of using composer you need to add module's files on your app/code folder.
Then enable it and update the database:
```
php bin/magento module:enable Dadolun_SibOrderSync
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```
##### CONFIGURATION
You must enable the order sync from "Stores > Configurations > Dadolun > Sendinblue > Order Sync" section.
The module provides a "Sync order" CTA on adminhtml that move all existing order (made from newsletter subscribed contacts) to Sendinblue (only new orders are synced on runtime).

## Contributing
Contributions are very welcome. In order to contribute, please fork this repository and submit a [pull request](https://docs.github.com/en/free-pro-team@latest/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request).
