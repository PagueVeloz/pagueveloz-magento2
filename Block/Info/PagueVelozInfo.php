<?php

namespace Trezo\PagueVeloz\Block\Info;

use Magento\Framework\View\Element\Template;

class PagueVelozInfo extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Trezo_PagueVeloz::info/pagueveloz.phtml';

    protected $paguevelozTransactions;
    protected $orderIncrementId;
    protected $scopeConfig;
    protected $helperData;
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Trezo\PagueVeloz\Model\Sql $paguevelozTransactions,
        \Trezo\PagueVeloz\Helper\Data $helperData,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        Template\Context $context,
        array $data = []
    ) {

        parent::__construct($context, $data);

        $this->transactionRepository = $transactionRepository;
        $this->paguevelozTransactions = $paguevelozTransactions;
        $this->scopeConfig = $context->getScopeConfig();
        $this->helperData = $helperData;
    }

    /**
     * Return the complete PagueVelozShopline query url to verify the payment status
     * @return string pagueveloz query url
     */
    public function getPagueVelozInfos()
    {
        // Get Payment Info
        /** @var \Magento\Payment\Model\Info $info */
        $info = $this->getInfo();
        if ($info) {
            $queryUrl = $this->scopeConfig->getValue('payment/pagueveloz/query_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $transactions = $this->paguevelozTransactions->getCollection()
                                                           ->addFieldToSelect('*')
                                                            ->addFieldToFilter('number', $info->getOrder()->getIncrementId())
                                                            ->getFirstItem();


                                                            // echo $transactions
            $queryDc = $transactions->getQueryDc();
            $this->orderIncrementId = $transactions->getNumber();
            $paguevelozQueryUrl =  $queryUrl . '?DC=' . $queryDc;
        }
        return $paguevelozQueryUrl;
    }

    public function getBoletoPrintUrl()
    {
        return $this->helperData->getBoletoPrintUrl($this->orderIncrementId);
    }
}
