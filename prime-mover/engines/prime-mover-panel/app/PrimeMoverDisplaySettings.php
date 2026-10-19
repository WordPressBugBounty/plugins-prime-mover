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

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display Prime Mover settings
 *
 */
class PrimeMoverDisplaySettings
{
    private $prime_mover_customdir_settings;
    private $prime_mover_excludedplugin_settings;
    private $prime_mover_excluded_uploads_settings;
    private $prime_mover_display_maintenance_settings;
    private $prime_mover_display_security_settings;
    private $prime_mover_display_dropbox_settings;
    private $prime_mover_display_encryption_settings;
    private $prime_mover_display_gdrive_settings;
    private $prime_mover_runtime_setings;
    
    /**
     * Constructor
     * @param PrimeMoverDisplayCustomDirSettings $prime_mover_customdir_settings
     * @param PrimeMoverDisplayExcludedPluginsSettings $prime_mover_excludedplugin_settings
     * @param PrimeMoverDisplayExcludedUploadSettings $prime_mover_excluded_uploads_settings
     * @param PrimeMoverDisplayMaintenanceSettings $prime_mover_display_maintenance_settings
     * @param PrimeMoverDisplaySecuritySettings $prime_mover_display_security_settings
     * @param PrimeMoverDisplayDropBoxSettings $prime_mover_display_dropbox_settings
     * @param PrimeMoverDisplayEncryptionSettings $prime_mover_display_encryption_settings
     * @param PrimeMoverDisplayGDriveSettings $prime_mover_display_gdrive_settings
     * @param PrimeMoverDisplayRunTimeSettings $prime_mover_runtime_setings
     */
    public function __construct(
        PrimeMoverDisplayCustomDirSettings $prime_mover_customdir_settings, 
        PrimeMoverDisplayExcludedPluginsSettings $prime_mover_excludedplugin_settings,
        PrimeMoverDisplayExcludedUploadSettings $prime_mover_excluded_uploads_settings,
        PrimeMoverDisplayMaintenanceSettings $prime_mover_display_maintenance_settings,
        PrimeMoverDisplaySecuritySettings $prime_mover_display_security_settings,
        PrimeMoverDisplayDropBoxSettings $prime_mover_display_dropbox_settings,
        PrimeMoverDisplayEncryptionSettings $prime_mover_display_encryption_settings,
        PrimeMoverDisplayGDriveSettings $prime_mover_display_gdrive_settings,
        PrimeMoverDisplayRunTimeSettings $prime_mover_runtime_setings
        ) 
    {
        $this->prime_mover_customdir_settings = $prime_mover_customdir_settings;
        $this->prime_mover_excludedplugin_settings = $prime_mover_excludedplugin_settings;
        $this->prime_mover_excluded_uploads_settings = $prime_mover_excluded_uploads_settings;
        $this->prime_mover_display_maintenance_settings = $prime_mover_display_maintenance_settings;
        $this->prime_mover_display_security_settings = $prime_mover_display_security_settings;
        $this->prime_mover_display_dropbox_settings = $prime_mover_display_dropbox_settings;
        $this->prime_mover_display_encryption_settings = $prime_mover_display_encryption_settings;     
        $this->prime_mover_display_gdrive_settings = $prime_mover_display_gdrive_settings;
        $this->prime_mover_runtime_setings = $prime_mover_runtime_setings;
    }
    
    /**
     * Get Prime Mover runtime settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayRunTimeSettings
     */
    public function getPrimeMoverRunTimeSettings()
    {
        return $this->prime_mover_runtime_setings;
    }

    /**
     * Display Gdrive settings instance
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayGDriveSettings
     */
    public function getPrimeMoverDisplayGdriveSettings()
    {
        return $this->prime_mover_display_gdrive_settings;
    }
    
    /**
     * Display encryption settings instance
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayEncryptionSettings
     */
    public function getPrimeMoverDisplayEncryptionSettings()
    {
        return $this->prime_mover_display_encryption_settings;
    }
    
    /**
     * Custom dir settings instance
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayCustomDirSettings
     */
    public function getPrimeMoverCustomDirSettings()
    {
        return $this->prime_mover_customdir_settings;
    }

    /**
     * Display exclude plugin settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayExcludedPluginsSettings
     */
    public function getPrimeMoverExcludePluginSetings()
    {
        return $this->prime_mover_excludedplugin_settings;
    }

    /**
     * Get Prime Mover exclude uplaods
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayExcludedUploadSettings
     */
    public function getPrimeMoverExcludedUploads()
    {
        return $this->prime_mover_excluded_uploads_settings;
    }  
 
    /**
     * Get display maintenace mode settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayMaintenanceSettings
     */
    public function getPrimeMoverDisplayMaintenanceSettings()
    {
        return $this->prime_mover_display_maintenance_settings;
    } 
    
    /**
     * Get display security settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplaySecuritySettings
     */
    public function getDisplaySecuritySettings()
    {
        return $this->prime_mover_display_security_settings;
    } 
 
    /**
     * Get display Dropbox settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayDropBoxSettings
     */
    public function getDisplayDropBoxSettings()
    {
        return $this->prime_mover_display_dropbox_settings;
    }
    
    /**
     * Get Prime Mover instance
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     */
    public function getPrimeMover()
    {
        return $this->getPrimeMoverCustomDirSettings()->getPrimeMover();
    }
    
    /**
     * Get Prime Mover settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettings
     */
    public function getPrimeMoverSettings()
    {
        return $this->getPrimeMoverCustomDirSettings()->getPrimeMoverSettings();
    }
    
    /**
     * Get settings markup
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsMarkups
     */
    public function getSettingsMarkup()
    {
        return $this->getPrimeMoverSettings()->getSettingsMarkup();
    } 
    
    /**
     * Get authorization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization
     */
    public function getSystemAuthorization()
    {
        return $this->getPrimeMover()->getSystemAuthorization();
    }
    
    /**
     * Get component utilities
     */
    public function getComponentAuxiliary()
    {
        return $this->getPrimeMoverSettings()->getComponentUtilities();
    }
    
    /**
     * Get Prime Mover settings template
     * @return string|\Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsTemplate
     */
    public function getPrimeMoverSettingsTemplate()
    {
        return $this->getPrimeMoverExcludePluginSetings()->getPrimeMoverSettingsTemplate();
    }
    
    public function getSettingsConfig()
    {
        return $this->getPrimeMoverSettingsTemplate()->getSettingsConfig();
    }
    
    /**
     * Initialize plugin settings display hooks
     */
    public function initHooks()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        add_action('prime_mover_control_panel_settings', [$this, 'showCustomExportDirectorySetting'], 1); 
        add_action('prime_mover_control_panel_settings', [$this, 'showExcludedPluginsSetting'], 11);        
        add_action('prime_mover_control_panel_settings', [$this, 'showExcludedUploadsSetting'], 12); 
        
        add_action('prime_mover_control_panel_settings', [$this, 'showMaintenanceModeSetting'], 10);    
        add_action('prime_mover_control_panel_settings', [$this, 'showRunTimeParameterSettings'], 15); 
        add_action('prime_mover_control_panel_settings', [$this, 'showDownloadSecuritySetting'], 20);  
        
        add_action('prime_mover_control_panel_settings', [$this, 'showDownloadAuthenticationSetting'], 30); 
        
        add_action('prime_mover_control_panel_settings', [$this, 'showDropBoxSetting'], 30); 
        add_action('prime_mover_control_panel_settings', [$this, 'showEncryptionSetting'], 35); 
        add_action('prime_mover_control_panel_settings', [$this, 'showGdriveSettings'], 30); 
        
        add_action('prime_mover_last_table_heading_settings', [$this, 'maybeInformProSetting'], 10, 2);   
        add_action('prime_mover_panel_after_enqueue_assets', [$this, 'primeMoverEnqueueClipBoardJs'], 10);
        add_action('prime_mover_control_panel_settings', [$this, 'showGlobalSanitizeUsers'], 37); 
    }
    
    /**
     * Optionally sanitize all users when sanitizing admins/super admin users.
     */
    public function showGlobalSanitizeUsers()
    {
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = 'sanitizeusers_global_status';
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];
        $globalsanitizeusers_label = __('User privacy', 'prime-mover');
        $enable_label = __('Sanitize all users during export', 'prime-mover');
        
        $sanitize_admins_url = esc_url(
            $this->getPrimeMover()->getSystemFunctions()->getScheduledBackupSettingsUrl() . '#prime-mover-sanitize_admins-label'
            );
        
        $current_domain = parse_url(network_site_url(), PHP_URL_HOST);        
        $sprintf = sprintf(
            wp_kses(
                /* translators: 1: Sanitize admin settings in Toolbox URL, 2: Current network/site domain */
                __(
                    'When sanitizing admin/super-admin users is enabled, sanitize all users as well. This setting requires the <strong><em>Prime Mover -> Toolbox -> Sanitize admins</em></strong> setting to be enabled first.<br>' .  
                    '<p class="description prime-mover-settings-paragraph">In multisite, each subsite has its own <strong>"Sanitize admins"</strong> setting; make sure to switch to the correct subsite using the blog ID switcher tool at the top of the Toolbox page before saving the setting.</p>' .
                    '<div class="notice notice-info inline prime-mover-notice prime-mover-user-privacy-notice description prime-mover-settings-paragraph">' .
                    '<h3 class="prime-mover-notice-heading">Sanitization Modes</h3>' .
                    '<ul class="prime-mover-notice-list">' .
                    '<li>' .
                    '<strong>Safe-Discard Mode (Default):</strong> Blocks all emails (e.g., <code>clr_am9obi5kb2...@err.loc</code>). Outbound emails cannot be sent or received.' .
                    '</li>' .
                    '<li>' .
                    '<strong>Developer Mode:</strong> Enables email testing via plus-addressing (e.g., <code>dev+s_a1b2c3d4_u1000@gmail.com</code>). Outbound emails can be sent and received.<br>' .
                    '<span class="prime-mover-notice-subtext">To activate, define this in <code>%2$s</code>\'s <code>wp-config.php</code> file (Gmail or Outlook recommended):</span><br>' .
                    '<code class="prime-mover-code-block">define( \'PRIME_MOVER_DEV_EMAIL_OVERRIDE\', \'yourname@gmail.com\' );</code><br>' .
                    '<em class="prime-mover-notice-note">Note: If using a custom domain email, ensure your mail provider supports plus-addressing (+).</em>' .
                    '</li>' .
                    '</ul>' .
                    '</div>',
                    'prime-mover'
                    ),
                [
                    'a'      => [ 'href' => true ],
                    'br'     => [],
                    'strong' => [],
                    'h3'     => ['class' => true],
                    'em'     => [ 'class' => true ],
                    'ul'     => [ 'class' => true ],
                    'li'     => [],
                    'p'      => [ 'class' => true ],
                    'span'   => [ 'class' => true ],
                    'code'   => [ 'class' => true ],
                    'div'    => [ 'class' => true ],
                ]
                ),
            $sanitize_admins_url,
            esc_html( $current_domain )
            );

        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxFormTemplate($globalsanitizeusers_label, $identifier, $config, 'true', $enable_label, $sprintf, 0, true, $button_specs);
    }
 
    /**
     * Show runtime parameter settings
     */
    public function showRunTimeParameterSettings()
    {
        $this->getPrimeMoverRunTimeSettings()->showRunTimeParameterSettings();
    }
    
    /**
     * Enqueue clipboard JS that is needed for settings
     */
    public function primeMoverEnqueueClipBoardJs()
    {
        $this->getComponentAuxiliary()->enqueueClipBoardJs();        
    }
    
    /**
     * Display Gdrive settings
     */
    public function showGdriveSettings()
    {
        $this->getPrimeMoverDisplayGdriveSettings()->showGdriveSettings();
    }
    
    /**
     * Display encryption settings
     */
    public function showEncryptionSetting()
    {
        $this->getPrimeMoverDisplayEncryptionSettings()->showEncryptionSetting();
    }
    
    /**
     * Display DropBox settings
     */
    public function showDropBoxSetting()
    {
        $this->getDisplayDropBoxSettings()->showDropBoxSetting();
    }
    
    /**
     * Show download authentication settings
     */
    public function showDownloadAuthenticationSetting()
    {
        $this->getDisplaySecuritySettings()->showDownloadAuthenticationSetting();
    }
    
    /**
     * Show security settings
     */
    public function showDownloadSecuritySetting()
    {
        $this->getDisplaySecuritySettings()->showDownloadSecuritySetting();
    }
    
    /**
     * Show maintenance mode setting
     */
    public function showMaintenanceModeSetting()
    {
        $this->getPrimeMoverDisplayMaintenanceSettings()->showMaintenanceModeSetting();
    }
    
    /**
     * Show excluded uploads settings
     */
    public function showExcludedUploadsSetting()
    {
        $this->getPrimeMoverExcludedUploads()->showExcludedUploadsSetting();
    }
    
    /**
     * Display exclude plugin settings
     */
    public function showExcludedPluginsSetting()
    {
        $this->getPrimeMoverExcludePluginSetings()->showExcludedPluginsSetting();
    }
    
    /**
     * Show custom export path directory setting
     */
    public function showCustomExportDirectorySetting()
    {
        $this->getPrimeMoverCustomDirSettings()->showCustomExportDirectorySetting();
    }
     
    /**
     * Maybe display documentation link for a setting
     * @param string $url
     * @param number $blog_id
     */
    public function maybeInformProSetting($url = '', $blog_id = 0)
    {
        $render = false;
        if ($blog_id && is_multisite()) {
            $render = apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id);
        } else {
            $render = apply_filters('prime_mover_is_loggedin_customer', false);
        }
        
        if (false === $render) { ?>
            <p class="prime_mover_pro_class_heading_table"><span class="dashicons dashicons-lock prime-mover-dashicons-lock"></span><?php esc_html_e('PRO FEATURE', 'prime-mover'); ?></p>        
    <?php 
        } else {            
            if ($url) {
    ?>    
            <p class="prime-mover-settings-doc-link">
                <a class="prime-mover-external-link" target="_blank" href="<?php echo esc_url($url); ?>"><?php esc_html_e('Documentation', 'prime-mover'); ?></a>
            </p>
    <?php 
            }
        }
    }
}