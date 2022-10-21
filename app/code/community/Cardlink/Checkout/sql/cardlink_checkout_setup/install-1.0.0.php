<?php

/**
 * Database migration script to create the required card token storage table and add support columns to order/quote payment tables.
 * 
 * @author Cardlink S.A.
 */

$installer = $this;
$installer->startSetup();

$storedTokensTableName = 'cardlink_stored_tokens';
$storedTokensTable = $installer->getTable($storedTokensTableName);

$table = $installer->getConnection()
    ->newTable($storedTokensTable)

    // The primay key of the table.
    ->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ),
        'Stored Token Id'
    )

    // The merchant ID that the token is bound to.
    ->addColumn(
        'merchant_id',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        30,
        array(
            'nullable' => true,
            'default' => null
        ),
        'Merchant ID'
    )

    // The foreign key to the customers table.
    ->addColumn(
        'customer_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ),
        'Customer Entity Id'
    )

    // The actual data of the card's token.
    ->addColumn(
        'token',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        255,
        array(
            'nullable' => true,
            'default' => null
        ),
        'Token Data'
    )

    // The type of the card (i.e. visa, mastercard, etc).
    ->addColumn(
        'type',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        25,
        array(
            'nullable'  => true,
            'default' => null
        ),
        'Card Type'
    )

    // The last 4 digits of the card.
    ->addColumn(
        'last_digits',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        4,
        array(
            'nullable'  => false,
        ),
        'Last 4 Digits'
    )

    // The expiration date of the card in YYYYMMDD format.
    ->addColumn(
        'expiration',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        8,
        array(
            'nullable'  => false,
        ),
        'Date of Expiration'
    )

    // The date and time that the token was inserted in the table.
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(
            'nullable'  => false,
            'default' => 'CURRENT_TIMESTAMP()'
        ),
        'Created At'
    )

    ->addIndex(
        $installer->getIdxName(
            $storedTokensTableName,
            array(
                'expiration'
            )
        ),
        array(
            'expiration'
        )
    )

    ->addIndex(
        $installer->getIdxName(
            $storedTokensTableName,
            array(
                'merchant_id',
                'customer_id'
            )
        ),
        array(
            'merchant_id',
            'customer_id'
        )
    )

    // Foreign key with cascade on delete of the linked customer entity.
    ->addForeignKey(
        $installer->getFkName($storedTokensTableName, 'customer_id', 'customer/entity', 'entity_id'),
        'customer_id',
        $installer->getTable('customer/entity'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )

    ->setComment('Cardlink Stored Customer Payment Token Entity');

$installer->getConnection()->createTable($table);

$installer->endSetup();
