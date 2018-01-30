<?php
namespace Trezo\PagueVeloz\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use PagueVeloz\PagueVeloz;

class Payment extends AbstractMethod
{
    const CODE                                      = 'pagueveloz';
    const CPF                                       = '01';
    const CNPJ                                      = '02';

    protected $_infoBlockType = 'Trezo\PagueVeloz\Block\Info\PagueVelozInfo';

    protected $_code = self::CODE;
    protected $_isOffline                   = false;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;

    protected $_isInitializeNeeded          = true;

    protected $_paguevelozCode            = null;
    protected $_paguevelozKey             = null;
    protected $_logger                      = null;
    protected $_paguevelozTransactions    = null;
    protected $_timezoneInterface;
    protected $_productMetadata;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Trezo\PagueVeloz\Model\Sql $paguevelozTransactions,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        $this->_logger = $logger;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_paguevelozTransactions = $paguevelozTransactions;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_productMetadata = $productMetadata;
        PagueVeloz::Url('https://www.pagueveloz.com.br/api');
    }

    /**
     * Initialize
     */
    public function initialize($action, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $this->proccessBoleto($payment);

        if (($status = $this->getConfigData('order_status'))) {
            $stateObject->setStatus($status);
            $state = \Magento\Sales\Model\Order::STATE_NEW;
            $stateObject->setState($state);
            $stateObject->setIsNotified(true);
        }

        return $this;
    }

    public function proccessBoleto(\Magento\Payment\Model\InfoInterface $payment)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $orderId = $order->getId();
        $orderIncrementId = $order->getIncrementId();
        $expirationConfig = $this->getConfigData('expiration_days');
        $bankExpiration = $this->getExpirationWithBusinessDays($expirationConfig, 'dmY');
        $observationExpiration = $this->getExpirationWithBusinessDays($expirationConfig, 'd/m/Y');
        $aditionalDaysToTransactionExpirationConfig = $this->getConfigData('aditional_days_to_transaction_expiration');
        $transactionExpirationDays = $expirationConfig;
        $amount = $payment->getOrder()->getGrandTotal();

        //adiciona dias a mais para a expiração da transação do boleto, ideal para quando o banco demora a confirmar o pagamento.
        if ($aditionalDaysToTransactionExpirationConfig) {
            $transactionExpirationDays += $aditionalDaysToTransactionExpirationConfig;
        }

        $email = $this->getConfigData('email');
        $token = $this->getConfigData('token');

        /*
        $token = 'aba82741-59bb-45c8-ada5-de7b0b38725f';
        $email = 'andre@trezo.com.br';
        //$token = base64_encode($email . ":" . $token);

        $boleto = PagueVeloz::Boleto();
        $boleto->auth
            ->setEmail($email)
            ->setToken($token);
          //  ->setSenha('QSOtNR9mLhYR');

        $boleto->dto->setValor(1)
            ->setVencimento('25-01-2018')
            ->setSeuNumero('123')
            ->setSacado('Eu')
            ->setCPFCNPJSacado('01234567890')
            ->setParcela(1)
            ->setLinha1('oi')
            ->setLinha2('xau')
            ->setPdf(0);

        $response = $boleto->Post();
        $boletoPagueVeloz = json_decode($response->body);
        */

        $transactionExpiration = $this->getExpirationWithBusinessDays($transactionExpirationDays, 'd-m-Y');
        $address = $order->getBillingAddress();
        $name = $address->getName();
        $taxVat = str_replace([',', ' ', '.', '-', '/'], '', $order->getCustomerTaxvat());
        $obsadd1 = sprintf(__('ORDER NUMBER: %s'), $orderIncrementId);
        $obsadd2 = $this->getConfigData('obsadd3');

        $boleto = PagueVeloz::Boleto();
        $boleto->auth
            ->setEmail($email)
            ->setToken($token);

        $boleto->dto->setValor($amount)
            ->setVencimento($transactionExpiration)
            ->setSeuNumero($orderIncrementId)
            ->setSacado($name)
            ->setCPFCNPJSacado($taxVat)
            ->setParcela(1)
            ->setLinha1($obsadd1)
            ->setLinha2($obsadd2)
            ->setPdf($this->getConfigData('pdf_enabled'));

        $response = $boleto->Post();
        $boletoPagueVeloz = json_decode($response->body);

        /**
        class stdClass#9 (2) {
          public $Id =>
          string(7) "1220518"
          public $Url =>
          string(81) "https://www.pagueveloz.com.br/Boleto/01694741500000001000002001000665300021478200"
        }
        */

        $this->_paguevelozTransactions->setOrderId($orderId);
        $this->_paguevelozTransactions->setAmount($amount);
        $this->_paguevelozTransactions->setExpiration($transactionExpiration);
        $this->_paguevelozTransactions->setNumber($orderIncrementId);
        $this->_paguevelozTransactions->setUrl($boletoPagueVeloz->Url);
        $this->_paguevelozTransactions->setPagueVelozId($boletoPagueVeloz->Id);
        $this->_paguevelozTransactions->save();

        $payment->setAmount($amount);
        $payment->setLastTransId($orderId);

        return $this;
    }

    /**
     * Calculate Boleto expiration date (considering business days)
     * @param  int    $numberOfDays
     * @param  string $format
     * @return date
     */
    public function getExpirationWithBusinessDays($numberOfDays, $format = 'Y-m-d')
    {
        // @TODO create admin fields to save holidays
        $dateAct = $this->_timezoneInterface->date()->format('Y-m-d H:i:s');
        $date = new \DateTime($dateAct);
        $inicialTimeStamp = $date->getTimestamp();
        $timestemp = $date->getTimestamp();

        // loop for X days
        for ($i=0; $i<$numberOfDays; $i++) {
            // add 1 day to timestamp
            $addDay = 86400;

            // get what day it is next day
            $nextDay = date('w', ($timestemp+$addDay));

            // if it's Saturday or Sunday get $i-1
            if ($nextDay == 0 || $nextDay == 6) {
                $i--;
            }

            // modify timestamp, add 1 day
            $timestemp = $timestemp+$addDay;
        }

        $holidayDates = $this->getHolidayDates();
        for ($i=0; $i < sizeof($holidayDates); $i++) {
            //  foreach ($holidayDates as $holidayDate){
            $holidayDateTime = new \DateTime($holidayDates[$i]);
            $holidayDateTimeStamp = $holidayDateTime->getTimestamp();
            $dayOfWeek = date('w', ($holidayDateTimeStamp));
            // if isen't Saturday or Sunday
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                if ($holidayDateTimeStamp >= $inicialTimeStamp && $holidayDateTimeStamp <= $timestemp) {
                    // add 1 day to timestamp
                    $addDay = 86400;
                    $timestemp = $timestemp+$addDay;
                }
            }

            $dayOfWeek = date('w', ($timestemp));
            while ($dayOfWeek == 0 || $dayOfWeek == 6) {
                $addDay = 86400;
                $timestemp = $timestemp+$addDay;
                $dayOfWeek = date('w', ($timestemp));
            }
        }

        $date->setTimestamp($timestemp);

        return $date->format($format);
    }

    public function getHolidayDates()
    {
        if (!$this->getConfigData('holidays')) {
            return false;
        }

        // Magento 2.2 backward incompatible changes
        if (version_compare($this->_productMetadata->getVersion(), '2.2.0', '<')) {
            $holidayArray = unserialize($this->getConfigData('holidays'));
        } else {
            $holidayArray = json_decode($this->getConfigData('holidays'), true);
        }
                $holidayDates = array();
        $year = date('Y');

        foreach ($holidayArray as $holiday) {
            //remove empty spaces
            $holidayDate = str_replace(' ', '', $holiday['date']);
            if ($holidayDate != '') {
                $dataArray = explode('/', $holidayDate);
                $month = '';
                $day = '';

                if (array_key_exists(0, $dataArray)) {
                    $day = $dataArray[0];
                }

                if (array_key_exists(1, $dataArray)) {
                    $month = $dataArray[1];
                }

                //id is a valid date
                if (checkdate($month, $day, $year)) {
                    $fullDate = $year . $month . $day;
                    $holidayDates[] = $fullDate;
                }
            }
        }

        //remove duplicated values
        $holidayDates = array_unique($holidayDates);
        //sort
        sort($holidayDates);

        return $holidayDates;
    }
}
