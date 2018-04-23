<?php

namespace Trezo\PagueVeloz\Block\Onepage;

class Success extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
    }

    public function getOrderIncrementId()
    {
        return $this->checkoutSession->getLastRealOrderId();
    }

    public function canShowBoleto()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order->getPayment()->getMethod() == \Trezo\PagueVeloz\Model\Payment::CODE) {
            return true;
        }

        return false;
    }

    public function getBoletoPrintUrl()
    {
        return $this->_urlBuilder->getUrl('pagueveloz/standard/show', array('number' => $this->getOrderIncrementId()));
    }
}
