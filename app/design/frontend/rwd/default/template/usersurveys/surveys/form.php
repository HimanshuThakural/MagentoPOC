<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 * @category    design
 * @package     rwd_default
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
?>
<div id="messages_product_view"><?php echo $this->getMessagesBlock()->getGroupedHtml() ?></div>
<div class="page-title">
    <h1><?php echo Mage::helper('contacts')->__('Surveys') ?></h1>
</div>
<form action="<?php echo $this->getFormAction(); ?>" id="contactForm" method="post" class="scaffold-form">
    <div class="fieldset">
        <h2 class="legend"><?php echo Mage::helper('contacts')->__('Contact Information') ?></h2>
        <p class="required"><?php echo Mage::helper('contacts')->__('* Required Fields') ?></p>
        <ul class="form-list">
            <li class="fields">
                <div class="field">
                    <label for="name" class="required"><em>*</em><?php echo Mage::helper('contacts')->__('Name') ?></label>
                    <div class="input-box">
                        <input name="name" id="name" title="<?php echo Mage::helper('contacts')->__('Name') ?>" value="<?php echo $this->escapeHtml($this->helper('contacts')->getUserName()) ?>" class="input-text required-entry" type="text" />
                    </div>
                </div>
                <div class="field">
                    <label for="email" class="required"><em>*</em><?php echo Mage::helper('contacts')->__('Email') ?></label>
                    <div class="input-box">
                        <input name="email" id="email" title="<?php echo Mage::helper('contacts')->__('Email') ?>" value="<?php echo $this->escapeHtml($this->helper('contacts')->getUserEmail()) ?>" class="input-text required-entry validate-email" type="email" autocapitalize="off" autocorrect="off" spellcheck="false" />
                    </div>
                </div>
            </li>
            <li>
                <label for="telephone"><?php echo Mage::helper('contacts')->__('Telephone') ?></label>
                <div class="input-box">
                    <input name="telephone" id="telephone" title="<?php echo Mage::helper('contacts')->__('Telephone') ?>" value="" class="input-text" type="tel" />
                </div>
            </li>
            <li class="wide">
                <label for="comment" class="required"><em>*</em><?php echo Mage::helper('contacts')->__('Comment') ?></label>
                <div class="input-box">
                    <textarea name="comment" id="comment" title="<?php echo Mage::helper('contacts')->__('Comment') ?>" class="required-entry input-text" cols="5" rows="3"></textarea>
                </div>
            </li>
        </ul>
    </div>
    <div class="buttons-set">
        <input type="text" name="hideit" id="hideit" value="" style="display:none !important;" />
        <button type="submit" title="<?php echo Mage::helper('contacts')->__('Submit') ?>" class="button"><span><span><?php echo Mage::helper('contacts')->__('Submit') ?></span></span></button>
    </div>
</form>
<script type="text/javascript">
//<![CDATA[
    var contactForm = new VarienForm('contactForm', true);
//]]>
</script> ?>
