<?php

/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 *
 */
class shopPayremPluginSettingsAction extends waViewAction
{
    /** @var  shopPayremPlugin */
    private $plugin;

    protected function preExecute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }

        $this->plugin = wa()->getPlugin('payrem');
    }

    public function execute()
    {
        $this->getResponse()->setTitle(_wp('Payment Reminder plugin settings'));
        $this->view->assign('settings_controls', $this->getControls());
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
}