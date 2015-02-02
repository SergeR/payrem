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
        'value'        => 1
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
        'value'        => 'Ждем оплаты заказа {$order.id}',
        'control_type' => waHtmlControl::INPUT
    ),

    'message_body'    => array(
        'title'        => 'Текст сообщения',
        'description'  => 'HTML+Smarty',
        'value'        => '',
        'control_type' => waHtmlControl::CUSTOM . ' ' . 'shopPayremPlugin::getTextEditorControl'
    )

);
