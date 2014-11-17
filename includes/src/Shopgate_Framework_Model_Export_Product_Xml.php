<?php
/**
 * Shopgate GmbH
 * URHEBERRECHTSHINWEIS
 * Dieses Plugin ist urheberrechtlich geschützt. Es darf ausschließlich von Kunden der Shopgate GmbH
 * zum Zwecke der eigenen Kommunikation zwischen dem IT-System des Kunden mit dem IT-System der
 * Shopgate GmbH über www.shopgate.com verwendet werden. Eine darüber hinausgehende Vervielfältigung, Verbreitung,
 * öffentliche Zugänglichmachung, Bearbeitung oder Weitergabe an Dritte ist nur mit unserer vorherigen
 * schriftlichen Zustimmung zulässig. Die Regelungen der §§ 69 d Abs. 2, 3 und 69 e UrhG bleiben hiervon unberührt.
 * COPYRIGHT NOTICE
 * This plugin is the subject of copyright protection. It is only for the use of Shopgate GmbH customers,
 * for the purpose of facilitating communication between the IT system of the customer and the IT system
 * of Shopgate GmbH via www.shopgate.com. Any reproduction, dissemination, public propagation, processing or
 * transfer to third parties is only permitted where we previously consented thereto in writing. The provisions
 * of paragraph 69 d, sub-paragraphs 2, 3 and paragraph 69, sub-paragraph e of the German Copyright Act shall remain unaffected.
 *
 * @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: pliebig
 * Date: 10.03.14
 * Time: 10:20
 * E-Mail: p.liebig@me.com
 */

/**
 * xml export model for products
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Export_Product_Xml
    extends Shopgate_Model_Catalog_Product
{
    /**
     * @var Mage_Catalog_Model_Product $item
     */
    protected $item;

    /**
     * @var Mage_Catalog_Model_Product $_parent
     */
    protected $_parent = null;

    /**
     * @var array
     */
    protected $fireMethods
        = array(
            'setLastUpdate',
            'setUid',
            'setName',
            'setTaxPercent',
            'setTaxClass',
            'setCurrency',
            'setDescription',
            'setDeeplink',
            'setPromotionSortOrder',
            'setInternalOrderInfo',
            'setAgeRating',
            'setWeight',
            'setWeightUnit',
            'setPrice',
            'setShipping',
            'setManufacturer',
            'setVisibility',
            'setStock',
            'setImages',
            'setCategoryPaths',
            'setProperties',
            'setIdentifiers',
            'setTags',
            'setRelations',
            'setAttributeGroups',
            'setInputs',
            'setChildren',
            'setDisplayType'
        );

    /**
     * @var array
     */
    protected $_ignoredProductAttributeCodes = array();

    /**
     * @var array
     */
    protected $_forcedProductAttributeCodes = array();

    /**
     * @var null
     */
    protected $_eanAttributeCode = null;

    /**
     * const for config path to include price with tax
     */
    const CONFIG_XML_PATH_PRICE_INCLUDES_TAX = 'tax/calculation/price_includes_tax';

    /**
     * set parent to null;
     */
    public function __construct()
    {
        parent::__construct();
        $this->_parent = null;
    }

    /**
     * @return Shopgate_Framework_Helper_Export
     */
    protected function _getExportHelper()
    {
        return Mage::helper('shopgate/export');
    }

    /**
     * @return Shopgate_Framework_Model_Config
     */
    protected function _getConfig()
    {
        return $this->_getHelper()->getConfig();
    }

    /**
     * return customer helper
     *
     * @return Shopgate_Framework_Helper_Customer
     */
    protected function _getCustomerHelper()
    {
        return Mage::helper('shopgate/customer');
    }

    /**
     * return default data helper
     *
     * @return Shopgate_Framework_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('shopgate');
    }

    /**
     * return config helper
     *
     * @return Shopgate_Framework_Helper_Config
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('shopgate/config');
    }

    /**
     * set last updated update date
     */
    public function setLastUpdate()
    {
        parent::setLastUpdate(date(DateTime::ISO8601, strtotime($this->item->getUpdatedAt())));
    }

    /**
     * set unique ID
     */
    public function setUid()
    {
        parent::setUid($this->item->getId());
    }

    /**
     * set name
     */
    public function setName()
    {
        parent::setName($this->item->getName());
    }

    /**
     * @return float
     */
    protected function _getTaxRate()
    {
        $request = new Varien_Object(
            array(
                'country_id'        => Mage::getStoreConfig(
                                           "tax/defaults/country",
                                           $this->_getConfig()->getStoreViewId()
                    ),
                'customer_class_id' => Mage::getModel("tax/calculation")->getDefaultCustomerTaxClass(
                                           $this->_getConfig()->getStoreViewId()
                    ),
                'product_class_id'  => $this->item->getTaxClassId()
            )
        );

        /** @var Mage_Tax_Model_Calculation $model */
        $model = Mage::getSingleton('tax/calculation');

        return $model->getRate($request);
    }

    /**
     * set tax percentage
     */
    public function setTaxPercent()
    {
        parent::setTaxPercent($this->_getTaxRate());
    }

    /**
     * set tax class
     */
    public function setTaxClass()
    {
        parent::setTaxClass($this->item->getTaxClassId());
    }

    /**
     * set currency
     */
    public function setCurrency()
    {
        parent::setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());
    }


    /**
     * set description
     */
    public function setDescription()
    {
        parent::setDescription($this->_getExportHelper()->createFullDescription($this->item, $this->_parent));
    }

    /**
     * set deep link
     */
    public function setDeeplink()
    {
        parent::setDeeplink($this->_getExportHelper()->getDeepLink($this->item, $this->_parent));
    }

    /**
     * set promotion sort order
     */
    public function setPromotionSortOrder()
    {
        //ToDo implement promotion logic in Magento
        parent::setPromotionSortOrder(false);
    }

    /**
     * set internal order info
     */
    public function setInternalOrderInfo()
    {
        $internalOrderInfo = array(
            "store_view_id" => $this->item->getStoreId(),
            "product_id"    => $this->item->getId(),
            "item_type"     => $this->item->getTypeId(),
            "exchange_rate" => $this->_getExportHelper()->convertPriceCurrency(1),
        );

        parent::setInternalOrderInfo($this->_getConfig()->jsonEncode($internalOrderInfo));
    }

    /**
     * set age rating
     */
    public function setAgeRating()
    {
        parent::setAgeRating(false);
    }

    /**
     * set weight
     */
    public function setWeight()
    {
        parent::setWeight($this->item->getWeight());
    }

    /**
     * set weight unit
     */
    public function setWeightUnit()
    {
        $weightUnit = Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_WEIGHT_UNIT);
        switch ($weightUnit) {
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_KG;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_KG;
                break;
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_GRAMM;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_GRAMM;
                break;
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_AUTO;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_DEFAULT;
                break;
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_POUND;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_POUND;
                break;
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_OUNCE;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_OUNCE;
                break;
        }

        parent::setWeightUnit($weightUnit);
    }

    /**
     * set price
     */
    public function setPrice()
    {
        $price = new Shopgate_Model_Catalog_Price();
        if (!$this->getIsChild() || ($this->getIsChild() 
            && !Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_USE_ROOT_PRICES))) {
            $price->setPrice($this->item->getPrice());
            $price->setCost($this->item->getCost());

            $rulePrice  = Mage::helper("shopgate/export")->calcProductPriceRule($this->item);
            $finalPrice = $this->item->getFinalPrice();
            if ($rulePrice && $rulePrice < $price) {
                $finalPrice = $rulePrice;
            }

            $price->setSalePrice($finalPrice);
            $price->setMsrp($this->item->getMsrp());
            $isGross = Mage::getStoreConfig(
                           self::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
                           $this->_getConfig()->getStoreViewId()
            );
            if ($isGross) {
                $price->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS);
            } else {
                $price->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET);
            }

            foreach ($this->item->getTierPrice() as $tier) {
                if (
                    ($tier['website_id'] == Mage::app()->getStore()->getWebsiteId() || $tier['website_id'] == 0)
                    && $price->getSalePrice() > $tier['website_price']
                ) {
                    $tierPrice = new Shopgate_Model_Catalog_TierPrice();
                    if ($tier['all_groups'] != 1) {
                        $tierPrice->setCustomerGroupUid($tier['cust_group']);
                    }
                    $tierPrice->setFromQuantity($tier['price_qty']);
                    $tierPrice->setReduction($price->getSalePrice() - $tier['website_price']);
                    $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
                    if ($this->item->isSuper() && Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_USE_ROOT_PRICES)) {
                        $tierPrice->setAggregateChildren(true);
                    }
                    $tierPrice->setCustomerGroupUid($tier['cust_group']);
                    $price->addTierPriceGroup($tierPrice);
                }
            }
        }
        parent::setPrice($price);
    }

    /**
     * set shipping
     */
    public function setShipping()
    {
        $shipping = new Shopgate_Model_Catalog_Shipping();
        $shipping->setAdditionalCostsPerUnit(false);
        $shipping->setCostsPerOrder(false);
        $shipping->setIsFree(false);

        parent::setShipping($shipping);
    }

    /**
     * set manufacturer
     */
    public function setManufacturer()
    {
        $title = $this->_getExportHelper()->getManufacturer($this->item);
        if (!empty($title)) {
            $manufacturer = new Shopgate_Model_Catalog_Manufacturer();
            $manufacturer->setUid($this->item->getManufacturer());
            $manufacturer->setTitle($title);
            $manufacturer->setItemNumber(false);
            parent::setManufacturer($manufacturer);
        }
    }

    /**
     * set visibility
     */
    public function setVisibility()
    {
        $level      = null;
        $visibility = new Shopgate_Model_Catalog_Visibility();
        switch ($this->item->getVisibility()) {
            case Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH:
                $level = Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG_AND_SEARCH;
                break;
            case Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG:
                $level = Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG;
                break;
            case Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH:
                $level = Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_SEARCH;
                break;
            case Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE:
                $level = Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_NOT_VISIBLE;
                break;
        }
        $visibility->setLevel($level);
        $visibility->setMarketplace(true);

        parent::setVisibility($visibility);
    }

    /**
     * set stock
     */
    public function setStock()
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = $this->item->getStockItem();
        $stock     = new Shopgate_Model_Catalog_Stock();
        $useStock  = false;
        if ($stockItem->getManageStock()) {
            switch ($stockItem->getBackorders() && $stockItem->getIsInStock()) {
                case Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY:
                case Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY:
                    break;
                default:
                    $useStock = true;
                    break;
            }
        }
        $stock->setUseStock($useStock);
        if ($stock->getUseStock()) {
            $stock->setAvailabilityText($this->_getExportHelper()->getAvailableText($this->item, $this->_getConfig()
                                                                                                      ->getStoreViewId()));
            $stock->setBackorders($stockItem->getBackorders());
            $stock->setIsSaleable($this->item->getIsSalable());
            $stock->setMaximumOrderQuantity($stockItem->getMaxSaleQty());
            $stock->setMinimumOrderQuantity($stockItem->getMinSaleQty());
            $stock->setStockQuantity(($this->item->getIsSalable()) ? $stockItem->getQty() : 0);
        }

        parent::setStock($stock);
    }

    /**
     * set images
     */
    public function setImages()
    {
        $result = array();
        $images = $this->getImageObjects($this->item);
        if (!empty($images)) {
            foreach ($images as $image) {
                $imagesItemObject = new Shopgate_Model_Media_Image();
                $imagesItemObject->setUrl($image['url']);
                $imagesItemObject->setTitle($image['title']);
                $imagesItemObject->setAlt($image['alt']);
                $imagesItemObject->setSortOrder($image['position']);
                array_push($result, $imagesItemObject);
            }
        }
        parent::setImages($result);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    public function getImageObjects($product)
    {
        $images       = array();
        $mediaGallery = $this->_getExportHelper()->getMediaImages($product);
        if (!empty($mediaGallery)) {
            foreach ($product->getMediaGalleryImages() as $image) {
                if ($image->getFile()) {
                    $_image = array(
                        'url'      => $image->getUrl(),
                        'title'    => $image->getLabel(),
                        'alt'      => $image->getLabel(),
                        'position' => $image->getPosition()
                    );
                    array_push($images, $_image);
                }
            }
        }

        return $images;
    }

    /**
     * set category path
     */
    public function setCategoryPaths()
    {
        $result = array();
        if ($this->item->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
            && $this->item->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
        ) {

            $itemsOrderOption = Mage::getStoreConfig(
                                    Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_ITEM_SORT

            );
            $linkedCategories = Mage::getResourceSingleton('shopgate/product')->getCategoryIdsAndPosition($this->item);

            foreach ($linkedCategories as $link) {
                /** @var Mage_Catalog_Model_Category $category */
                $category           = Mage::getModel('catalog/category')->load($link['category_id']);
                $categoryItemObject = new Shopgate_Model_Catalog_CategoryPath();
                $categoryItemObject->setUid($category->getId());

                switch ($itemsOrderOption) {
                    case Shopgate_Framework_Model_System_Config_Source_Item_Sort::SORT_TYPE_PRICE_DESC:
                        $sortIndex = round($this->item->getFinalPrice() * 100, 0);
                        $categoryItemObject->setSortOrder($sortIndex);
                        break;
                    case Shopgate_Framework_Model_System_Config_Source_Item_Sort::SORT_TYPE_POSITION:
                    default:
                        $categoryItemObject->setSortOrder($link['position']);
                }
                array_push($result, $categoryItemObject);
            }
        }

        parent::setCategoryPaths($result);
    }

    /**
     * set properties
     */
    public function setProperties()
    {
        if (empty($this->_ignoredProductAttributeCodes)) {
            $ignoredProductAttributeCodes        = array("manufacturer", "model");
            $ignoredProperties                   = explode(
                ",",
                Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_FILTER_PROPERTIES)
            );
            $ignoredProductAttributeCodes        = array_merge($ignoredProductAttributeCodes, $ignoredProperties);
            $this->_ignoredProductAttributeCodes = array_unique($ignoredProductAttributeCodes);
        }

        if (empty($this->_forcedProductAttributeCodes)) {
            $forcedProperties = explode(
                ",",
                Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_FORCE_PROPERTY_EXPORT)
            );
            $this->_forcedProductAttributeCodes = array_unique($forcedProperties);
        }

        $result = array();

        $attributes = $this->item->getAttributes();
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();

            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if ($attribute && $attribute->getIsVisibleOnFront() || in_array($code, $this->_forcedProductAttributeCodes)) {
                if (in_array($code, $this->_ignoredProductAttributeCodes) && !in_array($code, $this->_forcedProductAttributeCodes)) {
                    continue;
                }
                $propertyItemObject = new Shopgate_Model_Catalog_Property();
                $propertyItemObject->setUid($attribute->getId());
                $propertyItemObject->setLabel($attribute->getStoreLabel($this->_getConfig()->getStoreViewId()));
                $propertyItemObject->setValue($attribute->getFrontend()->getValue($this->item));
                array_push($result, $propertyItemObject);
            }
        }

        parent::setProperties($result);
    }

    /**
     * set identifiers
     */
    public function setIdentifiers()
    {
        $result = array();

        $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
        $identifierItemObject->setType('SKU');
        $identifierItemObject->setValue($this->item->getSku());
        array_push($result, $identifierItemObject);

        $eanAttributeCode = Mage::getStoreConfig(
                                Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EAN_ATTR_CODE
        );
        if (!empty($eanAttributeCode)) {
            $ean = $this->item->getAttributeText($eanAttributeCode);
            if (!empty($ean)) {
                $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
                $identifierItemObject->setType('EAN');
                $identifierItemObject->setValue($ean);
                array_push($result, $identifierItemObject);
            }
        }
        parent::setIdentifiers($result);
    }

    /**
     * set tags
     */
    public function setTags()
    {
        $result = array();
        $tags   = explode(',', $this->item->getMetaKeyword());

        foreach ($tags as $tag) {
            if (!ctype_space($tag) && !empty($tag)) {
                $tagItemObject = new Shopgate_Model_Catalog_Tag();
                $tagItemObject->setValue(trim($tag));
                array_push($result, $tagItemObject);
            }
        }

        parent::setTags($result);
    }

    /**
     * set relations
     */
    public function setRelations()
    {
        $result = array();

        $crossSellIds = $this->item->getCrossSellProductIds();
        if (!empty($crossSellIds)) {
            $crossSellRelation = new Shopgate_Model_Catalog_Relation();
            $crossSellRelation->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_CROSSSELL);
            $crossSellRelation->setValues($crossSellIds);
            array_push($result, $crossSellRelation);
        }

        $upsellIds = $this->item->getUpSellProductIds();
        if (!empty($upsellIds)) {
            $upSellRelation = new Shopgate_Model_Catalog_Relation();
            $upSellRelation->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL);
            $upSellRelation->setValues($upsellIds);
            array_push($result, $upSellRelation);
        }

        $relatedIds = $this->item->getRelatedProductIds();
        if (!empty($relatedIds)) {
            $relatedRelation = new Shopgate_Model_Catalog_Relation();
            $relatedRelation->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_RELATION);
            $relatedRelation->setValues($relatedIds);
            array_push($result, $relatedRelation);
        }

        parent::setRelations($result);
    }

    /**
     * set attribute groups
     */
    public function setAttributeGroups()
    {
        if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $productTypeInstance = $this->item->getTypeInstance(true);
            if ($productTypeInstance == null || !method_exists($productTypeInstance, "getUsedProducts")) {
                return;
            }
            $configurableAttributes = $productTypeInstance->getConfigurableAttributes($this->item);
            $result                 = array();
            foreach ($configurableAttributes as $attribute) {
                /* @var $attribute Mage_Catalog_Model_Product_Type_Configurable_Attribute */
                $attributeItem = new Shopgate_Model_Catalog_AttributeGroup();
                $attributeItem->setUid($attribute->getAttributeId());
                $attributeItem->setLabel($attribute->getLabel());
                array_push($result, $attributeItem);
            }
            parent::setAttributeGroups($result);
        }
    }

    /**
     * set inputs
     */
    public function setInputs()
    {
        $result = array();
        foreach ($this->item->getOptions() as $option) {
            $inputType = $this->_mapInputType($option['type']);

            $inputItem = new Shopgate_Model_Catalog_Input();
            $inputItem->setUid($option['option_id']);
            $inputItem->setType($inputType);
            $inputItem->setLabel($option['title']);
            $inputItem->setValidation($this->_buildInputValidation($inputType, $option));
            $inputItem->setRequired($option['is_require']);
            $inputItem->setOptions($this->_buildInputOptions($inputType, $option));

            array_push($result, $inputItem);
        }

        if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $result = $this->_setBundleOptions();
        }

        parent::setInputs($result);
    }

    /**
     * @param $inputType
     * @param $option
     *
     * @return Shopgate_Model_Catalog_Validation
     */
    protected function _buildInputValidation($inputType, $option)
    {
        $validation = new Shopgate_Model_Catalog_Validation();

        switch ($inputType) {
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_STRING;
                $validation->setValue($option['max_characters']);
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_TYPE_FILE;
                $validation->setValue($option['file_extension']);
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_DATE;
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_DATE;
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_TIME;
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_RADIO:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_MULTIPLE:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_STRING;
                break;
            default:
                return $validation;
        }

        $validation->setValidationType($validationType);

        return $validation;
    }

    /**
     * @param $inputType
     * @param $option
     *
     * @return array
     */
    protected function _buildInputOptions($inputType, $option)
    {
        $optionValues = array();

        switch ($inputType) {
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME:
                $inputOption = new Shopgate_Model_Catalog_Option();
                $inputOption->setAdditionalPrice($option->getPrice());
                array_push($optionValues, $inputOption);
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_RADIO:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_MULTIPLE:
                foreach ($option->getValues() as $id => $value) {
                    $inputOption = new Shopgate_Model_Catalog_Option();
                    $inputOption->setUid($id);
                    $inputOption->setLabel($value->getTitle());
                    $inputOption->setAdditionalPrice($value->getPrice());
                    array_push($optionValues, $inputOption);
                }
                break;
        }

        return $optionValues;
    }

    /**
     * @param $mageType
     *
     * @return string
     */
    protected function _mapInputType($mageType)
    {
        switch ($mageType) {
            case "field":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT;
                break;
            case "area":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA;
                break;
            case "file":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE;
                break;
            case "select":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case "drop_down":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case "radio":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_RADIO;
                break;
            case "checkbox":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX;
                break;
            case "multiple":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_MULTIPLE;
                break;
            case "date":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE;
                break;
            case "date_time":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME;
                break;
            case "time":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME;
                break;
            default:
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT;
        }

        return $inputType;
    }

    /**
     *
     */
    public function setChildren()
    {
        $children = array();
        if ($this->item->isConfigurable()) {
            $childProducts = $this->item->getTypeInstance()->getUsedProducts();
        }

        if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE || $this->item->isGrouped()) {
            $childIds      = $this->item->getTypeInstance()->getChildrenIds($this->item->getId());
            $childProducts = array();
            foreach ($childIds as $childOption) {
                foreach ($childOption as $childId) {
                    array_push($childProducts, Mage::getModel('catalog/product')
                                                   ->setStoreId($this->_getConfig()->getStoreViewId())
                                                   ->load($childId));
                }
            }
        }

        if (!empty($childProducts)) {
            foreach ($childProducts as $childProduct) {
                /** @var Shopgate_Framework_Model_Export_Product_Xml $child */
                $child = Mage::getModel('shopgate/export_product_xml');
                $child->setItem($childProduct);
                $child->setParentItem($this->item);
                $child->setData('uid', $this->item->getId() . '_' . $childProduct->getId());
                $child->setIsChild(true);
                $child->setAttributes($this->item);
                $child->setFireMethodsForChildren();
                $child->generateData();

                array_push($children, $child);
            }
        }

        parent::setChildren($children);
    }

    /**
     * @param Mage_Catalog_Model_Product $parent
     */
    public function setAttributes($parent)
    {
        $result = array();
        if ($this->getIsChild() && $parent->isConfigurable()) {
            /** @var Mage_Catalog_Model_Product_Type_Configurable $productTypeInstance */
            $productTypeInstance = $parent->getTypeInstance(true);
            $allowAttributes     = $productTypeInstance->getConfigurableAttributes($parent);
            foreach ($allowAttributes as $attribute) {
                /** @var Mage_Catalog_Model_Product_Type_Configurable_Attribute $attribute */

                $itemAttribute = new Shopgate_Model_Catalog_Attribute();
                $attribute     = $attribute->getProductAttribute();

                if ($attribute == null) {
                    continue;
                }

                $itemAttribute->setGroupUid($attribute->getAttributeId());
                $attrValue = $this->item->getResource()->getAttribute($attribute->getAttributeCode())->getFrontend();
                $itemAttribute->setLabel($attrValue->getValue($this->item));
                array_push($result, $itemAttribute);
            }
        }
        parent::setAttributes($result);
    }

    /**
     *
     */
    public function setFireMethodsForChildren()
    {
        $this->fireMethods = array(
            'setLastUpdate',
            'setName',
            'setTaxPercent',
            'setTaxClass',
            'setCurrency',
            'setDescription',
            'setDeeplink',
            'setPromotionSortOrder',
            'setInternalOrderInfo',
            'setAgeRating',
            'setWeight',
            'setWeightUnit',
            'setPrice',
            'setShipping',
            'setManufacturer',
            'setVisibility',
            'setStock',
            'setImages',
            'setCategoryPaths',
            'setProperties',
            'setIdentifiers',
            'setTags',
            'setRelations',
            'setInputs',
            'setChildren',
        );
    }

    /**
     * @return string|void
     */
    public function setDisplayType()
    {
        if ($this->item->isGrouped()) {
            parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_LIST);
        }

        if ($this->item->isConfigurable() || $this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_SELECT);
        }

        if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_SIMPLE);
        }
    }


    /**
     * @param Mage_Catalog_Model_Product $parent
     */
    public function setParentItem(Mage_Catalog_Model_Product $parent)
    {
        $this->_parent = $parent;
    }

    /**
     * @return array
     */
    protected function _setBundleOptions()
    {
        $bundleOptions       = $this->item->getPriceModel()->getOptions($this->item);
        $isGross             = Mage::getStoreConfig(self::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
                                                    $this->_getConfig()->getStoreViewId());
        $stock               = parent::getStock();
        $selectionQuantities = $optionValues = $result = array();

        foreach ($bundleOptions as $bundleOption) {
            /* @var $bundleOption Mage_Bundle_Model_Option */
            if (!is_array($bundleOption->getSelections())) {
                $stock->setIsSaleable(false);
                $bundleOption->setSelections(array());
            }

            foreach ($bundleOption->getSelections() as $selection) {
                $option = new Shopgate_Model_Catalog_Option();
                /** @var $selection Mage_Catalog_Model_Product */
                $qty            = max(1, (int)$selection->getSelectionQty());
                $selectionPrice = $this->item
                    ->getPriceModel()
                    ->getSelectionFinalPrice($this->item, $selection, 1, $selection->getSelectionQty());

                $selectionPrice = Mage::helper('tax')->getPrice($selection, $selectionPrice, $isGross);

                $selectionName = $qty > 1 ? $qty . " x " : '';
                $selectionName .= $this->_getMageCoreHelper()->escapeHtml($selection->getName());
                $selectionId = $selection->getData('option')->getData('option_id');

                if (!array_key_exists($selectionId, $selectionQuantities)) {
                    $selectionQuantities[$selectionId] = 0;
                }

                if ($this->item->getStockItem()->getManageStock() && $selection->isSaleable()
                    && $this->item->getStockItem()->getQty() > 0
                ) {
                    if ($selectionQuantities[$selectionId] !== null) {
                        $selectionQuantities[$selectionId] += $this->item->getStockItem()->getQty();
                    }
                } else {
                    if (!$this->item->getStockItem()->getManageStock()) {
                        $selectionQuantities[$selectionId] = null;
                    } else {
                        if (!$selection->isSaleable() && $this->item->getStockItem()->getBackorders()) {
                            $selectionQuantities[$selectionId] = null;
                        } else {
                            $selectionQuantities[$selectionId] = 0;
                        }
                    }
                }

                $option->setUid($selection->getSelectionId());
                //$option->setSortOrder($selection->getPosition());
                $option->setLabel($selectionName);
                $option->setAdditionalPrice($selectionPrice);
                array_push($optionValues, $option);
                if ($selectionQuantities[$selectionId] === null) {
                    unset($selectionQuantities[$selectionId]);
                }
            }
            $inputItem = new Shopgate_Model_Catalog_Input();
            $inputItem->setUid($bundleOption->getId());
            $inputItem->setType($this->_mapInputType($bundleOption->getType()));
            $inputItem->setLabel($bundleOption->getTitle());
            $inputItem->setValidation($this->_buildInputValidation($inputItem->getType(), null));
            $inputItem->setRequired($bundleOption->getRequired());
            $inputItem->setOptions($optionValues);
            array_push($result, $inputItem);
        }

        $stockQty = count($selectionQuantities) ? min($selectionQuantities) : 0;
        $stock->setStockQuantity($stockQty);
        if (!count($selectionQuantities)) {
            $stock->setUseStock(false);
        }
        $this->setStock($stock);

        return $result;
    }

    /**
     * @return Mage_Core_Helper_Data
     */
    protected function _getMageCoreHelper()
    {
        return Mage::helper('core');
    }
} 