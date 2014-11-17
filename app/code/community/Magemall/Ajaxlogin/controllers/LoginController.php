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
 * Magemall Ajaxlogin Login Controller
 */

class Magemall_Ajaxlogin_LoginController extends Mage_Core_Controller_Front_Action
{
	/**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }
    
    public function formAction() {
        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $login = $session->login($login['username'], $login['password']);
                    $this->getResponse()->setBody('<script>top.location.reload();</script>');
                    return ;
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        }
        $this
            ->loadLayout()
            ->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }
}