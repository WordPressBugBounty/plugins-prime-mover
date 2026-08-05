<?php
namespace Codexonics\PrimeMoverFramework\utilities;

use Freemius;
use FS_Plugin_License;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover Freemius Integration
 * Utiliy class for integrating Freemius SDK with Prime Mover
 *
 */
class PrimeMoverFreemiusIntegration
{    
    private $freemius_options;    
    private $shutdown_utilities;
    private $pricing_page_ids;
    
    const FREEMIUS_USERKEY = '_freemius_usermeta';
    
    /**
     * Constructor
     * @param PrimeMoverShutdownUtilities $shutdown_utilities
     */
    public function __construct(PrimeMoverShutdownUtilities $shutdown_utilities)
    {
        $this->freemius_options = [];
        $this->shutdown_utilities = $shutdown_utilities;
        
        $this->pricing_page_ids = [
            'admin_page_migration-panel-settings-pricing',
            'admin_page_migration-panel-settings-pricing-network',
            'prime-mover_page_migration-panel-settings-pricing',
            'prime-mover_page_migration-panel-settings-pricing-network',
            'prime-mover-pro_page_migration-panel-settings-pricing-network'
            ];
    }
    
    /**
     * Get pricing page IDs
     * @return string|string[]
     */
    public function getPricingPageIds()
    {
        return $this->pricing_page_ids;
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
     * Get shutdown utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverShutdownUtilities
     */
    public function getShutdownUtilities()
    {
        return $this->shutdown_utilities;        
    }
    
    /**
     * Get system functions
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions
     */
    public function getSystemFunctions()
    {
        return $this->getShutdownUtilities()->getSystemFunctions();
    }
    
    /**
     * Set freemius options
     * @param array $options
     */
    public function setFreemiusOptions($options = [])
    {
        $this->freemius_options = $options;
    }
    
    /**
     * Get freemius options
     * @return array
     */
    public function getFreemiusOptions()
    {
        return $this->freemius_options;
    }
        
    /**
     * Get system authorization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization
     */
    public function getSystemAuthorization()
    {
        return $this->getShutdownUtilities()->getSystemAuthorization();
    }
        
    /**
     * Init hook class
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itAddsInitHooks()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itChecksIfHooksAreOutdated()
     */
    public function initHooks()
    {      
        add_action('prime_mover_shutdown_actions', [$this, 'restoreFreemiusSettingsOnError']);       
        add_action('prime_mover_before_db_processing', [$this, 'backupFreemiusOptionsImport'], 10, 2);
        
        add_action('prime_mover_after_db_processing', [$this, 'restoreFremiusOptionsImportMultisite'], 10, 2);
        add_action('prime_mover_before_only_activated', [$this, 'restoreFreemiusOptions'], 10, 1);
        
        add_filter('prime_mover_multisite_blog_is_licensed', [$this, 'maybeBlogIDLicensed'], 10, 2);         
        add_filter('prime_mover_filter_upgrade_pro_text', [$this, 'appendCartIcon'], 9999, 3);
        
        add_action('prime_mover_dashboard_content', [$this, 'showGettingStartedOnFreeUsers'], 1); 
        add_action('admin_menu', [$this, 'outputSupportMenu'], 9999999999 );
        
        add_action('network_admin_menu', [$this, 'outputSupportMenu'], 9999999999 );
        add_action('admin_page_access_denied', [$this, 'redirectToExternalContactPage']);
        
        add_filter('prime_mover_filter_export_footprint', [$this, 'addBothPrimeMoverVersionsToPlugins'], 35, 1);
        add_action('prime_mover_after_db_processing', [$this, 'activateOnlyOneVersion'], 100, 2);
        add_filter('prime_mover_is_loggedin_customer', [$this, 'primeMoverCheckIfLoggedInCustomer'], 10, 1);
        
        add_filter('prime_mover_filter_config_after_diff_check', [$this, 'primeMoverAlwaysExcludeItselfInDiff'], 10, 1);
        add_filter('prime_mover_input_footprint_package_array', [$this, 'addBothPrimeMoverVersionsToPlugins'], 35, 1);
        add_filter('prime_mover_ajax_rendered_js_object', [$this, 'correctUpgradeMessageBrowserLimit'], 10, 1);      
        
        $this->injectFreemiusHooks();
    }
    
    /**
     * Add Freemius customization hooks
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itAddsInitHooks()
     */
    protected function injectFreemiusHooks()
    {
        $freemius = $this->getFreemius();
        
        $freemius->add_action('connect/after_license_input', [$this, 'unableToActivateLicenseAction']);        
        $freemius->add_filter('known_license_issues_url', [$this, 'filterLicenseIssuesUrl']);
        
        $freemius->add_action('after_account_details', [$this, 'unableToActivateLicenseAccountDetails'], 100);
        $freemius->add_action('after_account_details', [$this, 'maybeInviteToUpgrade'], 10);
        $freemius->add_action('after_network_account_connection', [$this, 'maybeRedirectToAccountPage'], 10, 1); 
        
        $freemius->add_action('after_account_details', [$this, 'handleWhiteLabelDeactivation'], 5);
    }

    /**
     * Maybe redirect to account page
     * Hooked to `after_network_account_connection` action
     * @param boolean $user
     */
    public function maybeRedirectToAccountPage($user = false)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (false !== $user ) {
            return;
        }
        
        if (!function_exists('fs_is_network_admin')) {
            return;
        }
        
        if (!fs_is_network_admin()) {
            return;
        }
                
        if (true === $this->getFreemius()->is_anonymous()) {
            return;
        } 
        
        $result['success'] = true;
        $result['next_page'] = esc_url_raw($this->getFreemius()->get_account_url());
        
        $this->jsonEncode($result);
    }
    
    /**
     * Output json code
     * @param array $result
     */
    protected function jsonEncode($result = [])
    {
        echo json_encode($result);
        exit; 
    }
     
    /**
     * Unable to activate license in account details page.
     */
    public function unableToActivateLicenseAccountDetails()
    {
        ?>
        <div class="postbox prime-mover-account-details-div">
             <h3><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Account FAQ', 'prime-mover'); ?></h3>
                 <ol>
                 <li><a class="prime-mover-external-link" target="_blank" href="<?php echo esc_url(CODEXONICS_ACTIVATE_LICENSE_GUIDE);?>">
                     <?php esc_html_e('How to fix if I cannot activate license? Or if PRO features are not activated?', 'prime-mover'); ?></a></li>
                 <li><a class="prime-mover-external-link" target="_blank" href="<?php echo esc_url(CODEXONICS_LICENSING_GUIDE);?>">
                     <?php esc_html_e('How many licenses do I need when upgrading to the PRO version?', 'prime-mover'); ?></a></li>
                 <li><a class="prime-mover-external-link" target="_blank" href="<?php echo esc_url(CODEXONICS_ACTIVATE_PRO_GUIDE);?>">
                     <?php esc_html_e('How to activate Prime Mover PRO license?', 'prime-mover'); ?></a></li>                    
                 </ol>
        </div>    
    <?php     
    }
    
    /**
     * Handle white label mode
     */
    public function handleWhiteLabelDeactivation()
    {
        if (false === $this->maybeLoggedInUserIsCustomer() && $this->isWhiteLabeled() && 'yes' === $this->hasUsableLicense() && false === $this->getSystemFunctions()->getSystemInitialization()->isUsingFreeCode()) {
            ?>
            <div class="postbox prime-mover-account-details-div">
                 <h3 id="prime_mover_account_upgrade_plan_text"><span class="dashicons dashicons-cart prime-mover-cart-dashicon"></span><?php esc_html_e('Activate PRO', 'prime-mover'); ?></h3>                
                    <p class="notice notice-info notice-large"><?php                     
                    printf(
                        wp_kses(
                            /* translators: %s: Codexonics documentation reference URL link address string */
                            __( 'It looks like your Freemius account license is white labeled. To activate the license on this site, please check out this <a class="prime-mover-external-link" href="%s">complete guide</a>.', 'prime-mover' ),
                            [
                                'a' => [
                                    'class' => true,
                                    'href'  => true,
                                ],
                            ]
                        ),
                        esc_url( CODEXONICS_WHITE_LABEL_GUIDE )
                    );
                    ?> 
                    </p>               
            </div>
            <?php
        }        
    }    
    
    /**
     * Maybe invite to upgrade
     */
    public function maybeInviteToUpgrade()
    {
        if ('no' === $this->hasUsableLicense() && false === $this->getSystemFunctions()->getSystemInitialization()->isUsingFreeCode()) {
            $is_white_labeled = $this->isWhiteLabeled();
            if ($is_white_labeled) {
                $upgrade_url = CODEXONICS_UPGRADE_PLAN_GUIDE;
                $class = 'prime-mover-external-link';
            } else {
                $upgrade_url = $this->getFreemius()->get_upgrade_url();
                $class = '';
            }
            ?>
            <div class="postbox prime-mover-account-details-div">
                 <h3 id="prime_mover_account_upgrade_plan_text"><span class="dashicons dashicons-cart prime-mover-cart-dashicon"></span><?php esc_html_e('Upgrade plan', 'prime-mover'); ?></h3>                
                    <p class="notice-large"><?php                     
                    printf(
                        wp_kses(
                            /* translators: 1: CSS class name, 2: Destination plan management or guide panel link URL string */
                            __( 'You have used up all of your license activation quota. You should <a class="%1$s" href="%2$s">upgrade your plan</a> to activate the license to other production sites.', 'prime-mover' ),
                            [
                                'a' => [
                                    'class' => true,
                                    'href'  => true,
                                ],
                            ]
                        ),
                        esc_attr($class),
                        esc_url($upgrade_url)
                    );
                    ?> 
                    </p>               
            </div>
            <?php
        }        
    }
    
    /**
     * Filter license issues URL
     * @return string
     */
    public function filterLicenseIssuesUrl()
    {
        return esc_url(CODEXONICS_ACTIVATE_LICENSE_GUIDE);
    }
    
    /**
     * Unable to activate license guide link
     */
    public function unableToActivateLicenseAction()
    {
    ?>
        <div class="prime-mover-unable-to-activate-license-help">
            <p><span class="prime-mover-unable-activate-license-span">
                <?php esc_html_e('Unable to activate license key?', 'prime-mover'); ?>
                </span> -  
                 <a class="prime-mover-external-link" target="_blank" 
            href="<?php echo esc_url(CODEXONICS_ACTIVATE_LICENSE_GUIDE);?>"><?php esc_html_e("Check this complete guide", "prime-mover");?>
                 </a></p>
        </div>            
    <?php     
    }
        
    /**
     * Append cart icon
     * @param string $markup
     * @param number $blog_id
     * @param boolean $show_icons
     * @return string
     */
    public function appendCartIcon($markup = '', $blog_id = 0, $show_icons = true)
    {
        if ($show_icons) {
            $markup = '<i class="dashicons dashicons-cart prime-mover-cart-dashicon"></i>' . $markup;
        }
        
        return $markup;
    }
    
    /**
     * Correct upgrade message for browser limit
     * @param array $msg
     * @return array
     */
    public function correctUpgradeMessageBrowserLimit($msg = [])
    {
        if (!isset($msg['prime_mover_exceeded_browser_limit']) ) {
            return $msg;
        }
        
        $uploadsize_limit = $this->getSystemFunctions()->getSystemInitialization()->getBrowserFileUploadSizeLimit();
        $human_readable = $this->getSystemFunctions()->humanFileSize($uploadsize_limit, 0);
        
        /* translators: %s: Formatted file size string (e.g. 512MB) */
        $limit_html = "<p>" . sprintf( __( 'Restoring packages beyond %s using browser uploads is not recommended for best performance.', 'prime-mover' ), esc_html( $human_readable ) ) . "</p>" .
            "<p>" . __( 'Please upload this package to this path via FTP: <em>{{WPRIME_EXPORT_PATH}}</em>', 'prime-mover' ) . "</p>" .
            "<p>" . __( 'You can then restore via <em>Prime Mover - Packages</em> in backend.', 'prime-mover' ) . "</p>";
        
        $msg['prime_mover_exceeded_browser_limit'] = wp_kses(
            $limit_html,
            [
                'p'  => [],
                'em' => [],
            ]
            );
        
        return $msg;
    }    
 
    /**
     * Always Exclude Prime Mover Plugin in Diffs
     * @param array $ret
     * @return array
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itAlwaysExcludePrimeMoverItselfInDiff()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itDoesNotExcludePluginsNotAuthorized() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itDoesNotExcludePluginsIfNotPrimeMover() 
     */
    public function primeMoverAlwaysExcludeItselfInDiff($ret = [])
    {
        if ( ! $this->getSystemAuthorization()->isUserAuthorized()) {
            return $ret;
        }
        if (empty($ret['diff'])) {
            return $ret;
        }
        if (empty($ret['diff']['plugins'])) {
            return $ret;
        }
        if (isset($ret['diff']['plugins'][PRIME_MOVER_DEFAULT_FREE_BASENAME])) {
            unset($ret['diff']['plugins'][PRIME_MOVER_DEFAULT_FREE_BASENAME]);
        }
        if (isset($ret['diff']['plugins'][PRIME_MOVER_DEFAULT_PRO_BASENAME])) {
            unset($ret['diff']['plugins'][PRIME_MOVER_DEFAULT_PRO_BASENAME]);
        }
        return $ret;
    }
    
    /**
     * API - checked if logged-in customer
     * Returns TRUE if logged-in customer, otherwise FALSE
     * @param boolean $ret
     * @return boolean
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itReturnsTrueIfLoggedInCustomer()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itReturnsFalseIfNonAuthorizedUser()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itReturnsFalseIfNonCustomer()
     */
    public function primeMoverCheckIfLoggedInCustomer($ret = false)
    {
        if ( ! $this->getSystemAuthorization()->isUserAuthorized()) {
            return false;
        }
        if ($this->isCustomer()) {
            return true;
        } else {
            return false;
        }        
    }
    
    /**
     * Activate only one Prime Mover version at 
     * restore based on customer information
     * This should work in both single-site and multisite.
     * Since version 1.4.4, blog ID is switched to make sure this is correctly handled on a correct subsite (if multisite).
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itSkipsDeactivationIfCoreExistAndActive()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itSkipsDeactivationIfProExistAndActive()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itActivatesProVersionOnlyIfCustomer() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itActivatesFreeVersionOnlyIfNotCustomer()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itReturnsRunTimeErrorIfNoVersionsExists()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itReturnsRunTimeErrorIfBothDeactivatedButExists()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itDoesNotDoAnythingWhenNotAuthorized()
     * @param array $ret
     * @param number $blogid_to_import
     * @return void
     */
    public function activateOnlyOneVersion($ret = [], $blogid_to_import = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $this->getSystemFunctions()->switchToBlog($blogid_to_import);
        $do_deactivation = false;
        $free_active = false;        
        $pro_active = false;
        
        $free_exist = true;
        $pro_exist = true;
        wp_cache_delete('alloptions', 'options');
        
        if (!$this->getSystemFunctions()->getPluginFullPath(PRIME_MOVER_DEFAULT_FREE_BASENAME, true) ) {
            $free_exist = false;            
            $this->getSystemFunctions()->deactivatePlugins(PRIME_MOVER_DEFAULT_FREE_BASENAME, true);
        }
        if (!$this->getSystemFunctions()->getPluginFullPath(PRIME_MOVER_DEFAULT_PRO_BASENAME, true) ) {
            $pro_exist = false;            
            $this->getSystemFunctions()->deactivatePlugins(PRIME_MOVER_DEFAULT_PRO_BASENAME, true);
        }
        
        if ($free_exist && $this->getSystemFunctions()->isPluginActive(PRIME_MOVER_DEFAULT_FREE_BASENAME)) {
            $free_active = true;
        }
        
        if ($pro_exist && $this->getSystemFunctions()->isPluginActive(PRIME_MOVER_DEFAULT_PRO_BASENAME)) {
            $pro_active = true;
        }
        
        if ($free_active && $pro_active) {
            $do_deactivation = true;
        }
        
        if (!$free_active && !$pro_active ) {            
            $this->getSystemFunctions()->restoreCurrentBlog();
            do_action('prime_mover_log_processed_events', 'ERROR: Prime Mover encounters fatal error and deactivated.', 0, 'import', __FUNCTION__, $this);
            do_action( 'prime_mover_shutdown_actions', ['type' => 1, 'message' => esc_html__('Prime Mover encounters fatal error and deactivated.', 'prime-mover')] );
            return wp_die();
        }        
        if (!$do_deactivation ) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            do_action('prime_mover_log_processed_events', 'Only one Prime Mover version is active, this is correct.. skipping deactivation.', 0, 'import', __FUNCTION__, $this);
            return;
        }
        
        if (is_multisite()) {
            $this->handleMultisiteDeactivationSequence();
        } else {
            $this->handleSingleSiteDeactivationSequence();
        }
        
        $this->getSystemFunctions()->restoreCurrentBlog();
    }
    
    /**
     * Handle multisite Prime Mover deactivation sequence
     */
    protected function handleMultisiteDeactivationSequence()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $maybefree_network_active = is_plugin_active_for_network(PRIME_MOVER_DEFAULT_FREE_BASENAME);
        $maybepro_network_active = is_plugin_active_for_network(PRIME_MOVER_DEFAULT_PRO_BASENAME);        
        if ($maybefree_network_active && $maybepro_network_active) {
            
            $this->handleSingleSiteDeactivationSequence();
            return;
        }        
        
        if ($maybefree_network_active) {
            do_action('prime_mover_log_processed_events', 'Free version network activated - deactivating PRO version at this switched subsite.', 0, 'import', __FUNCTION__, $this);
            $this->getSystemFunctions()->deactivatePlugins(PRIME_MOVER_DEFAULT_PRO_BASENAME, true);
            return;
        }
        
        if ($maybepro_network_active) {
            do_action('prime_mover_log_processed_events', 'PRO version network activated - deactivating FREE version at this switched subsite.', 0, 'import', __FUNCTION__, $this);
            $this->getSystemFunctions()->deactivatePlugins(PRIME_MOVER_DEFAULT_FREE_BASENAME, true);
            return;            
        }        
    }
    
    /**
     * Handle single site Prime Mover deactivation sequence
     */
    protected function handleSingleSiteDeactivationSequence()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if ($this->isCustomer()) {
            do_action('prime_mover_log_processed_events', 'Logged-in customer deactivating free version.', 0, 'import', __FUNCTION__, $this);
            $this->getSystemFunctions()->deactivatePlugins(PRIME_MOVER_DEFAULT_FREE_BASENAME, true);
        } else {
            do_action('prime_mover_log_processed_events', 'Non-customer logged-in deactivating pro version.', 0, 'import', __FUNCTION__, $this);
            $this->getSystemFunctions()->deactivatePlugins(PRIME_MOVER_DEFAULT_PRO_BASENAME, true);
        }
    }
       
    /**
     * Add both Prime Mover versions
     * This is to prevent from running into a situation where no Prime Mover
     * is activated after import due to differing versions.
     * @param array $export_system_footprint
     * @return void|string
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itAddsBothPrimeMoverVersionsToPlugins()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itDoesNotAddAnythingIfNotAuthorized()
     */
    public function addBothPrimeMoverVersionsToPlugins($export_system_footprint = [])
    {
        if ( ! $this->getSystemAuthorization()->isUserAuthorized()) {
            return $export_system_footprint;
        } 
        if (empty($export_system_footprint['plugins'][PRIME_MOVER_DEFAULT_FREE_BASENAME])) {
            $export_system_footprint['plugins'][PRIME_MOVER_DEFAULT_FREE_BASENAME] = PRIME_MOVER_VERSION;
        }
        if (empty($export_system_footprint['plugins'][PRIME_MOVER_DEFAULT_PRO_BASENAME])) {
            $export_system_footprint['plugins'][PRIME_MOVER_DEFAULT_PRO_BASENAME] = PRIME_MOVER_VERSION;
        }
        return $export_system_footprint;
    }
    
    /**
     * Redirect to external contact page
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itRedirectsToExternalContactPage()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itDoesNotRedirectIfNotContactPage()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itDoesNotProcessRedirectionsIfNotAuthorized() 
     */
    public function redirectToExternalContactPage()
    {
        if ( ! $this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }       
        $args = [
            'page' => $this->getSystemFunctions()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter()
        ];
        
        $contact = $this->getSystemFunctions()->getSystemInitialization()->getUserInput('get', $args, 'verify_contact_page', '', 0, true, false);
        if (empty($contact['page'])) {
            return;
        }
        $contact_page = $contact['page'];
        if ( 'migration-panel-settings-contact' === $contact_page) {
            $this->redirecToSiteContact();
        }
    }
    
    /**
     * Redirect to site contact page
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itRedirectsToExternalContactPage()
     */
    protected function redirecToSiteContact()
    {
        wp_redirect( CODEXONICS_CONTACT );
        exit;
    }
    
    /**
     * Output support menu
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itOutputsContactSupportMenuIfCustomer() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itOutputsWordPressForumIfNotCustomer() 
     */
    public function outputSupportMenu() {
        if ( $this->isCustomer()) {
            remove_submenu_page( 'migration-panel-settings', 'migration-panel-settings-wp-support-forum' );
        }       
    }
    
    /**
     * Check if customer is paying through Freemius
     * @return boolean
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itReturnsFalseIfNonCustomer()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itReturnsTrueIfHasAnyActiveLicenseOnMultisite()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverFreemiusIntegration::itReturnsFalseIfDoesNotHaveAnyActiveLicenseOnMultisite()
     */
    private function isCustomer()
    {        
        if (plugin_basename(PRIME_MOVER_MAINPLUGIN_FILE) !== PRIME_MOVER_DEFAULT_PRO_BASENAME) {
            return false;
        }        
        
        if (is_multisite()) {
            return $this->getFreemius()->has_any_active_valid_license();
        } else {
            return $this->getFreemius()->is_paying_or_trial();
        }                  
    }
    
    /**
     * Check if Multisite network admin screens
     * @return boolean
     */
    private function isNetworkAdmin()
    {
        return (is_multisite() && is_network_admin());
    }
    
    /**
     * Render dashboard markup
     * @param string $migration_tools
     * @param string $target
     * @param string $plan
     * @param boolean $pro
     * @param string $upgrade_url
     * @param string $support
     * @param string $contact_us
     * @param string $settings
     */
    private function renderDashboardMarkup($migration_tools = '', $target = '', $plan = '', $pro = false, $upgrade_url = '', $support = '', $contact_us = '', $settings = '')
    {
        if ( ! $migration_tools || ! $target || ! $plan || ! $upgrade_url || ! $support || ! $contact_us || ! $settings ) {
            return;
        }
        ?>
        <div class="wrap">
          <div class="card">
          <h2><?php esc_html_e( 'Getting Started', 'prime-mover' ); ?></h2>
               <div class="notice-large highlight">                    
                   <p><?php                    
                   printf(
                       wp_kses(
                           /* translators: %1$s: Formatted text title name string of the plugin package plan, %2$s: Destination text description label pointing to the target dashboard link area */
                           __( 'Thank you for using <strong>%1$s</strong> ! Start migrating now by going to %2$s:', 'prime-mover' ),
                           [ 'strong' => [] ]
                       ),
                       esc_html( $plan ),
                       esc_html( $target )
                   );
                   ?> 
                   </p> 
               </div>                                      
               <p><a href="<?php echo esc_url($migration_tools);?>" class="button button-primary"><?php 
               /* translators: %s: Destination text description label pointing to the target dashboard link area */
               printf( esc_html__('Go to %s', 'prime-mover'), esc_html($target)); 
               ?></a></p>                     
         <h2><?php esc_html_e( 'Packages', 'prime-mover' ); ?></h2> 
              <?php 
              $backups_menu_url = $this->getSystemFunctions()->getBackupMenuUrl();
              ?>                
              <div class="notice-large highlight">
                    <p><?php esc_html_e('Manage your packages - download, export, restore, delete, etc.', 'prime-mover'); ?>.</p>
               </div>                                      
                     <p><a href="<?php echo esc_url($backups_menu_url);?>" class="button button-primary"><?php esc_html_e('Go to Packages', 'prime-mover'); ?></a></p>                                                     
          </div>   
          
          <?php if ( ! $pro ) : ?>           
           <div class="card">                 
                <?php                   
                    $free_trial = $this->getFreemius()->get_upgrade_url('annual', true);                    
                    $heading = esc_html__('Upgrade to Pro Version', 'prime-mover');
                    if ('yes' === $this->hasUsableLicense() && false === $this->getSystemFunctions()->getSystemInitialization()->isUsingFreeCode()) {
                        $heading = esc_html__('Activate Pro Version', 'prime-mover');
                    }
                ?>                    
                <h2><?php echo esc_html($heading); ?></h2>             
                 <div class="notice-large highlight">
                         <?php if (is_multisite() ) : ?>  
                              <p>
                                  <?php 
                                  esc_html_e( 'Migrate faster and secure your migration with database / media files encryption. Plus many more useful features you can get with Pro version. Click the button below to compare FREE and PRO plans.', 'prime-mover' );
                                  ?>
                             </p>        
                         <?php else : ?>                                                  
                              <p>
                                  <?php 
                                  if ('' === $this->hasUsableLicense()) {                                      
                                      printf(
                                          wp_kses(
                                              /* translators: %s: 14-days FREE trial registration page landing URL destination path string */
                                              __( 'Migrate faster and secure your migration with database / media files encryption. <a href="%s">Start your 14-days FREE trial now.</a>', 'prime-mover' ),
                                              [ 'a' => [ 'href' => true ] ]
                                          ),
                                          esc_url( $free_trial )
                                      );
                                  } else {
                                      esc_html_e( 'Migrate faster and secure your migration with database / media files encryption.', 'prime-mover' );
                                  }
                                   ?> 
                              </p>                                                    
                         <?php endif; ?>
                 </div>                  
                     <?php 
                         $upgrade_url = apply_filters('prime_mover_filter_upgrade_pro_url', $upgrade_url);
                         $upgrade_text = apply_filters('prime_mover_filter_upgrade_pro_text', esc_html__('Upgrade to Pro Version', 'prime-mover') , 0, false);
                     ?>                    
                     <p><a href="<?php echo esc_url($upgrade_url);?>" class="button button-primary"><?php echo esc_html($upgrade_text); ?></a></p>   
                     <?php $this->outputSupportAndDocumentationMarkup($pro, $support, $contact_us); ?>              
          </div>         
          <?php endif; ?>
  
            <?php if ( $pro ) : ?>
           <div class="card">                      
               <h2><?php esc_html_e( 'Settings', 'prime-mover' ); ?></h2>             
                 <div class="notice-large highlight">
                     <p><?php                      
                     printf(
                         wp_kses(
                             /* translators: %s: Formatted text title name string of the plugin package plan */
                             __( '<strong>%s</strong> includes settings page. Please take a moment to review these settings and make sure they are correct.', 'prime-mover' ),
                             [ 'strong' => [] ]
                         ),
                         esc_html( $plan )
                     );
                     ?>
                     </p> 
                 </div>                                      
                 <p><a href="<?php echo esc_url($settings);?>" class="button button-primary"><?php esc_html_e( 'Go to Settings', 'prime-mover' ); ?></a></p>   
                 <?php $this->outputSupportAndDocumentationMarkup($pro, $support, $contact_us); ?>
          </div>    
          <?php endif; ?> 
	   </div>
	   <?php  
    }
    
    /**
     * Output Support and documentation block
     * @param boolean $pro
     * @param string $support
     * @param string $contact_us
     */
    private function outputSupportAndDocumentationMarkup($pro = false, $support = '', $contact_us = '')
    {
        ?>
        <h2><?php esc_html_e( 'Support and Documentation', 'prime-mover' ); ?></h2>             
            <div class="notice-large highlight">
                <?php if ( ! $pro ) : ?>
                    <p><?php                    
                    $free_msg = sprintf(
                        /* translators: %1$s: Dynamically injected support desk tracking info statement text or link anchor element, %2$s: Codexonics documentation directory reference URL address string link path */
                        __( '%1$s. You can also read <a target="_blank" class="prime-mover-external-link" href="%2$s">documentation here</a>.', 'prime-mover' ),
                        $support,
                        esc_url( CODEXONICS_DOCUMENTATION )
                    );
                    
                    echo wp_kses(
                        $free_msg,
                        [
                            'a' => [
                                'target' => true,
                                'class'  => true,
                                'href'   => true,
                                'title'  => true,
                                'id'     => true,
                            ],
                        ]
                    );
                    ?>
                     <?php esc_html_e('Please rate us in WordPress.org. Thank you!', 'prime-mover'); ?>
                    </p>
                     <?php endif; ?> 
                     <?php if ($pro) : ?>
                      <p><?php                       
                      $pro_msg = sprintf(
                          /* translators: %1$s: Dynamically injected support desk tracking info statement text or link anchor element, %2$s: Codexonics documentation directory reference URL address string link path */
                          __( '%1$s. You can also read <a target="_blank" class="prime-mover-external-link" href="%2$s">documentation here</a>. Contact us if you like to report bugs, etc.', 'prime-mover' ),
                          $support,
                          esc_url( CODEXONICS_DOCUMENTATION )
                      );
                      
                      echo wp_kses(
                          $pro_msg,
                          [
                              'a' => [
                                  'target' => true,
                                  'class'  => true,
                                  'href'   => true,
                                  'title'  => true,
                                  'id'     => true,
                              ],
                          ]
                      );
                     ?>
                     </p>                        
                     <?php endif; ?>
            </div> 
           <?php if ( ! $pro ) : ?>                                    
               <p><a href="https://wordpress.org/plugins/prime-mover/" class="button button-primary"><?php esc_html_e( 'Rate us', 'prime-mover' ); ?></a></p> 
           <?php endif; ?>  
           <?php if ( $pro ) : ?>                                    
               <p><a href="<?php echo esc_url($contact_us);?>" class="button button-primary"><?php esc_html_e( 'Contact us', 'prime-mover' ); ?></a></p> 
           <?php endif; ?>                                           
    <?php     
    }
    
    /**
     * Get settings URL
     * @return string
     */
    public function getSettingsPageUrl()
    {
        $settings = admin_url( 'admin.php?page=migration-panel-basic-settings');
        if ($this->isNetworkAdmin()) {
            $settings = network_admin_url( 'admin.php?page=migration-panel-basic-settings');
        }
        return $settings;
    }
    
    /**
     * Show Getting started guide on Free users
     */
    public function showGettingStartedOnFreeUsers()
    {
        $pro = false;
        $plan = "Prime Mover Free version";        
        
        $support = sprintf(
        wp_kses(
        /* translators: %s: Codexonics community support forum reference URL link address string */
        __( '<a target="_blank" class="prime-mover-external-link" href="%s">Community support</a> is available with free version.', 'prime-mover' ),
        [
        'a' => [
        'target' => true,
        'class'  => true,
        'href'   => true,
        ],
        ]
        ),
        'https://wordpress.org/support/plugin/prime-mover/'
            );
        $settings = "#";
        
        if ($this->isCustomer()) {
            $plan = "Prime Mover Pro version";
            $support = esc_html__( 'Technical support is included with Pro version', 'prime-mover' );
            
            $pro = true;
            $settings = $this->getSettingsPageUrl();
        }
        
        $upgrade_url = $this->getFreemius()->get_upgrade_url();
        $migration_tools = admin_url( 'tools.php?page=migration-tools');
        
        $contact_us = $this->getSystemFunctions()->getSystemInitialization()->getContactUsPage();
        $target = esc_html__( 'Migration Tools', 'prime-mover' );
        
        if (is_multisite() && is_network_admin()) {
            $migration_tools = network_admin_url( 'sites.php');
            $target = esc_html__( 'Network Sites', 'prime-mover' );
        }
        
        $this->renderDashboardMarkup($migration_tools, $target, $plan, $pro, $upgrade_url, $support, $contact_us, $settings);
    }
    
    /**
     * Checks if customer
     * @param boolean $logged_user_check
     * @return boolean
     */
    public function maybeLoggedInUserIsCustomer($logged_user_check = true)
    {
        if (!is_user_logged_in() && $logged_user_check) {
            return true;
        }     
        
        return ($this->isCustomer());
    }
    
    /**
     * Restore freemius settings on error
     */
    public function restoreFreemiusSettingsOnError()
    {
        $blog_id = $this->getShutDownUtilities()->primeMoverGetProcessedID();
        $this->restoreFreemiusOptions($blog_id);        
    }
        
    /**
     * Backup freemius settings during import
     * @param array $ret
     * @param number $blogid_to_import
     */
    public function backupFreemiusOptionsImport($ret = [], $blogid_to_import = 0)
    {
        $this->backupFreemiusOptions($blogid_to_import);        
    }
    
    /**
     * Restore freemius settings during import
     * @param array $ret
     * @param number $blogid_to_import
     */
    public function restoreFremiusOptionsImportMultisite($ret = [], $blogid_to_import = 0)
    {
        if (!is_multisite()) {
            return;
        }
        $this->restoreFreemiusOptions($blogid_to_import);
    }
    
    /**
     * Checks if multisite subsite is licensed using API
     * On single-site, it's enough to check if the user is a customer
     * This should work for both single-sites and multisites
     * @param boolean $ret
     * @param number $blog_id
     * @return boolean
     */
    public function maybeBlogIDLicensed($ret = false, $blog_id = 0)
    {
        return $this->isBlogLicensed($blog_id);
    }

    /**
     * Is blog ID licensed in multisite?
     * @param number $blog_id
     * @return boolean
     */
    protected function isBlogLicensed($blog_id = 0)
    {
        if (!is_multisite() && $this->isCustomer()) {
            return true; 
        }
        if (!$this->isCustomer()) {
            return false;    
        }
        if (!$this->getSystemFunctions()->blogIsUsable($blog_id)) {
            return false;
        }
        $freemius = $this->getFreemius();
        if ( ! method_exists($freemius, 'get_install_by_blog_id') ) {
            return false;
        }
        $install = $this->getFreemius()->get_install_by_blog_id($blog_id);       
        if ( ! is_object($install) || ! \FS_Plugin_License::is_valid_id( $install->license_id )) {
            return false;
        }            
        return true;
    }
    
    /**
     * Backup Freemius related options before dB processing
     * @param number $blog_id
     */
    private function backupFreemiusOptions($blog_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $settings_array = [];
        $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();
        $current_options = $this->getAllFreemiusSDKOptions($blog_id);
        if ( ! is_array($current_options) ) {
            return;
        }
        
        $this->setFreemiusOptions($current_options);
        foreach ($current_options as $option) {
            $this->getSystemFunctions()->switchToBlog($blog_id);
            $settings_array[$option] = get_option($option);
            $this->getSystemFunctions()->restoreCurrentBlog();
        }
        
        $meta_key = $this->getFreemiusMetaKey(self::FREEMIUS_USERKEY, $blog_id, $current_user_id);
        do_action('prime_mover_update_user_meta', $current_user_id, $meta_key, $settings_array); 
    }

    /**
     * Get all Freemius SDK options
     * @param number $blog_id
     * @param boolean $network
     * @return array|mixed[]
     */
    private function getAllFreemiusSDKOptions($blog_id = 0, $network = false)
    {        
        if (!is_multisite()) {
            $network = false;
        }
        
        if (!$network) {
            $this->getSystemFunctions()->switchToBlog($blog_id);
        }
        $wpdb = $this->getSystemFunctions()->getSystemInitialization()->getWpdB();        
        $affected_options = [];
        
        if ($network) {
            $options_query = "SELECT meta_key FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'fs_%'";  
        } else {
            $options_query = "SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE 'fs_%'";            
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $option_query_results = $wpdb->get_results($options_query, ARRAY_N);        
        
        if (!is_array($option_query_results) || empty($option_query_results)) {
            if (!$network) {
                $this->getSystemFunctions()->restoreCurrentBlog();
            }            
            return [];
        }
        
        foreach ($option_query_results as $v) {
            if (!is_array($v)) {
                continue;
            }
            $affected_options[] = reset($v);
        }
        
        if (!$network) {
            $this->getSystemFunctions()->restoreCurrentBlog(); 
        }
        
        return $affected_options;
    }
    
    /**
     * Delete all freemius options
     * @param number $blog_id
     * @param boolean $network
     */
    private function deleteAllFreemiusOptions($blog_id = 0, $network = false)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        if (!is_multisite()) {          
            $network = false;
        }
        
        $current_options = $this->getAllFreemiusSDKOptions($blog_id, $network);
        if (!is_array($current_options) ) {
            return;
        }        
        foreach ($current_options as $option) {         
            if ($network) {
                $this->getSystemFunctions()->deleteSiteOption($option, false, '', true, true);
            } else {
                $this->getSystemFunctions()->switchToBlog($blog_id);
                delete_option($option);
                $this->getSystemFunctions()->restoreCurrentBlog();
            }          
        }
    }
    
    /**
     * Restore Freemius settings after dB processing
     * @param number $blog_id
     */
    public function restoreFreemiusOptions($blog_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();
        $meta_key = $this->getFreemiusMetaKey(self::FREEMIUS_USERKEY, $blog_id, $current_user_id);
        
        $current_settings = get_user_meta($current_user_id, $meta_key, true);
        if (empty($current_settings)) {
            return;
        }
        
        $this->deleteAllFreemiusOptions($blog_id);
        foreach ($current_settings as $option_name => $option_value) {
            $this->getSystemFunctions()->switchToBlog($blog_id);
            update_option($option_name, $option_value);
            $this->getSystemFunctions()->restoreCurrentBlog();
        }        
        delete_user_meta($current_user_id, $meta_key);
    } 
    
    /**
     * Maybe set object cache
     * @param string $value
     * @param string $obj_key
     * @return string
     */
    protected function maybeSetObjectCache($value = 'no', $obj_key = 'prime_mover_maybe_has_usable_license')
    {
        wp_cache_set($obj_key, $value);
        return $value;        
    }
    
    /**
     * Checks if has usable license
     * Returns 'yes' if usable
     * Returns 'no' if available however not usable (expired, or out of quota)
     * Returns '' if no license is being set or used.
     * @return string|mixed|boolean
     */
    public function hasUsableLicense()
    {        
        $obj_key = 'prime_mover_maybe_has_usable_license';
        $result = wp_cache_get($obj_key);
        if (false === $result) {            
            $obj = $this->getLicense();
            if (!$this->getSystemAuthorization()->isUserAuthorized() || is_null($obj)) {
                return $this->maybeSetObjectCache('', $obj_key);
            }
            
            if (false === $obj) {
                return $this->maybeSetObjectCache('no', $obj_key);
            }   
            
            if ('' === $obj || !is_object($obj)) {                
                return $this->maybeSetObjectCache('', $obj_key);
            }                              
            
            if($obj instanceof FS_Plugin_License) {
                return $this->maybeSetObjectCache('yes', $obj_key);
            }            

            return $this->maybeSetObjectCache('', $obj_key);
        }
        
        return $result;  
    }
    
    /**
     * Get license
     * @return NULL|string|boolean|FS_Plugin_License|boolean
     * Returns null if unable to compute due ot missing dependencies
     * Returns empty string if license object is not yet Set
     * Returns boolean FALSE if license object is set but unusable
     * Returns license object if set and usable 
     * @param boolean $can_activate_check
     * @return NULL|boolean|FS_Plugin_License|string|FS_Plugin_License|boolean
     */
    public function getLicense($can_activate_check = true) 
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return null;
        }  
        
        $freemius = $this->getFreemius();
        if (!is_object($freemius)) {
            return null;    
        }        
        
        $usable_license = false;
        if (method_exists($freemius, '_get_license')) {
            $usable_license = $freemius->_get_license();
        }
        
        if (is_object($usable_license) && false === $can_activate_check) {
            return $usable_license;            
        }
        
        if (!method_exists($freemius, '_get_available_premium_license')) {
            return null;    
        }
        
        if (!method_exists($freemius, 'get_site')) {
            return null;
        }
        
        $site = $freemius->get_site();
        if (!is_object($site)) {            
            return null;
        }
        
        if (!method_exists($site, 'is_localhost')) {
            return null;
        }
        
        $is_localhost = $site->is_localhost();  
        if (!method_exists($freemius, 'has_any_license')) {
            return null;
        }
        
        $has_any_license = $freemius->has_any_license();
        if (false === $has_any_license) {
            return '';
        }
        
        return $freemius->_get_available_premium_license($is_localhost);        
    }
    
    /**
     * Checks if white labeled
     * @return boolean
     */
    public function isWhiteLabeled()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return false;
        } 
        
        $freemius = $this->getFreemius();
        if (!is_object($freemius)) {
            return false;
        }
        
        if (!method_exists($freemius, 'is_whitelabeled')) {
            return false;
        }
        
        return $freemius->is_whitelabeled();
    }
    
    /**
     * Returns TRUE if pricing page otherwise FALSE
     * @return boolean
     */
    public function isPricingPage()
    {
        $current_screen = get_current_screen();
        if (!is_object($current_screen)) {
            return false;    
        }
        
        if (!isset($current_screen->id)) {
            return false;    
        }        
        
        $id = $current_screen->id;
        if (empty($id)) {
            return false;
        }
        
        return in_array($id, $this->getPricingPageIds());        
    }
    
    /**
     * Get Freemius meta key
     * @param string $meta_key
     * @param number $blog_id
     * @param number $user_id
     * @return string
     */
    private function getFreemiusMetaKey($meta_key = '', $blog_id = 0, $user_id = 0)
    {
        if (!is_multisite()) {
            $blog_id = 1;    
        }

        return "{$meta_key}_b{$blog_id}_u{$user_id}";
    }        
}