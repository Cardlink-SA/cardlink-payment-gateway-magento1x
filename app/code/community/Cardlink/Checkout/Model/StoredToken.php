<?php

/**
 * Data model of a stored card token's information.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Model_StoredToken
{
    /**
     *  The database table entity ID.
     */
    public $entityId;

    /**
     *  The merchant ID.
     */
    public $merchantId;

    /**
     *  The customer ID.
     */
    public $customerId;

    /**
     *  The card token data.
     */
    public $token;

    /**
     *  The type of the card.
     */
    public $type;

    /**
     *  The last 4 digits of the PAN of the card.
     */
    public $lastDigits;

    /**
     *  The the expiration date of the card (format YYYYMMDD).
     */
    public $expiration;

    /**
     *  The class constructor method.
     * 
     * @param array $storedToken The database entity of a stored token.
     */
    public function __construct($storedToken)
    {
        $this->entityId = $storedToken['entity_id'];
        $this->merchantId = $storedToken['merchant_id'];
        $this->customerId = $storedToken['customer_id'];
        $this->token = $storedToken['token'];
        $this->type = $storedToken['type'];
        $this->lastDigits = $storedToken['last_digits'];
        $this->expiration = $storedToken['expiration'];
    }

    /**
     * Returns the year part of the card's expiration date.
     * 
     * @return string
     */
    public function getExpiryYear()
    {
        return substr($this->expiration, 0, 4);
    }

    /**
     * Returns the month part of the card's expiration date.
     * 
     * @return string
     */
    public function getExpiryMonth()
    {
        return substr($this->expiration, 4, 2);
    }

    /**
     * Returns the day part of the card's expiration date.
     * 
     * @return string
     */
    public function getExpiryDay()
    {
        return substr($this->expiration, 6, 2);
    }

    /**
     * Determines that the token contains valid data (not null or empty string).
     * 
     * @return bool
     */
    public function isValid()
    {
        return $this->token !== null && trim($this->token) !== '';
    }

    /**
     * Determines that the card is currently expired.
     * 
     * @return bool
     */
    public function isExpired()
    {
        return date('Ymd') > $this->expiration;
    }

    /**
     * Returns a formatted expiration date string containing the month and year parts.
     * 
     * @return string
     */
    public function getFormattedExpiryDate()
    {
        return str_pad($this->getExpiryMonth(), 2, '0', STR_PAD_LEFT) . '/' . $this->getExpiryYear();
    }
}
