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
class User_Surveys_Block_Adminhtml_Surveys extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Block constructor
     */
    public function __construct()
    {
        $this->_blockGroup = 'user_surveys';
        $this->_controller = 'adminhtml_surveys';
        $this->_headerText = Mage::helper('user_surveys')->__('Manage Surveys');

        parent::__construct();

        if (Mage::helper('user_surveys/admin')->isActionAllowed('save')) {
            $this->_updateButton('add', 'label', Mage::helper('user_surveys')->__('Add New Survey'));
        } else {
            $this->_removeButton('add');
        }
        $this->addButton(
        		'user_surveys_featured_form_id',
        		array(
        				'label'      => Mage::helper('user_surveys')->__('Get Form Code'),
        				'onclick'    => 'setLocation(\'' . $this->getUrl('*/*/getCode').'\')',
        		)
        );
    }
}
