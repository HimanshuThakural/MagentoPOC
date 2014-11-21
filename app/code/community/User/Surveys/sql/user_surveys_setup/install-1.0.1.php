<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    User
 * @package     User_Surveys
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @var $installer Mage_Core_Model_Resource_Setup
 */
$installer = $this;

/**
 * Creating table social_events
 */
$surveys = $installer->getConnection()
    ->newTable($installer->getTable('user_surveys/surveys'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'identity' => true,
        'nullable' => false,
        'primary'  => true,
    ), 'Surveys id')
    ->addColumn('user_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable' => true,
        'default'  => null,
    ), 'User Id')
    ->addColumn('form_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable' => true,
        'default'  => null,
    ), 'Form Id')
    ->addColumn('question_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable' => true,
        'default'  => null,
    ), 'Question Id')
    ->addColumn('value', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => true,
    ), 'Value')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => true,
        'default'  => null,
    ), 'Creation Time');
    
$installer->getConnection()->createTable($surveys);

$forms = $installer->getConnection()
    ->newTable($installer->getTable('user_surveys/surveys_forms'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'identity' => true,
        'nullable' => false,
        'primary'  => true,
    ), 'Forms id')
    ->addColumn('questions_id', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
        'nullable' => false,
        'default'  => null,
    ), 'Questions Id')
    ->addColumn('form_name', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
        'nullable' => false,
        'default'  => null,
    ), 'Form Id')
   ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TINYINTEGER, 1, array(
        'nullable' => false,
        'default'  => null,
    ), 'Status')
   ->addColumn('visibility', Varien_Db_Ddl_Table::TYPE_TINYINTEGER, 255, array(
        'nullable' => false,
        'default'  => null,
    ), 'Visibility'); 
$installer->getConnection()->createTable($forms);

$questions = $installer->getConnection()
    ->newTable($installer->getTable('user_surveys/surveys_questions'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'identity' => true,
        'nullable' => false,
        'primary'  => true,
    ), 'Questions id')
    ->addColumn('questions', Varien_Db_Ddl_Table::TYPE_TEXT, 200, array(
        'nullable' => true,
        'default'  => null,
        'value'    => 'lklkj ?',
    ), 'Questions')
    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => true,
        'default'  => null,
        'value'    => 'text ?',
    ), 'Type')
   ->addColumn('options', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => true,
        'default'  => null,
    ), 'Options');
$installer->getConnection()->createTable($questions);
$installer->endSetup();
