<?php

/**
 * @package Payrem
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.0.0
 * @copyright (c) 2015, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopPayremPlugin extends shopPlugin
{
    /**
     * Список статусов заказов, пригодный для использования в настройках
     *
     * @return array
     * @throws waException
     */
    public static function listOrderStatuses()
    {
        $statuses = array();

        $workflow = new shopWorkflow();
        $available_states = $workflow->getAvailableStates();

        foreach ($available_states as $state_id => $state_data) {

            if (!in_array($state_id, array('deleted', 'refunded'))) {
                $statuses[] = array(
                    'value' => $state_id,
                    'title' => $state_data['name']
                );
            }
        }

        return $statuses;
    }

    /**
     * Отдает список методов оплаты пригодный для использованияв качестве списка опций для стандартного элемента формы
     * SELECT
     *
     * @return array
     */
    public static function listPaymentMethods()
    {
        $methods = array();
        $Plugin = new shopPluginModel();

        $payment_methods = $Plugin->listPlugins(shopPluginModel::TYPE_PAYMENT);
        foreach ($payment_methods as $payment_method) {
            $methods[] = array(
                'value' => $payment_method['id'],
                'title' => $payment_method['name']
            );
        }

        return $methods;
    }

    /**
     * Формирует кастомизированный элемент управления с Smarty/HTML редактором
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    public static function getTextEditorControl($name, $params)
    {

        $view = wa()->getView();
        $template = wa()->getAppPath('plugins/payrem/templates/text_editor.html', 'shop');
        $root_url = wa()->getRootUrl();

        $view->assign(compact('name', 'params', 'root_url'));

        return $view->fetch($template);
    }
}
