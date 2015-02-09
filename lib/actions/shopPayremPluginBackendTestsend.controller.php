<?php

/**
 * @package Payrem.controller
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.0.0
 * @copyright (c) 2015, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopPayremPluginBackendTestsendController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }

        $params = array(
            'test'  => 1,
            'email' => '',
            'delay' => 0,
            'order' => 0
        );

        $params = waRequest::post('sendtest', waRequest::TYPE_ARRAY, array()) + $params;

        try {
            $this->validate((object)$params);

            waRequest::setParam($params);
            $cli = new shopPayremSendCli();
            $cli->run();

        } catch (waException $e) {
            $this->setError($e->getMessage());
        }

        $this->response = _wp('Message sent. Check mailbox and system logs');
    }

    /**
     * @param stdClass $params
     * @throws waException
     */
    private function validate($params)
    {
        $plugin = wa('shop')->getPlugin('payrem');
        $ordersCollection = new shopOrdersCollection(array($params->order));
        $validDelays = array_filter(explode(',', preg_replace('/[^,\d]/', '', $plugin->getSettings('reminder_delay'))));
        $emailValidator = new waEmailValidator();
        $emailValidator->setOption('required', TRUE);

        if (!$emailValidator->isValid($params->email)) {
            throw new waException(_wp("Invalid e-mail"));
        }

        if (!in_array($params->delay, $validDelays)) {
            throw new waException(_wp('Invalid delay value'));
        }

        if (!$ordersCollection->count()) {
            throw new waException(_wp('Invalid order number'));
        }
    }

}