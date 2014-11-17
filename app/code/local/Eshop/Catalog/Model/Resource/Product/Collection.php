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
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product collection
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Eshop_Catalog_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    protected function _getSelectCountSql($select = null, $resetLeftJoins = true)
    {
       $this->_renderFilters();
       $countSelect = (is_null($select)) ?
           $this->_getClearSelect() :
           $this->_buildClearSelect($select);
       if(count($countSelect->getPart(Zend_Db_Select::GROUP)) > 0) {
           $countSelect->reset(Zend_Db_Select::GROUP);
       }
       $countSelect->columns('COUNT(DISTINCT e.entity_id)');
       if ($resetLeftJoins) {
           $countSelect->resetJoinLeft();
       }
       return $countSelect;
    }
}
