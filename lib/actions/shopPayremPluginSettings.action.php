<?php

/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 *
 */
class shopPayremPluginSettingsAction extends waViewAction
{
    /** @var  shopPayremPlugin */
    private $plugin;

    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }

        $this->plugin = wa()->getPlugin('payrem');
        $this->getResponse()->setTitle(_wp('Payment Reminder plugin settings'));
        $this->view->assign('settings_controls', $this->getControls());
        $this->view->assign('testsend_orders', $this->getLastOrders());
        $this->view->assign('cron_string', "12 */4 * * * /usr/bin/php -q " . wa()->getConfig()->getPath('root') . '/cli.php shop payremSend');
    }

    private function getControls()
    {
        return $this->plugin->getControls(array(
            'id'                  => 'payrem',
            'namespace'           => 'shop_payrem',
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper'     => '<div class="name">%s</div><div class="value">%s %s</div>'
        ));
    }

    /**
     * @return array
     */
    private function getLastOrders()
    {
        $collection = new shopOrderModel();

        $orders = $collection
            ->select('*')
            ->order('create_datetime DESC')
            ->limit(15)
            ->fetchAll();
        shopHelper::workupOrders($orders);

        return $orders;
    }
}