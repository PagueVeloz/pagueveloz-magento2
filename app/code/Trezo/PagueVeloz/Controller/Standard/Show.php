<?php
/**
 * A Magento 2 module named Trezo/PagueVeloz
 * Copyright (C) 2016
 *
 * This file is part of Trezo/PagueVeloz.
 *
 * Trezo/PagueVeloz is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Trezo\PagueVeloz\Controller\Standard;

class Show extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $order;
    protected $paguevelozTransactions;
    protected $context;
    protected $scopeConfig;

    /**
     * Constructor
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Framework\View\Result\PageFactory         $resultPageFactory
     * @param \Magento\Sales\Model\Order                         $order
     * @param \Trezo\PagueVeloz\Model\Sql                      $paguevelozTransactions
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\Order $order,
        \Trezo\PagueVeloz\Model\Sql $paguevelozTransactions,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);

        $this->context = $context;
        $this->order = $order;
        $this->paguevelozTransactions = $paguevelozTransactions;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return request parameter value
     *
     * @param  string $sKey
     * @return string
     */
    protected function getParam($sKey)
    {
        return $this->context->getRequest()->getParam($sKey, '');
    }

    protected function billetIsExpired($expiration)
    {
        $expirationDays = $this->scopeConfig->getValue('payment/pagueveloz/expiration_days', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $expirationDate = new \DateTime($expiration);
        $dateNow = new \DateTime('now');
        return $dateNow->diff($expirationDate)->format("%r%a") < ($expirationDays * (-1));
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $printUrl = $this->scopeConfig->getValue('payment/pagueveloz/submit_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $orderIncrementId = $this->getParam('number');
        $order = $this->order->loadByIncrementId($orderIncrementId);
        if ($order->isCanceled()) {
            return $this->getResponse()->setBody(__('This order was canceled'));
            return;
        }
        $collection = $this->paguevelozTransactions->getCollection()->addFieldToFilter('number', $orderIncrementId);
        $data = $collection->getFirstItem()->toArray();

        if ($collection->getSize() > 0) {
            $expired = $this->billetIsExpired($data['expiration']);
            if ($expired) {
                return $this->getResponse()->setBody(__('This billet is expired'));
            }

            $form = '';
            $form .= '<form method="post" id="pagueveloz_form_submit" style="visibility: hidden;" action="'. $printUrl.'">';
            $form .= '<fieldset id="submit_button_form"></fieldset>';
            $form .= '<input type="hidden" name="DC" value='.$data['submit_dc'].' id="submit_dc/>';
            $form .=  '<input id="test" name="" value="ok" onclick="submit_form.submit()" type="submit" class=" submit"/>';
            $form .=  "</form>";
            $form .= "<script type=\"text/javascript\">document.getElementById('pagueveloz_form_submit').submit();</script>";
            return $this->getResponse()->setBody($form);
        }

        return $this->getResponse()->setBody(__('Order not found'));
    }
}
