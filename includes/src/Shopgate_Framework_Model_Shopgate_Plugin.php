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
 * @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: Peter Liebig
 * Date: 22.01.14
 * Time: 12:26
 * E-Mail: p.liebig@me.com
 */

/**
 * @package      Shopgate_Framework
 * @author       Shopgate GmbH Butzbach
 */
class Shopgate_Framework_Model_Shopgate_Plugin extends ShopgatePlugin
{
    /**
     * const for enterprise gift wrapping
     */
    const GIFT_WRAP_OPTION_ID = 'EE_GiftWrap';

    /**
     * @var Shopgate_Framework_Model_Config
     */
    protected $_config;

    /**
     * @var bool
     */
    protected $_errorOnInvalidCoupon = true;

    /**
     * @var
     */
    protected $_defaultTax;

    /**
     * a stack to keep all virtual objects in
     * objects are shopgate-coupons and payment fees
     *
     * @var array
     */
    protected $_virtualObjectStack = array();

    /**
     * @var null | Shopgate_Framework_Model_Export_Product
     */
    protected $_exportProductInstance = null;

    /**
     * @var array | null
     */
    protected $_defaultCategoryRow = null;

    /**
     * Callback function for initialization by plugin implementations.
     * This method gets called on instantiation of a ShopgatePlugin child class and serves as __construct() replacement.
     * Important: Initialize $this->_config here if you have your own config class.
     *
     * @see http://wiki.shopgate.com/Shopgate_Library#startup.28.29
     */
    public function startup()
    {
        /* @var $config Shopgate_Framework_Helper_Config */
        $this->_config     = $this->_getConfig();
        $this->_defaultTax = Mage::getModel("tax/calculation")->getDefaultCustomerTaxClass(
                                 $this->_getConfig()->getStoreViewId()
        );
        return true;
    }

    /**
     *
     * @param string $action
     * @return string
     */
    public function getActionUrl($action)
    {
        return $this->_getHelper()->getOAuthRedirectUri(Mage::app()->getRequest()->getParam('storeviewid'));
    }

    /**
     * get config from shopgate helper
     *
     * @return Shopgate_Framework_Model_Config
     */
    protected function _getConfig()
    {
        return $this->_getConfigHelper()->getConfig();
    }

    /**
     * Executes a cron job with parameters.
     * $message contains a message of success or failure for the job.
     * $errorcount contains the number of errors that occurred during execution.
     *
     * @param string       $jobname
     * @param mixed|string $params     Associative list of parameter names and values.
     * @param string       $message    A reference to the variable the message is appended to.
     * @param int          $errorcount A reference to the error counter variable.
     *
     * @throws ShopgateLibraryException
     */
    public function cron($jobname, $params, &$message, &$errorcount)
    {
        $this->log("Start Run CRON-Jobs", ShopgateLogger::LOGTYPE_DEBUG);

        switch ($jobname) {
            case "set_shipping_completed":
                $this->log("> Run job {$jobname}", ShopgateLogger::LOGTYPE_DEBUG);
                $this->_cronSetShippingCompleted();
                break;
            case "cancel_orders":
                $this->log("> Run job {$jobname}", ShopgateLogger::LOGTYPE_DEBUG);
                $this->_cronCancelOrder();
                break;
            default:
                throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CRON_UNSUPPORTED_JOB,
                                                   '"' . $jobname . '"', true);
        }

        $this->log("END Run CRON-Jobs", ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * cron to set the shipping to complete
     */
    protected function _cronSetShippingCompleted()
    {
        /** @var Shopgate_Framework_Model_Resource_Shopgate_Order_Collection $collection */
        $collection = Mage::getResourceModel('shopgate/shopgate_order_collection')->getUnsyncedOrders();
        $this->log(">> Found {$collection->getSize()} potential orders to send", ShopgateLogger::LOGTYPE_DEBUG);

        foreach ($collection as $order) {
            /* @var $order Shopgate_Framework_Model_Shopgate_Order */
            $this->log(">> Order with ID {$order->getId()} loaded", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log(">> Call observer->setShippingStatus", ShopgateLogger::LOGTYPE_DEBUG);
            $shipment = Mage::getModel('sales/order_shipment')->setOrderId($order->getOrderId());

            $event         = new Varien_Event(
                array(
                    'data_object' => $shipment,
                    'shipment'    => $shipment
                )
            );
            $eventObserver = new Varien_Event_Observer();
            $eventObserver->setEvent($event);
            Mage::getModel('shopgate/observer')->setShippingStatus($eventObserver);
        }
    }

    /**
     * cron to cancel already cancelled orders
     */
    protected function _cronCancelOrder()
    {
        /** @var Shopgate_Framework_Model_Resource_Shopgate_Order_Collection $collection */
        $collection = Mage::getResourceModel('shopgate/shopgate_order_collection')->getAlreadyCancelledOrders();
        $this->log(">> Found {$collection->getSize()} potential orders to send", ShopgateLogger::LOGTYPE_DEBUG);

        foreach ($collection as $shopgateOrder) {
            /* @var $shopgateOrder Shopgate_Framework_Model_Shopgate_Order */
            /* @var $order Mage_Sales_Model_Order */
            $order = $shopgateOrder->getOrder();

            if (!$order->isCanceled()) {
                continue;
            }
            $this->log(
                 ">> Order with ID {$order->getId()} loaded and ready for cancellation",
                 ShopgateLogger::LOGTYPE_DEBUG
            );
            $this->log(">> Dispatching event order_cancel_after", ShopgateLogger::LOGTYPE_DEBUG);
            Mage::dispatchEvent('order_cancel_after', array('order' => $order, 'shopgate_order' => $shopgateOrder));
        }
    }

    /**
     * @param string $user
     * @param string $pass
     *
     * @return ShopgateCustomer
     * @throws ShopgateLibraryException
     * @see ShopgatePluginCore::getUserData()
     */
    public function getCustomer($user, $pass)
    {
        /** @var Mage_Customer_Model_Customer $magentoCustomer */
        $magentoCustomer = Mage::getModel('customer/customer');
        $magentoCustomer->setStore(Mage::app()->getStore());
        try {
            $magentoCustomer->authenticate($user, $pass);
        } catch (Exception $e) {
            switch ($e->getCode()) {
                case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                    throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CUSTOMER_ACCOUNT_NOT_CONFIRMED);
                    break;
                case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                    throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD);
                    break;
                default:
                    throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CUSTOMER_UNKNOWN_ERROR);
                    break;
            }
        }
        $shopgateCustomer = Mage::getModel('shopgate/export_customer')->loadGetCustomerData($magentoCustomer);
        return $shopgateCustomer;
    }

    /**
     * This method creates a new user account / user addresses for a customer in the shop system's database
     * The method should not abort on soft errors like when the street or phone number of a customer is not set.
     *
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_register_customer#API_Response
     *
     * @param string $user            The user name the customer entered at Shopgate.
     * @param string $pass            The password the customer entered at Shopgate.
     * @param        ShopgateCustomer A ShopgateCustomer object to be added to the shop system's database.
     *
     * @throws ShopgateLibraryException if an error occures
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        try {
            /** @var Mage_Customer_Model_Customer $magentoCustomer */
            $magentoCustomer = Mage::getModel("customer/customer");
            $magentoCustomer->setEmail($user);
            $magentoCustomer->setPassword($pass);
            $magentoCustomer->setStore(Mage::app()->getStore());
            $this->_getCustomerHelper()->registerCustomer($magentoCustomer, $customer);
        } catch (Mage_Customer_Exception $e) {
            if ($e->getCode() == Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_USER_ALREADY_EXISTS);
            } else {
                throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER, $e->getMessage(), true);
            }
        } catch (Exception $e) {
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $e->getMessage(), true);
        }
    }

    /**
     * Performs the necessary queries to add an order to the shop system's database.
     *
     * @see http://wiki.shopgate.com/Merchant_API_get_orders#API_Response
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_add_order#API_Response
     *
     * @param ShopgateOrder $order The ShopgateOrder object to be added to the shop system's database.
     *
     * @return array(
     *                             <ul>
     *                             <li>'external_order_id' => <i>string</i>, # the ID of the order in your shop system's database</li>
     *                             <li>'external_order_number' => <i>string</i> # the number of the order in your shop system</li>
     *                             </ul>)
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function addOrder(ShopgateOrder $order)
    {
        /* @var Mage_Sales_Model_Order $magentoOrder */
        /* @var Mage_Sales_Model_Quote $quote */
        /* @var Mage_Sales_Model_Service_Quote $service */
        try {
            $this->log("## Start to add new Order", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("## Order-Number: {$order->getOrderNumber()}", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("# Begin database Transaction", ShopgateLogger::LOGTYPE_DEBUG);
            Mage::getModel("sales/order")->getResource()->beginTransaction();
            $this->log("#> Succesfull created database Transaction", ShopgateLogger::LOGTYPE_DEBUG);

            $this->_errorOnInvalidCoupon = true;

            $this->log("# Try to load old shopgate order to check for duplicate", ShopgateLogger::LOGTYPE_DEBUG);
            /** @var Shopgate_Framework_Model_Shopgate_Order $magentoShopgateOrder */
            $magentoShopgateOrder = Mage::getModel("shopgate/shopgate_order")->load(
                                        $order->getOrderNumber(),
                                        "shopgate_order_number"
            );

            if ($magentoShopgateOrder->getId() !== null) {
                $this->log("# Duplicate Order", ShopgateLogger::LOGTYPE_DEBUG);

                $orderId = 'unset';
                if ($magentoShopgateOrder->getOrderId()) {
                    $orderId = $magentoShopgateOrder->getOrderId();
                }

                throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                                                   'orderId: ' . $orderId, true);
            }
            Mage::dispatchEvent('shopgate_add_order_before', array('shopgate_order' => $order));
            $this->log("# Add shopgate order to Session", ShopgateLogger::LOGTYPE_DEBUG);
            Mage::getSingleton("core/session")->setData("shopgate_order", $order);
            Mage::getSingleton('core/session')->setData('is_zero_tax', false);
            $this->log("# Create quote for order", ShopgateLogger::LOGTYPE_DEBUG);

            $quote = Mage::getModel('sales/quote')->setStoreId($this->_getConfig()->getStoreViewId());

            $quote->getBillingAddress()->setCartFixedRules(array());
            $quote->getShippingAddress()->setCartFixedRules(array());
            $quote = $this->executeLoaders($this->_getCreateOrderQuoteLoaders(), $quote, $order);
            $quote->setInventoryProcessed(false);
            $quote->setTotalsCollectedFlag(false);

            // Shipping rate is set at Shopgate_Framework_Model_Carrier_Fix
            $quote->getShippingAddress()->setCollectShippingRates(true);
            ShopgateLogger::getInstance()->log('setCollectShippingRates ok', ShopgateLogger::LOGTYPE_DEBUG);

            $rates  = $quote->getShippingAddress()->collectShippingRates()->getGroupedAllShippingRates();
            $title  = null;
            $method = 'shopgate_fix';
            if (array_key_exists('shopgate', $rates)) {
                /** @var Mage_Sales_Model_Quote_Address_Rate $addressRate */
                $addressRate = $rates['shopgate'][0];

                foreach ($rates as $_key) {
                    foreach ($_key as $rate) {
                        /** @var Mage_Sales_Model_Quote_Address_Rate $rate */
                        if ($rate->getCode() == $addressRate->getMethodTitle()) {
                            $method = $addressRate->getMethodTitle();
                            $addressRate->setCarrierTitle($rate->getCarrierTitle());
                            $addressRate->setMethodTitle($rate->getMethodTitle());
                            $addressRate->save();
                            $title = $addressRate->getCarrierTitle() . " - " . $addressRate->getMethodTitle();
                            break;
                        }
                    }
                }
            }
            $title = $title ? $title : $order->getShippingInfos()->getDisplayName();
            $quote->getShippingAddress()->setShippingDescription($title);
            $quote->collectTotals()->save();

            // due to compatibility with 3rd party modules which fetches the quote from the session (like phoenix_cod)
            Mage::getSingleton('checkout/session')->replaceQuote($quote);
            $this->log("# Create order from quote", ShopgateLogger::LOGTYPE_DEBUG);
            
            if ($order->getPaymentMethod() == ShopgateOrder::AMAZON_PAYMENT
                && $this->_getHelper()->isModuleOutputEnabled('Creativestyle_AmazonPayments')) {
                $magentoOrder = Mage::getModel('shopgate/payment_amazon')->createNewOrder($quote);
            } else {
                $service = Mage::getModel('sales/service_quote', $quote);
                if (!Mage::helper("shopgate/config")->getIsMagentoVersionLower15()) {
                    $service->submitAll();
                    $magentoOrder = $service->getOrder();
                } else {
                    $magentoOrder = $service->submit();
                }
            }
            
            $this->log("# Modify order", ShopgateLogger::LOGTYPE_DEBUG);
            $magentoOrder->setCanEdit(false);
            $magentoOrder->setCanShipPartially(true);
            $magentoOrder->setCanShipPartiallyItem(true);
            $magentoOrder = $this->executeLoaders($this->_getCreateOrderLoaders(), $magentoOrder, $order);
            $magentoOrder->setShippingDescription($title);
            $magentoOrder->setShippingMethod($method);
            $magentoOrder->save();

            $this->log("# Commit Transaction", ShopgateLogger::LOGTYPE_DEBUG);
            Mage::getModel("sales/order")->getResource()->commit();
            $this->log("## Order saved successful", ShopgateLogger::LOGTYPE_DEBUG);
            Mage::dispatchEvent(
                'shopgate_add_order_after',
                array(
                    'shopgate_order' => $order,
                    'order'          => $magentoOrder
                )
            );

            $warnings      = array();
            $totalShopgate = $order->getAmountComplete();
            $totalMagento  = $magentoOrder->getTotalDue();
            $this->log(
                 "
					Total Shopgate: {$totalShopgate} {$order->getCurrency()}
					Total Magento: {$totalMagento} {$order->getCurrency()}
					",
                 ShopgateLogger::LOGTYPE_DEBUG
            );

            $result = array(
                "external_order_id"     => $magentoOrder->getId(),
                "external_order_number" => $magentoOrder->getIncrementId()
            );

            $msg = "";
            if (!$this->_getHelper()->isOrderTotalCorrect($order, $magentoOrder, $msg)) {
                $this->log($msg);
                $warnings[] = array(
                    "message" => $msg
                );

                $result["warnings"] = $warnings;
            }
        } catch (ShopgateLibraryException $e) {
            Mage::getModel("sales/order")->getResource()->rollback();
            throw $e;
        } catch (Exception $e) {
            Mage::getModel("sales/order")->getResource()->rollback();
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, "{$e->getMessage()}\n{$e->getTraceAsString()}", true);
        }

        return $result;
    }

    /**
     * array of functions called to create the quote
     *
     * @return array
     */
    protected function _getCreateOrderQuoteLoaders()
    {
        return array(
            "_setQuoteItems",
            /** $this->_setQuoteItems */
            "_setQuoteShopgateCoupons",
            /** $this->_setQuoteShopgateCoupons */
            "_setQuotePayment",
            /** $this->_setQuotePayment */
            "_setQuotePaymentFee",
            /** $this->_setQuotePaymentFee */
            "_setQuoteVirtualItem",
            /** $this->_setQuoteVirtualItem */
            "_setQuoteCustomer",
            /** $this->_setQuoteCustomer */
            "_setQuoteShopCoupons",
            /** $this->_setQuoteShopCoupons */
        );
    }

    /**
     * Insert the ordered items to quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateCartBase       $order
     *
     * @return Mage_Sales_Model_Quote
     * @throws ShopgateLibraryException
     */
    protected function _setQuoteItems($quote, $order)
    {
        $this->log('_setQuoteItems', ShopgateLogger::LOGTYPE_DEBUG);

        foreach ($order->getItems() as $item) {
            /* @var $item ShopgateOrderItem */
            if ($item->getUnitAmountWithTax() < 0 && !$item->getInternalOrderInfo()) {
                continue;
            }

            $orderInfo     = $item->getInternalOrderInfo();
            $orderInfo     = $this->jsonDecode($orderInfo, true);
            $amountWithTax = $item->getUnitAmountWithTax();
            $amount        = $item->getUnitAmount();

            $stackQuantity = 1;
            if (!empty($orderInfo['stack_quantity']) && $orderInfo['stack_quantity'] > 1) {
                $stackQuantity = $orderInfo['stack_quantity'];
            }

            if ($stackQuantity > 1) {
                $amountWithTax = $amountWithTax / $stackQuantity;
                $amount        = $amount / $stackQuantity;
            }

            $pId = $orderInfo["product_id"];
            /** @var Mage_Catalog_Model_Product $product */
            $product       = Mage::getModel('catalog/product')->setStoreId($this->_getConfig()->getStoreViewId())
                                 ->load($pId);
            $productWeight = $product->getWeight();
            $itemNumber    = $item->getItemNumber();

            if (strpos($itemNumber, '-') !== false) {
                $productIds = explode('-', $itemNumber);
                $parentId   = $productIds[0];
                /** @var Mage_Catalog_Model_Product $parent */
                $parent = Mage::getModel('catalog/product')->setStoreId($this->_getConfig()->getStoreViewId())
                              ->load($parentId);
                if ($parent->isConfigurable()) {
                    $buyObject       = $this->_createQuoteItemBuyInfo($item, $parent, $stackQuantity);
                    $superAttributes = $parent->getTypeInstance(true)->getConfigurableAttributesAsArray($parent);
                    $superAttConfig  = array();

                    foreach ($superAttributes as $productAttribute) {
                        $superAttConfig[$productAttribute['attribute_id']] = $product->getData(
                                                                                     $productAttribute['attribute_code']
                        );
                    }
                    $buyObject->setSuperAttribute($superAttConfig);
                    $product = $parent;
                } else {
                    $buyObject = $this->_createQuoteItemBuyInfo($item, $product, $stackQuantity);
                }
            } else {
                $buyObject = $this->_createQuoteItemBuyInfo($item, $product, $stackQuantity);
            }

            $giftWrapInfo = false;
            if (isset($buyObject[self::GIFT_WRAP_OPTION_ID])) {
                $giftWrapInfo = $buyObject[self::GIFT_WRAP_OPTION_ID];
                unset($buyObject[self::GIFT_WRAP_OPTION_ID]);
            }
            $product->setData('shopgate_item_number', $itemNumber);
            $product->setData('shopgate_options', $item->getOptions());
            $product->setData('shopgate_inputs', $item->getInputs());
            $product->setData('shopgate_attributes', $item->getAttributes());
            try {
                /** @var $quotItem Mage_Sales_Model_Quote_Item */
                $quoteItem = $quote->addProduct($product, $buyObject);
                if (!($quoteItem instanceof Varien_Object)) {
                    if (Mage::helper('catalog')->__('The text is too long') == $quoteItem) {
                        Mage::throwException(Mage::helper('catalog')->__('The text is too long'));
                    } else {
                        throw new ShopgateLibraryException(
                            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                            "Error on adding product to quote! Details: " . var_export($quoteItem, true),
                            true);
                    }
                }
                $quoteItem = $quote->getItemByProduct($product);
                $quoteItem->setTaxPercent($item->getTaxPercent());
                $quoteItem->setWeight($productWeight);
                $quoteItem->setRowWeight((float)$product->getWeight() * $item->getQuantity());

                if (Mage::getStoreConfig(
                        Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
                        $orderInfo["store_view_id"]
                )
                ) {

                    if (false !== $giftWrapInfo) {
                        $amountWithTax -= $giftWrapInfo['price'];
                        $quoteItem->setGwId($giftWrapInfo['id']);
                    }

                    // re-add tax if item from shopgate order has 0% percent tax
                    // -> magento will remove tax later in address collector by itself
                    if (!(int)$item->getTaxPercent()) {

                        $rateRequest = Mage::getSingleton('tax/calculation')->getRateRequest(false, false, false);
                        $taxclassid  = $product->getData('tax_class_id');
                        $percent     = Mage::getSingleton('tax/calculation')->getRate(
                                           $rateRequest->setProductClassId($taxclassid)
                        );

                        $amountWithTax *= (100 + $percent) / 100;
                        Mage::getSingleton('core/session')->setData('is_zero_tax', true);
                    }

                    $quoteItem->setCustomPrice($amountWithTax);
                    $quoteItem->setOriginalCustomPrice($amountWithTax);
                } else {

                    if (false !== $giftWrapInfo) {
                        $amount -= $giftWrapInfo['price'];
                        $quoteItem->setGwId($giftWrapInfo['id']);
                    }

                    $quoteItem->setCustomPrice($amount);
                    $quoteItem->setOriginalCustomPrice($amount);
                }

                $quoteItem->setWeeeTaxApplied(serialize(array()));
            } catch (Exception $e) {
                $quote->setShopgateError(array($itemNumber => array($e->getCode() => $e->getMessage())));
            }
        }

        return $quote;
    }

    /**
     * @see http://inchoo.net/ecommerce/magento/programatically-add-bundle-product-to-cart-n-magento/
     *
     * @param ShopgateOrderItem          $item
     * @param Mage_Catalog_Model_Product $product
     * @param int                        $stackQuantity
     *
     * @return Varien_Object
     */
    protected function _createQuoteItemBuyInfo($item, $product, $stackQuantity)
    {
        $orderInfo = $item->getInternalOrderInfo();
        $orderInfo = $this->jsonDecode($orderInfo, true);

        $buyInfo = array(
            'qty'     => $item->getQuantity() * $stackQuantity,
            'product' => $product->getId(),
        );

        if (isset($orderInfo["options"])) {
            $buyInfo['super_attribute'] = $orderInfo["options"];
        }

        if ($item->getOptions()) {

            if ($orderInfo["item_type"] == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                foreach ($item->getOptions() as $orderOption) {
                    /* @var $orderOption ShopgateOrderItemOption */
                    $optionId = $orderOption->getOptionNumber();
                    $value    = $orderOption->getValueNumber();

                    if (self::GIFT_WRAP_OPTION_ID === $optionId && 0 < $value) {
                        $buyInfo[self::GIFT_WRAP_OPTION_ID] = array(
                            'id'    => $value,
                            'price' => $orderOption->getAdditionalAmountWithTax()
                        );
                        continue;
                    }
                    /** @var Mage_Catalog_Model_Product_Option $productOption */
                    $productOption = Mage::getModel("bundle/option")->load($optionId);

                    if ($productOption->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX
                        || $productOption->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE
                    ) {

                        if (!$value) {
                            continue;
                        }

                        $value = array($value);
                    }
                    /** @var Mage_Bundle_Model_Selection $bundleSelection */
                    $bundleSelection                         = Mage::getModel("bundle/selection")->load($value);
                    $buyInfo["bundle_option_qty"][$optionId] = max(1, (int)$bundleSelection->getSelectionQty());
                    $buyInfo["bundle_option"][$optionId]     = $value;
                }
            } else {
                foreach ($item->getOptions() as $orderOption) {
                    /* @var $orderOption ShopgateOrderItemOption */
                    $optionId = $orderOption->getOptionNumber();
                    $value    = $orderOption->getValueNumber();

                    if (self::GIFT_WRAP_OPTION_ID === $optionId && 0 < $value) {
                        $buyInfo[self::GIFT_WRAP_OPTION_ID] = array(
                            'id'    => $value,
                            'price' => $orderOption->getAdditionalAmountWithTax()
                        );
                        continue;
                    }
                    /** @var Mage_Catalog_Model_Product_Option $productOption */
                    $productOption = Mage::getModel("catalog/product_option")->load($optionId);

                    if ($productOption->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX
                        || $productOption->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE
                    ) {

                        if ($value == "0") {
                            continue;
                        }

                        $value = array($value);
                    }
                    $buyInfo["options"][$optionId] = $value;
                }
            }
        }

        if ($item->getInputs()) {
            foreach ($item->getInputs() as $orderInput) {
                /* @var $orderInput ShopgateOrderItemInput */
                $optionId                      = $orderInput->getInputNumber();
                $value                         = $orderInput->getUserInput();
                $buyInfo["options"][$optionId] = $value;
            }
        }

        $obj = new Varien_Object($buyInfo);

        return $obj;
    }

    /**
     * Add coupons managed by shopgate to the quote
     * These coupons will added as dummy article with negative amount
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateCartBase       $order
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _setQuoteShopgateCoupons(Mage_Sales_Model_Quote $quote, ShopgateCartBase $order)
    {
        $this->_getSimpleShopgateCoupons($order);
        return $quote;
    }

    /**
     * @param ShopgateCartBase $order
     *
     * @throws ShopgateLibraryException
     */
    protected function _getSimpleShopgateCoupons(ShopgateCartBase $order)
    {
        if ($order instanceof ShopgateOrder) {
            foreach ($order->getItems() as $item) {
                /** @var ShopgateOrderItem $item */
                if ($item->getUnitAmountWithTax() > 0 && $item->getInternalOrderInfo()) {
                    continue;
                }

                $obj = new Varien_Object();
                $obj->setName($item->getName());
                $obj->setItemNumber($item->getItemNumber());
                $obj->setUnitAmountWithTax($item->getUnitAmountWithTax());
                $this->_virtualObjectStack[] = $obj;
            }
        } else {
            if ($order instanceof ShopgateCart) {
                foreach ($order->getShopgateCoupons() as $coupon) {
                    /** @var ShopgateShopgateCoupon $coupon */
                    $obj = new Varien_Object();
                    $obj->setName($coupon->getName());
                    $obj->setItemNumber("COUPON");
                    $obj->setUnitAmountWithTax(-1 * $coupon->getAmount());
                    $this->_virtualObjectStack[] = $obj;
                }
            } else {
                throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE);
            }
        }
    }

    /**
     * Set the payment for the given quote
     * Default: Shopgate, Paypal or ShopgateGeneric
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateOrder          $order
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _setQuotePayment($quote, $order)
    {
        $payment = $this->_getMagentoPaymentMethod($order->getPaymentMethod());

        if ($payment instanceof Shopgate_Framework_Model_Payment_MobilePayment) {
            $this->log("payment is shopgate", ShopgateLogger::LOGTYPE_DEBUG);
            $payment->setShopgateOrder($order);
        }

        $quote->getPayment()->setMethod($payment->getCode());
        $paymentInfo = array();

        if ($payment->getCode() == Mage::getModel("shopgate/payment_mobilePayment")->getCode()) {
            $paymentInfo = $order->getPaymentInfos();
        }

        $info                                       = $order->getPaymentInfos();
        $paymentInfo['is_customer_invoice_blocked'] = $order->getIsCustomerInvoiceBlocked();
        $paymentInfo['is_test']                     = $order->getIsTest();
        $paymentInfo['is_paid']                     = $order->getIsPaid();

        if ($order->getPaymentMethod() == ShopgateOrder::PREPAY) {
            $paymentInfo["mailing_address"] = $info["purpose"];
        }

        if ($order->getAmountShopPayment() != 0) {
            $paymentInfo["amount_payment"] = $order->getAmountShopPayment();
        }

        $quote->getPayment()->setAdditionalData(serialize($paymentInfo));
        $quote->getPayment()->setLastTransId($order->getPaymentTransactionNumber());

        if ($order->getPaymentMethod() == ShopgateOrder::AMAZON_PAYMENT) {
            if ($quote->isVirtual()) {
                $quote->getBillingAddress()->setPaymentMethod($payment->getCode() ? $payment->getCode() : null);
            } else {
                $quote->getShippingAddress()->setPaymentMethod($payment->getCode() ? $payment->getCode() : null);
            }

            $data = array( 'method' => $payment->getCode(),
                           'checks' => Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_USE_FOR_COUNTRY
                                       | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_USE_FOR_CURRENCY
                                       | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
            );

            $quote->getPayment()->importData($data);
            $quote->getPayment()->setTransactionId($info['mws_order_id']);
        }
        
        return $quote;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateCartBase       $order
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _setQuotePaymentFee($quote, ShopgateCartBase $order)
    {
        $amountShopPayment = $order->getAmountShopPayment();
        if ($amountShopPayment >= 0) {
            return $quote;
        }

        $paymentName = '-';

        if ($order instanceof ShopgateOrder) {
            /* @var $order ShopgateOrder */
            $info = $order->getPaymentInfos();
            if (isset($info['shopgate_payment_name'])) {
                $paymentName = $info['shopgate_payment_name'];
            }
        }

        $paymentItem = new Varien_Object(
            array(
                'name'                 => $this->_getHelper()->__('Payment: %s', $paymentName),
                'item_number'          => 'SGPayment',
                'unit_amount_with_tax' => $amountShopPayment
            )
        );

        $this->_virtualObjectStack[] = $paymentItem;

        return $quote;
    }

    /**
     * @param string $paymentType
     *
     * @return Mage_Payment_Model_Method_Abstract
     */
    protected function _getMagentoPaymentMethod($paymentType)
    {
        $this->log("start _getMagentoPaymentMethod", ShopgateLogger::LOGTYPE_DEBUG);
        $payment = null;

        switch ($paymentType) {
            case ShopgateOrder::SHOPGATE:
                $payment = Mage::getModel("shopgate/payment_shopgate");
                break;
            case ShopgateOrder::PAYPAL:
                $payment = Mage::getModel("paypal/standard");
                break;
            case ShopgateOrder::COD:
                if ($this->_getHelper()->isModuleEnabled("Phoenix_CashOnDelivery")) {
                    $version = Mage::getConfig()->getModuleConfig("Phoenix_CashOnDelivery")->version;
                    if (version_compare($version, '1.0.8', '<')) {
                        $payment = Mage::getModel("cashondelivery/cashOnDelivery");
                    } else {
                        $payment = Mage::getModel("phoenix_cashondelivery/cashOnDelivery");
                    }
                    break;
                }
                break;
            case ShopgateOrder::PREPAY:
                if ($this->_getHelper()->isModuleOutputEnabled("Phoenix_BankPayment")) {
                    $payment = Mage::getModel("bankpayment/bankPayment");
                    break;
                }
                break;
            case ShopgateOrder::INVOICE:
                $payment = Mage::getModel("payment/method_purchaseorder");
                break;
            case ShopgateOrder::AMAZON_PAYMENT:
                if ($this->_getHelper()->isModuleOutputEnabled("Creativestyle_AmazonPayments")) {
                    $payment = Mage::getModel('amazonpayments/payment_advanced');
                    break;
                }
                break;
            default:
                $payment = Mage::getModel("shopgate/payment_mobilePayment");
                break;
        }

        if (!$payment) {
            $payment = Mage::getModel("shopgate/payment_mobilePayment");
        }

        $this->log("end _getMagentoPaymentMethod", ShopgateLogger::LOGTYPE_DEBUG);
        return $payment;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _setQuoteVirtualItem(Mage_Sales_Model_Quote $quote)
    {
        if (!count($this->_virtualObjectStack)) {
            return $quote;
        }

        $quote->setIsSuperMode(true);
        $name          = '';
        $number        = '';
        $amountWithTax = 0.0;

        foreach ($this->_virtualObjectStack as $obj) {
            /* @var $obj Varien_Object */
            $name .= ('' == $name ? '' : ', ') . $obj->getName();
            $number .= ('' == $number ? '' : ', ') . $obj->getItemNumber();
            $amountWithTax += $obj->getUnitAmountWithTax();

            /* @var $merged Mage_Catalog_Model_Product */
            $merged = Mage::getModel('catalog/product');
            $merged->setPriceCalculation(false);
            $merged->setName($name);
            $merged->setSku($number);
            $merged->setPrice($amountWithTax);

            // typeid is important for magento 1.4
            $merged->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL);

            /* @var $quoteItem Mage_Sales_Model_Quote_Item */
            $quoteItem = $quote->addProduct($merged, 1);

            $quoteItem->setCustomPrice($amountWithTax);
            $quoteItem->setOriginalPrice($amountWithTax);
            $quoteItem->setOriginalCustomPrice($amountWithTax);

            $quoteItem->setNoDiscount(true);
            $quoteItem->setRowWeight(0);
            $quoteItem->setWeeeTaxApplied(serialize(array()));
        }

        return $quote;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateCartBase       $order
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _setQuoteCustomer($quote, $order)
    {
        if ($order->getExternalCustomerId()) {
            $this->log(
                 'external customer id: ' . $order->getExternalCustomerId(),
                 ShopgateLogger::LOGTYPE_DEBUG
            );
            $quote->setCustomer(Mage::getModel("customer/customer")->load($order->getExternalCustomerId()));
            $this->log('external customer loaded', ShopgateLogger::LOGTYPE_DEBUG);
        }

        if ($order->getInvoiceAddress()) {
            $this->log('invoice address start', ShopgateLogger::LOGTYPE_DEBUG);

            $quote->getBillingAddress()->setShouldIgnoreValidation(true);
            $billingAddressData = $this->_getSalesHelper()->createAddressData(
                                       $order, $order->getInvoiceAddress(), true
            );
            $billingAddress     = $quote->getBillingAddress()->addData($billingAddressData);

            $this->log('invoice address end', ShopgateLogger::LOGTYPE_DEBUG);
        }

        if ($order->getDeliveryAddress()) {
            $this->log('delivery address start', ShopgateLogger::LOGTYPE_DEBUG);

            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
            $shippingAddressData = $this->_getSalesHelper()->createAddressData(
                                        $order, $order->getDeliveryAddress(), false
            );
            $shippingAddress     = $quote->getShippingAddress()->addData($shippingAddressData);

            $this->_getHelper()->setShippingMethod($shippingAddress, $order);
            $this->log('delivery address end', ShopgateLogger::LOGTYPE_DEBUG);
        }

        $quote->setCustomerEmail($order->getMail());
        $this->log('customer email: ' . $order->getMail(), ShopgateLogger::LOGTYPE_DEBUG);

        if ($order->getInvoiceAddress()) {
            $this->log('invoice address start (names)', ShopgateLogger::LOGTYPE_DEBUG);

            $quote->setCustomerPrefix($quote->getShippingAddress()->getPrefix());
            $quote->setCustomerFirstname($order->getInvoiceAddress()->getFirstName());
            $quote->setCustomerLastname($order->getInvoiceAddress()->getLastName());

            $this->log('invoice address end (names)', ShopgateLogger::LOGTYPE_DEBUG);
        }

        $externalCustomerId = $order->getExternalCustomerId();
        if (empty($externalCustomerId)) {
            $this->log('external customer number available', ShopgateLogger::LOGTYPE_DEBUG);
            $quote->setCustomerIsGuest(1);
        } else {
            $this->log('external customer number unavailable', ShopgateLogger::LOGTYPE_DEBUG);

            $quote->setCustomerIsGuest(0);
            if ($order->getInvoiceAddress()) {
                $billingAddress->setCustomerAddressId($order->getInvoiceAddress()->getId());
            }

            if ($order->getDeliveryAddress()) {
                $shippingAddress->setCustomerAddressId($order->getDeliveryAddress()->getId());
            }
        }

        $quote->setIsActive("0");
        $quote->setRemoteIp("shopgate.com");

        $quote->save();

        return $quote;
    }


    /**
     * Add coupon from this system to quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateCartBase       $order
     *
     * @return Mage_Sales_Model_Quote
     * @throws ShopgateLibraryException
     */
    protected function _setQuoteShopCoupons($quote, $order)
    {
        if (count($order->getExternalCoupons()) > 1) {
            throw new ShopgateLibraryException(ShopgateLibraryException::COUPON_TOO_MANY_COUPONS);
        }

        foreach ($order->getExternalCoupons() as $coupon) {
            /* @var $coupon ShopgateShopgateCoupon */
            $couponInfos = $this->jsonDecode($coupon->getInternalInfo(), true);

            if ($order instanceof ShopgateOrder) {

                if (!$coupon->getInternalInfo()) {
                    throw new ShopgateLibraryException(ShopgateLibraryException::COUPON_NOT_VALID, 'Field "internal_info" is empty.');
                }
                /** @var Mage_SalesRule_Model_Coupon $mageCoupon */
                if ($this->_getConfigHelper()->getIsMagentoVersionLower1410()) {
                    $mageCoupon = Mage::getModel('salesrule/rule')->load($couponInfos["coupon_id"]);
                } else {
                    $mageCoupon = Mage::getModel('salesrule/coupon')->load($couponInfos["coupon_id"]);
                }

                $count = (int)$mageCoupon->getTimesUsed();
                $count--;
                $mageCoupon->setTimesUsed($count);
                $mageCoupon->save();
            }

            $quote->setCouponCode($coupon->getCode());
            foreach ($quote->getAllAddresses() as $address) {
                $address->setCouponCode($coupon->getCode());
            }
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();

            if ($this->_errorOnInvalidCoupon) {
                if ($coupon->getCode() != $quote->getCouponCode()) {
                    throw new ShopgateLibraryException(
                        ShopgateLibraryException::COUPON_NOT_VALID,
                        'Code transferred by Shopgate"' . $coupon->getCode() . '" != "' . $quote->getCouponCode()
                        . '" code in Magento'
                    );
                }
            }
            $quote->save();
        }

        return $quote;
    }


    /**
     * array of functions called to create the order
     *
     * @return array
     */
    protected function _getCreateOrderLoaders()
    {
        return array(
            "_setShopgateOrder",
            /** $this->_setShopgateOrder */
            "_setOrderStatusHistory",
            /** $this->_setOrderStatusHistory */
            "_setOrderPayment",
            /** $this->_setOrderPayment */
            "_setAdditionalOrderInfo",
            /** $this->_setAdditionalOrderInfo */
            "_addCustomFields",
            /** $this->_addCustomFields */
            "_setOrderState",
            /** $this->_setOrderState */
            "_sendNewOrderMail",
            /** $this->_sendNewOrderMail */
        );
    }

    /**
     * @param Mage_Sales_Model_Order $magentoOrder
     * @param ShopgateOrder          $shopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _addCustomFields(Mage_Sales_Model_Order $magentoOrder, ShopgateOrder $shopgateOrder)
    {
        foreach ($shopgateOrder->getCustomFields() as $field) {
            $magentoOrder->setDataUsingMethod($field->getInternalFieldName(), $field->getValue());
        }
        $invoiceAddress = $shopgateOrder->getInvoiceAddress();
        foreach ($invoiceAddress->getCustomFields() as $field) {
            $billing = $magentoOrder->getBillingAddress();
            $billing->setDataUsingMethod($field->getInternalFieldName(), $field->getValue());
            $magentoOrder->setBillingAddress($billing);
        }
        $deliveryAddress = $shopgateOrder->getDeliveryAddress();
        foreach ($deliveryAddress->getCustomFields() as $field) {
            $shipping = $magentoOrder->getShippingAddress();
            $shipping->setDataUsingMethod($field->getInternalFieldName(), $field->getValue());
            $magentoOrder->setShippingAddress($shipping);
        }

        return $magentoOrder;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _sendNewOrderMail($order)
    {
        if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ORDER_SEND_NEW_ORDER_MAIL)) {
            $order->sendNewOrderEmail();
        }

        return $order;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _setAdditionalOrderInfo($order)
    {
        $order->setEmailSent("0");
        return $order;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param ShopgateOrder          $shopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _setOrderStatusHistory($order, $shopgateOrder)
    {
        $order->addStatusHistoryComment($this->_getHelper()->__("[SHOPGATE] Order added by Shopgate."), false);
        $order->addStatusHistoryComment(
              $this->_getHelper()->__(
                   "[SHOPGATE] Shopgate order number: %s",
                   $shopgateOrder->getOrderNumber()
              ),
              false
        );

        if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ORDER_CUSTOMFIELDS_TO_STATUSHISTORY, 
                                 $this->_getConfig()->getStoreViewId())) {
            foreach ($shopgateOrder->getCustomFields() as $field) {
                $order->addStatusHistoryComment(
                      $this->_getHelper()
                           ->__("[SHOPGATE] Custom fields:") . "\n\"" . addslashes($field->getLabel()) . "\" => \"" . addslashes($field->getValue()) . "\"", false);
            }
        }

        return $order;
    }

    /**
     * Performs the necessary queries to update an order in the shop system's database.
     *
     * @see http://wiki.shopgate.com/Merchant_API_get_orders#API_Response
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_update_order#API_Response
     *
     * @param ShopgateOrder $order The ShopgateOrder object to be updated in the shop system's database.
     *
     * @return array(
     *                             <ul>
     *                             <li>'external_order_id' => <i>string</i>, # the ID of the order in your shop system's database</li>
     *                             <li>'external_order_number' => <i>string</i> # the number of the order in your shop system</li>
     *                             </ul>)
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function updateOrder(ShopgateOrder $order)
    {
        $this->log("## Start to update Order", ShopgateLogger::LOGTYPE_DEBUG);
        $this->log(
             "## Order-Number: {$order->getOrderNumber()}",
             ShopgateLogger::LOGTYPE_DEBUG
        );

        Mage::dispatchEvent('shopgate_update_order_before', array('shopgate_order' => $order));
        $this->log("# Begin database transaction", ShopgateLogger::LOGTYPE_DEBUG);
        Mage::getModel("sales/order")->getResource()->beginTransaction();

        /** @var Shopgate_Framework_Model_Shopgate_Order $shopgateOrder */
        $shopgateOrder = Mage::getModel("shopgate/shopgate_order")->load(
                             $order->getOrderNumber(),
                             'shopgate_order_number'
        );

        if ($shopgateOrder->getId() == null) {
            $this->log("# order not found", ShopgateLogger::LOGTYPE_DEBUG);
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_ORDER_NOT_FOUND);
        }
        $this->log("# Add shopgate order to Session", ShopgateLogger::LOGTYPE_DEBUG);
        Mage::getSingleton("core/session")->setData("shopgate_order", $order);
        $this->log("# load Magento-order", ShopgateLogger::LOGTYPE_DEBUG);
        $magentoOrder = $shopgateOrder->getOrder();
        $magentoOrder = $this->_getUpdateOrderLoaders($magentoOrder, $order, $shopgateOrder);
        $magentoOrder->addStatusHistoryComment(
                     $this->_getHelper()->__("[SHOPGATE] Order updated by Shopgate."),
                     false
        );

        $magentoOrder->save();
        $this->log("# Commit Transaction", ShopgateLogger::LOGTYPE_DEBUG);
        Mage::getModel("sales/order")->getResource()->commit();
        $this->log("## Order saved successful", ShopgateLogger::LOGTYPE_DEBUG);
        Mage::dispatchEvent(
            'shopgate_update_order_after',
            array(
                'shopgate_order' => $order,
                'order'          => $magentoOrder
            )
        );

        if (!$this->_isValidShipping($magentoOrder, $order, $shopgateOrder)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_ORDER_STATUS_IS_SENT);
        }

        return array(
            'external_order_id'     => $magentoOrder->getId(),
            'external_order_number' => $magentoOrder->getIncrementId()
        );
    }

    /**
     * update order loaders
     *
     * @param Mage_Sales_Model_Order                  $magentoOrder
     * @param ShopgateOrder                           $shopgateOrder
     * @param Shopgate_Framework_Model_Shopgate_Order $magentoShopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getUpdateOrderLoaders($magentoOrder, $shopgateOrder, $magentoShopgateOrder)
    {
        $magentoOrder = $this->_updateOrderPayment($magentoOrder, $shopgateOrder);
        $magentoOrder = $this->_updateOrderShipping($magentoOrder, $shopgateOrder, $magentoShopgateOrder);
        $magentoOrder = $this->_setShopgateOrder($magentoOrder, $shopgateOrder, $magentoShopgateOrder);
        return $magentoOrder;
    }

    /**
     * @param Mage_Sales_Model_Order $magentoOrder
     * @param ShopgateOrder          $shopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _updateOrderPayment($magentoOrder, $shopgateOrder)
    {
        if ($shopgateOrder->getUpdatePayment()) {
            $this->log("# Update payment", ShopgateLogger::LOGTYPE_DEBUG);
            $magentoOrder = $this->_setOrderPayment($magentoOrder, $shopgateOrder);
            $this->log("# Update payment successful", ShopgateLogger::LOGTYPE_DEBUG);
        }
        return $magentoOrder;
    }

    /**
     * @param Mage_Sales_Model_Order                  $magentoOrder
     * @param ShopgateOrder                           $shopgateOrder
     * @param Shopgate_Framework_Model_Shopgate_Order $magentoShopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _updateOrderShipping($magentoOrder, $shopgateOrder, $magentoShopgateOrder)
    {
        if ($shopgateOrder->getUpdateShipping()) {
            $this->log("# Update shipping", ShopgateLogger::LOGTYPE_DEBUG);
            $magentoOrder = $this->_setOrderState($magentoOrder, $shopgateOrder);
            if ($this->_isValidShipping($magentoOrder, $shopgateOrder, $magentoShopgateOrder)) {
                $message = "[SHOPGATE] Shipping of this order is not blocked by Shopgate.";
                $magentoOrder->addStatusHistoryComment($this->_getHelper()->__($message), false);
            } else {
                $message = "[SHOPGATE] Shipping of this order is not Blocked anymore!";
                $magentoOrder->addStatusHistoryComment($this->_getHelper()->__($message), false);
            }
        }

        return $magentoOrder;
    }

    /**
     * validate shipping
     *
     * @param Mage_Sales_Model_Order                       $magentoOrder
     * @param ShopgateOrder                                $shopgateOrder
     * @param Shopgate_Framework_Model_Shopgate_Order|NULL $magentoShopgateOrder
     *
     * @return bool
     */
    protected function _isValidShipping($magentoOrder, $shopgateOrder, $magentoShopgateOrder = null)
    {
        $isValidShipping = true;
        if (($shopgateOrder->getIsShippingBlocked() || $magentoShopgateOrder->getIsShippingBlocked())
            && $magentoOrder->getShipmentsCollection()->getSize() > 0
        ) {
            $isValidShipping = false;
        }

        return $isValidShipping;
    }

    /**
     * @param Mage_Sales_Model_Order                  $magentoOrder
     * @param ShopgateOrder                           $shopgateOrder
     * @param Shopgate_Framework_Model_Shopgate_Order $magentoShopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _setShopgateOrder($magentoOrder, $shopgateOrder, $magentoShopgateOrder = null)
    {
        if ($magentoShopgateOrder) {
            if ($shopgateOrder->getUpdatePayment()) {
                $magentoShopgateOrder->setIsPaid($shopgateOrder->getIsPaid());
            }

            if ($shopgateOrder->getUpdateShipping()) {
                $magentoShopgateOrder->setIsShippingBlocked($shopgateOrder->getIsShippingBlocked());
            }
        } else {
            $magentoShopgateOrder = Mage::getModel("shopgate/shopgate_order")
                                        ->setOrderId($magentoOrder->getId())
                                        ->setStoreId($this->_getConfig()->getStoreViewId())
                                        ->setShopgateOrderNumber($shopgateOrder->getOrderNumber())
                                        ->setIsShippingBlocked($shopgateOrder->getIsShippingBlocked())
                                        ->setIsPaid($shopgateOrder->getIsPaid())
                                        ->setIsTest($shopgateOrder->getIsTest())
                                        ->setIsCustomerInvoiceBlocked($shopgateOrder->getIsCustomerInvoiceBlocked());
        }

        $magentoShopgateOrder->setReceivedData(serialize($shopgateOrder));
        $magentoShopgateOrder->save();

        return $magentoOrder;
    }

    /**
     * @param Mage_Sales_Model_Order $magentoOrder
     * @param ShopgateOrder          $shopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _setOrderState($magentoOrder, $shopgateOrder)
    {

        if ($shopgateOrder->getIsShippingBlocked()) {
            if ($magentoOrder->getState() != Mage_Sales_Model_Order::STATE_HOLDED) {
                $magentoOrder->setHoldBeforeState($magentoOrder->getState());
                $magentoOrder->setHoldBeforeStatus($magentoOrder->getStatus());
            }
            $magentoOrder->setState(Mage_Sales_Model_Order::STATE_HOLDED, Mage_Sales_Model_Order::STATE_HOLDED);
        } else {
            $stateObject    = new Varien_Object();
            $methodInstance = $magentoOrder->getPayment()->getMethodInstance();
            if ($shopgateOrder->getPaymentMethod() != ShopgateOrder::AMAZON_PAYMENT) {
                // avoid calling order on amazon payment again 
                $methodInstance->initialize($methodInstance->getConfigData('payment_action'), $stateObject);
            }

            if (!$stateObject->getState()) {
                $status = $methodInstance->getConfigData("order_status");
                $stateObject->setState($this->_getHelper()->getStateForStatus($status));
                $stateObject->setStatus($status);
            }
            $magentoOrder->setState($stateObject->getState(), $stateObject->getStatus());

            if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ORDER_MARK_UNBLOCKED_AS_PAID)
                && !$shopgateOrder->getIsPaid()
            ) {
                $oldStatus = $shopgateOrder->getIsPaid();
                $shopgateOrder->setIsPaid(true);

                $magentoOrder->addStatusHistoryComment(
                             $this->_getHelper()->__(
                                  "[SHOPGATE] Set order as paid because shipping is not blocked and config is set to 'mark unblocked orders as paid'!"
                             ),
                             false
                )->setIsCustomerNotified(false);

                $magentoOrder = $this->_setOrderPayment($magentoOrder, $shopgateOrder);

                $shopgateOrder->setIsPaid($oldStatus);
            }

            if ($shopgateOrder->getIsPaid() && ($shopgateOrder->getPaymentMethod() === ShopgateOrder::PAYPAL
                || $shopgateOrder->getPaymentMethod() == ShopgateOrder::AMAZON_PAYMENT)) {
                $magentoOrder->setState(
                             Mage_Sales_Model_Order::STATE_PROCESSING,
                             Mage_Sales_Model_Order::STATE_PROCESSING
                );
            }
        }

        return $magentoOrder;
    }

    /**
     * Set Payment for the order
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @param ShopgateOrder          $shopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _setOrderPayment($magentoOrder, $shopgateOrder)
    {
        if ($shopgateOrder->getPaymentMethod() == ShopgateOrder::AMAZON_PAYMENT
            && Mage::helper('core')->isModuleOutputEnabled('Creativestyle_AmazonPayments')) {
            return Mage::getModel('shopgate/payment_amazon')->manipulateOrderWithPaymentData($magentoOrder,$shopgateOrder);
        }

        if ($shopgateOrder->getIsPaid() && $magentoOrder->getBaseTotalDue()) {
            $magentoOrder->getPayment()->setShouldCloseParentTransaction(true);
            $magentoOrder->getPayment()->registerCaptureNotification($shopgateOrder->getAmountComplete());

            $magentoOrder->addStatusHistoryComment($this->_getHelper()->__("[SHOPGATE] Payment received."), false)
                         ->setIsCustomerNotified(false);

            if ($shopgateOrder->getPaymentMethod() === ShopgateOrder::PAYPAL) {
                try {
                    $transaction = Mage::getModel("sales/order_payment_transaction");
                    $transaction->setOrderPaymentObject($magentoOrder->getPayment());
                    $transaction->setIsClosed(false);
                    $transaction->setTxnId($shopgateOrder->getPaymentTransactionNumber());
                    $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);

                    $transaction->save();

                    $magentoOrder->getPayment()->importTransactionInfo($transaction);
                } catch (Exception $x) {
                    $this->log($x->getMessage());
                }
            }
        }
        $magentoOrder->getPayment()->setLastTransId($shopgateOrder->getPaymentTransactionNumber());

        return $magentoOrder;
    }

    /**
     * Redeems coupons that are passed along with a ShopgateCart object.
     *
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_redeem_coupons#API_Response
     *
     * @param ShopgateCart $cart The ShopgateCart object containing the coupons that should be redeemed.
     *
     * @return array('external_coupons' => ShopgateExternalCoupon[])
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function redeemCoupons(ShopgateCart $cart)
    {
        $result                      = array();
        $this->_errorOnInvalidCoupon = false;
        $mageCart                    = $this->_createMagentoCartFromShopgateCart($cart);

        foreach ($this->checkCoupons($mageCart, $cart) as $coupon) {
            /** @var ShopgateShopgateCoupon $coupon */
            /** @var Mage_SalesRule_Model_Coupon $mageCoupon */
            if ($this->_getConfigHelper()->getIsMagentoVersionLower1410()) {
                $mageCoupon = Mage::getModel('salesrule/rule')->load($coupon->getCode(), 'coupon_code');
            } else {
                $mageCoupon = Mage::getModel('salesrule/coupon')->load($coupon->getCode(), 'code');
            }

            if ($mageCoupon->getId() && $coupon->getIsValid()) {
                $count = (int)$mageCoupon->getTimesUsed();
                $count++;
                $mageCoupon->setTimesUsed($count);
                $mageCoupon->save();
            }
            $result[] = $coupon;
        }

        return $result;
    }

    /**
     * Create a Magento cart and the quote
     *
     * @throws ShopgateLibraryException
     *
     * @param ShopgateCart $cart
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _createMagentoCartFromShopgateCart(ShopgateCart $cart)
    {
        $mageCart = Mage::getSingleton('checkout/cart');
        /** @var Mage_Sales_Model_Quote $mageQuote */
        $mageQuote = $mageCart->getQuote();
        $mageQuote = $this->executeLoaders($this->_getCheckCartQuoteLoaders(), $mageQuote, $cart);
        $mageQuote->getShippingAddress()->setCollectShippingRates(true);

        return $mageCart;
    }

    /**
     * @return array
     */
    protected function _getCheckCartQuoteLoaders()
    {
        return array(
            "_setQuoteItems",
            /** $this->_setQuoteItems */
            "_setQuoteShopgateCoupons",
            /** $this->_setQuoteShopgateCoupons */
            "_setQuoteCustomer",
            /** $this->_setQuoteCustomer */
        );
    }

    /**
     * Check coupons for validation
     * Function will throw an ShopgateLibraryException if
     * * Count of coupons > 1
     * * Coupon cannot found
     * * Magento throws an exception
     *
     * @param              $mageCart
     * @param ShopgateCart $cart
     *
     * @return mixed|null|ShopgateExternalCoupon
     * @throws ShopgateLibraryException
     */
    public function checkCoupons($mageCart, ShopgateCart $cart)
    {
        /* @var $mageQuote Mage_Sales_Model_Quote */
        /* @var $mageCart Mage_Checkout_Model_Cart */
        /* @var $mageCoupon Mage_SalesRule_Model_Coupon */
        /* @var $mageRule Mage_SalesRule_Model_Rule */

        if (!$cart->getExternalCoupons()) {
            return null;
        }

        $externalCoupons    = array();
        $mageQuote          = $mageCart->getQuote();
        $validCouponsInCart = 0;

        foreach ($cart->getExternalCoupons() as $coupon) {
            /** @var ShopgateExternalCoupon $coupon */
            $externalCoupon = new ShopgateExternalCoupon();
            $externalCoupon->setIsValid(true);
            $externalCoupon->setCode($coupon->getCode());

            try {
                $mageQuote->setCouponCode($coupon->getCode());
                $mageQuote->setTotalsCollectedFlag(false)->collectTotals();
            } catch (Exception $e) {
                $externalCoupon->setIsValid(false);
                $externalCoupon->setNotValidMessage($e->getMessage());
            }

            if ($this->_getConfigHelper()->getIsMagentoVersionLower1410()) {
                $mageRule   = Mage::getModel('salesrule/rule')->load($coupon->getCode(), 'coupon_code');
                $mageCoupon = $mageRule;
            } else {
                $mageCoupon = Mage::getModel('salesrule/coupon')->load($coupon->getCode(), 'code');
                $mageRule   = Mage::getModel('salesrule/rule')->load($mageCoupon->getRuleId());
            }

            if ($mageRule->getId() && $mageQuote->getCouponCode()) {
                $couponInfo              = array();
                $couponInfo["coupon_id"] = $mageCoupon->getId();
                $couponInfo["rule_id"]   = $mageRule->getId();

                $amountCoupon = $mageQuote->getSubtotal() - $mageQuote->getSubtotalWithDiscount();

                $storeLabel = $mageRule->getStoreLabel(Mage::app()->getStore()->getId());
                $externalCoupon->setName($storeLabel ? $storeLabel : $mageRule->getName());
                $externalCoupon->setDescription($mageRule->getDescription());
                $externalCoupon->setIsFreeShipping((bool)$mageQuote->getShippingAddress()->getFreeShipping());
                $externalCoupon->setInternalInfo($this->jsonEncode($couponInfo));

                if (Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DISCOUNT_TAX, $this->_getConfig()
                                                                                                   ->getStoreViewId())
                ) {
                    $externalCoupon->setAmount($amountCoupon);
                } else {
                    $externalCoupon->setAmountNet($amountCoupon);
                }
            } else {
                $externalCoupon->setIsValid(false);
                $externalCoupon->setNotValidMessage(
                               $this->_getHelper()->__(
                                    'Coupon code "%s" is not valid.',
                                    Mage::helper('core')->escapeHtml($coupon->getCode())
                               )
                );
            }

            if ($externalCoupon->getIsValid() && $validCouponsInCart >= 1) {
                $errorCode = ShopgateLibraryException::COUPON_TOO_MANY_COUPONS;
                $externalCoupon->setIsValid(false);
                $externalCoupon->setNotValidMessage(ShopgateLibraryException::getMessageFor($errorCode));
            }

            if ($externalCoupon->getIsValid()) {
                $validCouponsInCart++;
            }

            $externalCoupons[] = $externalCoupon;
        }

        return $externalCoupons;
    }

    /**
     * Checks the content of a cart to be valid and returns necessary changes if applicable.
     * This currently only supports the validation of coupons.
     *
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_check_cart#API_Response
     *
     * @param ShopgateCart $cart The ShopgateCart object to be checked and validated.
     *
     * @return array(
     *                           'external_coupons' => ShopgateExternalCoupon[], # list of all coupons</li>
     *                           'items' => array(...), # list of item changes (not supported yet)</li>
     *                           'shippings' => array(...), # list of available shipping services for this cart (not supported yet)</li>
     *                           )
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function checkCart(ShopgateCart $cart)
    {
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');
        $db->beginTransaction();
        $this->_errorOnInvalidCoupon = false;
        $this->_getCustomerHelper()->addCustomerToCart($cart);
        $mageCart = $this->_createMagentoCartFromShopgateCart($cart);
        $response = array(
            "currency"         => Mage::app()->getStore()->getCurrentCurrencyCode(),
            "external_coupons" => array(),
            "shipping_methods" => array(),
            "payment_methods"  => array(),
            "items"            => array(),
            "customer"         => $this->_getSalesHelper()->getCustomerData(
                                       $cart, $this->_getConfig()->getStoreViewId()
                )
        );

        if ($coupon = $this->checkCoupons($mageCart, $cart)) {
            $response["external_coupons"] = $coupon;
        }

        if ($shippingMethods = $this->_getSalesHelper()->getShippingMethods($mageCart)) {
            $response["shipping_methods"] = $shippingMethods;
        }

        if ($paymentMethods = $this->_getSalesHelper()->getPaymentMethods($mageCart)) {
            $response["payment_methods"] = $paymentMethods;
        }

        if ($items = $this->_getSalesHelper()->getItems($mageCart->getQuote(), $cart)) {
            $response["items"] = $items;
        }

        $db->rollback();

        return $response;
    }

    /**
     * Create a quote and collects stock information
     *
     * @param ShopgateCart $sgCart
     *
     * @see ShopgatePlugin::checkStock()
     *
     * @return array()
     */
    public function checkStock(ShopgateCart $sgCart)
    {
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');
        $db->beginTransaction();

        $mageCart  = Mage::getSingleton('checkout/cart');
        $mageQuote = $this->_setQuoteItems($mageCart->getQuote(), $sgCart);
        $items     = $this->_getSalesHelper()->getItems($mageQuote, $sgCart);

        $db->rollback();

        return $items;
    }

    /** =========================================== CATEGORY EXPORT ================================================= */
    /** =========================================== CATEGORY EXPORT ================================================= */
    /** =========================================== CATEGORY EXPORT ================================================= */

    /**
     * Loads the product categories of the shop system's database and passes them to the buffer.
     * Use ShopgatePlugin::buildDefaultCategoryRow() to get the correct indices for the field names in a Shopgate categories csv and
     * use ShopgatePlugin::addCategoryRow() to add it to the output buffer.
     *
     * @see http://wiki.shopgate.com/CSV_File_Categories
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_get_categories_csv
     * @throws ShopgateLibraryException
     */
    protected function createCategoriesCsv()
    {
        $this->log("Start Export Categories...", ShopgateLogger::LOGTYPE_ACCESS);
        $this->log("Start Export Categories...", ShopgateLogger::LOGTYPE_DEBUG);

        $maxCategoryPosition = Mage::getModel("catalog/category")->getCollection()
                                   ->setOrder('position', 'DESC')
                                   ->getFirstItem()
                                   ->getPosition();

        $this->log("Max Category Position: {$maxCategoryPosition}", ShopgateLogger::LOGTYPE_DEBUG);
        $maxCategoryPosition += 100;

        $categoryExportModel = Mage::getModel('shopgate/export_category_csv');
        $categoryExportModel->setDefaultRow($this->buildDefaultCategoryRow());
        $categoryExportModel->setMaximumPosition($maxCategoryPosition);

        if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_IS_EXPORT_STORES)) {
            $storesToExport = $this->_getConfig()->getExportStores(true);
            $groups         = Mage::app()->getWebsite()->getGroups();

            foreach ($groups as $group) {
                /* @var $group Mage_Core_Model_Store_Group */
                $store = null;
                foreach ($group->getStores() as $_store) {
                    /* @var $_store Mage_Core_Model_Store */
                    if (in_array($_store->getId(), $storesToExport)) {
                        $store = $_store;
                        break;
                    }
                }
                if (!$store) {
                    continue;
                }

                $rootCatId = $group->getRootCategoryId();
                $this->log("Root-Category-Id: {$rootCatId}", ShopgateLogger::LOGTYPE_DEBUG);

                $category                    = array();
                $category["category_number"] = $rootCatId;
                $category["category_name"]   = $group->getName();
                $category["url_deeplink"]    = $store->getUrl();
                $this->addCategoryRow($category);

                $this->log("Start Build Category-Tree recursively...", ShopgateLogger::LOGTYPE_DEBUG);
                $this->_buildCategoryTree('csv', $rootCatId);
            }
        } else {
            $rootCatId = Mage::app()->getStore()->getRootCategoryId();
            $this->log("Root-Category-Id: {$rootCatId}", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("Start Build Category-Tree recursively...", ShopgateLogger::LOGTYPE_DEBUG);
            $this->_buildCategoryTree('csv', $rootCatId);
        }

        $this->log("End Build Category-Tree Recursively...", ShopgateLogger::LOGTYPE_DEBUG);
        $this->log("Finished Export Categories...", ShopgateLogger::LOGTYPE_ACCESS);
        $this->log("Finished Export Categories...", ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * @param string $type
     * @param int    $parentId
     * @param null   $uIds
     */
    protected function _buildCategoryTree($type, $parentId, $uIds = null)
    {
        $this->log("Build Tree with Parent-ID: {$parentId}", ShopgateLogger::LOGTYPE_DEBUG);

        $category = Mage::getModel('catalog/category');
        /** @var Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree $tree */
        $tree = $category->getTreeModel();
        $root = Mage::getResourceSingleton('catalog/category_tree')->load()->getNodeById($parentId);

        $maxCategoryPosition = Mage::getModel("catalog/category")->getCollection()
                                   ->setOrder('position', 'DESC')
                                   ->getFirstItem()
                                   ->getPosition();

        $this->log("Max Category Position: {$maxCategoryPosition}", ShopgateLogger::LOGTYPE_DEBUG);
        $maxCategoryPosition += 100;

        $categories = $tree->getChildren($root);
        if ($uIds) {
            $categories = array_intersect_key($categories, $uIds);
        }

        if ($this->splittedExport) {
            $categories = array_slice($categories, $this->exportOffset, $this->exportLimit);
            $this->log("Limit: " . $this->exportLimit, ShopgateLogger::LOGTYPE_ACCESS);
            $this->log("[*] Limit: {$this->exportLimit}", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("Offset: " . $this->exportOffset, ShopgateLogger::LOGTYPE_ACCESS);
            $this->log("[*] Offset: {$this->exportOffset}", ShopgateLogger::LOGTYPE_DEBUG);
        }

        foreach ($categories as $categoryId) {
            $this->log("Load Category with ID: {$categoryId}", ShopgateLogger::LOGTYPE_DEBUG);
            /** @var Mage_Catalog_Model_Category $category */
            $category = Mage::getModel("catalog/category")->load($categoryId);
            if ($type == "csv") {
                $categoryExportModel = Mage::getModel('shopgate/export_category_csv');
                $categoryExportModel->setDefaultRow($this->buildDefaultCategoryRow());
                $categoryExportModel->setItem($category);
                $categoryExportModel->setParentId($parentId);
                $categoryExportModel->setMaximumPosition($maxCategoryPosition);
                $this->addCategoryRow($categoryExportModel->generateData());
            } else {
                $categoryExportModel = Mage::getModel('shopgate/export_category_xml');
                $categoryExportModel->setItem($category);
                $categoryExportModel->setParentId($parentId);
                $categoryExportModel->setMaximumPosition($maxCategoryPosition);
                $this->addCategoryModel($categoryExportModel->generateData());
            }
            $this->exportLimit--;

            if ($parentId == $category->getId()) {
                continue;
            }
        }
    }

    /**
     * @param int   $limit
     * @param int   $offset
     * @param array $uids
     */
    protected function createCategories($limit = null, $offset = null, array $uids = null)
    {
        $this->log("Start Export Categories...", ShopgateLogger::LOGTYPE_ACCESS);
        $this->log("Start Export Categories...", ShopgateLogger::LOGTYPE_DEBUG);

        if (!is_null($limit) && !is_null($offset)) {
            $this->setSplittedExport(true);
            $this->setExportLimit($limit);
            $this->setExportOffset($offset);
        }

        if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_IS_EXPORT_STORES)) {
            $storesToExport = $this->_getConfig()->getExportStores(true);
            $groups         = Mage::app()->getWebsite()->getGroups();

            foreach ($groups as $group) {
                /* @var $group Mage_Core_Model_Store_Group */
                $store = null;
                foreach ($group->getStores() as $_store) {
                    /* @var $_store Mage_Core_Model_Store */
                    if (in_array($_store->getId(), $storesToExport)) {
                        $store = $_store;
                        break;
                    }
                }
                if (!$store) {
                    continue;
                }

                $rootCatId = $group->getRootCategoryId();

                $this->log("Start Build Category-Tree recursively...", ShopgateLogger::LOGTYPE_DEBUG);
                $this->_buildCategoryTree('xml', $rootCatId, $uids);
            }
        } else {
            $rootCatId = Mage::app()->getStore()->getRootCategoryId();
            $this->log("Root-Category-Id: {$rootCatId}", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("Start Build Category-Tree recursively...", ShopgateLogger::LOGTYPE_DEBUG);
            $this->_buildCategoryTree('xml', $rootCatId, $uids);
        }

        $this->log("End Build Category-Tree Recursively...", ShopgateLogger::LOGTYPE_DEBUG);
        $this->log("Finished Export Categories...", ShopgateLogger::LOGTYPE_ACCESS);
        $this->log("Finished Export Categories...", ShopgateLogger::LOGTYPE_DEBUG);
    }

    /** ========================================== CATEGORY EXPORT END ============================================== */
    /** ========================================== CATEGORY EXPORT END ============================================== */
    /** ========================================== CATEGORY EXPORT END ============================================== */

    /** ============================================== ITEM EXPORT ================================================== */
    /** ============================================== ITEM EXPORT ================================================== */
    /** ============================================== ITEM EXPORT ================================================== */

    /**
     * Loads the products of the shop system's database and passes them to the buffer.
     * If $this->splittedExport is set to "true", you MUST regard $this->offset and $this->limit when fetching items from the database.
     * Use ShopgatePlugin::buildDefaultItemRow() to get the correct indices for the field names in a Shopgate items csv and
     * use ShopgatePlugin::addItemRow() to add it to the output buffer.
     *
     * @see http://wiki.shopgate.com/CSV_File_Items
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_get_items_csv
     * @throws ShopgateLibraryException
     */
    protected function createItemsCsv()
    {
        $this->log("Export start...", ShopgateLogger::LOGTYPE_ACCESS);
        $this->log("[*] Export Start...", ShopgateLogger::LOGTYPE_DEBUG);

        $this->setDefaultItemRowOptionCount($this->_getHelper()->getMaxOptionCount());

        $this->log(
             'number of options to be exported: ' . $this->getDefaultItemRowOptionCount(),
             ShopgateLogger::LOGTYPE_DEBUG
        );

        $start      = microtime();
        $productIds = $this->_getExportProduct(false, $this->exportLimit, $this->exportOffset);

        $i = 1;
        /** @var Shopgate_Model_Export_Product_Csv $productExportModel */
        $productExportModel = Mage::getModel('shopgate/export_product_csv');
        $productExportModel->setDefaultRow($this->buildDefaultItemRow());
        $productExportModel->setDefaultTax($this->_defaultTax);
        foreach ($productIds as $productId) {
            $product = Mage::getModel('catalog/product')
                           ->setStoreId($this->_getConfig()->getStoreViewId())
                           ->load($productId);
            $this->log("#{$i}", ShopgateLogger::LOGTYPE_DEBUG);
            $i++;
            /** @var Mage_Catalog_Model_Product $product */
            if ($this->_getExportHelper()->productHasRequiredFileOption($product)) {
                $this->log(
                     "Exclude Product with ID: {$product->getId()} from CSV: custom option type file",
                     ShopgateLogger::LOGTYPE_DEBUG
                );
                continue;
            }
            $memoryUsage     = memory_get_usage(true);
            $memoryUsage     = round(($memoryUsage / 1024 / 1024), 2);
            $memoryPeekUsage = memory_get_peak_usage(true);
            $memoryPeekUsage = round(($memoryPeekUsage / 1024 / 1024), 2);

            $this->log(
                 "[{$product->getId()}] Start Load Product with ID: {$product->getId()}",
                 ShopgateLogger::LOGTYPE_DEBUG
            );
            $this->log("Memory usage: {$memoryUsage} MB", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("Memory peek usage: {$memoryPeekUsage} MB", ShopgateLogger::LOGTYPE_DEBUG);

            $this->log(
                 "[{$product->getId()}] Product-Data:\n" . print_r(
                     array(
                         "id"   => $product->getId(),
                         "name" => $product->getName(),
                         "sku"  => $product->getSku(),
                         "type" => $product->getTypeId(),
                     ),
                     true
                 ),
                 ShopgateLogger::LOGTYPE_DEBUG
            );

            if ($product->isSuper()) {
                if (!$product->isGrouped()) {
                    // add config parent
                    $this->addItem($productExportModel->generateData($product));
                }
            } else {
                $parentIds = Mage::getModel('catalog/product_type_configurable')
                                 ->getParentIdsByChild($product->getId());
                if (!empty($parentIds)) {
                    foreach ($parentIds as $parentId) {
                        /** @var Mage_Catalog_Model_Product $parentProduct */
                        $parentProduct = Mage::getModel("catalog/product")
                                             ->setStoreId($this->_getConfig()->getStoreViewId())
                                             ->load($parentId);
                        // add config child
                        $this->addItem($productExportModel->generateData($product, $parentProduct));
                    }
                }
                if ($product->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
                    // add simple product
                    $this->addItem($productExportModel->generateData($product));
                }
            }
        }

        $end      = microtime();
        $duration = $end - $start;

        $this->log("[*] Export duration {$duration} seconds", ShopgateLogger::LOGTYPE_DEBUG);
        $this->log("[*] Export End...", ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * returns the products to export
     *
     * @param bool $xml
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    protected function _getExportProduct($xml = false, $limit = null, $offset = null)
    {
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('id');
        if (!Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_IS_EXPORT_STORES)) {
            $collection->addStoreFilter($this->_getConfig()->getStoreViewId());
        }

        if ($xml) {
            $collection->addAttributeToFilter(
                       'visibility',
                       array('nin' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
            );
        }

        $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $collection->addAttributeToFilter(
                   'type_id',
                   array(
                       "in" => $this->_getConfig()->getExportProductTypes()
                   )
        );

        $ids = $collection->getAllIds();
        if (!is_null($limit) && !is_null($offset)) {
            $ids = $collection->getAllIds($limit, $offset);
            $this->log("Limit: " . $this->exportLimit, ShopgateLogger::LOGTYPE_ACCESS);
            $this->log("[*] Limit: {$this->exportLimit}", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("Offset: " . $this->exportOffset, ShopgateLogger::LOGTYPE_ACCESS);
            $this->log("[*] Offset: {$this->exportOffset}", ShopgateLogger::LOGTYPE_DEBUG);
        }

        return $ids;
    }

    /**
     * build product row
     *
     * @param      $product
     * @param null $parentItem
     *
     * @return Varien_Object
     */
    protected function _buildProductRow($product, $parentItem = null)
    {
        $item = $this->buildDefaultItemRow();
        $item = $this->executeLoaders($this->getCreateItemsCsvLoaders(), $item, $product, $parentItem);

        return $item;
    }

    /**
     * prepared for xml structure
     *
     * @param  $product
     *
     * @return mixed
     */
    protected function _buildProductItem($product)
    {
        $exportModel = Mage::getModel('shopgate/export_product_xml');
        return $exportModel->setItem($product)->generateData();
    }

    /**
     * Returns default row for item export csv.
     *
     * @return array
     */
    protected function buildDefaultItemRow()
    {
        $row                       = parent::buildDefaultItemRow();
        $row['related_shop_items'] = '';
        return $row;
    }

    /**
     * @param int   $limit
     * @param int   $offset
     * @param array $uids
     */
    protected function createItems($limit = null, $offset = null, array $uids = null)
    {
        $this->log("Export start...", ShopgateLogger::LOGTYPE_ACCESS);
        $this->log("[*] Export Start...", ShopgateLogger::LOGTYPE_DEBUG);

        $this->log(
             'number of options to be exported: ' . $this->getDefaultItemRowOptionCount(),
             ShopgateLogger::LOGTYPE_DEBUG
        );

        $start      = microtime();
        $productIds = $uids ? $uids : $this->_getExportProduct(true, $limit, $offset);

        $i = 1;

        foreach ($productIds as $productId) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')->setStoreId($this->_getConfig()->getStoreViewId())
                           ->load($productId);
            $this->log("#{$i}", ShopgateLogger::LOGTYPE_DEBUG);
            $i++;

            $memoryUsage     = memory_get_usage(true);
            $memoryUsage     = round(($memoryUsage / 1024 / 1024), 2);
            $memoryPeekUsage = memory_get_peak_usage(true);
            $memoryPeekUsage = round(($memoryPeekUsage / 1024 / 1024), 2);

            $this->log(
                 "[{$product->getId()}] Start Load Product with ID: {$product->getId()}",
                 ShopgateLogger::LOGTYPE_DEBUG
            );
            $this->log("Memory usage: {$memoryUsage} MB", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("Memory peek usage: {$memoryPeekUsage} MB", ShopgateLogger::LOGTYPE_DEBUG);

            $this->log(
                 "[{$product->getId()}] Product-Data:\n" . print_r(
                     array(
                         "id"   => $product->getId(),
                         "name" => $product->getName(),
                         "sku"  => $product->getSku(),
                         "type" => $product->getTypeId(),
                     ),
                     true
                 ),
                 ShopgateLogger::LOGTYPE_DEBUG
            );

            if ($product->isSuper()) {
                $this->addItem($this->_buildProductItem($product));
            } else {
                $parentIds = Mage::getModel('catalog/product_type_configurable')
                                 ->getParentIdsByChild($product->getId());
                if (!empty($parentIds)) {
                    foreach ($parentIds as $parentId) {
                        /** @var Mage_Catalog_Model_Product $parentProduct */
                        $parentProduct = Mage::getModel("catalog/product")
                                             ->setStoreId($this->_getConfig()->getStoreViewId())
                                             ->load($parentId);
                        // add config child
                        $this->addItem($this->_buildProductItem($product, $parentProduct));
                    }
                }
                if ($product->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
                    // add simple product
                    $this->addItem($this->_buildProductItem($product));
                }
            }
        }

        $end      = microtime();
        $duration = $end - $start;

        $this->log("[*] Export duration {$duration} seconds", ShopgateLogger::LOGTYPE_DEBUG);
        $this->log("[*] Export End...", ShopgateLogger::LOGTYPE_DEBUG);
    }

    /** ============================================ ITEM EXPORT END ================================================ */
    /** ============================================ ITEM EXPORT END ================================================ */
    /** ============================================ ITEM EXPORT END ================================================ */

    /** ============================================ REVIEW EXPORT ================================================== */
    /** ============================================ REVIEW EXPORT ================================================== */
    /** ============================================ REVIEW EXPORT ================================================== */

    /**
     * Loads the product reviews of the shop system's database and passes them to the buffer.
     * Use ShopgatePlugin::buildDefaultReviewRow() to get the correct indices for the field names in a Shopgate reviews csv and
     * use ShopgatePlugin::addReviewRow() to add it to the output buffer.
     *
     * @see http://wiki.shopgate.com/CSV_File_Reviews
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_get_reviews_csv
     * @throws ShopgateLibraryException
     */
    protected function createReviewsCsv()
    {
        $reviewExportModel = Mage::getModel('shopgate/export_review_csv');
        $reviewExportModel->setDefaultRow($this->buildDefaultReviewRow());
        foreach ($this->_getReviewCollection() as $review) {
            $this->addReviewRow($reviewExportModel->generateData($review));
        }
    }

    /**
     * xml review creation
     */
    protected function createReviews()
    {
        $reviewExportModel = Mage::getModel('shopgate/export_review_xml');
        foreach ($this->_getReviewCollection() as $review) {
            $this->addItem($reviewExportModel->setItem($review)->generateData());
        }
    }

    /**
     * @return array
     */
    protected function _getReviewCollection()
    {
        /** @var Mage_Review_Model_Resource_Review_Collection $reviewCollection */
        $reviewCollection = Mage::getModel('review/review')
                                ->getResourceCollection()
                                ->addStoreFilter($this->_getConfig()->getStoreViewId())
                                ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED);

        if ($this->splittedExport) {
            $reviewCollection
                ->getSelect()
                ->limit($this->exportLimit, $this->exportOffset);
        }

        return $reviewCollection
            ->addRateVotes()
            ->setDateOrder();
    }

    /** ========================================== REVIEW EXPORT END ================================================ */
    /** ========================================== REVIEW EXPORT END ================================================ */
    /** ========================================== REVIEW EXPORT END ================================================ */

    /** ========================================== SETTING EXPORT =================================================== */
    /** ========================================== SETTING EXPORT =================================================== */
    /** ========================================== SETTING EXPORT =================================================== */

    /**
     * Returns an array of certain settings of the shop. (Currently mainly tax settings.)
     *
     * @see                           http://wiki.shopgate.com/Shopgate_Plugin_API_get_settings#API_Response
     * @return array(
     *                                'tax' => Contains the tax settings as follows:
     *                                array(
     *                                'tax_classes_products' => A list of product tax class identifiers.</li>
     *                                'tax_classes_customers' => A list of customer tax classes.</li>
     *                                'tax_rates' => A list of tax rates.</li>
     *                                'tax_rules' => A list of tax rule containers.</li>
     *                                )
     *                                )
     * @throws ShopgateLibraryException on invalid log in data or hard errors like database failure.
     */
    public function getSettings()
    {
        $settings = array(
            "customer_groups"            => array(),
            "allowed_shipping_countries" => array(),
            "allowed_address_countries"  => array(),
            "tax"                        => array(
                "product_tax_classes"  => array(),
                "customer_tax_classes" => array(),
                "tax_rates"            => array(),
                "tax_rules"            => array(),
            )
        );

        $settingsExport = Mage::getModel('shopgate/export_settings')->setDefaultRow($settings);
        return $settingsExport->generateData();
    }

    /** ========================================= SETTING EXPORT END ================================================ */
    /** ========================================= SETTING EXPORT END ================================================ */
    /** ========================================= SETTING EXPORT END ================================================ */

    /** ========================================= HELPER METHODS START ===============================================*/
    /** ========================================= HELPER METHODS START ===============================================*/
    /** ========================================= HELPER METHODS START ===============================================*/
    /**
     * @return Shopgate_Framework_Helper_Export
     */
    protected function _getExportHelper()
    {
        return Mage::helper('shopgate/export');
    }

    /**
     * @return Shopgate_Framework_Helper_Customer
     */
    protected function _getCustomerHelper()
    {
        return Mage::helper('shopgate/customer');
    }

    /**
     * @return Shopgate_Framework_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('shopgate');
    }

    /**
     * @return Shopgate_Framework_Helper_Sales
     */
    protected function _getSalesHelper()
    {
        return Mage::helper('shopgate/sales');
    }

    /**
     * @return Shopgate_Framework_Helper_Config
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('shopgate/config');
    }

    /**
     * @return Shopgate_Framework_Helper_Data
     */
    protected function _getDataHelper()
    {
        return Mage::helper('shopgate/data');
    }

    /**
     * @return null|Shopgate_Framework_Model_Export_Product
     */
    protected function _getExportProductInstance()
    {
        if (!$this->_exportProductInstance) {
            $this->_exportProductInstance = Mage::getModel('shopgate/export_product');
        }
        return $this->_exportProductInstance;
    }

    /** ========================================== HELPER METHODS END ================================================*/
    /** ========================================== HELPER METHODS END ================================================*/
    /** ========================================== HELPER METHODS END ================================================*/

    /** ========================================== GENERAL STUFF START ===============================================*/
    /** ========================================== GENERAL STUFF START ===============================================*/
    /** ========================================== GENERAL STUFF START ===============================================*/

    /**
     * return info for API request to get current state of config values
     *
     * @return array|mixed[]
     */
    public function createPluginInfo()
    {
        $moduleInfo = array(
            'Magento-Version' => Mage::getVersion(),
            'Magento-Edition' => $this->_getConfigHelper()->getEdition(),
            'Magento-StoreId' => Mage::app()->getStore()->getId()
        );

        return $moduleInfo;
    }

    /**
     * get additional data from the magento instance
     *
     * @return array|mixed[]
     */
    public function createShopInfo()
    {
        $shopInfo         = parent::createShopInfo();
        $entitiesCount    = $this->_getDataHelper()->getEntitiesCount($this->config->getStoreViewId());
        $pluginsInstalled = array('plugins_installed' => $this->_getDataHelper()->getThirdPartyModules());

        return array_merge($shopInfo, $entitiesCount, $pluginsInstalled);
    }

    /**
     * get debug info
     *
     * @return array|mixed[]
     */
    public function getDebugInfo()
    {
        return Mage::helper("shopgate/debug")->getInfo();
    }

    /** ========================================== GENERAL STUFF END =================================================*/
    /** ========================================== GENERAL STUFF END =================================================*/
    /** ========================================== GENERAL STUFF END =================================================*/

    /**
     * create pages csv
     */
    protected function createPagesCsv()
    {
        // TODO: Implement createPagesCsv() method.
    }

    /**
     * Loads the Media file information to the products of the shop system's database and passes them to the buffer.
     *
     * Use ShopgatePlugin::buildDefaultMediaRow() to get the correct indices for the field names in a Shopgate media csv and
     * use ShopgatePlugin::addMediaRow() to add it to the output buffer.
     *
     * @see http://wiki.shopgate.com/CSV_File_Media#Sample_Media_CSV_file
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_get_media_csv
     *
     * @throws ShopgateLibraryException
     */
    protected function createMediaCsv()
    {
        // TODO: Implement createMediaCsv() method.
    }

    /**
     * Exports orders from the shop system's database to Shopgate.
     *
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_get_orders
     *
     * @param string $customerToken
     * @param string $customerLanguage
     * @param int    $limit
     * @param int    $offset
     * @param string $orderDateFrom
     * @param string $sortOrder
     *
     * @return ShopgateExternalOrder[] A list of ShopgateExternalOrder objects
     *
     * @throws ShopgateLibraryException
     */
    public function getOrders($customerToken, $customerLanguage, $limit = 10, $offset = 0, $orderDateFrom = '', $sortOrder = 'created_desc')
    {
        return Mage::getModel('shopgate/export_customer_orders')
                   ->getOrders($customerToken, $limit, $offset, $orderDateFrom, $sortOrder);
    }

    /**
     * Updates and returns synchronization information for the favourite list of a customer.
     *
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_sync_favourite_list
     *
     * @param string             $customerToken
     * @param ShopgateSyncItem[] $items A list of ShopgateSyncItem objects that need to be synchronized
     *
     * @return ShopgateSyncItem[] The updated list of ShopgateSyncItem objects
     */
    public function syncFavouriteList($customerToken, $items)
    {
        // TODO: Implement syncFavouriteList() method.
    }
}
