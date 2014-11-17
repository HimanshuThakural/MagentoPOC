<?php

/**
 * Product View block
 *
 * @category   Mage
 * @package    Exogento_ImagePreview
 * @module     ImagePreview
 */
class Exogento_ImagePreview_Block_View extends Mage_Catalog_Block_Product_View_Media
{

	protected $_images_json;
	
    public function _construct()
    {
    	parent::_construct();
    	$product = $this->getProduct();
    	$helper = $this->helper('catalog/image');
    	$this->_images_json = array();
    	$imagePos = -1;
    	foreach ($this->getGalleryImages() as $image) {
    		$src = $helper->init($product, 'thumbnail', $image->getFile())->resize(500);
    		$this->_images_json[$imagePos += 1] = array ("src" => $src->__toString(), "alt" => $this->htmlEscape($image->getLabel())); 
    	}
    }
    
    public function getImagesJson() {
    	return Zend_Json::encode($this->_images_json);
    }
    
}
