<?php

namespace Trezo\PagueVeloz\Cron;

class Cron
{
    protected $logger;
    protected $scopeConfig;
    protected $orderCollectionFactory;
    protected $resourceIterator;
    protected $orderObject;
    protected $paguevelozTransactions;
    protected $invoiceService;
    protected $transaction;
    protected $transportBuilder;
    protected $storeManager;
    protected $inlineTranslation;
    protected $timezoneInterface;

/**
 * Constructor
 * @param \Psr\Log\LoggerInterface                                   $logger
 * @param \Magento\Framework\App\Config\ScopeConfigInterface         $scopeConfig
 * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
 * @param \Magento\Framework\Model\ResourceModel\Iterator            $resourceIterator
 * @param \Magento\Sales\Model\Order                                 $orderObject
 * @param \Trezo\PagueVeloz\Model\Sql                              $paguevelozTransactions
 * @param \Magento\Sales\Model\Service\InvoiceService                $invoiceService
 * @param \Magento\Framework\DB\Transaction                          $transaction
 * @param \Magento\Framework\Mail\Template\TransportBuilder          $transportBuilder
 * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
 * @param \Magento\Framework\Translate\Inline\StateInterface         $inlineTranslation
 */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Model\ResourceModel\Iterator $resourceIterator,
        \Magento\Sales\Model\Order $orderObject,
        \Trezo\PagueVeloz\Model\Sql $paguevelozTransactions,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->resourceIterator = $resourceIterator;
        $this->orderObject = $orderObject;
        $this->paguevelozTransactions = $paguevelozTransactions;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $orders = $this->orderCollectionFactory->create()->addAttributeToSelect('*');
        $orders->addAttributeToFilter('status', [
                                                'in' => [
                                                            'pending',
                                                            'pendingpreorder',
                                                            'pending_payment'
                                                        ]
                                                ]);
        $orders->getSelect()->joinLeft(['payment_table' => 'sales_order_payment'], 'main_table.entity_id = payment_table.parent_id', ['method'], null);
        // Dentre os pendentes, apenas com forma de pagamento PagueVelozShopline
        $orders->addAttributeToFilter('payment_table.method', array('in' => array(\Trezo\PagueVeloz\Model\Payment::CODE)));
        $this->resourceIterator->walk($orders->getSelect(), [[$this, 'callbackPagueVelozReturn']]);
    }

    public function callbackPagueVelozReturn(array $args)
    {
        $actualDate = $this->timezoneInterface->date()->format('Y-m-d');
        $order = $this->orderObject->load($args['row']['entity_id']);
        $code = $this->scopeConfig->getValue('payment/pagueveloz/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $key = $this->scopeConfig->getValue('payment/pagueveloz/key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $queryUrl = $this->scopeConfig->getValue('payment/pagueveloz/query_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $format = '1'; //retorno sera dado em XML caso seja 0 o retorno eh html
        $number = $order->getIncrementId();
        $paguevelozOrderNumber = substr($number, -8);
        $orderId = $order->getId();
        $paguevelozDataQuery = $this->paguevelozCripto->geraConsulta($code, $paguevelozOrderNumber, $format, $key);
        $link = $queryUrl . '?dc=' . $paguevelozDataQuery;
        $xml = simplexml_load_file($link);

        foreach ($xml->PARAMETER->PARAM as $paguevelozXml) {
            switch ($paguevelozXml['ID']) {
                case 'codEmp':
                    $codEmp = (string)$paguevelozXml['VALUE'];
                    break;
                case 'Pedido':
                    $paguevelozOrderNumber = (string)$paguevelozXml['VALUE'];
                    break;
                case 'Valor':
                    $paguevelozAmount = (string)$paguevelozXml['VALUE'];
                    break;
                case 'tipPag':
                    $paguevelozPaymentType = (string)$paguevelozXml['VALUE'];
                    break;
                case 'sitPag':
                    $paguevelozPaymentSituation = (string)$paguevelozXml['VALUE'];
                    break;
                case 'dtPag':
                    $paguevelozPaidDate = (string)$paguevelozXml['VALUE'];
                    break;
                case 'codAuto':
                    $codAuto = (string)$paguevelozXml['VALUE'];
                    break;
                case 'numId':
                    $paguevelozNumberId = (string)$paguevelozXml['VALUE'];
                    break;
                case 'compVend':
                    $compVend  = (string)$paguevelozXml['VALUE'];
                    break;
                case 'tipCart':
                    $tipCart   = (string)$paguevelozXml['VALUE'];
                    break;
            }
        }

        /*
         ###########################################################
        TRATAMENTO DE ERROS
        ###########################################################
        */
        if ($paguevelozPaymentSituation != 00) {
            $transactions = $this->paguevelozTransactions->getCollection()->addFieldToFilter('number', $order->getIncrementId())->getFirstItem();
            $transExpiration = $transactions->getExpiration();
            //ACAO CANCELAR PEDIDO - (fora do prazo)
            // && $paguevelozPaymentType == 00 && $paguevelozPaymentSituation == 03 ||  && $paguevelozPaymentType == 02 && $paguevelozPaymentSituation == 04
            if ($actualDate > $transExpiration && ($paguevelozPaymentType == 00 && $paguevelozPaymentSituation == 03 || $paguevelozPaymentType == 02 && $paguevelozPaymentSituation == 04)) {
                $order->cancel();
                $this->sendCancellationEmail($order);
                //Adiciona entrada no historico do pedido avisando do sucesso
                $order->addStatusHistoryComment($paguevelozPaymentType.':'.$paguevelozPaymentSituation.': Pedido cancelado (Fora do prazo) -'.$actualDate.":".$transExpiration.'');
                //salva ordem
                $order->save();
            }
        }

        /*
        ###########################################################
        PAGAMENTO CONFIRMADO (00 - Pagamento efetuado)
        ###########################################################
        */

        if (!isset($paguevelozAmount) || empty($paguevelozAmount)) {
            // $this->_debug('Valor pago não foi encontrado no XML de retorno!');
            return;
        }

        $paguevelozAmountPago =  str_ireplace(',', '', $paguevelozAmount);
        $paguevelozAmountPedido = $this->formatInput($order->getGrandTotal());
        //Faz a comparacao entre o valor resgatado no XML e o valor do pedido
        $this->logger->addInfo($number);
        if ($paguevelozAmountPago == $paguevelozAmountPedido && $paguevelozPaymentSituation == 00) {
            // Adiciona entrada no historico do pedido avisando do sucesso
            $order->addStatusHistoryComment($paguevelozPaymentType.":".$paguevelozPaymentSituation.": Pagamento confirmado no PagueVeloz! - Valor total de ".$paguevelozAmountPago." data do pagamento:".$paguevelozPaidDate);

            $order->setState('processing');
            // Muda status
            if ($order->getStatus() == 'pendingpreorder') {
                $order->setStatus('processingpreorder');
            } else {
                $order->setStatus('approved');
            }

            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();
            }

            $order->save();
        } elseif ($paguevelozPaymentSituation == 00 && $paguevelozAmountPago != $paguevelozAmountPedido) {
            /*
            ###########################################################
            VALOR DIVERGENTE
            ###########################################################
            */

            // Adiciona entrada no historico do pedido avisando do sucesso
            $order->addStatusHistoryComment("Atencao: Pedido com valor divergente no PagueVeloz!".$paguevelozPaymentType.":".$paguevelozPaymentSituation." - Valor pago: ".$paguevelozAmountPago);
            $order->save();
            // Envia email de notificao
            $this->notifyAdmin($number, $paguevelozOrderNumber, $paguevelozPaymentSituation, $paguevelozAmountPago);
            // Muda status
            $order->setStatus('valor_divergente');
            $order->save();
        }
    }

    protected function formatInput($amount)
    {
        $amountStr = number_format($amount, 2, '', '');
        $amountStr = str_ireplace(".", "", $amountStr);
        $amountStr = str_ireplace(",", "", $amountStr);

        return $amountStr;
    }

    protected function notifyAdmin($paguevelozOrderNumber, $orderIncrementId, $paguevelozPaymentSituation, $amountPaid = 0)
    {
        $enableEmail = $this->scopeConfig->getValue('payment/pagueveloz/enable_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($enableEmail) {
            $emailTo = $this->scopeConfig->getValue('payment/pagueveloz/email_to', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $emailFrom = $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $emailFromName = $this->scopeConfig->getValue('trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $this->inlineTranslation->suspend();
            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData([
                            'paguevelozOrder' => $paguevelozOrderNumber,
                            'magentoOrder' => $orderIncrementId,
                            'paymentSituation' => $paguevelozPaymentSituation,
                            'paidAmount' => $amountPaid
                        ]);

            $sender = [
                        'name' => $emailFromName,
                        'email' => $emailFrom,
                    ];

            $transport = $this->transportBuilder->setTemplateIdentifier('trezo_pagueveloz_cron')
                                                ->setTemplateOptions(
                                                    [
                                                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                                                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                                                    ]
                                                )
                                                ->setTemplateVars(['data' => $postObject])
                                                ->setFrom($sender)
                                                ->addTo($emailTo)
                                                ->getTransport();
            try {
                $transport->sendMessage();
            } catch (\Exception $e) {
                $this->logger->addInfo("Error on send admin divergent value email. error: " . $e->getMessage());
            }
            $this->inlineTranslation->resume();
        }
    }

    protected function sendCancellationEmail($order)
    {
        $emailFrom = $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $emailFromName = $this->scopeConfig->getValue('trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->inlineTranslation->suspend();
        $postObject = new \Magento\Framework\DataObject();
        $postObject->setData([
                        'order_id' => $order->getIncrementId()
                    ]);

        $sender = [
                    'name' => $emailFromName,
                    'email' => $emailFrom,
                ];

        $cancelationEmailTemplate = $this->scopeConfig->getValue('payment/pagueveloz/cancellation_email_template', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $transport = $this->transportBuilder->setTemplateIdentifier($cancelationEmailTemplate)
                                            ->setTemplateOptions(
                                                [
                                                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                                                'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                                                ]
                                            )
                                            ->setTemplateVars(['data' => $postObject])
                                            ->setFrom($sender)
                                            ->addTo($order->getCustomerEmail())
                                            ->getTransport();
        try {
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->addInfo("Error on send cancelation email. error: " . $e->getMessage());
        }
        $this->inlineTranslation->resume();
    }
}
