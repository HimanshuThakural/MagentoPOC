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
class User_Surveys_Model_Observer
{
    /**
     * Event before show event item on frontend
     * If specified new post was added recently (term is defined in config) we'll see message about this on front-end.
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeSurveysDisplayed(Varien_Event_Observer $observer)
    {
        $surveysItem = $observer->getEvent()->getSurveysItem();
        $currentDate = Mage::app()->getLocale()->date();
        $surveysCreatedAt = Mage::app()->getLocale()->date(strtotime($surveysItem->getCreatedAt()));
        $daysDifference = $currentDate->sub($surveysCreatedAt)->getTimestamp() / (60 * 60 * 24);
        /*if ($daysDifference < Mage::helper('user_surveys')->getDaysDifference()) {
            Mage::getSingleton('core/session')->addSuccess(Mage::helper('user_surveys')->__('Recently added'));
        }*/
    }
}
