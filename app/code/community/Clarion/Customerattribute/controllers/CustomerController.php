<?php
class Clarion_Customerattribute_CustomerController extends Mage_Core_Controller_Front_Action {
    protected function getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }

    public function formAction() {
        $this->loadLayout();

        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->setTitle(Mage::helper('profileimage')->__('My Profileimage'));
        }

        $this->renderLayout();
    }
    /*
    @params
    @author Severtek
    @comments save uploaded image
    */
    public function uploadAction() {
    	/* echo "<pre>";
    	print_r($_FILES);
    	echo "</pre>";
    	exit(); */
        $session = Mage::getSingleton('core/session');
        $customer = $this->getCustomerSession()->getCustomer();

        if ($this->getRequest()->isPost()
                && isset($_FILES['profileimage-file']['name'])
                && ($_FILES['profileimage-file']['name'] != '')) {
            try {
                $customer->setDataChanges(true)->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        $this->_redirectReferer();
    }

    public function viewProfileimageAction() {
        $file = null;
        $plain = false;
        $customerSession = Mage::getSingleton('customer/session');
        if ($customerId = $this->getRequest()->getParam('id')) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
        } elseif ($customerSession->getId()) {
            $customer = $customerSession->getCustomer();
        } else {
            return $this->norouteAction();
        }
        $path = Mage::getBaseDir('media') . DS . 'customer';
        $file = $customer->getData(Clarion_Customerattribute_Model_Config::Profileimage_ATTR_CODE);
        $ioFile = new Varien_Io_File();
        $ioFile->open(array('path' => $path));
        $fileName = $ioFile->getCleanPath($path . $file);
        $path = $ioFile->getCleanPath($path);

        if ((!$ioFile->fileExists($fileName) || strpos($fileName, $path) !== 0) && !Mage::helper('core/file_storage')->processStorageFile(str_replace('/', DS, $fileName))
        ) {
            return $this->norouteAction();
        }

        if ($plain) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            switch (strtolower($extension)) {
                case 'gif':
                    $contentType = 'image/gif';
                    break;
                case 'jpg':
                    $contentType = 'image/jpeg';
                    break;
                case 'png':
                    $contentType = 'image/png';
                    break;
                default:
                    $contentType = 'application/octet-stream';
                    break;
            }

            $ioFile->streamOpen($fileName, 'r');
            $contentLength = $ioFile->streamStat('size');
            $contentModify = $ioFile->streamStat('mtime');
            $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Pragma', 'public', true)
                    ->setHeader('Content-type', $contentType, true)
                    ->setHeader('Content-Length', $contentLength)
                    ->setHeader('Last-Modified', date('r', $contentModify))
                    ->clearBody();
            $this->getResponse()->sendHeaders();
            while (false !== ($buffer = $ioFile->streamRead())) {
                echo $buffer;
            }
        } else {
            $name = pathinfo($fileName, PATHINFO_BASENAME);
            $this->_prepareDownloadResponse($name, array(
                'type' => 'filename',
                'value' => $fileName
            ));
        }
        exit();
    }
}
?>