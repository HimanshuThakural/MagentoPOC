<?php
/**
 * @category Mxperts
 * @package Mxperts_jQuerytools
 * @authors TMEDIA cross communications <info@tmedia.de>, Johannes Teitge <teitge@tmedia.de>, Igor Jankovic <jankovic@tmedia.de>, Daniel Sasse <d.sasse1984@googlemail.com>
 * @developer Johannes Teitge <teitge@tmedia.de>  
 * @copyright TMEDIA cross communications, Doris Teitge-Seifert
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * 
 * Initial-Release V 1.1.0 - 7-18-2009 
 * Update to new version of jQuery-Tools V 1.1.1 - 9-23-2009  
 *
 *           
 */
class Mxperts_Jquery_Tools_Block_Page_Html_Head extends Mxperts_Jquery_Block_Page_Html_Head
{

    public function getjQueryToolsFilenames()
    {           
      $filenames = '';   
      
      if ( Mage::getStoreConfig('jquerytools/jquerytools/active') == 1) {
        if (Mage::getStoreConfig('jquerytools/jquerytools/jquerytools_minified') == 1) {
          $minified = '.min'; 
        } else {  
          $minified = '';
        }  
        
        if (Mage::getStoreConfig('jquerytools/jquerytools_tabs/tabs') == 1) {        
          $filenames[] = 'jquery/jquerytools/tools.tabs-1.0.4'.$minified.'.js';
          
          if (Mage::getStoreConfig('jquerytools/jquerytools_tabs/tabs_sliedshow') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.tabs.slideshow-1.0.2'.$minified.'.js';                
          }
          if (Mage::getStoreConfig('jquerytools/jquerytools_tabs/tabs_history') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.tabs.history-1.0.2'.$minified.'.js';                
          }
        }
        
        if (Mage::getStoreConfig('jquerytools/jquerytools_tooltip/tooltip') == 1) {        
          $filenames[] = 'jquery/jquerytools/tools.tooltip-1.1.2'.$minified.'.js';

          if (Mage::getStoreConfig('jquerytools/jquerytools_tooltip/tooltip_slide') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.tooltip.slide-1.0.0'.$minified.'.js';                
          }
          if (Mage::getStoreConfig('jquerytools/jquerytools_tooltip/tooltip_position') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.tooltip.dynamic-1.0.1'.$minified.'.js';                
          }
        }
        
        if (Mage::getStoreConfig('jquerytools/jquerytools_scrollable/scrollable') == 1) {        
          $filenames[] = 'jquery/jquerytools/tools.scrollable-1.1.2'.$minified.'.js';

          if (Mage::getStoreConfig('jquerytools/jquerytools_scrollable/scrollable_circular') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.scrollable.circular-0.5.1'.$minified.'.js';                
          }
          if (Mage::getStoreConfig('jquerytools/jquerytools_scrollable/scrollable_autoscroll') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.scrollable.autoscroll-1.0.1'.$minified.'.js';                
          }
          if (Mage::getStoreConfig('jquerytools/jquerytools_scrollable/scrollable_navigator') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.scrollable.navigator-1.0.2'.$minified.'.js';                
          }
          if (Mage::getStoreConfig('jquerytools/jquerytools_scrollable/scrollable_mouse') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.scrollable.mousewheel-1.0.1'.$minified.'.js';                
          }
        }
        
        if (Mage::getStoreConfig('jquerytools/jquerytools_overlay/overlay') == 1) {        
          $filenames[] = 'jquery/jquerytools/tools.overlay-1.1.2'.$minified.'.js';
          if (Mage::getStoreConfig('jquerytools/jquerytools_overlay/overlay_gallery') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.overlay.gallery-1.0.0'.$minified.'.js';                
          }
          if (Mage::getStoreConfig('jquerytools/jquerytools_overlay/overlay_apple') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.overlay.apple-1.0.1'.$minified.'.js';                
          }
          
                          
        }        
        
        if (Mage::getStoreConfig('jquerytools/jquerytools_expose/expose') == 1) {        
          $filenames[] = 'jquery/jquerytools/tools.expose-1.0.5'.$minified.'.js';                
        }        
                
        if ( Mage::getStoreConfig('jquerytools/flowplayer/flowplayer_active') == 0) {
          if (Mage::getStoreConfig('jquerytools/jquerytools_flashembed/flashembed') == 1) {        
            $filenames[] = 'jquery/jquerytools/tools.flashembed-1.0.4'.$minified.'.js';                
          }                
        }                
      }       
    
      return $filenames;    
    
    }
    
    public function getFlowplayerFilenames()
    {           
      $last_version = '-3.1.1';      
      $filenames = '';    
      
      if ( Mage::getStoreConfig('jquerytools/flowplayer/flowplayer_active') == 1) {
      
        if (Mage::getStoreConfig('jquerytools/flowplayer/flowplayer_minified') == 1) {
          $minified = '.min'; 
        } else {  
          $minified = '';
        }                  
        $filenames[] = 'jquery/flowplayer/flowplayer'.$last_version.$minified.'.js';
        
        if (Mage::getStoreConfig('jquerytools/flowplayer/flowplayer_controlbar') == 1) {        
          $filenames[] = 'jquery/flowplayer/flowplayer.controls-3.0.2'.$minified.'.js';                
        }
        
        if (Mage::getStoreConfig('jquerytools/flowplayer/flowplayer_embed') == 1) {        
          $filenames[] = 'jquery/flowplayer/flowplayer.embed-3.0.2'.$minified.'.js';                
        }
        
        if (Mage::getStoreConfig('jquerytools/flowplayer/flowplayer_playlist') == 1) {        
          $filenames[] = 'jquery/flowplayer/flowplayer.playlist-3.0.6'.$minified.'.js';                
        }
        
      }
      
      return $filenames;
    
    }
    

    public function getCssJsHtml()
    {
        if ( Mage::getStoreConfig('mxperts/jquerysettings/active') == 1) {

          // Jquery-Tools 
          $files = $this->getjQueryToolsFilenames();          
          if (is_array($files) && (count($files) > 0)) {            
            foreach($files as $file) {
              $this->additional_files[] = $file;
            }              
          }
        
          // Flow-Player        
          $files = $this->getFlowplayerFilenames();         
          if (is_array($files) && (count($files) > 0)) {            
            foreach($files as $file) {
              $this->additional_files[] = $file;
            }              
          }
                   
        } // .../active') == 1
        
        $html = parent::getCssJsHtml();
        return $html;
    }

}
