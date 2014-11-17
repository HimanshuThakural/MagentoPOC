<?php
/**
 * Customer attribute add/edit form block
 *
 * @category    Clarion
 * @package     Clarion_Customerattribute
 * @author      Clarion Magento Team
 */
class Clarion_Customerattribute_Block_Adminhtml_Customerattribute_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
  	$form = new Varien_Data_Form();
  	//$this->setForm($form);

    /*$fieldset->addField('customer_group_image', 'image',
            array(
                'name'  => 'group_image',
                'label' => Mage::helper('customer')->__('Group Image'),
                'title' => Mage::helper('customer')->__('Group Image'),
                'note'  => Mage::helper('customer')->__('Any notes for this field yoiu can add here'),
                'value' => '',
                'required' => false,
            )
        );*/


    $fieldset->addField('filename', 'image', array(
    	'name'  => 'filename',
     'label' => Mage::helper('customer')->__('Profileimage'),
     'required' => false,
     'name' => 'filename', ));




    $form->setUseContainer(true);// form renderer to output the surrounding <form> tags
    $this->setForm($form);
    return parent::_prepareForm();
  }
}
