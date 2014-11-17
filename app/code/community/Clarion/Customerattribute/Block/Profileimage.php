<?php
class Clarion_Customerattribute_Block_Profileimage extends Mage_Core_Block_Template {
    protected  $_profileimage = true;
    const DEFAULT_WIDTH = 75;
    const DEFAULT_HEIGHT = 75;
    
    /*public function __construct() {
        parent::__construct();
        $customer = $this->getCustomerFromSession();
        $this->getCustomerFromSession();
       
        if($customer){
            $customerObj = Mage::getModel('customer/customer')->load($customer->getId());
            if($profileimage = $customerObj->getSstechProfileimage()){
                $this->_profileimage = $profileimage; 
            }else{
                $this->_profileimage = null;     
            }
        }
    }
    */
    protected function getCustomerFromSession(){
        return Mage::getSingleton('customer/session')->getCustomer();
    }
     /*
        @params 
        @author Severtek
        @comments adjust width
    */
    protected function getWidth(){
        $configWidth = (int)Mage::getStoreConfig(
                          'customer/profileimage_group/profileimage_field_width',
                          Mage::app()->getStore()
                        );
        if($configWidth > 0){
            $width = $configWidth;
        }else{
            $width = self::DEFAULT_WIDTH;
        }
        return $width;
    }
    
    
    public function getProfileimage(){
        return $this->_profileimage;
    }
     /*
        @params 
        @author Severtek
        @comments adjust height
    */
    protected function getHeight(){
        $configHeight = (int)Mage::getStoreConfig(
                           'customer/profileimage_group/profileimage_field_height',
                           Mage::app()->getStore()
                        );
        if($configHeight > 0){
            $height = $configHeight;
        }else{
            $height = self::DEFAULT_HEIGHT;
        }
        
        return $height;
    }
    public function getUploadUrl(){
        return Mage::getUrl('*/customer/upload');
    }
    public function getProfileimagePath($attribute_code){
        return $this->getUrl('customerattribute/customer/viewProfileimage/attr_code/'.$attribute_code);
    }    
    public function getProfileimageHtml($attribute_code){
        $html = "<img src='"
                .$this->getProfileimagePath($attribute_code).
                "' width ='".$this->getWidth()
                ."' height='".$this->getHeight()
                ."'/>";
        return $html; 
    }
}
