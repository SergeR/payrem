<?php
/**
 * @package Payrem
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.0.0
 * @copyright (c) 2015, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
return array(

    'active'          => array(
        'title'        => _wp('Plugin active'),
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 0
    ),

    /**
     * Настрйока с перечислением статусов заказов, которые будем отслеживать
     */
    'statuses'        => array(
        'title'            => _wp('Order statuses'),
        'description'      => 'Укажите, какие статусы заказов необходимо отслеживать',
        'control_type'     => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopPayremPlugin', 'listOrderStatuses'),
        'value'            => array()
    ),

    /**
     * Настройка выбора методов оплаты, которые будем отслеживать
     */
    'payment_methods' => array(
        'title'            => _wp('Payment methods'),
        'description'      => 'Укажите, заказы с каким методом оплаты необходимо отслеживать',
        'control_type'     => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopPayremPlugin', 'listPaymentMethods'),
        'value'            => array()
    ),

    'reminder_delay'  => array(
        'title'        => 'Задержка отправки',
        'description'  => 'Укажите, через сколько дней после оформления заказа отправлять напоминание. Можно указать несколько значений, используя запятую в качестве разделителя',
        'value'        => "3,5",
        'control_type' => waHtmlControl::INPUT
    ),

    'delete'          => array(
        'title'        => 'Автоматически отменять заказ',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 1
    ),

    'delete_delay'    => array(
        'title'        => 'Задержка отмены',
        'description'  => 'Укажите, через какое количество дней отменять заказ',
        'value'        => 7,
        'control_type' => waHtmlControl::INPUT
    ),

    'message_from'    => array(
        'title'        => 'E-mail отправителя',
        'description'  => 'Адрес e-mail, с которого будет отправлено сообщение',
        'value'        => '',
        'control_type' => waHtmlControl::INPUT
    ),

    'message_subject' => array(
        'title'        => 'Тема сообщения',
        'description'  => 'Тема сообщения, которое будет отправлено клиенту',
        'value'        => 'Оплатите свой заказ {$order.id_str} в магазине {$wa->shop->settings("name")}',
        'control_type' => waHtmlControl::INPUT
    ),

    'message_body'    => array(
        'title'        => 'Текст сообщения',
        'description'  => 'HTML+Smarty',
        'value'        => '{$end_date=strtotime($order.create_datetime)+$delay*86400}' . "\n" .
            '<h1 style="font-face:sans-serif;font-size:16pt">Здравствуйте, {$customer->getName()|escape}!</h1>' . "\n" .
            '<p style="font-face:sans-serif;font-size:12pt">{strtotime($order.create_datetime)|wa_date:"humandate"} вы сделали заказ в нашем интернет-магазине <i>{$wa->shop->settings("name")}</i> и выбрали способ оплаты <i>&laquo;{$order.payment_name|escape}&raquo;</i>.</p>' . "\n\n" .
            '<p>К сожалению, оплата до сих пор не поступила. Пожалуйста, оплатите ваш заказ до {$end_date|wa_date:"humandate"}, иначе он будет отменен.</p>' . "\n\n" .
            '<p>Если у вас возникли какие-то трудности с оплатой или любые другие вопросы относительно вашего заказа, напишите нам письмо на адрес <b><a href="mailto:{$wa->shop->settings("email")}">{$wa->shop->settings("email")|escape}</a></b> или позвоните по телефону <b style="white-space:nowrap">{$wa->shop->settings("phone")}</b>. Будем рады помочь.</p>' . "\n\n" .
            '--<br>' . "\n" .
            '{$wa->shop->settings("name")}<br>' . "\n" .
            '{$wa->shop->settings("phone")}<br>' . "\n" .
            '{$wa->shop->settings("email")}' . "\n",
        'control_type' => waHtmlControl::CUSTOM . ' ' . 'shopPayremPlugin::getTextEditorControl'
    )

);
