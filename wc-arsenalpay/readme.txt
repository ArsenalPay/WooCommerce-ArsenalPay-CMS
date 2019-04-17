=== ArsenalPay for WooCommerce ===
Contributors: ArsenalPay
Donate link: https://arsenalpay.ru/support.html
Tags: payment gateway, woocommerce, payment system, e-commerce
Requires at least: 4.0
Requires WooCommerce at least: 3.3.3
Tested up to: 4.9.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept major card(VISA/Masercard/Maestro/Mir) and mobile (MTS/Beeline/TELE2/Rostelecom) payments with ArsenalPay on your website.

== Description ==
 
**Basic feature list:**

 * Allows seamlessly integrate unified payment frame into your site.
 * New payment method ArsenalPay will appear to pay for your products and services.
 * Supports two languages (Russian, English).

[Official integration guide page](https://arsenalpay.ru/developers.html)

**ОПИСАНИЕ РЕШЕНИЯ**

ArsenalPay – удобный и надежный платежный сервис для бизнеса любого размера. 

Используя платежный модуль от ArsenalPay, вы сможете принимать онлайн-платежи от клиентов по всему миру с помощью: 
пластиковых карт международных платёжных систем Visa и MasterCard, эмитированных в любом банке
баланса мобильного телефона операторов МТС, Мегафон, Билайн, Ростелеком и ТЕЛЕ2
различных электронных кошельков 

**Преимущества сервиса:**

 - [Самые низкие тарифы](https://arsenalpay.ru/tariffs.html)
 - Бесплатное подключение и обслуживание
 - Легкая интеграция
 - [Агентская схема: ежемесячные выплаты разработчикам](https://arsenalpay.ru/partnership.html)
 - Вывод средств на расчетный счет без комиссии
 - Сервис смс оповещений
 - Персональный личный кабинет
 - Круглосуточная сервисная поддержка клиентов 

А ещё мы можем взять на техническую поддержку ваш сайт и создать для вас мобильные приложения для Android и iOS. 

ArsenalPay – увеличить прибыль просто! 
Мы работаем 7 дней в неделю и 24 часа в сутки. А вместе с нами множество российских и зарубежных компаний. 

**Как подключиться:** 

1. Вы скачали модуль и установили его у себя на сайте;
2. Отправьте нам письмом ссылку на Ваш сайт на pay@arsenalpay.ru либо оставьте заявку через [форму на сайте](https://arsenalpay.ru/#register);
3. Мы Вам вышлем коммерческие условия и технические настройки;
4. После Вашего согласия мы отправим Вам проект договора на рассмотрение.
5. Подписываем договор и приступаем к работе.

Всегда с радостью ждем ваших писем с предложениями. 

pay@arsenalpay.ru 

[arsenalpay.ru](https://arsenalpay.ru)

== Installation ==

1. Login to the WordPress admin section.
2. Go to **Plugins>Add New>Upload Plugin**.
3. Search for **ArsenalPay for WooCommerce**.
4. Click **Install Now** and then **Activate Plugin**.

== Settings ==

1. Go to **WooCommerce>Settings>Checkout**.
2. There choose **ArsenalPay** method.
3. Make following settings:
 - Check the box next to **Enable ArsenalPay**.
 - You can edit **Title** and **Description** of ArsenalPay payment method as you would like to display it at your site.
 - Fill out **callbackKey**, **widget** , **widgetKey** fields with your received callbackKey, widget and widgetKey.
 - Your online shop will be receiving callback requests about processed payments for automatically order status change. The callbacks will being received onto the address assigned in **Callback URL** string upside of the payment plugin settings. Callback is set to address: `http(s)://yourSiteAddress/?wc-api=wc_gw_arsenalpay&arsenalpay=callback`
 - You can enable/disable logging by checking/unchecking the box.
 - You can specify IP address only from which it will be allowed to receive callback requests about payments from ArsenalPay onto your site in **Allowed IP address** field.
 - Compare the tax rates in your store with the tax rates that will come to the Federal Tax Service.
5. Finally, save your settings by clicking on **Save Changes**

Settings of tax rates is only necessary if you are connected to online checkout. See https://arsenalpay.ru/documentation.html#54-fz-integraciya-s-onlajn-kassoj. For connection, please contact our manager.

If you do not already have a tax regime on your site:
1. Go to the administration panel **WooCommerce> Settings> General**.
2. Select **"Include taxes and calculate taxes"**
3. Click the **Save** button
4. Set up taxes, information can be found on https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/
5. Return to the settings of the payment system **Arsenalpay**

You need to compare taxes in your store and in the Federal Tax Service. Settings for customization:
 - **Default tax rate** - The default tax rate will be in the check, if no other rate is specified on the goods card.
 - If you have created tax rates on the site, you will see a list of taxes: on left side tax rate in your store, on right - in the Federal Tax Service. Please compare them.
 - Click **"Save Changes"** to complete the setup.

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

== Frequently Asked Questions ==
You can find answers to frequently asked question [here](https://arsenalpay.ru/support.html).
== Screenshots ==

== Changelog ==

= 1.0.0 =
* Initial release, bundles WordPress 4.0/4.0.1 and WooCommerce 2.2.4/2.2.8* to 1.6.0.9.

= 1.0.1 = 
* Tested up to WordPress 4.4.1 and WooCommerce 2.5.1.
* Added opportunity to send additional parameters to payment frame.
* Edited the callback function allowing to handle the callback amount less than the order total amount. 

= 1.0.2 =
* Bag of passing parameters to payment page fixed.

= 1.0.3 =
* The payment module is transferred from the frame to the widget.

= 1.1.1 =
* Added support for online cashbox

Here's a link to [ArsenalPay website](https://arsenalpay.ru/ "ArsenalPay payment gateway") and one to [ArsenalPay support](https://arsenalpay.ru/support.html "Support").

== Upgrade Notice ==

= 1.0.1 =
Upgrade if you need to handle the amount in callback less that the total amount of the order. Or need to send additional parameters to the payment frame.

= 1.0.2 =
Upgrade to fix bag with passing params.

= 1.0.3 =
Upgrade to transfer frontend frame to the widget.

= 1.1.1 =
Upgrade to be able to use the online cashbox
