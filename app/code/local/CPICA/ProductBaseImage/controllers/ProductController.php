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
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product controller
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
include_once("Mage/Catalog/controllers/ProductController.php");
class CPICA_ProductBaseImage_ProductController extends Mage_Catalog_ProductController
{ 
public function showProductImageAction()
    {   
        $post = $this->getRequest()->getPost();
        $colorId = $post["colorId"];
        $productId = $post["productId"];

        $product = Mage::getModel("catalog/product")->load($productId);
        
        if ($colorId == null) {
            $baseImage = $product->getData()['image'];
            $baseImagePath = Mage::getBaseUrl('media').'catalog/product'.$baseImage;
            echo($baseImagePath);
            exit();
        }

        if($product->getTypeId() == "configurable") {
            $configurable = $product->getTypeInstance()->getUsedProductIds(); 

            foreach ($configurable as $config) { 
                $associatedProduct = Mage::getModel('catalog/product')->load($config); 
                $simpleProductData = $associatedProduct->getData();
            
                if($simpleProductData['color'] == $colorId) {
                    $image = $simpleProductData['image'];
                    $mediaGalleryArray = $simpleProductData['media_gallery']['images'];
                    
                    foreach ($mediaGalleryArray as $key => $value) {
                        $imgArray[$key] = Mage::getBaseUrl('media').'catalog/product'.$value['file'];
                    }
                        
                    $imagePath = Mage::getBaseUrl('media').'catalog/product'.$image;
                    echo json_encode(array("a" => $imgArray, "b" => $imagePath));
                    // echo json_encode($imagePath);
                    exit();
                }
            }  
        }
    }
}
