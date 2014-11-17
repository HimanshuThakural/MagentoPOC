<?php
/**
 * GIKO / LUOCHUNHUI.COM
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to LUOCHUNHUI/GIKO (OSL 3.0).
 * License, which extends the Open Software License (OSL 3.0).
 * The GIKO Extension License is available at this URL: 
 * http://magento.luochunhui.com/cms/magento_extension_license.html
 * The Open Software License is available at this URL: 
 * http://opensource.org/licenses/osl-3.0.php
 * 
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, GIKO is 
 * not held liable for any inconsistencies or abnormalities in the 
 * behaviour of this code. 
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by GIKO, outlined in the 
 * provided GIKO Extension License. 
 * Upon discovery of modified code in the process of support, the Licensee 
 * is still held accountable for any and all billable time GIKO spent 
 * during the support process.
 * GIKO does not guarantee compatibility with any other framework extension. 
 * GIKO is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to 
 * wo@luochunhui.com, so we can send you a copy immediately.
 *
 * @category    Magemall
 * @copyright   Copyright (c) 2012 Luoc Chunhui<wo@luochunhui.com>. (http://www.luochunhui.com)
 * @license     http://magento.luochunhui.com/docs/index/copyright  Open Software License (OSL 3.0)
 * @author      Luo Chunhui <wo@luochunhui.com>
 */

/**
 * Magemall Ajaxlogin Form Block.
 */

class User_Surveys_Block_Form extends Mage_Core_Block_Template {
    public function getMessages()
    {
        return Mage::getSingleton('customer/session')->getMessages(true);
    }

    public function getPostAction()
    {
        return Mage::getUrl('ajaxlogin/login/form', array('_secure'=>true));
    }

    public function getMethod()
    {
        return $this->getQuote()->getMethod();
    }

    public function getMethodData()
    {
        return $this->getCheckout()->getMethodData();
    }

    public function getSuccessUrl()
    {
        return $this->getUrl('*/*');
    }

    public function getErrorUrl()
    {
        return $this->getUrl('*/*');
    }

    /**
     * Retrieve username for form field
     *
     * @return string
     */
    public function getUsername()
    {
        return Mage::getSingleton('customer/session')->getUsername(true);
    }
    
}