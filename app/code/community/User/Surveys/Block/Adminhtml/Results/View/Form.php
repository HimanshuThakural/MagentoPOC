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
class User_Surveys_Block_Adminhtml_Results_View_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare form action
     *
     * @return User_Surveys_Block_Adminhtml_News_Edit_Form
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('viewModel');
				
		$model->getSelect()
		->joinLeft(array('que' => 'surveys_questions'),
				'main_table.question_id = que.id',
				array('surveys_questions' => 'questions'));

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
                'legend' => $this->__('User Reviews')
            )
        );
        
        $data= $model->getData();
        
        foreach ($data as $key=>$value){
        	$text = $value['surveys_questions'].' :: '.$value['value'];
        	$fieldset->addField($value['question_id'], 'text', array(
        			'name'     => 'value'.$value['question_id'],
        			'value'    => $value['value'],
        			'label'    => Mage::helper('user_surveys')->__($text),
        			'title'    => Mage::helper('user_surveys')->__($value['question_id']),
        			'style'    => 'display:none;',
        			'required' => false,        			
        	));
        }


        //die('HERRE');
        
        $form->setValues($model->getData());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }

}

