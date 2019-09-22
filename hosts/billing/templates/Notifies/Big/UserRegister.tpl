{*
 *  Joonte Billing System
 *  Copyright © 2012 Vitaly Velikodnyy
 *}
{assign var=Theme value="Регистрационные данные" scope=global}
Здравствуйте, {$User.Name|default:'$User.Name'}!

Поздравляем, вы зарегистрировались на ресурсе {$smarty.const.HOST_ID|default:'$HOST_ID'}!

Просим Вас прочитать до конца данное электронное письмо, так как содержащаяся в нем информация поможет Вам в дальнейшей работе.

Ваши данные для входа в биллинговую систему:
  * Адрес для входа:
      http://{$smarty.const.HOST_ID|default:'HOST_ID'}/Logon
  * Ваш электронный адрес (используется для входа в биллинговую систему):
      {$User.Email|default:'$User.Email'}
  * Ваш пароль:
      {$Password|default:'$Password'}

Сохраните эти данные в надежном месте, они потребуются для дальнейшей работы.

Обращаем Ваше внимание на то, что возможность управления услугами активируется ТОЛЬКО ПОСЛЕ ПОСТУПЛЕНИЯ ОПЛАТЫ.

Если Вам необходимо выставить новый счёт или повторно распечатать ранее выставленный - пожалуйста, воспользуйтесь следующей ссылкой http://{$smarty.const.HOST_ID|default:'HOST_ID'}/Invoices.

Для обратной связи с нами используйте соответсвующий раздел нашего сайта, либо центр поддержки находящийся по адресу: http://{$smarty.const.HOST_ID|default:'HOST_ID'}/Tickets.

Внимание!
Вся информация от нашей компании будет приходить по электронному адресу, Jabber, Telegram, SMS сообщениями, по тем данным которые Вы указали при регистрации.
Пожалуйста, следите за тем, чтобы эти данные были актуальными.
В случае необходимости смените их в биллинговой системе.

Идентификатор: {$User.ID}

{if !$MethodSettings.CutSign}
--
{$From.Sign|default:'$From.Sign'}

{/if}

