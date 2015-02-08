=== ArsenalPay for WooCommerce ===
Contributors: ArsenalPay
Donate link: https://arsenalpay.ru/support.html
Tags: payment gateway, woocommerce, payment system, e-commerce
Requires at least: 4.0
Requires WooCommerce at least: 2.2.4
Tested up to: 4.0.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept major card(VISA/Masercard/Maestro) and mobile (MTS/Beeline/TELE2/Rostelecom) payments with ArsenalPay on your website.

== Description ==

Basic feature list:

 * Allows seamlessly integrate unified payment frame into your site.
 * New payment method ArsenalPay will appear to pay for your products and services.
 * Allows to pay using mobile commerce and bank aquiring. More methods are about to become available. Please check for updates.
 * Supports two languages (Russian, English).

== Installation ==

1. Download zip archive of the ArsenalPay plugin.
2. Login to the WordPress admin section. 
3. Go to **Plugins>Add New>Upload Plugin** and upload it.
4. Click **Install Now** and then **Activate Plugin**

== Settings ==
1. Go to **WooCommerce>Settings>Checkout**.
2. There choose **ArsenalPay** method.
3. Make following settings:
 - Check the box next to **Enable ArsenalPay**.
 - You can edit **Title** and **Description** of ArsenalPay payment method as you would like to display it at your site.
 - Fill out **Unique token**, **Sign key** fields with your received token and key.
 - Check **Frame URL** to be as `https://arsenalpay.ru/payframe/pay.php`
 - Choose payment type in **src parameter** as `card` to activate payments with bank cards or `mk` to activate payments from mobile phone accounts.
 - **css parameter**. You can specify CSS file to apply it to the view of payment frame by inserting css-file url.
 - You can specify IP address only from which it will be allowed to receive callback requests about payments from ArsenalPay onto your site in **Allowed IP address** field.
 - Your online shop will be receiving callback requests about processed payments for automatically order status change. The callbacks will being received onto the address assigned in **Callback URL** string upside of the payment plugin settings. Callback is set to address: `http(s)://yourSiteAddress/?wc-api=wc_gw_arsenalpay&arsenalpay=callback`
 - If it is needed to add one more step to check a payer order number before payment processing you should fill out the field of **Check URL** in the module settings with url-address to which ArsenalPay will be sending requests with check parameters. By default the address is the same with **Callback URL**. 
 - Set **Frame mode** as `1` to display payment frame inside your site, otherwise a payer will be redirected directly to the payment frame url.
 - You can adjust **width**, **height**, **frameborder** and **scrolling** of ArsenalPay payment frame by setting iframe parameters. For instance, you can insert string in format: `width="100%" height"500" frameborder="0" scrolling="no"`. Go to html standard reference for more detailes about iframe parameters.
 - You can enable/disable logging by checking/unchecking the box.
5. Finally, save your settings by clicking on **Save Changes**


== How to uninstall ==
1. Go to **Plugins** in WordPress admin section and find **ArsenalPay** in plugin list.
2. Click on **Deactivate**. 
3. Further you can delete files from your server by clicking on **Delete** and submitting the fact of deletion.

== Usage ==
After successful installation and proper settings new choice of payment method with ArsenalPay will appear on your site. To make payment for an order a payer will need to:

1. Choose goods from the shop catalog.
2. Go into the order page.
3. Choose the ArsenalPay payment method.
4. Check the order detailes and confirm the order.
5. After filling out the information depending on the payment type he will receive SMS about payment confirmation or will be redirected to the page with the result of his payment.

== Changelog ==

= 1.0.0 =
* Initial release, bundles WordPress 4.0/4.0.1 and WooCommerce 2.2.4/2.2.8* to 1.6.0.9.


Here's a link to [ArsenalPay website](https://arsenalpay.ru/ "ArsenalPay payment gateway") and one to [ArsenalPay support][https://arsenalpay.ru/support.html "Support"].
