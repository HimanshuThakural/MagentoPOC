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

/*Start by Atul Pathak*/
class User_Surveys_Adminhtml_QuestionsController extends Mage_Adminhtml_Controller_Action
{
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

    public function indexAction()
    {   
        $this->_title($this->__('Questions'))->_title($this->__('Questions Inventory'));
        $this->loadLayout();
        $this->_setActiveMenu('surveys/surveys');
        $this->_addContent($this->getLayout()->createBlock('user_surveys/adminhtml_questions'));
        $this->renderLayout();
    }

    /**
     * Create new Questions
     */
    public function newAction()
    {
        // same form is used to create and edit
        $this->_forward('edit');
    }

    public function editAction()
    {   
        $this->_title($this->__('Surveys'))
             ->_title($this->__('Manage Questions'));
        
        $model = Mage::getModel('user_surveys/questions');

        $questionId = $this->getRequest()->getParam('id');
        
        if ($questionId) {
            $model->load($questionId);

            if (!$model->getId()) {
                $this->_getSession()->addError(
                    Mage::helper('user_surveys')->__('Question no longer exist.')
                );
                return $this->_redirect('*/*/');
            }

            $this->_title($model->getTitle());
            $breadCrumb = Mage::helper('user_surveys')->__('Edit Question');
        }
        else 
        {
            $this->_title(Mage::helper('user_surveys')->__('New Question'));
            $breadCrumb = Mage::helper('user_surveys')->__('New Item');
        }

        // Init breadcrumbs
        $this->_initAction()->_addBreadcrumb($breadCrumb, $breadCrumb);

        // 3. Set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);

        if (!empty($data)) {
            $model->addData($data);
        }

        Mage::register('questionData', $model);
        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveAction()
    {   
        $redirectPath   = '*/*';
        $redirectParams = array();
        $msg= "Added";

        //data from post
        $data = $this->getRequest()->getPost();
        echo '<pre>'; print_r($data); echo '</pre>';
        if ($data) {
            //load questions model for saving    
            $model = Mage::getModel('user_surveys/questions');
        
            //check for id for edit action
            $id = $this->getRequest()->getParam('id');

            if($id) {
                $model->load($id);
                $msg= "Edited";
            }

            //get question value
            $question= $data['quesion_text'];

            //get type value
            $type= $data['input_type'];

            //get options
            $options= $data['Field_option(s)'];
            $array_options = explode(',', $options);
            $trimmed_array=array_map('trim',$array_options);
            $comma_separated_options = implode(",", $trimmed_array);
            
            // Using setters to set in Model
            $model->setQuestions($question);
            $model->setType($type);
            $model->setOptions($comma_separated_options);
            
            //saving into model
            $model->save();
            
            // display success message
            $this->_getSession()->addSuccess(
            Mage::helper('user_surveys')->__('Question ' .$msg. ' Successfully.'));    
        }
        $this->_redirect($redirectPath, $redirectParams);
    }   

    public function deleteAction()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('id');

        if ($id) {
            try {
                // init model and delete
                /** @var $model User_Surveys_Model_Item */

            	
            	/*Start by Ankush Kumar*/
            	$collection = Mage::getModel('user_surveys/surveys')
            	->getCollection()
            	->addFieldToFilter('question_id', array('eq' => $id))
            	->getData();

            	$surveys = Mage::getModel('user_surveys/surveys');
            	
            	foreach ($collection as $key => $value)
            	{
            		$surveys->load($value['id']);
            		$surveys->delete();
            	}
            	
            	$model = Mage::getModel('user_surveys/questions');
            	$model->load($id);
            	/*End by Ankush Kumar*/
            	
                if (!$model->getId()) {
                    Mage::throwException(Mage::helper('user_surveys')->__('Unable to find Question'));
                }
                $model->delete();

                // display success message
                $this->_getSession()->addSuccess(
                    Mage::helper('user_surveys')->__('Question deleted Successfully.')
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('user_surveys')->__('An error occurred while deleting Question.')
                );
            }
        }

        // go to grid
        $this->_redirect('*/*/');
    }


    /**
     * Grid ajax action
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }  

    
}
/*End by Atul Pathak*/