<?php

/**
 * @package Payrem.Cli
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.0.0
 * @copyright (c) 2015, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopPayremSendCli extends waCliController
{

    /** @var shopPayremPlugin */
    private $plugin;

    /** @var shopOrderModel */
    private $Order;

    /** @var shopOrderParamsModel */
    private $OrderParam;

    /** @var shopOrderLogModel */
    private $OrderLog;

    /** @var waSmarty3View */
    private $view;

    /** @var shopWorkflow */
    private $workflow;

    /** @var array */
    private $generalSettings;

    /**
     * @throws waException
     */
    public function execute()
    {
        if (!$this->plugin->getSettings('active') && !$this->isTestRun()) {
            return;
        }

        // Если включена опция удаления, посмотрим, нет ли заказов для удаления
        // В тестовом режиме удаление не работает по-любому
        if ($this->plugin->getSettings('delete') && !$this->isTestRun()) {
            $this->deleteOrders();
        }

        //todo: Refactor to use shopOrdersCollection

        // Поиск заказов для рассылки напоминаний
        $to_remind = $this->isTestRun() ? $this->getTestOrder(waRequest::param('order')) : $this->getOrdersToRemind();
        $delays = $this->getDelays();
        sort($delays);

        if ($to_remind) {
            while ($order = $to_remind->fetchAssoc()) {
                $order['params'] = $this->OrderParam->get($order['id']);

                foreach ($delays as $delay) {
                    $from_time = time() - ($delay + 1) * 86400;
                    $to_time = time() - $delay * 86400;

                    if ((strtotime($order['create_datetime']) >= $from_time &&
                            strtotime($order['create_datetime']) <= $to_time &&
                            !isset($order['params']["payrem-$delay"])) ||
                        $this->isTestRun()
                    ) {
                        try {
                            $order = shopHelper::workupOrders($order, TRUE);

                            $result = $this->sendRemindMail($delay, $order);
                            if ($result && !$this->isTestRun()) {
                                $this->OrderParam->insert(array('order_id' => $order['id'], 'name' => "payrem-$delay", 'value' => time()));
                                $this->OrderLog->add(array(
                                    'order_id'        => $order['id'],
                                    'contact_id'      => NULL,
                                    'action_id'       => NULL,
                                    'text'            => '<i class="icon16 alarm-clock"></i>' . sprintf_wp("Payment reminder (%d days) sent to customer", $delay),
                                    'before_state_id' => $order['state_id'],
                                    'after_state_id'  => $order['state_id']
                                ));
                            }
                        } catch (waException $e) {
                            waLog::log("Unable to send reminder after $delay days of waiting for payment for order #{$order['id']}:\n" . $e);
                        }
                        break;
                    }
                }

            }
        }
    }

    protected function preExecute()
    {
        $this->plugin = wa('shop')->getPlugin('payrem');
        $this->Order = new shopOrderModel();
        $this->OrderParam = new shopOrderParamsModel();
        $this->OrderLog = new shopOrderLogModel();
        $this->view = wa()->getView();
        $this->workflow = new shopWorkflow();
        $this->generalSettings = wa('shop')->getConfig()->getGeneralSettings();
    }

    /**
     * Делает запрос на выборку заказов, удовлетворяющих условиям, заданным в настройках плагина,
     * с учетом задержек на отправку уведомлений, статусов и методов оплаты
     *
     * @return waDbResultSelect|NULL
     */
    private function getOrdersToRemind()
    {

        $statuses = array_keys($this->plugin->getSettings('statuses'));
        $payment_methods = array_keys($this->plugin->getSettings('payment_methods'));
        $delays = $this->getDelays();

        // Will not work without required settings
        if (!$statuses || !$payment_methods || !$delays) {
            return NULL;
        }

        $from_time = time() - (max($delays) + 1) * 86400;
        $to_time = time() - min($delays) * 86400;

        if (!$from_time || !$to_time) {
            return NULL;
        }

        foreach ($payment_methods as &$payment_method) {
            $payment_method = "'" . $this->OrderParam->escape($payment_method) . "'";
        }

        foreach ($statuses as &$status) {
            $status = "'" . $this->Order->escape($status) . "'";
        }

        // @formatter:off
        $subquery = "SELECT " .
            "order_id AS param_order_id, name AS param_name, value AS param_payment_id " .
            "FROM `" . $this->OrderParam->getTableName() . "` " .
            "WHERE `name` = 'payment_id' AND `value` IN (" . join(',', $payment_methods) . ")";

        $query = "SELECT " .
            "o.* ".
            "FROM `" . $this->Order->getTableName() . "` AS `o` ".
            "INNER JOIN ($subquery) AS `opm` " .
            "ON `o`.`id` = `opm`.`param_order_id` " .
            "WHERE ".
                "`o`.`create_datetime` BETWEEN FROM_UNIXTIME($from_time) AND FROM_UNIXTIME($to_time) AND ".
                "`o`.`paid_date` IS NULL AND ".
                "`o`.`state_id` IN (" . join(',', $statuses) . ") AND" .
                "`o`.`contact_id` IS NOT NULL";
        // @formatter:on

        return $this->Order->query($query);
    }

    /**
     * @todo Merge getOrdersToDelete() and getOrdersToRemind() they are very similar
     * @return null|waDbResultSelect
     */
    private function getOrdersToDelete()
    {
        $statuses = array_keys($this->plugin->getSettings('statuses'));
        $payment_methods = array_keys($this->plugin->getSettings('payment_methods'));
        $delay = intval($this->plugin->getSettings('delete_delay'));

        // Will not work without required settings
        if (!$statuses || !$payment_methods || !$delay) {
            return NULL;
        }

        $from_time = time() - ($delay + 1) * 86400;
        $to_time = time() - $delay * 86400;

        if (!$from_time || !$to_time) {
            return NULL;
        }

        foreach ($payment_methods as &$payment_method) {
            $payment_method = "'" . $this->OrderParam->escape($payment_method) . "'";
        }

        foreach ($statuses as &$status) {
            $status = "'" . $this->Order->escape($status) . "'";
        }

        // @formatter:off
        $subquery = "SELECT " .
            "order_id AS param_order_id, name AS param_name, value AS param_payment_id " .
            "FROM `" . $this->OrderParam->getTableName() . "` " .
            "WHERE `name` = 'payment_id' AND `value` IN (" . join(',', $payment_methods) . ")";

        $query = "SELECT " .
            "o.* ".
            "FROM `" . $this->Order->getTableName() . "` AS `o` ".
            "INNER JOIN ($subquery) AS `opm` " .
            "ON `o`.`id` = `opm`.`param_order_id` " .
            "WHERE ".
                "`o`.`create_datetime` BETWEEN FROM_UNIXTIME($from_time) AND FROM_UNIXTIME($to_time) AND ".
                "`o`.`paid_date` IS NULL AND ".
                "`o`.`state_id` IN (" . join(',', $statuses) . ") AND" .
                "`o`.`contact_id` IS NOT NULL";
        // @formatter:on

        return $this->Order->query($query);
    }

    private function getTestOrder($id)
    {
        // @formatter:off
        $query = "SELECT " .
            "o.* ".
            "FROM `" . $this->Order->getTableName() . "` AS `o` ".
            "WHERE `o`.id = $id";
        // @formatter:on

        return $this->Order->query($query);
    }

    /**
     * @return array
     */
    private function getDelays()
    {
        if ($this->isTestRun()) {
            return array(waRequest::param('delay'));
        }

        // Expected an array of numerical values extracted from comma-separated string
        return array_filter(explode(',', preg_replace('/[^,\d]/', '', $this->plugin->getSettings('reminder_delay'))));
    }

    /**
     * Пытается отправить письмо контакту
     *
     * @param int $delay
     * @param array $order
     * @return bool
     * @throws waException
     */
    private function sendRemindMail($delay, $order)
    {
        $this->view->clearAllAssign();
        $customer = new shopCustomer($order['contact_id']);
        $email = $customer->get('email', 'default');
        $order_url = wa()->getRouteUrl('/frontend/myOrderByCode', array('id' => $order['id'], 'code' => $order['params']['auth_code']), TRUE);
        $status = $this->workflow->getStateById($order['state_id'])->getName();

        if ($this->isTestRun()) {
            $email = waRequest::param('email');
        }

        if (!$email) {
            throw new waException("Contact has no email");
        }

        $shipping_address = $this->getAddress('shipping', $order);
        $billing_address = $this->getAddress('billing', $order);

        $this->view->assign(compact(
            'billing_address',
            'customer',
            'delay',
            'email',
            'order',
            'order_url',
            'shipping_address',
            'status'));

        $subject = $this->view->fetch('string:' . $this->plugin->getSettings('message_subject'));
        $body = $this->view->fetch('string:' . $this->plugin->getSettings('message_body'));
        $from = $this->plugin->getSettings('message_from');

        $message = new waMailMessage($subject, $body);
        $message->setTo(array($email => $customer->getName()));
        $message->setFrom($from, $this->generalSettings['name']);

        return $message->send();
    }

    /**
     * Записывает сообщение в лог только если включен режим отладки
     *
     * @param string $str Сообщение для записи в лог
     * @throws waException
     */
    private function debugLog($str)
    {
        if (waSystemConfig::isDebug()) {
            waLog::log($str);
        }
    }

    /**
     * @param string $type
     * @param array $order
     * @return string
     */
    private function getAddress($type, $order)
    {
        $address = shopHelper::getOrderAddress($order['params'], $type);
        $formatter = new waContactAddressOneLineFormatter(array('image' => FALSE));
        $address = $formatter->format(array('data' => $address));

        return $address['value'];
    }

    /**
     * @param array $order
     * @return array|NULL
     * @throws waException
     */
    private function deleteOrder($order)
    {
        $actions = $this->workflow->getStateById($order['state_id'])->getActions(NULL, TRUE);
        if (!isset($actions['delete'])) {
            $status = $this->workflow->getStateById($order['state_id'])->getName();
            throw new waException("Failed to delete order {$order['id']}. Action 'delete' is not available for order status '$status'");
        }

        $delete_action = $this->workflow->getActionById('delete');
        if (!$delete_action) {
            throw new waException("Failed to delete order {$order['id']}. getActionById('delete') fail.");
        }

        return $delete_action->run($order['id']);
    }

    /**
     * @throws waException
     */
    private function deleteOrders()
    {
        $to_delete = $this->getOrdersToDelete();

        if ($to_delete) {
            while ($order = $to_delete->fetchAssoc()) {
                try {
                    $result = $this->deleteOrder($order);
                    if (!$result) {
                        throw new waException("Failed to delete order {$order['id']}. shopWorkflowDeleteAction fail");
                    }
                    $this->debugLog("Successfully deleted order {$order['id']}");
                } catch (waException $e) {
                    $this->debugLog($e->getMessage());
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function isTestRun()
    {
        return waRequest::param('test', 0, waRequest::TYPE_INT) ? TRUE : FALSE;
    }

}