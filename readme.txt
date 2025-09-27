=== CMI Payment Gateway for WooCommerce ===
Contributors: xanx - Sohail Koutari
Tags: woocommerce, payment gateway, morocco, cmi, credit card, payment, ecommerce
Requires at least: 5.8
Tested up to: 6.3
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Official CMI (Centre Monétique Interbancaire) payment gateway integration for WooCommerce. Accept credit card payments securely in Morocco.

== Description ==

CMI Payment Gateway for WooCommerce enables you to accept credit card payments securely through CMI's payment platform in Morocco. This plugin provides a seamless integration between WooCommerce and CMI's payment services.

= Features =

* Accept credit card payments through CMI
* Support for both test and live modes
* Secure payment processing with 3D Secure
* Supports multiple currencies (MAD, EUR, USD)
* Configurable minimum and maximum payment amounts
* Detailed transaction information in order details
* Comprehensive payment verification and validation
* Clear error messaging for failed transactions
* Test mode indicator for development
* Fully translatable

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 6.0 or higher
* PHP 8.1 or higher
* SSL Certificate (for live mode)
* CMI Merchant Account

= Test Cards =

For testing in sandbox mode, you can use these test cards:
* Visa: 4000000000000010
* MasterCard: 5453010000066100
* 3D Secure Test: 5191630100004896 (Auth code: 123)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wc-cmi-gateway`, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce > Settings > Payments to configure the gateway.
4. Enter your CMI credentials (Client ID and Store Key).

= Configuration =

1. Enable/Disable - Turn the gateway on or off
2. Title - What the user sees during checkout
3. Description - What the user sees during checkout
4. Client ID - Your CMI merchant ID
5. Store Key - Your CMI store key
6. Test Mode - Enable this for testing
7. Supported Currencies - Select which currencies you accept
8. Min/Max Amount - Set transaction limits

== Frequently Asked Questions ==

= Is SSL required? =

Yes, SSL is required for live transactions. For testing, you can use the gateway without SSL.

= How do I get CMI credentials? =

Contact CMI directly through their website to set up a merchant account and obtain your credentials.

= Can I test the gateway before going live? =

Yes, enable test mode and use the provided test cards to simulate transactions.

= Which currencies are supported? =

By default, MAD (Moroccan Dirham). EUR and USD may be available depending on your CMI account configuration.

== Screenshots ==

1. Gateway configuration page
2. Checkout payment form
3. Successful payment page
4. Transaction details in order view

== Changelog ==

= 1.0.0 =
* Initial release
* Basic gateway functionality
* Test mode support
* Multiple currency support
* Configurable amount limits
* 3D Secure support
* Detailed transaction info
* Test card support

== Upgrade Notice ==

= 1.0.0 =
Initial release of CMI Payment Gateway for WooCommerce.

== Privacy Policy ==

This plugin handles sensitive payment data but does not store card numbers or sensitive authentication data. All payment processing is handled securely on CMI's servers. The plugin stores only transaction IDs and approval codes for order reference.
