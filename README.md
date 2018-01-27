# ArsenalPay Plugin for Woocommerce of Wordpress CMS

*Arsenal Media LLC*

[Arsenal Pay processing center]( https://arsenalpay.ru/)

## Version
1.0.3

*Has been tested on WordPress from 4.0 to 4.8.3 and WooCommerce from 2.2.4 to 3.2.3

Basic feature list:

 * Allows seamlessly integrate unified payment widget into your site.
 * New payment method ArsenalPay will appear to pay for your products and services.
 * Allows to pay using mobile commerce and bank aquiring. More methods are about to become available. Please check for updates.
 * Supports two languages (Russian, English).
 
## How to install
1. Login to the WordPress admin section.
2. Go to **Plugins>Add New**.
3. Search for **ArsenalPay for WooCommerce**.
4. Click **Install Now** and then **Activate Plugin**.


## Settings
1. Go to **WooCommerce>Settings>Checkout**.
2. There choose **ArsenalPay** method.
3. Make following settings:
 - Check the box next to **Enable ArsenalPay**.
 - You can edit **Title** and **Description** of ArsenalPay payment method as you would like to display it at your site.
 - Fill out **Callback key**, **Widget id** , **Widget key** fields with your received callback key, widget id and widget key.
 - Your online shop will be receiving callback requests about processed payments for automatically order status change. The callbacks will being received onto the address assigned in **Callback URL** string upside of the payment plugin settings. Callback is set to address: `http(s)://yourSiteAddress/?wc-api=wc_gw_arsenalpay&arsenalpay=callback`
 - You can enable/disable logging by checking/unchecking the box.
 - You can specify IP address only from which it will be allowed to receive callback requests about payments from ArsenalPay onto your site in **Allowed IP address** field.
5. Finally, save your settings by clicking on **Save Changes**


## How to uninstall
1. Go to **Plugins** in WordPress admin section and find **ArsenalPay** in plugin list.
2. Click on **Deactivate**. 
3. Further you can delete files from your server by clicking on **Delete** and submitting the fact of deletion.

## Usage
After successful installation and proper settings new choice of payment method with ArsenalPay will appear on your site. To make payment for an order a payer will need to:

1. Choose goods from the shop catalog.
2. Go into the order page.
3. Choose the ArsenalPay payment method.
4. Check the order detailes and confirm the order.
5. After filling out the information depending on the payment type he will receive SMS about payment confirmation or will be redirected to the page with the result of his payment.

------------------
### О ПЛАГИНЕ
* Плагин платежной системы ArsenalPay под WooCommerce для WordPress позволяет легко встроить платежную страницу на Ваш сайт.
* После установки плагина у Вас появится новый вариант оплаты товаров и услуг через платежную систему ArsenalPay.
* Платежная система ArsenalPay позволяет совершать оплату с различных источников списания средств: мобильных номеров (МТС/Мегафон/Билайн/TELE2), пластиковых карт (VISA/MasterCard/Maestro/Мир). Перечень доступных источников средств постоянно пополняется. Следите за обновлениями.
* Плагин поддерживает русский и английский языки.

### УСТАНОВКА
1. Зайдите в администрирование WordPress;
2. Пройдите в **Плагины>Добавить новый**;
3. Найдите плагин **ArsenalPay for WooCommerce**;
4. Нажмите на **Установить** и затем **Активировать плагин**.

### НАСТРОЙКА
1. В администрировании WordPress пройдите в **WooCommerce>Настройки>Оплата**.
2. Выберите закладку **ArsenalPay**.
3. Проведите следуюшие настройки:
 - Проставьте галочку возле **Включить ArsenalPay**.
 - Вы можете изменить **Заголовок** и **Описание** платежного метода ArsenalPay так, как Вам хотелось бы отобразить его на Вашем сайте.
 - Заполните поля **Callback key**, **Widget id**, **Widget key**, присвоенными Вам ключом для подписи, номером виджета и ключем подписи виджета.
 - Ваш интернет-магазин будет получать уведомления о совершенных платежах. На адрес, указанный в поле **Callback URL** вверху настроек плагина, от ArsenalPay будет поступать запрос с результатом платежа для фиксирования статусов заказа в системе предприятия. Обратный запрос настроен на адрес: `http(s)://адресВашегоСайта/?wc-api=wc_gw_arsenalpay&arsenalpay=callback`
 - Вы можете включать/выключать протоколирование для управления сохранением логов для отладки.
 - Вы можете задать ip-адрес, только с которого будут разрешены обратные запросы о совершаемых платежах, в поле **Разрешенный IP-адрес**.
7. Закончив, сохраните настройки нажатием на **Сохранить изменения**.

### УДАЛЕНИЕ
1. Пройдите в раздел **Плагины** в администрировании WordPress;
2. Найдите **ArsenalPay** в списке плагинов;
2. Нажмите на **Деактивировать**;
3. Затем Вы можете удалить файлы с Вашего сервера, нажав на **Удалить** и подтвердив удаление.

### ИСПОЛЬЗОВАНИЕ
После успешной установки и настройки плагина на сайте появится возможность выбора платежной системы ArsenalPay.
Для оплаты заказа с помощью платежной системы ArsenalPay нужно:

1. Выбрать из каталога товар, который нужно купить.
2. Перейти на страницу оформления заказа (покупки).
3. В разделе "Платежные системы" выбрать платежную систему ArsenalPay.
4. Перейти на страницу подтверждения введенных данных и ввода источника списания средств (мобильный номер, пластиковая карта и т.д.).
5. После ввода данных об источнике платежа, в зависимости от его типа, либо придет СМС о подтверждении платежа, либо покупатель будет перенаправлен на страницу с результатом платежа.

------------------
### ОПИСАНИЕ РЕШЕНИЯ
ArsenalPay – удобный и надежный платежный сервис для бизнеса любого размера. 

Используя платежный модуль от ArsenalPay, вы сможете принимать онлайн-платежи от клиентов по всему миру с помощью: 
пластиковых карт международных платёжных систем Visa и MasterCard, эмитированных в любом банке
баланса мобильного телефона операторов МТС, Мегафон, Билайн, Ростелеком и ТЕЛЕ2
различных электронных кошельков 

### Преимущества сервиса: 
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

### Как подключиться: 
1. Вы скачали модуль и установили его у себя на сайте;
2. Отправьте нам письмом ссылку на Ваш сайт на pay@arsenalpay.ru либо оставьте заявку на [сайте](https://arsenalpay.ru/#register) через кнопку "Подключиться";
3. Мы Вам вышлем коммерческие условия и технические настройки;
4. После Вашего согласия мы отправим Вам проект договора на рассмотрение.
5. Подписываем договор и приступаем к работе.

Всегда с радостью ждем ваших писем с предложениями. 

pay@arsenalpay.ru 

[arsenalpay.ru](https://arsenalpay.ru)
 




 
