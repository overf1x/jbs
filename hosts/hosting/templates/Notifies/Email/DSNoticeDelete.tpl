{*
 *  Joonte Billing System
 *  Copyright © 2012 Vitaly Velikodnyy
 *}
{assign var=Theme value="Оканчивается срок блокировки выделенного сервера" scope=global}
{assign var=ExpDate value=$DSOrder.StatusDate+86400}
Здравствуйте, {$User.Name|default:'$User.Name'}!

Уведомляем Вас о том, что оканчивается срок блокировки Вашего выделенного сервера, заказ №{$DSOrder.OrderID|string_format:"%05u"}, IP адрес {$DSOrder.IP|default:'$DSOrder.IP'}.
Дата удаления заказа {$ExpDate|date_format:"%d.%m.%Y"}

{$From.Sign|default:'$From.Sign'}

