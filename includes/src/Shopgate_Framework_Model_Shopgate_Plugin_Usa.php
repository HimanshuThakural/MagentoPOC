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
 * Date: 16.01.14
 * Time: 16:05
 * E-Mail: p.liebig@me.com
 */

/**
 * plugin model usa to adjust special stuff according to pricing
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */

class Shopgate_Framework_Model_Shopgate_Plugin_Usa extends Shopgate_Framework_Model_Shopgate_Plugin
{
    /**
     * 3rd param Mage_Catalog_Model_Product $parentItem default null
     *
     * @param array                      $item
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Catalog_Model_Product $parentItem
     * @return array
     */
    protected function itemExportOptions($item, $product, $parentItem)
    {
        if (!empty($parentItem) && $parentItem->isConfigurable()) {
            $product = $parentItem;
        }

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
            || !empty($parentItem) && $parentItem->isConfigurable()
            || $product->isConfigurable()) {
            $options = $product->getOptions();

            $num_inputs  = 1;
            $num_options = 1;
            $maxOptions  = $this->getDefaultItemRowOptionCount();
            $maxInputs   = $this->getDefaultItemRowInputCount();

            foreach ($options as $option) {
                /* @var Mage_Catalog_Model_Product_Option $option  */

                if ($option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN
                    || $option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_RADIO
                    || $option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE
                    || $option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX
                ) {

                    if ($num_options > $maxOptions) {
                        $this->log(
                             'reached maximum number of options (' . $maxOptions . ')',
                             ShopgateLogger::LOGTYPE_DEBUG
                        );
                        break;
                    }

                    $_option       = $option->getId() . "=" . $option->getDefaultTitle();
                    $_option_value = array();

                    if (!$option->getIsRequire()) {
                        $_option_value[] = "0=" . Mage::helper('shopgate')->__("None");
                    }

                    $priceMultiplicator = $this->_getStackPriceMultiplicator($product->getStockItem());
                    foreach ($option->getValues() as $value) {
                        $price           = $value->getPrice() * 100 * $priceMultiplicator;
                        $price           = $this->_getExportHelper()->convertPriceCurrency($price);
                        $price           = intval($price);
                        $_option_value[] = $value->getId() . "=" . trim($value->getTitle()) . "=>" . $price;
                    }

                    $oInfos                           = $this->jsonDecode($item["internal_order_info"], true);
                    $oInfos["has_individual_options"] = true;

                    $item["has_options"]                  = "1";
                    $item["internal_order_info"]          = $this->jsonEncode($oInfos);
                    $item["option_{$num_options}"]        = $_option;
                    $item["option_{$num_options}_values"] = implode("||", $_option_value);

                    $num_options++;
                } else {
                    if ($option->getType() === Mage_Catalog_Model_Product_Option::OPTION_TYPE_FIELD
                        || $option->getType() === Mage_Catalog_Model_Product_Option::OPTION_TYPE_AREA
                    ) {

                        if ($num_inputs > $maxInputs) {
                            $this->log(
                                 'reached maximum number of inputs (' . $maxInputs . ')',
                                 ShopgateLogger::LOGTYPE_DEBUG
                            );
                            break;
                        }

                        $priceMultiplicator = $this->_getStackPriceMultiplicator($product->getStockItem());

                        $item["has_input_fields"]                     = "1";
                        $item["input_field_{$num_inputs}_number"]     = $option->getId();
                        $item["input_field_{$num_inputs}_type"]       = "text";
                        $item["input_field_{$num_inputs}_label"]      = $option->getDefaultTitle();
                        $item["input_field_{$num_inputs}_infotext"]   = "";
                        $item["input_field_{$num_inputs}_required"]   = $option->getIsRequire();
                        $item["input_field_{$num_inputs}_add_amount"] = $this->formatPriceNumber(
                                                                             $option->getPrice() * $priceMultiplicator,
                                                                             2
                        );
                        $num_inputs++;

                    }
                }
            }
        }

        return $item;
    }

    /**
     * @param ShopgateCartBase $order
     * @throws ShopgateLibraryException
     */
    protected function _getSimpleShopgateCoupons(ShopgateCartBase $order)
    {
        if ($order instanceof ShopgateOrder) {
            foreach ($order->getItems() as $item) {
                /** @var ShopgateOrderItem $item */
                if ($item->getUnitAmount() >= 0) {
                    continue;
                }

                $obj = new Varien_Object();
                $obj->setName($item->getName());
                $obj->setItemNumber($item->getItemNumber());
                $obj->setUnitAmountWithTax($item->getUnitAmount());

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
     * @return array
     */
    protected function buildDefaultItemRow()
    {
        $row = parent::buildDefaultItemRow();
        unset($row["unit_amount"]);
        unset($row["old_unit_amount"]);
        unset($row["tax_percent"]);
        $newFields = array(
            "tax_class"           => "",
            "unit_amount_net"     => "0",
            "old_unit_amount_net" => "",
        );

        $row = array_slice($row, 0, 3, true) +
               $newFields +
               array_slice($row, 3, count($row) - 3, true);
        return $row;
    }
}
