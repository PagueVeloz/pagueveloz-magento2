<?php

namespace Trezo\PagueVeloz\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $urlBuilder;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {

        parent::__construct($context);
        $this->urlBuilder = $context->getUrlBuilder();
    }

    public function getBoletoPrintUrl($orderIncrementId)
    {
        return $this->urlBuilder->getUrl('pagueveloz/standard/show', ['number' => $orderIncrementId]);
    }
}
