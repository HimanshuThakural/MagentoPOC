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

class User_Surveys_Block_Adminhtml_Questions_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Init Grid default properties
     *
     */
    public function __construct()
    {   
        parent::__construct();
        $this->setId('questions_grid');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }
    
    /**
     * Prepare collection for Grid
     *
     * @return User_Surveys_Block_Adminhtml_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('user_surveys/questions')->getResourceCollection();
        $this->setCollection($collection);
         parent::_prepareCollection();
         return $this;
    }

    /**
     * Prepare Grid columns
     *
     * @return Mage_Adminhtml_Block_Catalog_Search_Grid
     */
    protected function _prepareColumns()
    {

        $this->addColumn('id', array(
            'header'    => Mage::helper('user_surveys')->__('ID #'),
            'width'     => '150px',
            'index'     => 'id',
        	
        ));

        $this->addColumn('questions', array(
            'header'    => Mage::helper('user_surveys')->__('Question Name'),
            'index'     => 'questions',
        	
        ));
        
        return parent::_prepareColumns();
    }

    /**
     * Return row URL for js event handlers
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    /**
     * Grid url getter
     *
     * @return string current grid url
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}

