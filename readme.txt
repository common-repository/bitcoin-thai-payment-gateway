=== CoinPay Thai Payment Gateway ===
Contributors: dave111223
Donate link: https://coinpay.co.th/
Tags: woocommerce, bitcoin, coinpay, bitcoin.co.th, payment gateway, payment module
Requires at least: 3.0.1
Tested up to: 4.7.2
Stable tag: 3.0.1
License: AGPLv3.0 or later
License URI: http://opensource.org/licenses/AGPL-3.0

Woocommerce payment module for use with https://coinpay.co.th merchant accounts

== Description ==
NOTE: You must install the main "WooCommerce - excelling eCommerce" plugin in order to use this plugin

This module allows you to accept Bitcoin payments in Thailand via your https://coinpay.co.th merchant account

When users choose the "Bitcoin" payment option at the checkout they will be shown a bitcoin address to send their payment.  Once they have sent the Bitcoin payment they can click "Place Order" to complete the order.

Orders are accepted immediately and put as status "on-hold" until confirmations are received, then orders are automatically updated to "Completed"

* Note if you have been using the Bitcoin.co.th merchant service we have now migrated your account to CoinPay.in.th.  Please update to this module and use https://coinpay.co.th to check your account details

== Installation ==

1. Unzip/Extract the plugin zip to your computer
2. Upload `\bitcointhai_wp\` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to WP Admin -> Woocommerce -> Settings -> Payment Gateways -> CoinPay
5. Click the "Enable Bitcoin" box, and enter your API ID/API Key for your merchant account
6. Save changes

Customers can now pay using Bitcoin

== Frequently Asked Questions ==

= Does this plugin require Woocommerce? =

Yes you must install the main woocommerce plugin in order for this plugin to work.

= Does this plugin work with other eCommerce plugins? =

At the moment it is only for Woocommerce, but we plan to add additional eCommerce plugin support in future releases, such sa WP e-Commerce.

== Screenshots ==

1. Not available

== Changelog ==
= 3.0.1 = 
Fixed vunerability in the API signature

= 3.0.0 =
- Updated plugin to work with the new Coinpay API, which allows multiple crypto-currencies, including Bitcoin, Bitcoin Cash, Litecoin and Dash.
- New plugin links directly to your BX.in.th exchange account.

= 2.2.2 =
- Bugfix for only PHP versions (use __return_false instead of function(){return false;} )

= 2.2.1 =
- Allow https IPN notification URL

= 2.2 =
- Fix bug for woocommerce 2.6+ (lower case module ID)
- Remove forced curl cypher list

= 2.1.5 =
Added fix for cached Bitcoin.co.th order IDs

= 2.1.4 =
Use CURL_SSLVERSION_TLSv1 for older systems

= 2.1.3 =
Ask CURL to use TLSv1_2 due to SSLv3 being disabled on CoinPay for security

= 2.1.2 =
More CURL debugging added

= 2.1.1 =
Minor fix to CURL debugging

= 2.1 =
Added more debugging information

= 2.0 =
Rebranded merchant service as CoinPay

= 1.2.3 =
* Hide warnings on the CURLOPT_FOLLOWLOCATION

= 1.2.2 =
* Fixed jquery conflict bugs.

= 1.2.1 =
* Minor update to better catch API response errors

= 1.2 =
* Updated to be compatible with woocommerce version 2.2.8
* Changed references to bitcoin.in.th to bitcoin.co.th
* Better theme compatibility

= 1.1 =

* New secure SSL API URL

= 1.0 =

* Initial Bitcoin Thai Payment Gateway release

== Upgrade Notice ==

= 1.0 =
* Initial Bitcoin Thai Payment Gateway release

== Arbitrary section ==


