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
class User_Surveys_IndexController extends Mage_Core_Controller_Front_Action {
	/**
	 * Pre dispatch action that allows to redirect to no route page in case of disabled extension through admin panel
	 */
	public function preDispatch() {
		parent::preDispatch ();
		
		if (! Mage::helper ( 'user_surveys' )->isEnabled ()) {
			$this->setFlag ( '', 'no-dispatch', true );
			$this->_redirect ( 'noRoute' );
		}
	}
	
	/**
	 * Index action
	 */
	public function indexAction() {
		$this->loadLayout ()->getLayout ();
		$customerSession = Mage::getSingleton ( 'customer/session' );
		
		if(!Mage::helper('customer')->isLoggedIn()) {
 		    Mage::getSingleton('customer/session')
            ->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());    		
		}	
		
		$listBlock = $this->getLayout ()->getBlock ( 'forms.list' );
		if ($listBlock) {
			$currentPage = abs ( intval ( $this->getRequest ()->getParam ( 'p' ) ) );
			if ($currentPage < 1) {
				$currentPage = 1;
			}
			$listBlock->setCurrentPage ( $currentPage );
		}
		
		$this->renderLayout ();
	}
	
	/**
	 * Surveys validate action
	 */
	public function validateAction($cust_id, $id) {
		$collection = Mage::getModel ( 'user_surveys/surveys' )->getCollection ()->addFieldToFilter ( 'form_id', array (
				'eq' => $id 
		) )->addFieldToFilter ( 'user_id', array (
				'eq' => $cust_id 
		) );
		
		$model = Mage::getModel ( 'user_surveys/forms' )->getCollection ()->addFieldToFilter ( 'visibility', array (
				'eq' => 1 
		) );
		
		$currentUrl = Mage::helper ( 'core/url' )->getCurrentUrl ();
		
		foreach ( $model->getData () as $key => $value ) {
			$featuredId = $value ['id'];
		}
		
		if (count ( $collection ) > 0) {
			$message = $this->__ ( 'Sorry, Survey form is already filled by you.' );
			if (!preg_match ( "/view/", $currentUrl) &&  $id == $featuredId ) {
				Mage::getSingleton ( 'core/session' )->addError ( $message );
				session_write_close ();
				$this->getResponse ()->setBody ( '<script>top.location.reload();</script>' );
				return;
			}
			else {
				Mage::getSingleton ( 'core/session' )->addError ( $message );
				session_write_close ();
				$this->_redirect ( '*/*/index' );
			}
		}
	}
	
	/**
	 * Surveys view action
	 */
	public function viewAction() {
		$customerSession = Mage::getSingleton ( 'customer/session' );

		if(!Mage::helper('customer')->isLoggedIn()) {
 		    Mage::getSingleton('customer/session')
            ->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());    		
		}	
		if (! $customerSession->isLoggedIn ()) {
			$this->_redirect ( 'customer/account/login' );
		}
		
		$customer_id = Mage::getSingleton ( 'customer/session' )->getId ();
		
		$formId = $this->getRequest ()->getParam ( 'id' );
		
		$this->validateAction ( $customer_id, $formId );
		
		if (! $formId) {
			return $this->_forward ( 'noRoute' );
		}
		
		$model = Mage::getModel ( 'user_surveys/forms' );
		$model->load ( $formId );
		
		$questionIds = explode ( ',', $model ['questions_id'] );
		$question = array ();
		$type = array ();
		$options = array ();
		$opt = array ();
		
		foreach ( $questionIds as $key => $value ) {
			$collection = Mage::getModel ( 'user_surveys/questions' )->load ( $value );
			$question [$value] = $collection->getQuestions ();
			
			$type [$value] = $collection->getType ();
			
			$options [$value] = $collection->getOptions ();
		}
		
		foreach ( $options as $key => $value ) {
			if ($value) {
				$opt [$key] = explode ( ',', $value );
			}
		}
		
		Mage::register ( 'questions', $question );
		Mage::register ( 'type', $type );
		Mage::register ( 'options', $opt );
		
		if (! $model->getId ()) {
			return $this->_forward ( 'noRoute' );
		}
		
		Mage::register ( 'surveys_item', $model );
		
		Mage::dispatchEvent ( 'before_surveys_item_display', array (
				'surveys_item' => $model 
		) );
		
		$this->loadLayout ();
		$itemBlock = $this->getLayout ()->getBlock ( 'forms.item' );
		if ($itemBlock) {
			$listBlock = $this->getLayout ()->getBlock ( 'forms.list' );
			if ($listBlock) {
				$page = ( int ) $listBlock->getCurrentPage () ? ( int ) $listBlock->getCurrentPage () : 1;
			} else {
				$page = 1;
			}
			$itemBlock->setPage ( $page );
		}
		$itemBlock->setFormAction ( Mage::getUrl ( '*/*/post' ) );
		$this->renderLayout ();
	}
	
	/**
	 * Surveys featuredSurvey action
	 */
	public function featuredSurveyAction() {
		$customerSession = Mage::getSingleton ( 'customer/session' );
		
		if(!Mage::helper('customer')->isLoggedIn()) {
 		    Mage::getSingleton('customer/session')
            ->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());    		
		}	
		if (! $customerSession->isLoggedIn ()) {
			$this->_redirect ( 'customer/account/login' );
		}
		
		$customer_id = Mage::getSingleton ( 'customer/session' )->getId ();
		$formId = $this->getRequest ()->getParam ( 'id' );
		
		$this->validateAction ( $customer_id, $formId );
		
		if (! $formId) {
			return $this->_forward ( 'noRoute' );
		}
		
		$model = Mage::getModel ( 'user_surveys/forms' );
		$model->load ( $formId );
		
		$questionIds = explode ( ',', $model ['questions_id'] );
		$question = array ();
		$type = array ();
		$options = array ();
		$opt = array ();
		
		foreach ( $questionIds as $key => $value ) {
			$collection = Mage::getModel ( 'user_surveys/questions' )->load ( $value );
			$question [$value] = $collection->getQuestions ();
			
			$type [$value] = $collection->getType ();
			
			$options [$value] = $collection->getOptions ();
		}
		
		foreach ( $options as $key => $value ) {
			if ($value) {
				$opt [$key] = explode ( ',', $value );
			}
		}
		
		Mage::register ( 'questions', $question );
		Mage::register ( 'type', $type );
		Mage::register ( 'options', $opt );
		Mage::register ( 'in_popup', '1' );
		
		if (! $model->getId ()) {
			return $this->_forward ( 'noRoute' );
		}
		
		Mage::register ( 'surveys_item', $model );
		
		Mage::dispatchEvent ( 'before_surveys_item_display', array (
				'surveys_item' => $model 
		) );
		
		$this->loadLayout ();
		$itemBlock = $this->getLayout ()->getBlock ( 'forms.item' );
		if ($itemBlock) {
			$listBlock = $this->getLayout ()->getBlock ( 'forms.list' );
			if ($listBlock) {
				$page = ( int ) $listBlock->getCurrentPage () ? ( int ) $listBlock->getCurrentPage () : 1;
			} else {
				$page = 1;
			}
			$itemBlock->setPage ( $page );
		}
		$itemBlock->setFormAction ( Mage::getUrl ( '*/*/post' ) );
		$this->renderLayout ();
	}
	
	/**
	 * Surveys Post action
	 */
	public function postAction() {
		$post = $this->getRequest ()->getPost ();
		$questionIdForCheckBox;
		if ($post) {
			$model = Mage::getModel ( 'user_surveys/surveys' );
			foreach ( $post as $key => $value ) {
				$userId = $post ['user_id'];
				$formId = $post ['form_id'];
				
				if (preg_match ( '/question_/', $key )) {
					$que_array = explode ( "_", $key );
					
					$questionId = $que_array [1];
					$model->setQuestionId ( $questionId );
					$model->setId ( null );
					$model->setUserId ( $userId );
					$model->setFormId ( $formId );
					$model->setValue ( $value );
					$model->save ();
				}
				
				if (preg_match ( '/option_/', $key )) {
					$checkbox_que_array = explode ( "--", $key );
					$questionIdForCheckBox [] = $checkbox_que_array [1];
				}
			}
			$QuestionIdUnique = array_unique ( $questionIdForCheckBox );
			
			foreach ( $post as $keys => $values ) {
				if (preg_match ( "/option/", $keys )) {
					foreach ( $QuestionIdUnique as $index => $queIds ) {
						if (preg_match ( "/--$queIds/", $keys )) {
							$feedback [$queIds] [] = $values;
						}
					}
				}
			}
			
			foreach ( $feedback as $key => $value ) {
				$data = implode ( ",", $value );
				$newarr ['checkbox_question-' . $key] = $data;
			}
			foreach ( $newarr as $key => $value ) {
				$userId = $post ['user_id'];
				$formId = $post ['form_id'];
				
				$que_array = explode ( "-", $key );
				$questionId = $que_array [1];
				
				$model->setQuestionId ( $questionId );
				$model->setId ( null );
				$model->setUserId ( $userId );
				
				$model->setFormId ( $formId );
				
				$model->setValue ( $value );
				$model->save ();
			}
		}
		Mage::getSingleton ( 'core/session' )->addSuccess ( "Thank You for participating in Survey." );
		if (isset ( $post ['featured_popup'] )) {
			$this->getResponse ()->setBody ( '<script>top.location.reload();</script>' );
			return;
		}
		$this->_redirect ( '*/*/index' );
	}
}