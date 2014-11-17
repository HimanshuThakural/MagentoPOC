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
class Social_Events_Block_List extends Mage_Core_Block_Template
{
    /**
     * Events collection
     *
     * @var Social_Events_Model_Resource_Events_Collection
     */
    protected $_eventsCollection = null;

    /**
     * Retrieve events collection
     *
     * @return Social_Events_Model_Resource_Events_Collection
     */
    protected function _getCollection()
    {
        return  Mage::getResourceModel('social_events/events_collection');
    }

    /**
     * Retrieve prepared events collection
     *
     * @return Social_Events_Model_Resource_Events_Collection
     */
    public function getCollection()
    {
        if (is_null($this->_eventsCollection)) {
            $this->_eventsCollection = $this->_getCollection();
            $this->_eventsCollection->prepareForList($this->getCurrentPage());
        }

        return $this->_eventsCollection;
    }

    /**
     * Return URL to item's view page
     *
     * @param Social_Events_Model_Events $eventsItem
     * @return string
     */
    public function getItemUrl($eventsItem)
    {
        return $this->getUrl('*/*/view', array('id' => $eventsItem->getId()));
    }

    /**
     * Fetch the current page for the events list
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->getData('current_page') ? $this->getData('current_page') : 1;
    }

    /**
     * Get a pager
     *
     * @return string|null
     */
    public function getPager()
    {
        $pager = $this->getChild('events_list_pager');
        if ($pager) {
            $eventsPerPage = Mage::helper('social_events')->getEventsPerPage();

            $pager->setAvailableLimit(array($eventsPerPage => $eventsPerPage));
            $pager->setTotalNum($this->getCollection()->getSize());
            $pager->setCollection($this->getCollection());
            $pager->setShowPerPage(true);

            return $pager->toHtml();
        }

        return null;
    }

    /**
     * Return URL for resized Events Item image
     *
     * @param Social_Events_Model_Events $item
     * @param integer $width
     * @return string|false
     */
    public function getImageUrl($item, $width)
    {
        return Mage::helper('social_events/image')->resize($item, $width);
    }
}
