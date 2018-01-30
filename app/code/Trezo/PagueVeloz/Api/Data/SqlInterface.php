<?php

namespace Trezo\PagueVeloz\Api\Data;

interface SqlInterface
{

    const SUBMIT_DC = 'submit_dc';
    const QUERY_DC = 'query_dc';
    const AMOUNT = 'amount';
    const NUMBER = 'number';
    const ORDER_ID = 'order_id';
    const EXPIRATION = 'expiration';
    const SQL_ID = 'sql_id';


    /**
     * Get sql_id
     * @return string|null
     */
    public function getSqlId();

    /**
     * Set sql_id
     * @param string $sql_id
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setSqlId($sqlId);

    /**
     * Get order_id
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     * @param string $order_id
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setOrderId($order_id);

    /**
     * Get amount
     * @return string|null
     */
    public function getAmount();

    /**
     * Set amount
     * @param string $amount
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setAmount($amount);

    /**
     * Get number
     * @return string|null
     */
    public function getNumber();

    /**
     * Set number
     * @param string $number
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setNumber($number);

    /**
     * Get expiration
     * @return string|null
     */
    public function getExpiration();

    /**
     * Set expiration
     * @param string $expiration
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setExpiration($expiration);

    /**
     * Get submit_dc
     * @return string|null
     */
    public function getSubmitDc();

    /**
     * Set submit_dc
     * @param string $submit_dc
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setSubmitDc($submit_dc);

    /**
     * Get query_dc
     * @return string|null
     */
    public function getQueryDc();

    /**
     * Set query_dc
     * @param string $query_dc
     * @return Trezo\PagueVeloz\Api\Data\SqlInterface
     */
    public function setQueryDc($query_dc);
}
