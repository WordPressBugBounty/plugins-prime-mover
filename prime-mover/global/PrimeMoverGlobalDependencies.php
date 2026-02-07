<?php
/****************************************************
 * PRIME MOVE GLOBAL DEPENDENCIES
 * Gets the requisite check instance
 * **************************************************
 */

if (!defined('ABSPATH')) {
    exit;
}

class PrimeMoverGlobalDependencies
{
    /**
     * Get Prime Mover requisites check instance
     * @return PrimeMoverRequirementsCheck
     */
    public function primeMoverGetRequisiteCheck()
    {
        global $prime_mover_plugin_manager;
        if (is_object($prime_mover_plugin_manager) && $prime_mover_plugin_manager->primeMoverMaybeLoadPluginManager()) {
            return true;
        }
        
        if (wp_doing_ajax()) {
            return true;
        }
        
        if (is_multisite() && !is_network_admin()) {
            return true;
        }
        
        $phprequirement = '5.6';
        
        $phpverdependency = new PrimeMoverPHPVersionDependencies($phprequirement);
        $wpcoredependency = new PrimeMoverWPCoreDependencies('4.9.8');
        $phpfuncdependency = new PrimeMoverPHPCoreFunctionDependencies();
        $foldernamedependency = new PrimeMoverPluginSlugDependencies(array(PRIME_MOVER_DEFAULT_FREE_BASENAME, PRIME_MOVER_DEFAULT_PRO_BASENAME));
        $coresaltdependency = new PrimeMoverCoreSaltDependencies();
        
        $required_paths = array(
            PRIME_MOVER_PLUGIN_CORE_PATH,
            PRIME_MOVER_PLUGIN_PATH,
            PRIME_MOVER_THEME_CORE_PATH,
            get_stylesheet_directory(),
            WPMU_PLUGIN_DIR,
            PRIME_MOVER_PLUGIN_MANAGER_SCRIPT
        );
        
        if (defined('WP_CONTENT_DIR')) {
            array_unshift($required_paths, WP_CONTENT_DIR);
        }
        
        $wp_upload_dir = primeMoverGetUploadsDirectoryInfo();
        
        $basedir = '';
        $export_dir = '';
        $import_dir = '';
        $tmp_dir = '';
        $lock_dir = '';
        
        if (!empty($wp_upload_dir['basedir'])) {
            $basedir = $wp_upload_dir['basedir'];
            $required_paths[] = $basedir;
        }
        
        if (!empty($wp_upload_dir['path'] ) )  {
            $required_paths[] = $wp_upload_dir['path'];
        }
        
        if ($basedir) {
            $basedir = trailingslashit($basedir);
            $export_dir = wp_normalize_path($basedir . PRIME_MOVER_EXPORT_DIR_SLUG);
            $import_dir = wp_normalize_path($basedir . PRIME_MOVER_IMPORT_DIR_SLUG);
            $tmp_dir = wp_normalize_path($basedir . PRIME_MOVER_TMP_DIR_SLUG);
            $lock_dir = wp_normalize_path($basedir . PRIME_MOVER_LOCK_DIR_SLUG);
        }
        
        
        if ($export_dir && is_dir($export_dir)) {
            $required_paths[] = $export_dir;
        }
        
        if ($import_dir && is_dir($import_dir)) {
            $required_paths[] = $import_dir;
        }
        
        if ($tmp_dir && is_dir($tmp_dir)) {
            $required_paths[] = $tmp_dir;
        }
        
        if ($lock_dir && is_dir($lock_dir)) {
            $required_paths[] = $lock_dir;
        }        
        
        $filesystem_dependency = new PrimeMoverFileSystemDependencies($required_paths);
        return new PrimeMoverRequirementsCheck($phpverdependency, $wpcoredependency, $phpfuncdependency, $filesystem_dependency, $foldernamedependency, $coresaltdependency);
    }    
}