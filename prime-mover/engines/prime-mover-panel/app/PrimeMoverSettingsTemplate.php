<?php
namespace Codexonics\PrimeMoverFramework\app;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Codexonics\PrimeMoverFramework\classes\PrimeMover;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsMarkups;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover Settings Template
 *
 */
class PrimeMoverSettingsTemplate
{       
    private $prime_mover;
    private $freemius_integration;
    private $settings_markups;
    private $settings;
    private $settings_config;
    private $utilities;
    
    /**
     * Constructor
     * @param PrimeMover $prime_mover
     * @param array $utilities
     * @param PrimeMoverSettingsMarkups $settings_markup
     * @param PrimeMoverSettings $prime_mover_settings
     * @param PrimeMoverSettingsConfig $prime_mover_settings_config
     */
    public function __construct(PrimeMover $prime_mover, array $utilities, PrimeMoverSettingsMarkups $settings_markup, PrimeMoverSettings $prime_mover_settings, PrimeMoverSettingsConfig $prime_mover_settings_config)
    {
        $this->prime_mover = $prime_mover;
        $this->freemius_integration = $utilities['freemius_integration'];
        $this->settings_markups = $settings_markup;        
        $this->settings = $prime_mover_settings;
        $this->settings_config = $prime_mover_settings_config;
        $this->utilities = $utilities;
    }
    
    /**
     * Get utilities
     * @return array
     */
    public function getUtilities()
    {
        return $this->utilities;
    }

    /**
     * Get Prime Mover
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     */
    public function getPrimeMover()
    {
        return $this->prime_mover;
    }
    
    /**
     * Get Freemius integration
     * @return array
     */
    public function getFreemiusIntegration()
    {
        return $this->freemius_integration;
    }
    
    /**
     * Get settings markups
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsMarkups
     */
    public function getSettingsMarkups()
    {
        return $this->settings_markups;
    }
    
    /**
     * Get Prime Mover settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettings
     */
    public function getPrimeMoverSettings()
    {
        return $this->settings;
    }
    
    /**
     * Get settings config
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsConfig
     */
    public function getSettingsConfig()
    {
        return $this->settings_config;
    }    
    
    /**
     * Output element identifier
     * @param array $config
     * @param string $identifier
     * @return string
     */
    protected function outputElementIdentifier($config = [], $identifier = '')
    {
        if (!isset($config[$identifier])) {
            return '';
        }
        
        return substr($config[$identifier], 1);        
    }
    
    /**
     * Render select form template
     * @param string $heading_text
     * @param string $identifier
     * @param array $config
     * @param string $compare_value
     * @param array $select_specs
     * @param string $description
     * @param number $blog_id
     * @param boolean $pro
     * @param array $button_specs
     * @param string $placeholder_text
     * @param boolean $cron_sched
     */
    public function renderSelectFormTemplate($heading_text = '', $identifier = '', $config = [], $compare_value = '',
        $select_specs = [], $description = '', $blog_id = 0, $pro = false, $button_specs = [], $placeholder_text = '', $cron_sched = false)
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        ?>
        <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">                
                <?php $this->renderLabelHeading($identifier, $heading_text, $config); ?>
            </th>
            <td>                             
                <p class="description">                     
                    <select name="pm_<?php echo esc_attr($config['ajax_key']); ?>" id="<?php echo esc_attr($this->outputElementIdentifier($config, 'data_selector')); ?>">
                        <option disabled selected value> -- <?php echo esc_html($placeholder_text); ?> -- </option>                        
                        <?php 
                        foreach ($select_specs as $select_key => $select_value) {
                        ?>                            
                        <option <?php selected($this->getPrimeMoverSettings()->getSetting($config['ajax_key'], false, '', false, $blog_id, $cron_sched), $select_key); ?> 
                          value="<?php echo esc_attr($select_key); ?>"><?php echo esc_html($select_value); ?></option>                                               
                        <?php 
                        }
                      ?>                        
                    </select> 
                </p> 
                
                <div class="prime-mover-setting-description">
                    <p class="description prime-mover-settings-paragraph">
         <?php 
             echo $description;
         ?>
                </p>
         <?php 
         $this->renderTemplateEnd($button_specs, $config, $pro, $blog_id);
    }
    
    /**
     * Render radio form template
     * @param string $heading_text
     * @param string $identifier
     * @param array $config
     * @param string $compare_value
     * @param array $radio_specs
     * @param string $description
     * @param number $blog_id
     * @param boolean $pro
     * @param array $button_specs
     */
    public function renderRadioFormTemplate($heading_text = '', $identifier = '', $config = [], $compare_value = '', 
        $radio_specs = [], $description = '', $blog_id = 0, $pro = false, $button_specs = [])
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        ?>
        <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">                
                <?php $this->renderLabelHeading($identifier, $heading_text, $config); ?>
            </th>
            <td>                             
                <p class="description">                
                      <?php 
                      foreach ($radio_specs as $radio_key => $radio_value) {
                      ?>                          
                      <label for="js-<?php echo esc_attr($radio_key); ?>">
                          <input <?php checked($this->getPrimeMoverSettings()->getSetting($config['ajax_key'], false, $config['default_value'], $config['return_default_if_no_key'], 
                              $blog_id), $radio_key); ?> type="radio" id="js-<?php echo esc_attr($radio_key); ?>" class="<?php echo esc_attr($this->outputElementIdentifier($config, 'data_selector')); ?>" 
                              name="pm_<?php echo esc_attr($config['ajax_key']); ?>" value="<?php echo esc_attr($radio_key); ?>">
                          <?php echo esc_html($radio_value); ?>
                      </label><br>                          
                      <?php 
                      }
                      ?>
                </p> 
                
                <div class="prime-mover-setting-description">
                    <p class="description prime-mover-settings-paragraph">
         <?php 
             echo $description;
         ?>
               </p>
         <?php 
         $this->renderTemplateEnd($button_specs, $config, $pro, $blog_id);
    }
    
    /**
     * Render checkbox form template
     * @param string $heading_text
     * @param string $identifier
     * @param array $config
     * @param string $compare_value
     * @param string $checkbox_label
     * @param string $description
     * @param number $blog_id
     * @param boolean $pro
     * @param array $button_specs
     * @param array $checkbox_specs
     */
    public function renderCheckBoxFormTemplate($heading_text = '', $identifier = '', $config = [], $compare_value = 'true', $checkbox_label = '', $description = '', 
        $blog_id = 0, $pro = false, $button_specs = [], $checkbox_specs = [])
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $default = '';
        if (isset($checkbox_specs['default'])) {
            $default = $checkbox_specs['default'];
        }
       
        $return_default_if_no_key = false;
        if (isset($checkbox_specs['return_default_if_no_key'])) {
            $return_default_if_no_key = $checkbox_specs['return_default_if_no_key'];
        }
    ?>
        <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">                
                <?php $this->renderLabelHeading($identifier, $heading_text, $config, $pro); ?>
            </th>
            <td>                             
                <p class="description">                
                   <label for="<?php echo esc_attr($this->outputElementIdentifier($config, 'data_selector')); ?>">                   
                   <input <?php checked($this->getPrimeMoverSettings()->getSetting($config['ajax_key'], false, $default , $return_default_if_no_key, $blog_id), $compare_value); ?> type="checkbox"
                      id="<?php echo esc_attr($this->outputElementIdentifier($config, 'data_selector')); ?>" autocomplete="off" name="pm_<?php echo esc_attr($config['ajax_key']); ?>" 
                      class="pm_class_<?php echo esc_attr($config['ajax_key']); ?>_checkbox" value="yes"> 
                   <?php echo esc_html($checkbox_label); ?>                                  
                   </label>
                </p> 
                
                <div class="prime-mover-setting-description">
                    <p class="description prime-mover-settings-paragraph">
         <?php 
             echo $description;
         ?>
                </p>
         <?php 
         $this->renderTemplateEnd($button_specs, $config, $pro, $blog_id);
    }
    
    /**
     * Render button form template meant for downloading logs or assets
     * @param string $heading_text
     * @param array $config
     * @param string $description
     * @param number $blog_id
     */
    public function renderButtonFormTemplate($heading_text = '', $config = [], $description = '', $blog_id = 0)
    {
        $this->getPrimeMoverSettings()->getSettingsMarkup()->startMarkup($heading_text);
        if (empty($config['nonce']) || empty($config['ajax_action']) || empty($config['ajax_key']) || empty($config['description'])) {
            return;    
        }        
        
        $nonce = $config['nonce'];
        $ajax_action = $config['ajax_action'];
        $ajax_key = $config['ajax_key'];
        $button_description = $config['description'];        
        ?>
        <p class="description prime-mover-settings-paragraph">
            <a class="button-primary" 
            href="<?php echo $this->getPrimeMoverSettings()->getSettingsMarkup()->generateDownloadLogUrl($ajax_action, $nonce, $ajax_key, $blog_id);?>">
            <?php echo esc_html($button_description);?></a>
        </p>
        
         <p class="description prime-mover-settings-paragraph">
          <?php echo $description; ?>
        </p> 
    <?php   
         $this->getPrimeMoverSettings()->getSettingsMarkup()->endMarkup();       
    }
      
    /**
     * Render button form confirmation template
     * @param string $heading_text
     * @param string $identifier
     * @param array $config
     * @param string $description
     * @param number $blog_id
     * @param boolean $pro
     * @param array $button_specs
     * @param string $dialog_message
     * @param string $dialog_heading
     */
    public function renderButtonFormConfirmTemplate($heading_text = '', $identifier = '', $config = [], $description = '', $blog_id = 0, $pro = false, $button_specs = [], $dialog_message = '', $dialog_heading = '')
    {       
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        ?>
        <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">                
                <?php $this->renderLabelHeading($identifier, $heading_text, $config); ?>
            </th>
            <td>                                             
                <div class="prime-mover-setting-description">
                    <p class="description prime-mover-settings-paragraph">
                        <?php 
                            echo $description;
                         ?>
                    </p>
         <?php 
         $this->renderTemplateEnd($button_specs, $config, $pro, $blog_id, $dialog_message, $dialog_heading);         
    }   
    
    /**
     * Render label heading
     * @param string $identifier
     * @param string $heading_text
     * @param array $config
     * @param boolean $pro
     */
    private function renderLabelHeading($identifier = '', $heading_text = '', $config = [], $pro = false)
    {
        $required_label = false;
        if (isset($config['show_as_required'])) {
            $required_label = $config['show_as_required'];
        }
        if ($required_label) {        
    ?>
            <label class="prime_mover_required_label" id="prime-mover-<?php echo esc_attr($identifier); ?>-label"><?php echo esc_html($heading_text); ?></label>
    <?php 
        } else {
    ?>
            <label id="prime-mover-<?php echo esc_attr($identifier); ?>-label"><?php echo esc_html($heading_text); ?></label>
    <?php 
        }
        if ($pro && !empty($config['documentation'])) {
            do_action('prime_mover_last_table_heading_settings', $config['documentation']);
        }
    }
    
    /**
     * Render template end
     * @param array $button_specs
     * @param array $config
     * @param boolean $pro
     * @param number $blog_id
     * @param string $dialog_message
     * @param string $dialog_heading
     */
    private function renderTemplateEnd($button_specs = [], $config = [], $pro = false, $blog_id = 0, $dialog_message = '', $dialog_heading = '')
    {
        $button_wrapper = 'div';
        if (isset($button_specs['button_wrapper'])) {
            $button_wrapper = $button_specs['button_wrapper'];
        }
        
        $button_class = 'button-primary';
        if (isset($button_specs['button_classes'])) {
            $button_class = $button_specs['button_classes'];
        }
        
        $button_text = '';
        if (isset($button_specs['button_text'])) {
            $button_text = $button_specs['button_text'];
        }
        
        $disabled = '';
        if (isset($button_specs['disabled'])) {
            $disabled = $button_specs['disabled'];
        }
        
        $title = '';
        if (isset($button_specs['title'])) {
            $title = $button_specs['title'];
        }
        
        $button_selector = $this->outputElementIdentifier($config, 'button_selector');
        $spinner_selector = $this->outputElementIdentifier($config, 'spinner_selector');
        $require_dialog = false;
        if (isset($config['dialog']) && true === $config['dialog']) {
            $require_dialog = true;
        }
        $this->getPrimeMoverSettings()->getSettingsMarkup()->renderSubmitButton(
            "{$config['nonce']}",
            "{$button_selector}",
            "{$spinner_selector}",
            $button_wrapper,
            $button_class,
            $button_text,
            $disabled,
            $title,
            $pro,
            $blog_id
        );
        
        $this->getPrimeMoverSettings()->getSettingsMarkup()->endMarkup();        
        if ($require_dialog) {
            echo $this->renderDialogMarkup($config, $dialog_message, $dialog_heading);
        }        
    }
    
    /**
     * Render dialog markup
     * @param array $config
     * @param string $dialog_message
     * @param string $dialog_heading
     */
    private function renderDialogMarkup($config = [], $dialog_message = '', $dialog_heading = '')
    {
        $dialog_selector = '';
        if (empty($config['dialog_selector'])) {
            return;    
        }
        
        $dialog_selector = $this->outputElementIdentifier($config, 'dialog_selector');
        ?>
        <div style="display:none;" id="<?php echo esc_attr($dialog_selector); ?>" title="<?php echo esc_attr($dialog_heading); ?>"> 
			<p><?php echo $dialog_message; ?></p>	      	  	
        </div>
    <?php
    }  
}
