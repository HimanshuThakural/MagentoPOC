<?php
/**
 * Shopgate GmbH
 *
 * URHEBERRECHTSHINWEIS
 *
 * Dieses Plugin ist urheberrechtlich geschützt. Es darf ausschließlich von Kunden der Shopgate GmbH
 * zum Zwecke der eigenen Kommunikation zwischen dem IT-System des Kunden mit dem IT-System der
 * Shopgate GmbH über www.shopgate.com verwendet werden. Eine darüber hinausgehende Vervielfältigung, Verbreitung,
 * öffentliche Zugänglichmachung, Bearbeitung oder Weitergabe an Dritte ist nur mit unserer vorherigen
 * schriftlichen Zustimmung zulässig. Die Regelungen der §§ 69 d Abs. 2, 3 und 69 e UrhG bleiben hiervon unberührt.
 *
 * COPYRIGHT NOTICE
 *
 * This plugin is the subject of copyright protection. It is only for the use of Shopgate GmbH customers,
 * for the purpose of facilitating communication between the IT system of the customer and the IT system
 * of Shopgate GmbH via www.shopgate.com. Any reproduction, dissemination, public propagation, processing or
 * transfer to third parties is only permitted where we previously consented thereto in writing. The provisions
 * of paragraph 69 d, sub-paragraphs 2, 3 and paragraph 69, sub-paragraph e of the German Copyright Act shall remain unaffected.
 *
 *  @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: Peter Liebig
 * Date: 24.01.14
 * Time: 18:04
 * E-Mail: p.liebig@me.com
 */

/**
 * mobile redirect model
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Mobile_Redirect extends Mage_Core_Model_Abstract
{

    /**
     * @var Shopgate_Framework_Model_Config
     */
    protected $_config;

    /**
     * construct and define config
     */
    public function _construct()
    {
        parent::_construct();
        $this->_config = Mage::helper("shopgate/config")->getConfig();
    }

    /**
     * redirect with 301
     */
    public function redirectWithCode()
    {
        $allowRedirect = !Mage::app()->getStore()->isAdmin();

        // isAjax is not available on Magento < 1.5 >> no ajax-check
        if (method_exists(Mage::app()->getRequest(), "isAjax")) {
            $allowRedirect = $allowRedirect && !Mage::app()->getRequest()->isAjax();
        }

        if (!$this->_config->isValidConfig()) {
        	Mage::getSingleton("core/session")->setData("shopgate_header", '');
            return;
        }

        if (!Mage::getStoreConfig(
                 Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE,
                 $this->_config->getStoreViewId()
        )
        ) {
        	Mage::getSingleton("core/session")->setData("shopgate_header", '');
            return;
        }

        $jsHeader = "";

        if ($allowRedirect) {
            $builder          = new ShopgateBuilder($this->_config);
            $shopgateRedirect = $builder->buildRedirect();

            $objId  = Mage::app()->getRequest()->getParam('id');
            $action = Mage::app()->getRequest()->getControllerName();

            $baseUrl    = trim(Mage::app()->getRequest()->getBaseUrl(), "/");
            $requestUrl = trim(Mage::app()->getRequest()->getRequestUri(), "/");

            if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_STORES)
                && $action == "index" && $baseUrl != $requestUrl) {
                $action = "category";
                $objId  = Mage::app()->getStore()->getRootCategoryId();
            }

            if(!in_array($action, array("category", "product", "index"))
               && !Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ENABLE_DEFAULT_REDIRECT)) {
                $shopgateRedirect->suppressRedirect();
            }

            $disabledRoutes = explode(
                ',',
                Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_DISABLE_REDIRECT_ROUTES)
            );
            $route      = Mage::app()->getRequest()->getRouteName();
            if (in_array($route, $disabledRoutes)) {
                $shopgateRedirect->suppressRedirect();
            }

            $disabledControllers = explode(
                ',',
                Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_DISABLE_REDIRECT_CONTROLLERS)
            );
            $controllername      = Mage::app()->getRequest()->getControllerName();
            if (in_array($controllername, $disabledControllers)) {
                $shopgateRedirect->suppressRedirect();
            }

            if ($controllername == "product") {
                $productId        = Mage::app()->getRequest()->getParam('id');
                $disabledProducts = explode(
                    ',',
                    Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_DISABLE_REDIRECT_PRODUCTS)
                );

                if (in_array($productId, $disabledProducts)) {
                    $shopgateRedirect->suppressRedirect();
                }
            }

            if ($controllername == "category") {
                $categoryId         = Mage::app()->getRequest()->getParam('id');
                $disabledCategories = explode(
                    ',',
                    Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_DISABLE_REDIRECT_CATEGORIES)
                );
                if (in_array($categoryId, $disabledCategories)) {
                    $shopgateRedirect->suppressRedirect();
                }
            }

            $redirectType = Mage::getStoreConfig(
            	Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_REDIRECT_TYPE,
            	$this->_config->getStoreViewId()
            );

            $automaticRedirect = $redirectType == Shopgate_Framework_Model_Config::REDIRECTTYPE_HTTP ? true : false;
            
            switch ($action) {
                case "product":
                    if (!defined('COMPILER_INCLUDE_PATH')) {
                        // Dirty fix - Load product model failed in Magento < 1.7 if compilation is enabled
                        /** @var $product Mage_Catalog_Model_Product */
                        $product = Mage::getModel("catalog/product")->setStoreId($this->_config->getStoreViewId())->load($objId);
                        if ($parentIds = Mage::getModel('catalog/product_type_configurable')
                            ->getParentIdsByChild($product->getId())) {
                            $child = $objId;
                            $super = array_shift($parentIds);
                            $objId = $super . '-' . $child;
                        }
                        $jsHeader = $shopgateRedirect->buildScriptItem($objId, $automaticRedirect);
                    } else {
                        $jsHeader = $shopgateRedirect->buildScriptItem($objId, $automaticRedirect);
                    }

                    break;
                case "category":
                    $jsHeader = $shopgateRedirect->buildScriptCategory($objId, $automaticRedirect);
                    break;
                case "page": // cms pages
                    $objId    = Mage::app()->getRequest()->getParam('page_id');
                    $page     = Mage::getModel("cms/page")->load($objId);
                    $jsHeader = $shopgateRedirect->buildScriptCms($page->getIdentifier(), $automaticRedirect);
                    break;
                case "index": // index
                    $jsHeader = $shopgateRedirect->buildScriptShop($automaticRedirect);
                    break;
                default:
                    $jsHeader = $shopgateRedirect->buildScriptDefault($automaticRedirect);
                    break;
            }
        }

        Mage::getSingleton("core/session")->setData("shopgate_header", $jsHeader);
    }
}
