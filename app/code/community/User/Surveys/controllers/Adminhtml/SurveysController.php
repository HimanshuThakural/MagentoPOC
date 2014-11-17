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
class User_Surveys_Adminhtml_SurveysController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return User_Surveys_Adminhtml_SurveysController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('surveys/manage')
            ->_addBreadcrumb(
                  Mage::helper('user_surveys')->__('Surveys'),
                  Mage::helper('user_surveys')->__('Surveys')
              )
            ->_addBreadcrumb(
                  Mage::helper('user_surveys')->__('Manage Surveys'),
                  Mage::helper('user_surveys')->__('Manage Surveys')
              )
        ;
        return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->_title($this->__('Surveys'))
             ->_title($this->__('Manage Surveys'));

        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Create new Surveys item
     */
    public function newAction()
    {
        // the same form is used to create and edit
        $this->_forward('edit');
    }

    /**
     * Edit Surveys item
     */
    public function editAction()
    {
        $this->_title($this->__('Surveys'))
             ->_title($this->__('Manage Surveys'));

        // 1. instance surveys model
        /* @var $model User_Surveys_Model_Item */
        $model = Mage::getModel('user_surveys/forms');
        /*$name = Mage::getSingleton('user/surveys') ->getAttribute('catalog_product', 'name')*/
        /*$model = Mage::getModel('user_surveys/forms');*/

        // 2. if exists id, check it and load data
        $formId = $this->getRequest()->getParam('id');
        if ($formId) {
            $model->load($formId);

            if (!$model->getId()) {
                $this->_getSession()->addError(
                    Mage::helper('user_surveys')->__('Surveys item does not exist.')
                );
                return $this->_redirect('*/*/');
            }
            // prepare title
            $this->_title($model->getTitle());
            $breadCrumb = Mage::helper('user_surveys')->__('Edit Item');
        } 
        else 
        {
            $this->_title(Mage::helper('user_surveys')->__('New Item'));
            $breadCrumb = Mage::helper('user_surveys')->__('New Item');
        }

        // Init breadcrumbs
        $this->_initAction()->_addBreadcrumb($breadCrumb, $breadCrumb);

        // 3. Set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);

        if (!empty($data)) {
            $model->addData($data);
        }

        // 4. Register model to use later in blocks
        Mage::register('surveys_item', $model);
        Mage::register('formId', $formId);        

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
        echo "<pre>"; print_r($data); echo "</pre>"; 
        if ($data) {
            $data = $this->_filterPostData($data);
            // init model and set data
            /* @var $model User_Surveys_Model_Item */
            $model = Mage::getModel('user_surveys/forms');
            
            $formId = $this->getRequest()->getParam('id');
            if ($formId) {
                $model->load($formId);
            }

            // Getting questions id in array
            $questionIds = array();
            foreach ($data as $key => $value) {
                if (preg_match('/questionsid_/',$key)) {
                    $value = explode("_", $key);
                    $questionIds[] = $value[1];
                }
            }
            //converting question ids array into string
            $ids = implode(",", $questionIds);
            //getting form name from post method
            $formName= $data['form_name'];

            $status= $data['status'];
            
            $visibility= $data['visibility'];
            //echo "<pre>"; print_r($visibility); echo "</pre>"; 

            if ($visibility == 1) {
                $collection = Mage::getModel('user_surveys/forms')
                ->getCollection()
                ->addFieldToFilter('visibility', array('eq' => 1))
                ->getData();

                if ($collection) {
                    foreach ($collection as $key => $value) {
                        $id= $value['id'];
                    }
                    $model->load($id);
                    $model->setVisibility(0)->save();
                }

                if($formId) {
                    $model->load($formId);
                }
                else {
                    $model = Mage::getModel('user_surveys/forms');
                }
                $model->setQuestionsId($ids);
                $model->setFormName($formName);
                $model->setStatus($status);
                $model->setVisibility($visibility);
                $model->save();                
                // $model->setStatus(1);
            }
            else {
                // Saving to Model
                $model->setQuestionsId($ids);
                $model->setFormName($formName);
                
                $model->setStatus($status);
                $model->setVisibility($visibility);
                
                //echo "<pre>"; print_r($model); echo "</pre>"; die("Hereeeee");
                //saving into model
                $model->save();
            }
            //echo "<pre>"; print_r($model); echo "</pre>"; die("HEREEEE");
            // display success message
            $this->_getSession()->addSuccess(
            Mage::helper('user_surveys')->__('Survey Form Edited Sucessfully'));
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
                /** @var $model User_Surveys_Model_Item */
                $model = Mage::getModel('user_surveys/forms');
                $model->load($itemId);
                if (!$model->getId()) {
                    Mage::throwException(Mage::helper('user_surveys')->__('Unable to find a Survey'));
                }
                $model->delete();

                // display success message
                $this->_getSession()->addSuccess(
                    Mage::helper('user_surveys')->__('Survey Form deleted.')
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('user_surveys')->__('An error occurred while deleting the Form.')
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
                return Mage::getSingleton('admin/session')->isAllowed('surveys/manage/save');
                break;
            case 'delete':
                return Mage::getSingleton('admin/session')->isAllowed('surveys/manage/delete');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('surveys/manage');
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
     * Flush Surveys Posts Images Cache action
     */
    public function flushAction()
    {
        if (Mage::helper('user_surveys/image')->flushImagesCache()) {
            $this->_getSession()->addSuccess('Cache successfully flushed');
        } else {
            $this->_getSession()->addError('There was error during flushing cache');
        }
        $this->_forward('index');
    }

    /**
     * Index action
     */
    public function resultsAction()
    {
        $this->_title($this->__('Feedbacks'))
             ->_title($this->__('Manage Feedbacks'));
	
		$id = $this->getRequest()->getParam('id');
        $model =  Mage::getModel('user_surveys/forms')->load($id);
        $formId= $model->getId();
  
        $collection = Mage::getResourceModel('user_surveys/surveys_collection')
        ->addFieldToFilter('form_id', array('eq' => $formId));    
   
        $collection->getSelect()
        ->joinLeft(array('cus' => 'customer_entity'),
                         'main_table.user_id = cus.entity_id',
                   array('customer_email' => 'email'))
        ->group('user_id');
     
        /*Start By Ankush*/
        Mage::register('collection', $collection);
        /*End By Ankush*/
        
		$this->_initAction();
        $this->renderLayout();    
    }
    
    /* Start By Ankush*/

    public function viewAction()
    {
    	$this->_title($this->__('View'))
    		 ->_title($this->__('Surveys Feedback'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('user_surveys/surveys');
        $model->load($id);        
        
        $formId= $model->getFormId();
        $userId= $model->getUserId();
        
        $models = Mage::getModel('user_surveys/surveys')->getCollection();
        $models->addFieldToFilter('user_id', array('eq' => $userId));
        $models->addFieldToFilter('form_id', array('eq' => $formId));
        $models->load();
		Mage::register('viewModel', $models); 
     
        $this->_initAction();
        $this->renderLayout();
    }
}
