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

class User_Surveys_Block_Adminhtml_Questions_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare form action
     *
     * @return User_Surveys_Block_Adminhtml_News_Edit_Form
     */
    protected function _prepareForm()
    {   
        $questionId = $this->getRequest()->getParam('id');
        $model = Mage::getModel('user_surveys/questions')->load($questionId);
        
        $questionData= Mage::registry('questionData');

        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/save'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));
        $form->setUseContainer(true);

        $fieldset = $form->addFieldset(
            'general',
            array(
                'legend' => $this->__('Manage Question')
            )
        );
        if ($questionData->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }

        $inputTypes = Mage::getModel('eav/adminhtml_system_config_source_inputtype')->toOptionArray();

        $fieldset->addField('type', 'select', array(
            'name' => 'input_type',
            'label' => Mage::helper('eav')->__('Question Input Type'),
            'title' => Mage::helper('eav')->__('Question Input Type'),
            'value' => 'text',
            'values'=> $inputTypes
        ));

        $fieldset->addField('questions', 'textarea', array(
            'name'     => 'quesion text',
            'label'    => Mage::helper('user_surveys')->__('Question'),
            'title'    => Mage::helper('user_surveys')->__('Question')
        ));

        $fieldset->addField('options', 'textarea', array(
            'name'     => 'Field option(s)',
            'label'    => Mage::helper('user_surveys')->__('Field option(s) if any'),
            'title'    => Mage::helper('user_surveys')->__('Field option(s)'),

            'note' => Mage::helper('user_surveys')->__('Multiple values should be separated with comma "," and Should Not CONTAIN SPACES. '),

        ));

        // Add the fields that we want to be editable.

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
