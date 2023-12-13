# Improntus MachPay Module for Magento2

### Installation

```sh
$ php bin/magento module:enable Improntus_MachPay --clear-static-content
$ php bin/magento setup:upgrade
$ rm -rf var/di var/view_preprocessed var/cache generated/*
$ php bin/magento setup:static-content:deploy
```

## Description

Improntus MachPay is a payment module for Magento2 that integrates the MachPay payment system into Magento's checkout process. It allows customers to easily perform payments using MachPay.

## Features

- Integration of MachPay as a payment method.
- Allows customers to pay using MachPay.
- Functionality for refunding orders.
- Functionality for cancelling MachPay orders on user's request.
- Supports translations, with a Spanish (Chile) translation already provided.
- Button for cancelling the payment in the checkout process.
- Cron job for cancelling orders that have not been paid after a certain amount of time.
- Cron job for cleaning old qr codes from the server.

## Requirements

- Magento 2.x (tested with 2.4)
- PHP 7.4 or above

## Configuration

1. From Magento's backend, navigate to `Stores > Configuration > Sales > Payment Methods`.
2. Find MachPay in the payment methods list and configure it according to your preference.


## Author

[![N|Solid](https://improntus.com/wp-content/uploads/2022/05/Logo-Site.png)](https://www.improntus.com)
