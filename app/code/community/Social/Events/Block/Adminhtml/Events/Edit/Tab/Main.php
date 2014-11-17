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
 * @category    Social
 * @package     Social_Events
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Social_Events_Block_Adminhtml_Events_Edit_Tab_Main
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Prepare form elements for tab
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $model = Mage::helper('social_events')->getEventsItemInstance();
		
        /**
         * Checking if user have permissions to save information
         */
        if (Mage::helper('social_events/admin')->isActionAllowed('save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('news_main_');

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('social_events')->__('Events Item Info')
        ));

        if ($model->getId()) {
            $fieldset->addField('events_id', 'hidden', array(
                'name' => 'events_id',
            ));
        }

        $fieldset->addField('title', 'text', array(
            'name'     => 'title',
            'label'    => Mage::helper('social_events')->__('Events Title'),
            'title'    => Mage::helper('social_events')->__('Events Title'),
            'required' => true,
            'disabled' => $isElementDisabled
        ));

        $fieldset->addField('category', 'text', array(
            'name'     => 'category',
            'label'    => Mage::helper('social_events')->__('Category'),
            'title'    => Mage::helper('social_events')->__('Category'),
            'required' => true,
            'disabled' => $isElementDisabled
        ));

        $fieldset->addField('venue', 'text', array(
            'name'     => 'venue',
            'label'    => Mage::helper('social_events')->__('Venue'),
            'title'    => Mage::helper('social_events')->__('Venue'),
            'required' => true,
            'disabled' => $isElementDisabled
        ));

		 $fieldset->addField('entry_fee', 'text', array(
            'name'     => 'entry_fee',
            'label'    => Mage::helper('social_events')->__('Entry Fee'),
            'title'    => Mage::helper('social_events')->__('Entry Fee'),
            'required' => true,
            'disabled' => $isElementDisabled
        ));

        $fieldset->addField('contact_details', 'text', array(
            'name'     => 'contact_details',
            'label'    => Mage::helper('social_events')->__('Contact Details'),
            'title'    => Mage::helper('social_events')->__('Contact Details'),
            'required' => true,
            'disabled' => $isElementDisabled
        ));

        $fieldset->addField('published_at', 'date', array(
            'name'     => 'published_at',
            'format'   => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
            'image'    => $this->getSkinUrl('images/grid-cal.gif'),
            'label'    => Mage::helper('social_events')->__('Publishing Date'),
            'title'    => Mage::helper('social_events')->__('Publishing Date'),
            'required' => true
        ));

        $fieldset->addField('end_date', 'date', array(
            'name'     => 'end_date',
            'format'   => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
            'image'    => $this->getSkinUrl('images/grid-cal.gif'),
            'label'    => Mage::helper('social_events')->__('End Date'),
            'title'    => Mage::helper('social_events')->__('End Date'),
            'required' => true
        ));

        Mage::dispatchEvent('adminhtml_events_edit_tab_main_prepare_form', array('form' => $form));

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('social_events')->__('Events Info');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('social_events')->__('Events Info');
    }

    /**
     * Returns status flag about this tab can be shown or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }
}
