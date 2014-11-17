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
class User_Surveys_Block_List extends Mage_Core_Block_Template
{
    /**
     * Surveys collection
     *
     * @var User_Surveys_Model_Resource_Surveys_Collection
     */
    protected $_formsCollection = null;

    /**
     * Retrieve surveys collection
     *
     * @return User_Surveys_Model_Resource_Surveys_Collection
     */
    protected function _getCollection()
    {
        return  Mage::getResourceModel('user_surveys/forms_collection');
    }

    /**
     * Retrieve prepared surveys collection
     *
     * @return User_Surveys_Model_Resource_Surveys_Collection
     */
    public function getCollection()
    {
        if (is_null($this->_formsCollection)) {
           $this->_formsCollection = $this->_getCollection();
            $this->_formsCollection->prepareForList($this->getCurrentPage());
        }

        return $this->_formsCollection;
    }

    /**
     * Return URL to item's view page
     *
     * @param User_Surveys_Model_Surveys $surveysItem
     * @return string
     */

     public function getItemUrl()
     {
     	$model = Mage::getModel('user_surveys/forms')->getResourceCollection()->addFieldToFilter('visibility', array('eq' => 1));
     	$data = $model->getData();
        return $data[0]['id'];
     }
    
     public function getFormUrl($featuredItemId)
     {
     	return $this->getUrl('*/*/view', array('id' => $featuredItemId));
     }
      
    /**
     * Fetch the current page for the surveys list
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
        $pager = $this->getChild('surveys_list_pager');
        if ($pager) {
            $surveysPerPage = Mage::helper('user_surveys')->getSurveysPerPage();

            $pager->setAvailableLimit(array($surveysPerPage => $surveysPerPage));
            $pager->setTotalNum($this->getCollection()->getSize());
            $pager->setCollection($this->getCollection());
            $pager->setShowPerPage(true);

            return $pager->toHtml();
        }

        return null;
    }

    /**
     * Return URL for resized Surveys Item image
     *
     * @param User_Surveys_Model_Surveys $item
     * @param integer $width
     * @return string|false
     */
    public function getImageUrl($item, $width)
    {
        return Mage::helper('user_surveys/image')->resize($item, $width);
    }
}
