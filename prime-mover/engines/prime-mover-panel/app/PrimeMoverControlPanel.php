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
use Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization;
use Freemius;

if (! defined('ABSPATH')) {
    exit;
}
/**
 * Prime Mover Extension to handle core and extension settings.
 *
 */
class PrimeMoverControlPanel
{ 
    private $system_authorization;
    private $prime_mover;
    private $freemius_integration;
    private $system_utilities;
    private $component_aux;
    
    /**
     * Constructor
     * @param PrimeMover $PrimeMover
     * @param PrimeMoverSystemAuthorization $system_authorization
     * @param array $utilities
     */
    public function __construct(PrimeMover $PrimeMover, PrimeMoverSystemAuthorization $system_authorization, array $utilities) 
    {
        $this->prime_mover = $PrimeMover;
        $this->system_authorization = $system_authorization;
        $this->freemius_integration = $utilities['freemius_integration'];
        $this->system_utilities = $utilities['sys_utilities'];
        $this->component_aux = $utilities['component_utilities'];
    }
    
    /**
     * Get component auxiliary
     * @return array
     */
    public function getComponentAux()
    {
        return $this->component_aux;
    }
    
    /**
     * Get system utilities object
     */
    public function getSystemUtilities()
    {
        return $this->system_utilities;
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
     * Get progress handlers
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverProgressHandlers
     */
    public function getProgressHandlers()
    {
        return $this->getPrimeMover()->getImporter()->getProgressHandlers();
    }
    
    /**
     * Get Freemius
     * @return Freemius
     */
    public function getFreemius()
    {
        return $this->getPrimeMover()->getSystemAuthorization()->getFreemius();
    }
    
    /**
     * Init hooks
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverControlPanel::itAddsInitHooks()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverControlPanel::itChecksIfHooksAreOutdated() 
     */
    public function initHooks() 
    {
        add_action('admin_enqueue_scripts', [$this, 'controlPanelEnqueueScripts'], 10, 1);
        add_filter('prime_mover_filter_export_dialog_title', [$this, 'maybeFilterExportDialogTitle'], 10, 2);
        add_filter('prime_mover_filter_backuppage_heading', [$this, 'maybeFilterBackupHeadingTitle'], 10, 2);
        
        add_filter('prime_mover_standard_extensions', [$this, 'exludeControlPanelExtensionInPluginsExport'], 15, 1 );
        add_filter('prime_mover_filter_site_footprint', [$this, 'removeControlPanelToSystemFootprint' ], 30, 3);
        
        add_filter('prime_mover_filter_error_output', [$this, 'addControlPanelVersionToErrorLog'], 10, 1); 
        add_filter('prime_mover_get_core_components', [$this, 'addControlPanelToComponents'], 10, 1);
        
        add_action('prime_mover_run_menus', [$this, 'addMenuPage'] );
        add_filter('prime_mover_filter_restore_button_text', [$this, 'useProRestoreButtonText'], 10, 2);
        add_filter('prime_mover_filter_export_button_text', [$this, 'useProExportButtonText'], 10, 2);
        
        add_filter('prime_mover_filter_button_class', [$this, 'usePrimaryButtonClassPro'], 10, 2); 
        add_filter('prime_mover_filter_upgrade_pro_text', [$this, 'maybeUseUpgradePlanText'], 10, 3);
        add_filter('prime_mover_filter_package_manager_columns', [$this, 'maybeAddMigrateColumn'], 10, 2);   
        
        add_action('admin_post_prime-mover-load-scheduled-backup-settings', [$this, 'maybeSwitchBlogForScheduledBackupSettings']);
        add_filter('prime_mover_control_panel_js_object', [$this, 'settingsApiJsObject'], 10, 1);
        add_filter('prime_mover_filter_upgrade_pro_url', [$this, 'maybeUseCorrectUpgradeUrl'], 10, 2);
    }
    
    /**
     * Output settings parameter to JS Object
     * @param array $var
     * @return array
     */
    public function settingsApiJsObject($var = [])
    {
        if (!isset($var['prime_mover_settings_js_api'])) {            
            $var['prime_mover_settings_js_api'] = apply_filters('prime_mover_settings_js_api_extension', []);
        }
        
        return $var;
    }
    
    /**
     * Filter backup menu page title
     * @param string $title
     * @param number $blog_id
     * @return string
     */
    public function maybeFilterBackupHeadingTitle($title = '', $blog_id = 0)
    {
        if (!$blog_id) {
            return $title;
        }
        
        if (true === apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id)) {
            return esc_html__('Prime Mover PRO Package Manager', 'prime-mover');
        }
        
        return $title;
    }
    
    /**
     * Maybe filter export dialog title
     * @param string $title
     * @param number $blog_id
     * @return string
     */
    public function maybeFilterExportDialogTitle($title = '', $blog_id = 0)
    {
        if (!$blog_id) {
            return $title;
        }
        if (true === apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id)) {
            return esc_attr__('PRO Export Options', 'prime-mover');
        }
            
        return $title;
    }
    
    /**
     * Add migrate column in PRO versions
     * @param array $columns
     * @param number $blog_id
     * @return array
     */
    public function maybeAddMigrateColumn($columns = [], $blog_id = 0)
    {
        if (!$blog_id || !is_array($columns) || !isset($columns['download'])) {
            return $columns;
        }    
        
        if (true === $this->getComponentAux()->canSupportRestoreUrlInFreeMode()) {
            unset($columns['download']);
            $columns['migrate'] = esc_html__('Migrate site', 'prime-mover');
            $columns['download'] = esc_html__('Download package', 'prime-mover'); 
        }               
        
        return $columns;    
    }

    /**
     * Maybe use upgrade plan text
     * @param string $text
     * @param number $blog_id
     * @param boolean $show_icons
     * @return string|string|mixed
     */
    public function maybeUseUpgradePlanText($text = '', $blog_id = 0, $show_icons = true)
    {      
        if ($this->getPrimeMover()->getSystemInitialization()->isUsingFreeCode()) {
            return $text;
        }
        
        if ('yes' === $this->getFreemiusIntegration()->hasUsableLicense()) {
            $text = esc_html__('Activate PRO', 'prime-mover');            
        } 
        
        if ('no' === $this->getFreemiusIntegration()->hasUsableLicense()) {
            $text = esc_html__('UPGRADE Plan', 'prime-mover');
        }
        
        return $text;
    }

    /**
     * Use correct upgrade URL based on the site status
     * @param string $url
     * @param number $blog_id
     */
    public function maybeUseCorrectUpgradeUrl($url = '', $blog_id = 0)
    {        
        if ($this->getPrimeMover()->getSystemInitialization()->isUsingFreeCode()) {
            return $url;
        }
        
        if ('yes' === $this->getFreemiusIntegration()->hasUsableLicense() || $this->getFreemiusIntegration()->isWhiteLabeled()) {
            if (false === $this->getFreemius()->is_anonymous()) {
                $url = $this->getFreemius()->get_account_url();
            }            
        }
        
        return $url;
    }
    
    /**
     * Identify licensed subsites in multisite
     * @param string $class
     * @param number $blog_id
     * @return string
     */
    public function usePrimaryButtonClassPro($class = '', $blog_id = 0)
    {
        if (!$blog_id || !$class) {
            return $class;
        }
        if (!is_multisite()) {
            return $this->getPrimeMover()->getSystemInitialization()->defaultButtonClasses();
        }
        if (false === apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id)) {
            return 'button';
        }
        return 'button-primary';
    }
    
    /**
     * Use PRO export button text to identify which subsites are using active license
     * @param string $text
     * @param number $blog_id
     * @return string
     */
    public function useProExportButtonText($text = '', $blog_id = 0)
    {
        if (!$blog_id) {
            return $text;
        }
       
        if (false === apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id)) {
            return $text;
        }
     
        $text = esc_html__('PRO Export', 'prime-mover');
        return $text;
    }
    
    /**
     * Use PRO restore button text to identify which subsites are using active license
     * @param string $text
     * @param number $blog_id
     */
    public function useProRestoreButtonText($text = '', $blog_id = 0)
    {        
        if (!$blog_id) {
            return $text;
        }
        
        if (false === apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id)) {
            return $text;
        }
        
        $text = esc_html__('PRO Restore', 'prime-mover');
        return $text;
    }
    
    /**
     * Add control panel to components
     * @param array $corecomponents
     * @return array
     */
    public function addControlPanelToComponents($corecomponents = [])
    {
        $corecomponents[] = plugin_basename(PRIME_MOVER_PANEL_MAINPLUGIN_FILE);
        return $corecomponents;        
    }
    
    /**
     * Get system functions
     */
    public function getSystemFunctions()
    {
        return $this->getPrimeMover()->getSystemFunctions();
    }
    
    /**
     * Add Control Panel version to error log
     * @param array $error_output
     * @return array
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverControlPanel::itAddsControlPanelVersionToErrorLog()
     */
    public function addControlPanelVersionToErrorLog($error_output = []) {
        if ( ! is_array( $error_output ) || isset( $error_output['control_panel_version']) ) {
            return $error_output;
        }
        $error_output['control_panel_version'] = PRIME_MOVER_PANEL_VERSION;
        
        return $error_output;
    }
    
    /**
     * Exclude control panel plugin
     * @param array $plugin_file
     * @return array
     */
    public function exludeControlPanelExtensionInPluginsExport(array $plugin_file)
    {
        $plugin_file[] = 'prime-mover-panel.php';
        return $plugin_file;
    }
    
    /**
     * Remove control panel plugin from footprint
     * @param array $footprint
     * @param number $blog_id
     * @param array $ret
     * @return array
     * @mainsitesupport_affected
     */
    public function removeControlPanelToSystemFootprint(array $footprint, $blog_id = 0, $ret = [])
    {
        $export_target_id = 0;
        if ( ! empty($ret['prime_mover_export_targetid'] ) ) {
            $export_target_id = (int)$ret['prime_mover_export_targetid'];
        }
        
        if (!empty($ret['prime_mover_export_type']) && 'single-site' === $this->getSystemFunctions()->generalizeExportTypeBasedOnGiven($ret)) {
            return $footprint;
        } elseif (1 === $export_target_id) {
            return $footprint;
        }
        
        $plugin_basename = plugin_basename(PRIME_MOVER_PANEL_MAINPLUGIN_FILE);
        if (isset($footprint['plugins'][$plugin_basename])) {
            unset($footprint['plugins'][$plugin_basename]);
        }
        
        return $footprint;
    }   

    /**
     * Enqueue panel style and script
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverControlPanel::itEnqueuesControlPanelAssetsWhenAllSet() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverControlPanel::itDoesNotEnqueueAssetWhenNotOnSitesPage()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverControlPanel::itDoesNotEnqueueIconCSSIfNotNetworkAdmin() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverControlPanel::itEnqueuesNonMinifiedWhenOnScriptDebug()
     */
    public function controlPanelEnqueueScripts() 
    {
        if (! $this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
           return; 
        }
        $current_screen = get_current_screen();
        
        if ( ! $this->getSystemFunctions()->maybeLoadAssets($current_screen ) ) {
            return;
        }
        
        $min = '.min';
        if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) {
            $min = '';
        }
        $js = "prime-mover-panel$min.js";

        wp_enqueue_style(
            'prime_mover_css_textsecurity',
            esc_url_raw(plugins_url('res/css/text-security/text-security.css', dirname(__FILE__))),
            ['wp-jquery-ui-dialog'],
            PRIME_MOVER_PANEL_VERSION
            );
        
        wp_enqueue_style(
            'prime_mover_css_control_panel',
            esc_url_raw(plugins_url('res/css/prime-mover-panel.css', dirname(__FILE__))),
            ['wp-jquery-ui-dialog', 'prime_mover_css_textsecurity'],
            PRIME_MOVER_PANEL_VERSION
        );
        
        wp_enqueue_script(
            'prime_mover_js_control_panel',
            esc_url_raw(plugins_url('res/js/' . $js, dirname(__FILE__))),
            ['jquery', 'jquery-ui-core', 'jquery-ui-dialog'],
            PRIME_MOVER_PANEL_VERSION
        ); 
        
        $error_message = sprintf( esc_html__('Server error found ! Please enable %s to generate %s and try again.', 'prime-mover'), '<strong>WP_DEBUG</strong>', '<strong>debug.log</strong>' );
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            $error_message = sprintf( esc_html__('Server error found ! Please check WordPress %s for more details.', 'prime-mover'), '<strong>debug.log</strong>' );
        }
        $host_domain = $this->getSystemUtilities()->computeHostDomain();
        wp_localize_script(
            'prime_mover_js_control_panel',
            'prime_mover_control_panel_renderer',
            apply_filters('prime_mover_control_panel_js_object', [
                'ajaxurl' => esc_url_raw(admin_url('admin-ajax.php')),
                'prime_mover_settings_ajax_spinner_gif' 	=> esc_url_raw(plugins_url('res/img/ajax-spinner.gif', dirname(__FILE__))),
                'prime_mover_delete_continue_button' => esc_js(__('Yes delete ALL', 'prime-mover')),
                'prime_mover_clearall_button' => esc_js(__('Clear all', 'prime-mover')),
                'prime_mover_cancel_button' => esc_js(__('Cancel', 'prime-mover')),
                'prime_mover_panel_error' => $error_message,
                'prime_mover_host_domain' => $host_domain,
                'prime_mover_expand_text' => esc_js(__('Click to expand', 'prime-mover')),
                'prime_mover_close_text' => esc_js(__('Click to close', 'prime-mover')),
            ])
        );
        
        do_action( 'prime_mover_panel_after_enqueue_assets');
    }
    
    /**
     * Added menu page for advanced settings
     */
    public function addMenuPage() 
    {
        $required_cap = 'manage_network_options';
        if ( ! is_multisite() ) {
            $required_cap = 'manage_options';
        } 

        add_submenu_page('migration-panel-settings', esc_html__('Basic settings', 'prime-mover'), esc_html__('Settings', 'prime-mover'),
            $required_cap, 'migration-panel-basic-settings', [$this, 'addBasicSubMenuPageCallBack']);
        
        add_submenu_page('migration-panel-settings', esc_html__('Advanced settings', 'prime-mover'), esc_html__('Advanced', 'prime-mover'),
            $required_cap, 'migration-panel-advance-settings', [$this, 'addSubMenuPageCallBack']);
        
        add_submenu_page('migration-panel-settings', esc_html__('Toolbox', 'prime-mover'), esc_html__('Toolbox', 'prime-mover'),
            $required_cap, 'migration-panel-toolbox', [$this, 'addScheduledBackupSettingsCallBack']);
    }

    /**
     * Add schedule backup setting sub-menu page callback
     */
    public function addScheduledBackupSettingsCallBack()
    {        
        $error = false;  
        $blog_id = 1;
        $multisite = false;
        if (is_multisite()) {
            $multisite = true;
        }
        if ($multisite) {
            $blog_id = $this->getPrimeMover()->getSystemInitialization()->getMainSiteBlogId();
        }
        
        $error_msg = sprintf(
            esc_html__('Error: Blog ID is not valid - go to %s, load the subsite and click %s', 'prime-mover'),
            '<em>' . esc_html__('Prime Mover -> Packages', 'prime-mover') . '</em>',
            '<strong>' . esc_html__('Scheduled backup settings','prime-mover') . '</strong>')
            ;
        
        $get = $this->getPrimeMover()->getSystemInitialization()->getUserInput('get', ['prime_mover_site_blog_id' => FILTER_SANITIZE_NUMBER_INT], 
            '', '', 0, true, true);
        
        if (!empty($get['prime_mover_site_blog_id'])) {
            $blog_id = $get['prime_mover_site_blog_id'];
            $blog_id = (int)$blog_id;
        }
       
        if (!$error && $multisite && !$blog_id) {
            $error = true;
        }       
       
        if (!$error && $multisite && !get_blogaddress_by_id($blog_id)) {
            $error = true;
        }
        ?>
      <div class="wrap">       
         <?php 
         if ($error) {
         ?>
             <h1><?php echo esc_html__('Site Tools', 'prime-mover'); ?></h1> 
             <div class="notice notice-error">
                 <p><?php echo $error_msg;?></p>         
             </div>         
         <?php 
         } else {
         ?>  
             <?php 
             if ($multisite) {
             ?>
             <h1><?php echo sprintf(esc_html__('Site Tools for blog ID: %d', 'prime-mover'), $blog_id); ?></h1>            
             <?php 
             } else {                 
             ?>
             <h1><?php echo esc_html__('Site Tools', 'prime-mover'); ?></h1>             
             <?php 
             }
             ?>     
             <?php 
                 do_action('prime_mover_show_scheduled_backup_notice', $blog_id);
             ?>        
             <p class="edit-site-actions prime-mover-edit-site-actions"><a href="<?php echo esc_url($this->getSystemFunctions()->getPublicSiteUrl($blog_id)); ?>"><?php esc_html_e('Visit Site', 'prime-mover');?></a> <span class="prime-mover-divider"> | </span> 
             <a href="<?php echo $this->getSystemFunctions()->getCreateExportUrl($blog_id, true); ?>"><?php esc_html_e('Migration Tools', 'prime-mover'); ?></a> <span class="prime-mover-divider"> | </span>
             <a href="<?php echo esc_url($this->getSystemFunctions()->getBackupMenuUrl($blog_id)); ?>"><?php esc_html_e('Package Manager', 'prime-mover'); ?></a> <span class="prime-mover-divider"> | </span>
             <a href="<?php echo esc_url($this->getSystemFunctions()->getEventViewerUrl($blog_id)); ?>"><?php esc_html_e('Event Viewer', 'prime-mover'); ?></a>
             </p>
             <p class="edit-site-actions prime-mover-edit-site-actions">
                 <?php esc_html_e('Site URL', 'prime-mover'); ?>: <code><?php echo esc_url($this->getSystemFunctions()->getPublicSiteUrl($blog_id)); ?></code>             
             </p>
             <hr/>
             <h2 class="prime_mover_toolbox_headings">*** <?php echo esc_html__('Scheduled backup settings', 'prime-mover'); ?> ***</h2> 
             <hr/> 
             <form method="get" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
                 <input type="hidden" name="action" value="prime-mover-load-scheduled-backup-settings">
                 <?php wp_nonce_field('prime-mover-load-scheduled-backup-settings', 'prime-mover-load-scheduled-backup-settings-nonce' ); ?>
                 <?php $this->getSystemUtilities()->displayMultisiteBlogIdSelectors([], $blog_id, true); ?>   
             </form>
             <p class="prime_mover_required_given">
             <?php esc_html_e('Required settings for automatic backup PRO feature', 'prime-mover'); ?>.              
             <?php 
             do_action('prime_mover_scheduled_backup_settings_per_site', $blog_id); 
             ?>    
      </div>       
    <?php  
         }
    }

    /**
     * Switch to blog scheduled backup settings
     */
    public function maybeSwitchBlogForScheduledBackupSettings()
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return $this->redirectToScheduledBackupSettings();
        }
        
        $get = $this->getPrimeMover()->getSystemInitialization()->getUserInput('get',
            [
                'prime_mover_site_blog_id' => FILTER_SANITIZE_NUMBER_INT,
                'action' => $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(),
                'prime-mover-load-scheduled-backup-settings-nonce' => $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter()
            ],
            '', '', 0, true, true);
            
        if (empty($get['action']) || empty($get['prime_mover_site_blog_id']) || empty($get['prime-mover-load-scheduled-backup-settings-nonce'])) {
            return $this->redirectToScheduledBackupSettings();
        }
            
        $action = $get['action'];
        $blog_id = $get['prime_mover_site_blog_id'];
        $nonce = $get['prime-mover-load-scheduled-backup-settings-nonce'];
            
        if ($action !== 'prime-mover-load-scheduled-backup-settings') {
            return $this->redirectToScheduledBackupSettings();
        }
            
        if (!wp_verify_nonce($nonce, $action)) {
            return $this->redirectToScheduledBackupSettings();
        }
            
        $blog_id = (int)$blog_id;
        if (!$blog_id) {
            return $this->redirectToScheduledBackupSettings();
        }            
        
        $this->redirectToScheduledBackupSettings($blog_id);
    }

    /**
     * Redirect to scheduled backup settings
     * @param string $target
     */
    protected function redirectToScheduledBackupSettings($blog_id = 0)
    {
        if (!$blog_id) {
            $blog_id = $this->getPrimeMover()->getSystemInitialization()->getMainSiteBlogId();
        }
        $target = $this->getSystemFunctions()->getScheduledBackupSettingsUrl($blog_id);
        wp_redirect($target);
        exit;
    }
    
    /**
     * Added menu page for basic settings
     */
    public function addBasicSubMenuPageCallBack()
    {
        ?>
      <div class="wrap">
         <h1><?php echo sprintf(esc_html__('%s Settings', 'prime-mover'), $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverPluginTitle()); ?></h1>
         <?php 
         if ($this->getProgressHandlers()->isNowPublicMaintenance()) {
         ?>
             <p><?php esc_html_e('Ongoing database export processing and maintenance. Settings panel is temporarily unavailable. Please try again later.', 'prime-mover')?></p>
         <?php     
         } else {
         ?>
            <?php do_action('prime_mover_control_panel_settings');?> 
         <?php 
         }
         ?>
      </div>       
    <?php
    }
    
    /**
     * Add advance sub-menu page callback
     */
    public function addSubMenuPageCallBack()
    {
        ?>
      <div class="wrap">
         <h1><?php echo apply_filters('prime_mover_filter_advance_settings_title', esc_html__('Advanced Settings Panel', 'prime-mover')); ?></h1>
         <?php 
         if ($this->getProgressHandlers()->isNowPublicMaintenance()) {
         ?>
             <p><?php esc_html_e('Ongoing database export processing and maintenance. Settings panel is temporarily unavailable. Please try again later.', 'prime-mover')?></p>
         <?php     
         } else {
         ?>
            <?php do_action('prime_mover_advance_settings');?>
         <?php 
         }
         ?>          
      </div>       
    <?php     
    }
    
    /**
     * Get Prime Mover
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     * @compatible 5.6
     */
    public function getPrimeMover()
    {
        return $this->prime_mover;
    }
}