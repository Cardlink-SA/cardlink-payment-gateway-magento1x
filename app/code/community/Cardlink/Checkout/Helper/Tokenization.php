<?php

/**
 * Helper class containing methods to handle card tokenization functionalities.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Helper_Tokenization
{
    /**
     * Retrieved all the stored card tokens belonging to a customer.
     * 
     * @param string $merchantId The merchant ID that the token must be bound to.
     * @param int $customerId The customer's entity ID.
     * @param bool $fetchActiveOnly Identifies that the function should only retrieve active tokens (rows that have not expired).
     * 
     * @return array Array of StoredToken objects representing the retrieved tokens of the customer.
     */
    public function getCustomerStoredTokens($merchantId, $customerId, $fetchActiveOnly = false)
    {
        $ret = array();

        if ($customerId) {
            // Fetch user stored credit card tokens

            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');

            $query = 'SELECT * FROM '
                . $resource->getTableName('cardlink_stored_tokens')
                . ' WHERE `merchant_id`=:merchantId'
                . ' AND `customer_id`=:customerId'
                . ' AND `token` IS NOT NULL ORDER BY `expiration` DESC';

            $binds = array(
                'merchantId' => $merchantId,
                'customerId' => $customerId
            );

            $results = $readConnection->fetchAll($query, $binds);

            foreach ($results as $storedTokenData) {
                $storedToken = new Cardlink_Checkout_Model_StoredToken($storedTokenData);

                if (
                    $fetchActiveOnly == false
                    || ($fetchActiveOnly == true && $storedToken->isExpired() == false)
                ) {
                    $ret[] = $storedToken;
                }
            }
        }

        return $ret;
    }

    /**
     * Retrieve a specific stored token belonging to a customer.
     * 
     * @param string $merchantId The merchant ID that the token must be bound to.
     * @param int $customerId The customer's entity ID.
     * @param int $tokenId The token's entity ID.
     * 
     * @return StoredToken|null
     */
    public function getCustomerStoredToken($merchantId, $customerId, $tokenId)
    {
        // Fetch user stored credit card token

        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        $query = 'SELECT * FROM '
            . $resource->getTableName('cardlink_stored_tokens')
            . ' WHERE `entity_id`=:tokenId AND `merchant_id`=:merchantId AND `customer_id`=:customerId'
            . ' LIMIT 1';

        $binds = array(
            'merchantId' => $merchantId,
            'customerId' => $customerId,
            'tokenId' => $tokenId
        );

        $storedTokenData = $readConnection->fetchAll($query, $binds);

        if (!empty($storedTokenData)) {
            return new Cardlink_Checkout_Model_StoredToken($storedTokenData[0]);
        }

        return null;
    }

    /**
     * Stores a new token belonging to the customer.
     * 
     * @param string $merchantId The merchant ID that the token must be bound to.
     * @param int $customerId The customer's entity ID.
     * @param string $token The actual card token.
     * @param string $type The type of the card that the token belongs to (i.e. visa, mastercard, amex, etc).
     * @param string $panLastDigits The last digits of the customer's PAN (Permanent Account Number) card.
     * @param string $panExpiration The expiration date of the customer's PAN (Permanent Account Number) card.
     */
    public function storeTokenForCustomer($merchantId, $customerId, $token, $type, $panLastDigits, $panExpiration)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $query = 'INSERT INTO '
            . $resource->getTableName('cardlink_stored_tokens')
            . ' SET `merchant_id`=:merchantId,'
            . ' `customer_id`=:customerId,'
            . ' `token`=:token,'
            . ' `type`=:type,'
            . ' `last_digits`=:lastDigits,'
            . ' `expiration`=:expiration,'
            . ' `created_at`=CURRENT_TIMESTAMP()';

        $binds = array(
            'merchantId' => $merchantId,
            'customerId' => $customerId,
            'token' => $token,
            'type' => $type,
            'lastDigits' => $panLastDigits,
            'expiration' => $panExpiration
        );

        $writeConnection->query($query, $binds);
    }

    /**
     * Method to invalidate a token in the database but still retain some information for reference purposes.
     * 
     * @param string $merchantId The merchant ID that the token must be bound to.
     * @param int $customerId The customer's entity ID.
     * @param int $tokenId The token's entity ID.
     */
    public function invalidateCustomerStoredToken($merchantId, $customerId, $tokenId)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');

        $result =  $writeConnection->update(
            $resource->getTableName('cardlink_stored_tokens'),
            array("token" => NULL),
            array(
                $writeConnection->quoteInto('`merchant_id`=?', $merchantId),
                $writeConnection->quoteInto('`customer_id`=?', $customerId),
                $writeConnection->quoteInto('`entity_id`=?', $tokenId)
            )
        );

        return $result > 0;
    }
}
