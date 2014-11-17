<?php
/**
 * GIKO / LUOCHUNHUI.COM
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to LUOCHUNHUI/GIKO (OSL 3.0).
 * License, which extends the Open Software License (OSL 3.0).
 * The GIKO Extension License is available at this URL: 
 * http://magento.luochunhui.com/cms/magento_extension_license.html
 * The Open Software License is available at this URL: 
 * http://opensource.org/licenses/osl-3.0.php
 * 
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, GIKO is 
 * not held liable for any inconsistencies or abnormalities in the 
 * behaviour of this code. 
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by GIKO, outlined in the 
 * provided GIKO Extension License. 
 * Upon discovery of modified code in the process of support, the Licensee 
 * is still held accountable for any and all billable time GIKO spent 
 * during the support process.
 * GIKO does not guarantee compatibility with any other framework extension. 
 * GIKO is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to 
 * wo@luochunhui.com, so we can send you a copy immediately.
 *
 * @category    Magemall
 * @copyright   Copyright (c) 2012 Luoc Chunhui<wo@luochunhui.com>. (http://www.luochunhui.com)
 * @license     http://magento.luochunhui.com/docs/index/copyright  Open Software License (OSL 3.0)
 * @author      Luo Chunhui <wo@luochunhui.com>
 */

/**
 * Magemall Ajaxlogin JS Block.
 */
class User_Surveys_Block_Js extends Mage_Core_Block_Text
{
    /**
     * Prepare and return block's html output
     *
     * @return string
     */
    protected function _toHtml()
    {
    	if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                return '';
        }
        
        $jsTiny = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . 'magemall/tinybox.js';
		$jsMain = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . 'magemall/ajaxlogin.js';
		$cssMain = $this->getSkinUrl('css/ajaxlogin.css'); 
		
		$url = $this->getUrl('index/featuredSurvey/id/<?php echo $featuredFormId ;?>');
        $html = <<<HTML
<!-- AjaxLogin -->
<link rel="stylesheet" type="text/css" href="{$cssMain}" media="all" />
<script type="text/javascript" src="{$jsTiny}"></script>
<script type="text/javascript" src="{$jsMain}"></script>
<script type="text/javascript">
var G_AJAXLOGIN_URL = "{$url}";
$$("a[href*=index/featuredSurvey/id/<?php echo $featuredFormId ;?>]").each(function(item) {
	item.href='javascript:void(0)';
	Event.observe(item, 'click', showloginbox);
});
</script>
<!-- End Giko AjaxLogin Code -->
<!-- Get free from http://magento.luochunhui.com/ajaxlogin.html -->
HTML;
        $this->addText($html);

        return parent::_toHtml();
    }
}