<?php
namespace Codexonics\PrimeMoverFramework\compatibility;

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
use Freemius;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover Freemius Compatibility Class
 * Helper class for interacting with Freemius
 *
 */
class PrimeMoverFreemiusCompat
{     
    private $prime_mover;
    private $freemius_options;
    private $core_modules;
    private $free_deactivation_option;
    private $action_links;
    private $utilities;
    
    const ON_UPGRADE_USER_VERIFIED = "prime_mover_upgrade_freemius_verified";
    
    /**
     * Construct
     * @param PrimeMover $prime_mover
     * @param array $utilities
     */
    public function __construct(PrimeMover $prime_mover, $utilities = [])
    {
        $this->prime_mover = $prime_mover;
        $this->freemius_options = [
            'fs_accounts',
            'fs_dbg_accounts',
            'fs_active_plugins',
            'fs_api_cache',
            'fs_dbg_api_cache',
            'fs_debug_mode',
            'fs_gdpr'
        ];
        
        $this->action_links = [
            'upgrade',
            'activate-license prime-mover',
            'opt-in-or-opt-out prime-mover',            
        ];
        
        $this->core_modules = [PRIME_MOVER_DEFAULT_FREE_BASENAME, PRIME_MOVER_DEFAULT_PRO_BASENAME];
        $this->free_deactivation_option = '_prime_mover_free_autodeactivated';
        $this->utilities = $utilities;
    }   
 
    /**
     * Get utilities
     * @return string|array
     */
    public function getUtilities()
    {
        return $this->utilities;
    }
    
    /**
     * Get Freemius integration
     */
    public function getFreemiusIntegration()
    {
        $utilities = $this->getUtilities();
        return $utilities['freemius_integration'];
    }
    
    /**
     * Get action links
     * @return string[]
     */
    public function getActionLinks()
    {
        return $this->action_links;
    }
    
    /**
     * Get auto deactivation option
     * @return string
     */
    public function getAutoDeactivationOption()
    {
        return $this->free_deactivation_option;
    }
    
    /**
     * Get core modules
     * @return string[]
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusCompat::itGetsCoreModules()
     */
    public function getCoreModules()
    {
        return $this->core_modules;
    }
    
    /**
     * Get Freemius
     * @return Freemius
     */
    public function getFreemius()
    {
        return $this->getSystemAuthorization()->getFreemius();
    }
    
    /**
     * Get Freemius options
     * @return string[]
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusCompat::itGetsFreemiusOptions() 
     */
    public function getFreemiusOptions()
    {
        return $this->freemius_options;
    }
    
    /**
     * Register hooks
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusCompat::itRegisterDeactivationHook() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusCompat::itRegistersHooks()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusCompat::itChecksIfHooksAreOutdated()
     */
    public function registerHooks()
    {        
        register_deactivation_hook(PRIME_MOVER_MAINPLUGIN_FILE, [$this, 'deactivationHook']);
        
        add_filter('network_admin_plugin_action_links_' . PRIME_MOVER_DEFAULT_PRO_BASENAME , [$this, 'userFriendlyActionLinks'], PHP_INT_MAX, 1);
        add_filter('plugin_action_links_' . PRIME_MOVER_DEFAULT_PRO_BASENAME , [$this, 'userFriendlyActionLinks'], PHP_INT_MAX, 1);
        add_filter('prime_mover_process_srchrplc_query_update', [$this, 'maybeSkipFreemiusOptionsUpdate'], 15, 4);
        
        add_filter('network_admin_plugin_action_links_' . PRIME_MOVER_DEFAULT_FREE_BASENAME , [$this, 'userFriendlyActionLinks'], PHP_INT_MAX, 1);
        add_filter('plugin_action_links_' . PRIME_MOVER_DEFAULT_FREE_BASENAME , [$this, 'userFriendlyActionLinks'], PHP_INT_MAX, 1);
        add_filter('prime_mover_filter_ret_after_rename_table', [$this, 'injectFreemiusOptionsForSrchRplcExclusion'], 10, 2); 
        
        add_action('network_admin_notices', [$this, 'maybeShowMainSiteOnlyMessage'] );
        add_action( 'init', [$this, 'maybeUpdateIfUserReadMessage']);             
        
        $this->injectFreemiusHooks();
    }
 
    /**
     * Exclude Freemius options in the import search and replace process
     * @param boolean $update
     * @param array $ret
     * @param string $table
     * @param array $where_sql
     * @return string|boolean
     */
    public function maybeSkipFreemiusOptionsUpdate($update = true, $ret = [], $table = '', $where_sql = [])
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$table) {
            return $update;
        }
        
        $blog_id = 0;
        if (!empty($ret['blog_id'])) {
            $blog_id = $ret['blog_id'];
        }
        
        $blog_id = (int)$blog_id;
        if (!$blog_id) {
            return $update;
        }
        
        $this->getSystemFunctions()->switchToBlog($blog_id);
        $wpdb = $this->getSystemInitialization()->getWpdB();
        $options = "{$wpdb->prefix}options";
        
        if ($table !== $options) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return $update;
        }
        
        if (!is_array($where_sql) || !is_array($ret) || !isset($ret['prime_mover_freemius_option_ids']) || !isset($where_sql[0])) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return $update;
        }
        
        $option_id_string = $where_sql[0];
        if (!$option_id_string) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return $update;
        }
        
        $freemius_option_ids = $ret['prime_mover_freemius_option_ids'];
        if (!is_array($freemius_option_ids) || empty($freemius_option_ids)) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return $update;
        }
        $int = 0;
        if (false !== strpos($option_id_string, '=')) {
            $exploded = explode("=", $option_id_string);
            $int = str_replace('"', '', $exploded[1]);
            $int = (int)$int;
        }
        
        $this->getSystemFunctions()->restoreCurrentBlog();
        if ($int && in_array($int, $freemius_option_ids)) {
            return false;
            
        } else {
            return $update;
        }
    }
    
    /**
     * Inject Freemius options for search replace exclusion
     * @param array $ret
     * @param number $blog_id
     * @return array
     */
    public function injectFreemiusOptionsForSrchRplcExclusion($ret = [], $blog_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !is_array($ret) || !$blog_id) {
            return $ret;
        }
        
        if (!isset($ret['prime_mover_freemius_option_ids'])) {
            $ret['prime_mover_freemius_option_ids'] = $this->getFreemiusOptionsOnImport($blog_id);
        }  
        
        return $ret;
    }
 
    /**
     * Get Freemius options on import so they are left untouched by the import process
     * @param number $blogid_to_import
     * @return array|mixed[]
     */
    protected function getFreemiusOptionsOnImport($blogid_to_import = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return [];
        }
        
        $affected_options = [];
        if (!$blogid_to_import) {
            return $affected_options;
        }
        
        $this->getSystemFunctions()->switchToBlog($blogid_to_import);
        $wpdb = $this->getSystemInitialization()->getWpdB();
        
        $options_query = "SELECT option_id FROM {$wpdb->prefix}options WHERE option_name LIKE %s";
        $prefix_search = $wpdb->esc_like('fs_') . '%';
        $option_query_prepared = $wpdb->prepare($options_query, $prefix_search);
        $option_query_results = $wpdb->get_results($option_query_prepared, ARRAY_N);
        
        if (!is_array($option_query_results) || empty($option_query_results)) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return $affected_options;
        }
        
        foreach ($option_query_results as $v) {
            if (! is_array($v)) {
                continue;
            }
            
            $val = reset($v);
            $affected_options[] = (int)$val;
        }
        
        $this->getSystemFunctions()->restoreCurrentBlog();
        return $affected_options;
    }
        
    /**
     * Add Freemius customization hooks
     */
    protected function injectFreemiusHooks()
    {
        $freemius = $this->getFreemius();       
        
        $freemius->add_filter('pricing/show_annual_in_monthly', '__return_false');
        $freemius->add_filter('freemius_pricing_js_path', [$this, 'setCustomPricingPath'], 10, 1);
        $freemius->add_filter('show_delegation_option', '__return_false');
        
        $freemius->add_filter('pricing_url', [$this, 'filterUpgradeUrl'], 10, 1);
        $freemius->add_filter('show_trial', [$this, 'maybehideTrial'], 10, 1);
        $freemius->add_action('account_page_load_before_departure', [$this, 'maybeRestoreCurrentBlog']);
    }
    
    /**
     * Hotfix for Freemius switching blogs during account edits, but never called restore current blog. 
     * Ideally, this permanent fix should be added to Freemius.
     */
    public function maybeRestoreCurrentBlog()
    {
        if (is_multisite() && is_network_admin() && ms_is_switched()) {
            restore_current_blog();
        }
    }
    
    /**
     * Maybe hide trial
     * @param boolean $show
     * @return string|boolean
     */
    public function maybehideTrial($show = true)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return $show;
        }
        
        if ('yes' === $this->getFreemiusIntegration()->hasUsableLicense() &&
            false === $this->getFreemiusIntegration()->maybeLoggedInUserIsCustomer() &&
            false === $this->getFreemiusIntegration()->isWhiteLabeled()
            ) {
                return false;
            }
            
        return $show;
    }
        
    /**
     * Filter upgrade URL for best upgrade experience
     * @param string $url
     * @return string
     */
    public function filterUpgradeUrl($url = '')
    {        
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return $url;
        }
        
        if ($this->getSystemInitialization()->isUsingFreeCode()) {
            return $url;
        }
        
        $filter = false;
        if ('yes' === $this->getFreemiusIntegration()->hasUsableLicense() &&
            false === $this->getFreemiusIntegration()->maybeLoggedInUserIsCustomer() &&
            false === $this->getFreemiusIntegration()->isWhiteLabeled()
            ) {
                $filter = true;
            }
            
        if (false === $filter) {                
            return $url;
        }
            
        $license = $this->getFreemiusIntegration()->getLicense();
        if (!is_object($license)) {                
            return $url;
        }
            
        if (!property_exists($license, 'id')) {
            return $url;
        }
        
        $id = $license->id;
        $id = trim($id);
        if (!$id) {
            return $url;
        }
        $id = (int)$id;
        $freemius = $this->getFreemius();
        if (!method_exists($freemius, 'get_user')) {
            return $url;
        }
        
        $user = $freemius->get_user();
        if (!is_object($user)) {
            return $url;
        }

        if (!property_exists($user, 'email')) {
            return $url;
        }
        
        $email = $user->email;
        $email = trim($email);
        if (!$email) {
            return $url;
        }
        
        if (!is_email($email)) {
            return $url;
        }
        
        $encoded = rawurlencode($email);
        $url = 'https://users.freemius.com/licenses/(details:licenses/' . $id . ')?email=' . $encoded;
        return $url;
    }    
    
    /**
     * Set Freemius custom pricing path
     * @param string $path
     * @return string
     */
    public function setCustomPricingPath($path = '')
    {
        if (!defined('PRIME_MOVER_MAINDIR') || !defined('WP_PLUGIN_DIR')) {
            return $path;
        }
        
        $slug = '/freemius-pricing/freemius-pricing.js';
        if (PRIME_MOVER_PRICING_PAGE_DEVELOPMENT_MODE) {
            $slug = '/pricing-page/dist/freemius-pricing.js';
        }        
        
        $maindir = PRIME_MOVER_MAINDIR;
        $plugindir = WP_PLUGIN_DIR;
        
        if (!is_string($maindir) || !is_string($plugindir)) {
            return $path;
        }
        
        $maindir = trim($maindir);
        $plugindir = trim($plugindir);
        
        if (!$maindir || !$plugindir) {
            return $path;
        }
        
        $basename = basename($maindir);
        $pricing_js = untrailingslashit($plugindir) . '/' . $basename . $slug;
        $pricing_js = wp_normalize_path($pricing_js);
        if ($this->getPrimeMover()->getSystemFunctions()->nonCachedFileExists($pricing_js)) {
            return $pricing_js;
        }
        
        return $path;
    }
    
    /**
     * Update if user read message
     */
    public function maybeUpdateIfUserReadMessage() {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $args = [
            'prime_mover_networksites_nonce' => $this->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(),
            'prime_mover_networksites_action' => $this->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(),
        ];
        
        $settings_get = $this->getPrimeMover()->getSystemInitialization()->getUserInput('get', $args, '', '', 0, true, true);
        if (empty($settings_get['prime_mover_networksites_action']) || empty($settings_get['prime_mover_networksites_nonce'])) {
            return;
        }
        
        $action = $settings_get['prime_mover_networksites_action'];
        $nonce = $settings_get['prime_mover_networksites_nonce'];
        
        if ('prime_mover_mark_user_read' === $action && $this->getSystemFunctions()->primeMoverVerifyNonce($nonce, 'prime_mover_user_read_mainsiteonly_notice')) {
            $this->getPrimeMover()->getSystemFunctions()->updateSiteOption($this->getPrimeMover()->getSystemInitialization()->getUserUnderstandMainSiteOnly(), 'yes', true, '', true, true);
            $this->redirectAndExit();
        }
    }
 
    /**
     * Redirect and exit helper
     */
    protected function redirectAndExit()
    {
        wp_safe_redirect(network_admin_url('sites.php') );
        exit;
    }
  
    /**
     * Generate import notice success URL
     * @return string
     */
    protected function generateNoticeSuccessUrl() {
        
        return add_query_arg(
            [
                'prime_mover_networksites_action' => 'prime_mover_mark_user_read',
                'prime_mover_networksites_nonce'  => $this->getSystemFunctions()->primeMoverCreateNonce('prime_mover_user_read_mainsiteonly_notice'),
            ], network_admin_url('sites.php')            
            );
    }
    
    /**
     * Show main site only message to user
     */
    public function maybeShowMainSiteOnlyMessage()
    {
        if (!$this->isOnNetworkSitesAuthorized()) {
            return;
        }
        
        if (!$this->isNetworkUsingOnlyMainSite()) {
            return;
        }        
        
        if (!$this->isUserNeedsToCreateSubSite()) {
            return;
        }    
        
        $upgrade_url = apply_filters('prime_mover_filter_upgrade_pro_url', $this->getFreemius()->get_upgrade_url());
        $upgrade_text = apply_filters('prime_mover_filter_upgrade_pro_text', esc_html__('upgrade to the PRO version', 'prime-mover') , 0, false);        
        $addsites_url = network_admin_url('site-new.php');        
        ?>
	    <div class="notice notice-info">  
	        <h2><?php esc_html_e('Important notice', 'prime-mover'); ?></h2>
	        <p><?php echo sprintf(esc_html__('Thank you for using %s. 
        To get started using the free version, you need to %s. Free version works on any number of multisite subsites. 
        If you want to export and restore the multisite main site, you need to %s. Thanks!', 'prime-mover'), 
	            '<strong>' . PRIME_MOVER_PLUGIN_CODENAME . '</strong>', 
	            '<a href="' . esc_url($addsites_url) . '">' . esc_html__('add a subsite for testing', 'prime-mover') . '</a>',
	            '<a href="' . esc_url($upgrade_url) . '">' . strtolower($upgrade_text) . '</a>'
	            );
                ?>
	        </p>	
       
		    <p><a class="button" href="<?php echo esc_url($this->generateNoticeSuccessUrl()); ?>"><?php esc_html_e('Yes, I understand', 'prime-mover'); ?></a>
		</div>
		<?php        
    }

    /**
     * Checks if only using main site
     * @return boolean
     */
    protected function isNetworkUsingOnlyMainSite()
    {
        $count = (int)get_blog_count();
        if ($count === 0) {
            return false;
        }
        if ($count > 1) {
            return false;
        }
        
        $mainsite_blogid = $this->getPrimeMover()->getSystemInitialization()->getMainSiteBlogId();
        if (apply_filters('prime_mover_maybe_load_migration_section', false, $mainsite_blogid)) {
            return false;
        }    
        
        return true;
    }
    
    /**
     * Is on network sites and authorized
     * @return boolean
     */
    protected function isOnNetworkSitesAuthorized()
    {        
        return (is_multisite() && $this->getPrimeMover()->getSystemInitialization()->isNetworkSites() && $this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized());        
    }
    
    /**
     * Returns TRUE if user needs to create subsite
     * Otherwise FALSE
     * @return void|boolean
     */
    protected function isUserNeedsToCreateSubSite()
    {
        $shouldread = false;
        $importantreadmsg_setting = $this->getPrimeMover()->getSystemInitialization()->getUserUnderstandMainSiteOnly();
        
        if ('yes' !== $this->getPrimeMover()->getSystemFunctions()->getSiteOption($importantreadmsg_setting, false, true, false, '', true, true)) {   
            $shouldread = true;
        }
        
        return $shouldread;
    }   
    
    /**
     * User friendly action links.
     * @param array $actions
     * @return array
     */
    public function userFriendlyActionLinks($actions = [])
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() ) {
            return $actions;
        }        
        if (!is_array($actions)) {
            return $actions;
        }
        if (empty($actions)) {
            return $actions;
        }
        $freemius = [];
        $core = [];
        $prime_mover = [];
        
        foreach ($actions as $k => $v) {
            if (in_array($k, $this->getActionLinks())) {
                $freemius[$k] = $v;                
            } elseif ($k === $this->getSystemInitialization()->getPrimeMoverActionLink()) {
                $prime_mover[$k] = $v;
            } else {
                $core[$k] = $v;
            }
        }
        
        return array_merge($core, $freemius, $prime_mover);
    }
        
    /**
     * Deactivation hook
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusCompat::itRunsDeactivationHooks()
     */
    public function deactivationHook()
    {      
        do_action('prime_mover_deactivated');
    }
    
    /**
     * Get system initialization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemInitialization
     */
    public function getSystemInitialization()
    {
        return $this->getPrimeMover()->getSystemInitialization();
    }
    
    /**
     * Get system authorization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusCompat::itRunsDeactivationHooks()
     */
    public function getSystemAuthorization()
    {
        return $this->getPrimeMover()->getSystemAuthorization();
    }
    
    /**
     * Get Prime Mover instance
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusCompat::itRunsDeactivationHooks()
     */
    public function getPrimeMover()
    {
        return $this->prime_mover;
    }
    
    /**
     * Get system functions
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions
     */
    public function getSystemFunctions()
    {
        return $this->getPrimeMover()->getSystemFunctions();
    }
}
