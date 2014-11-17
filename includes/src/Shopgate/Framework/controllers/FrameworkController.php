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
 * Date: 23.01.14
 * Time: 00:01
 * E-Mail: p.liebig@me.com
 */

/**
 * entry point for api requests
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
include_once Mage::getBaseDir("lib") . '/Shopgate/shopgate.php';

class Shopgate_Framework_FrameworkController extends Mage_Core_Controller_Front_Action
{
    /**
     * load the module and do api-request
     */
    public function preDispatch()
    {
        if (Mage::app()->getRequest()->getActionName() == 'receive_authorization') {
            Mage::app()->getRequest()->setParam('action', 'receive_authorization');
            Mage::app()->getStore()->setConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE, 1);
        }

        $this->_run();
    }

    /**
     * placeholder action, needs to be defined for router
     */
    public function receive_authorizationAction()
    {
        $this->_run();
    }

    /**
     * index action -> call run
     */
    public function indexAction()
    {
        $this->_run();
    }

    /**
     * run
     */
    protected function _run()
    {

        try {
            Mage::app()->loadArea("adminhtml");
            Mage::app()->getTranslator()->init("adminhtml", true);
            define("_SHOPGATE_API", true);
            define("_SHOPGATE_ACTION", Mage::app()->getRequest()->getParam("action"));

            define("SHOPGATE_PLUGIN_VERSION", Mage::helper("shopgate")->getModuleVersion());

            $config = Mage::helper("shopgate/config")->getConfig();

            $useUsaModul = $config->getNetMarketCountries();
            $country     = Mage::getStoreConfig("tax/defaults/country", $config->getStoreViewId());

            if (!Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE)) {
                throw new ShopgateLibraryException(ShopgateLibraryException::CONFIG_PLUGIN_NOT_ACTIVE, 'plugin not active', true);
            }

            $builder = new ShopgateBuilder($config);
            if (in_array($country, $useUsaModul)) {
                $plugin = Mage::getModel('shopgate/shopgate_plugin_usa', $builder);
            } else {
                $plugin = Mage::getModel('shopgate/shopgate_plugin', $builder);
            }
            $plugin->handleRequest(Mage::app()->getRequest()->getParams());
        } catch (ShopgateLibraryException $e) {
            $response = new ShopgatePluginApiResponseAppJson(
                (isset($_REQUEST["trace_id"]) ? $_REQUEST["trace_id"] : ""));
            $response->markError($e->getCode(), $e->getMessage());
            $response->setData(array());
            $response->send();
        } catch (Exception $e) {
            Mage::logException($e);
            echo "ERROR";
        }

        exit;
    }
}
