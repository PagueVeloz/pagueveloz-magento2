<?php

namespace Trezo\PagueVeloz\Model;

use Trezo\PagueVeloz\Api\Data\SqlInterface;

class Sql extends \Magento\Framework\Model\AbstractModel implements SqlInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Trezo\PagueVeloz\Model\ResourceModel\Sql');
    }

    /**
     * Get sql_id
     * @return string
     */
    public function getSqlId()
    {
        return $this->getData(self::SQL_ID);
    }

    /**
     * Set sql_id
     * @param string $sqlId
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setSqlId($sqlId)
    {
        return $this->setData(self::SQL_ID, $sqlId);
    }

    /**
     * Get order_id
     * @return string
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set order_id
     * @param string $order_id
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setOrderId($order_id)
    {
        return $this->setData(self::ORDER_ID, $order_id);
    }

    /**
     * Get amount
     * @return string
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * Set amount
     * @param string $amount
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * Get number
     * @return string
     */
    public function getNumber()
    {
        return $this->getData(self::NUMBER);
    }

    /**
     * Set number
     * @param string $number
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setNumber($number)
    {
        return $this->setData(self::NUMBER, $number);
    }

    /**
     * Get expiration
     * @return string
     */
    public function getExpiration()
    {
        return $this->getData(self::EXPIRATION);
    }

    /**
     * Set expiration
     * @param string $expiration
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setExpiration($expiration)
    {
        return $this->setData(self::EXPIRATION, $expiration);
    }

    /**
     * Get submit_dc
     * @return string
     */
    public function getSubmitDc()
    {
        return $this->getData(self::SUBMIT_DC);
    }

    /**
     * Set submit_dc
     * @param string $submit_dc
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setSubmitDc($submit_dc)
    {
        return $this->setData(self::SUBMIT_DC, $submit_dc);
    }

    /**
     * Get query_dc
     * @return string
     */
    public function getQueryDc()
    {
        return $this->getData(self::QUERY_DC);
    }

    /**
     * Set query_dc
     * @param string $query_dc
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setQueryDc($query_dc)
    {
        return $this->setData(self::QUERY_DC, $query_dc);
    }
}
