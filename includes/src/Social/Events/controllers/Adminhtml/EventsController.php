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
class Social_Events_Adminhtml_EventsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return Social_Events_Adminhtml_EventsController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('events/manage')
            ->_addBreadcrumb(
                  Mage::helper('social_events')->__('Events'),
                  Mage::helper('social_events')->__('Events')
              )
            ->_addBreadcrumb(
                  Mage::helper('social_events')->__('Manage Events'),
                  Mage::helper('social_events')->__('Manage Events')
              )
        ;
        return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->_title($this->__('Events'))
             ->_title($this->__('Manage Events'));

        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Create new Events item
     */
    public function newAction()
    {
        // the same form is used to create and edit
        $this->_forward('edit');
    }

    /**
     * Edit Events item
     */
    public function editAction()
    {
        $this->_title($this->__('Events'))
             ->_title($this->__('Manage Events'));

        // 1. instance events model
        /* @var $model Social_Events_Model_Item */
        $model = Mage::getModel('social_events/events');

        // 2. if exists id, check it and load data
        $eventsId = $this->getRequest()->getParam('id');
        if ($eventsId) {
            $model->load($eventsId);

            if (!$model->getId()) {
                $this->_getSession()->addError(
                    Mage::helper('social_events')->__('Events item does not exist.')
                );
                return $this->_redirect('*/*/');
            }
            // prepare title
            $this->_title($model->getTitle());
            $breadCrumb = Mage::helper('social_events')->__('Edit Item');
        } else {
            $this->_title(Mage::helper('social_events')->__('New Item'));
            $breadCrumb = Mage::helper('social_events')->__('New Item');
        }

        // Init breadcrumbs
        $this->_initAction()->_addBreadcrumb($breadCrumb, $breadCrumb);

        // 3. Set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        // 4. Register model to use later in blocks
        Mage::register('events_item', $model);

        // 5. render layout
        $this->renderLayout();
    }

    /**
     * Save action
     */
    public function saveAction()
    {
        $redirectPath   = '*/*';
        $redirectParams = array();

        // check if data sent
        $data = $this->getRequest()->getPost();
        if ($data) {
            $data = $this->_filterPostData($data);
            // init model and set data
            /* @var $model Social_Events_Model_Item */
            $model = Mage::getModel('social_events/events');

            // if events item exists, try to load it
            $eventsId = $this->getRequest()->getParam('events_id');
            if ($eventsId) {
                $model->load($eventsId);
            }
            // save image data and remove from data array
            if (isset($data['image'])) {
                $imageData = $data['image'];
                unset($data['image']);
            } else {
                $imageData = array();
            }
            $model->addData($data);

            try {
                $hasError = false;
                /* @var $imageHelper Social_Events_Helper_Image */
                $imageHelper = Mage::helper('social_events/image');
                // remove image

                if (isset($imageData['delete']) && $model->getImage()) {
                    $imageHelper->removeImage($model->getImage());
                    $model->setImage(null);
                }

                // upload new image
                $imageFile = $imageHelper->uploadImage('image');
                if ($imageFile) {
                    if ($model->getImage()) {
                        $imageHelper->removeImage($model->getImage());
                    }
                    $model->setImage($imageFile);
                }
                // save the data
                $model->save();

                // display success message
                $this->_getSession()->addSuccess(
                    Mage::helper('social_events')->__('The events item has been saved.')
                );

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $redirectPath   = '*/*/edit';
                    $redirectParams = array('id' => $model->getId());
                }
            } catch (Mage_Core_Exception $e) {
                $hasError = true;
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $hasError = true;
                $this->_getSession()->addException($e,
                    Mage::helper('social_events')->__('An error occurred while saving the events item.')
                );
            }

            if ($hasError) {
                $this->_getSession()->setFormData($data);
                $redirectPath   = '*/*/edit';
                $redirectParams = array('id' => $this->getRequest()->getParam('id'));
            }
        }

        $this->_redirect($redirectPath, $redirectParams);
    }

    /**
     * Delete action
     */
    public function deleteAction()
    {
        // check if we know what should be deleted
        $itemId = $this->getRequest()->getParam('id');
        if ($itemId) {
            try {
                // init model and delete
                /** @var $model Social_Events_Model_Item */
                $model = Mage::getModel('social_events/events');
                $model->load($itemId);
                if (!$model->getId()) {
                    Mage::throwException(Mage::helper('social_events')->__('Unable to find a event item.'));
                }
                $model->delete();

                // display success message
                $this->_getSession()->addSuccess(
                    Mage::helper('social_events')->__('The event item has been deleted.')
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('social_events')->__('An error occurred while deleting the event item.')
                );
            }
        }

        // go to grid
        $this->_redirect('*/*/');
    }

    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'new':
            case 'save':
                return Mage::getSingleton('admin/session')->isAllowed('events/manage/save');
                break;
            case 'delete':
                return Mage::getSingleton('admin/session')->isAllowed('events/manage/delete');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('events/manage');
                break;
        }
    }

    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array('time_published'));
        return $data;
    }

    /**
     * Grid ajax action
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Flush Events Posts Images Cache action
     */
    public function flushAction()
    {
        if (Mage::helper('social_events/image')->flushImagesCache()) {
            $this->_getSession()->addSuccess('Cache successfully flushed');
        } else {
            $this->_getSession()->addError('There was error during flushing cache');
        }
        $this->_forward('index');
    }
}
