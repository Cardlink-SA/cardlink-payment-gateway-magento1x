# Cardlink Check out Payment Gateway

- Contributors: cardlink
- Tags: payments, payment-gateway
- Requires at least: 1.8
- Tested up to: 1.9.4.5
- Requires PHP: 7.x
- License: GPLv2 or later
- License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Changelog

- **1.1.3**
  - Minor fixes.
  -	Removed unused files.
  -	Updated README.
- **1.1.2**
  - Added support for IRIS payments.
- **1.1.1**
  - Fixed bug that inhibits sending new order notification email.
- **1.1.0**
  - Bug fixes.
  - Validate Alpha Bonus digest.
- **1.0.0**
  - Initial release

## Support tickets

In case that you face any technical issue during the installation process, you can contact the Cardlink e-commerce team at ecommerce_support@cardlink.gr.


## Description

Cardlink Payment Gateway allows you to accept payment through various schemes such as Visa, Mastercard, Maestro, American Express, Diners, Discover cards on your website, with or without variable installments.

This module aims to offer new payment solutions to Cardlink merchants for their Magento 1.x online store without having web development knowledge. However, for the initial module installation some technical knowledge will be required.

Merchants with e-shops (redirect cases only) will be able to integrate the Cardlink Payment Gateway to their checkout page using the CSS layout that they want. Also, they could choose between redirect or IFRAME option for the payment environment.

Once the payment is made, the customer returns to the online store and the order is updated.

Once you have completed the requested tests and any changes to your website, you can activate your account and start accepting payments.

## Features

1. A dropdown option for instance between Worldline, Nexi και Cardlink.
2. Option to enable test environment (sandbox). All transactions will be redirected to the endpoint that represents the production environment by default. The endpoint will be different depending on which acquirer has been chosen from instance dropdown option.
3. Ability to define the maximum number of installments regardless of the total order amount.
4. Ability to define the ranges of the total order amounts and the maximum number of installments for each range.
5. Option for pre-authorization or sale transactions.
6. Option for a user tokenization service. The card token will be stored at the merchant's e-shop database and will be used by customers to auto-complete future payments.
7. In-store checkout option: the merchant can configure the payment process to take place inside a pop up with IFRAME to mask the redirection process from the customers.
8. A text field for providing the absolute or relative (to Cardlink Payment Gateway location on server) URL of custom CSS stylesheet, to apply custom CSS styles in the payment page.
9. Translation ready for Greek & English languages.
10. Support IRIS payments.

## Installation

You need to manually upload the contents of the .zip file of the module's latest version to your server's web root folder that your Magento store is installed. You will first need to extract the file's contents to a temporary folder.

Depending on your hosting provider, you will probably have to be familiar with the process of transferring files using an FTP or SFTP client. If no FTP/SFTP access is provided, use your hosting provider's administration panel to upload the folders to the folder of your Magento installation.

### Required Hosting Settings 

For security reasons, Web browsers will not send target domain cookies when the referrer website is on another domain and data are POSTed unless the ``SameSite`` option of these cookies is set to the value ``None``. If you fail to properly configure the required hosting settings, customers returning from the payment gateway will be automatically logged out from their accounts. The following configuration instructions will manipulate cookies set by your store to allow customer sessions to persist after returning from the payment gateway.

### Magento Web Settings

Go to System > Configuration > General > Web. If there is a section name Session Cookie Management, set the Same-Site setting to ``None``. This should be sufficient. Clear your site cookies or open an Incognito window, place a test order using card payment and check whether the user session is retained after a successful payment. If not, proceed with the following web server configuration actions.

#### Apache Web Server

For hosting solutions running the Apache web server software, you will need to add the following lines to your web site’s root ``.htaccess`` file. Make sure the ``mod_headers`` Apache module is installed and active.

```
<IfModule mod_headers.c>
Header always edit Set-Cookie ^(.*)$ $1;SameSite=None;Secure
</IfModule>
```

#### Nginx Web Server

If your hosting provider uses the Nginx web server instead, you will need to add/edit the following lines of code to your virtual host’s configuration file.

```
location / {
    proxy_cookie_path / "/; SameSite=None; Secure";
    …
}
```

#### Plesk Hosting Control Panel

If you are using Plesk and nginx in proxy mode, under ``Apache & nginx Setting for ... > Additional nginx directives`` add only the following line:

```
proxy_cookie_path / "/; SameSite=None; Secure";
```

If you are only using Apache, add the following configuration lines in the ``Additional Apache directives`` section on the same page. By default, Plesk has the Apache ``mod_headers`` module installed and active however, verify that this is the case for your Plesk installation.

```
<IfModule mod_headers.c>
Header always edit Set-Cookie ^(.*)$ $1;SameSite=None;Secure
</IfModule>
```

If you are unsure or unfamiliar with the actions described above, please ask a trained IT person or contact your hosting provider to do them for you.

## Screenshots

1.	The Cardlink Payment Gateway settings screen used to configure the main Cardlink gateway (System > Configuration > Sales > Payment Methods).

![image001](README-IMAGES/image001.png)

2.	This is the front-end of Cardlink Payment Gateway plugin located in checkout page.

![image002](README-IMAGES/image002.png)

3.	To set up IRIS payments, you will need to have the Merchant ID, Shared Secret and DIAS Customer ID specifically issued for use with IRIS. Other settings are similar to the ones for Card Payments.

![image003](README-IMAGES/image003.png)
