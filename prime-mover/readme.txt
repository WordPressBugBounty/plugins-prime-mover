=== Prime Mover - Backup and Migration	 ===
Contributors: codexonics, freemius
Donate link: https://codexonics.com
Tags: migrate wordpress, multisite migration, clone, backup
Requires at least: 5.2
Tested up to: 7.1
Requires PHP: 5.6
Stable tag: 2.2.0
License: GPLv3 or later
License URI: https://codexonics.com

The simplest all-around plugin to migrate WordPress and do backups. These support multisite backup/migration or cloning a WP site/multisite subsite.

== Description ==

= Easily Transfer WordPress Site to New Host/Server/Domain =

*   Move single-site installation to another single-site server.
*   Move WP single-site to existing multisite sub-site.
*   Migrate the subsite to another multisite subsite.
*   Migrate multisite sub-site to single-site.
*   Migrate within WordPress admin.
*   WordPress backup and restore packages within single-site or multisite.
*   Backup WordPress subsite (in multisite).
*   You can back up the WordPress database within admin before testing something and restoring it with one click.
*   Cross-platform compatible (Nginx / Apache / Litespeed / Microsoft IIS / Localhost).
*   Clone a single site and restore it to any server.
*   Clone subsite in multisite and restore it as single-site or multisite.
*   Supports legacy multisites.
*   Debug package.
*   Supports backup of the non-UTF8 single-site or multisite database.

https://youtu.be/QAVVXcoQU8g

= PRO Features =

*   Scheduled backups: Automatic backup support for multisite and single-site.
*   Save time during migration with the direct site-to-site package transfer.
*   Move the backup location outside the WordPress public directory for better security.
*   Migrate or backup WordPress multisite main site.
*   Encrypt WordPress database in backups for maximum data privacy.
*   Encrypt the WordPress media directory inside the backup for better security.
*   Encrypt plugin and theme files inside the backup/package for protection.
*   Export and restore the backup package from Dropbox.
*   Save and restore packages from and to Google Drive.
*   Exclude plugins from the backup (or network-activated plugins if multisite).
*   Exclude upload directory files from the backup to reduce the package size.
*   Improved migrated site performance by excluding unused database tables created by inactive plugins.
*   Create a new multisite subsite with a specific blog ID.
*   Disable network maintenance in multisite so only the affected subsite is in maintenance mode.
*   Configure migration parameters to optimize and tweak backup/migration packages.
*   It includes all complete restoration options at your own choice and convenience.
*   Full access to the settings screen to manage all basic and plugin advanced configurations.
*   Migrate non-UTF8 database charset to standard UTF8 database charset (utf8mb4).
*   Migrate UTF8 database charset (utf8mb4) to non-UTF8 database charset (edge case scenario).

= Documentation =

*	[Prime Mover Documentation](https://codexonics.com/prime_mover/prime-mover/)

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Optionally, opt-in to security and feature update notifications, including non-sensitive diagnostic tracking, with freemius.com. If you skip this, that's okay! Prime Mover will still work just fine.
4. You should see the Prime Mover Control Panel. Click "Go to Migration Tools" to start migrating sites.

== Frequently Asked Questions ==

= What makes Prime Mover unique to other existing migration and backup plugins? =

* The free version has no restrictions on package size, number of websites, or migration mode (single-site or multisite will work). (Note: Exporting/Restoring a multisite main site is a PRO feature)
* The free version supports WordPress multisite migration on any number of subsites, except that exporting/restoring the multisite main site is a PRO feature.
* It can back up WordPress multisite sub-sites or migrate multisite.
* No need to delete your WordPress installation, create/delete the database, and all other technical stuff. It will save you a lot of time.
* This is not hosting-dependent. Prime Mover is designed to work with any hosting company you choose.
* The free version has full multisite migration functionality, usually missing in most migration plugins' free versions.
* Full versatility—migrate from your local host dev site or from a live site to another live site.
* The entire migration will be done inside the WordPress admin. Anyone with administrator access can do it. Hiring a freelancer to do the job is unnecessary and will save you money.
* You won't have to mess with complicated migration settings; the free version has built-in settings. You can choose only a few options to export and migrate—that's it.
* You can save, download, delete, and migrate packages using the management page.
* No need to worry about PHP configuration and server settings. Compatible with most default PHP server settings, even in limited shared hosting.
* Prime Mover works with modern PHP versions 5.6 to 8.5+ (Google Drive feature requires at least PHP 7.4).
* The code follows PHP-fig coding standards (standard PHP coding guidelines).
* The free version supports backup and restoration of non-UTF8 sites. However, you need the PRO version to migrate non-UTF8 to the UTF8 (utf8mb4) database charset and vice versa.
* After migration, you don't need to worry about setting up users or changing passwords. It does not overwrite existing site users.

For more common questions, please read the [plugin FAQ listed on the developer site](https://codexonics.com/prime_mover/prime-mover/faq/).

== Screenshots ==

1. Single-site Migration Tools
2. Export options dialog
3. Export to single-site format example
4. Export to multisite subsite with blog ID of 23 example
5. Restore package via browser upload
6. Single-site package manager
7. Prime Mover network control panel
8. Export and restore packages from Network Sites
9. Multisite network package manager

== Upgrade Notice ==

Update now to get all the latest bug fixes, improvements, and features!

== Changelog ==

= 2.2.0 =

* Performance: Improve overall export and restore log performance by reducing file sizes.
* Deprecated: Remove PRIME_MOVER_PLUGIN_MANAGER_LOG constant and primeMoverMaybeEnablePluginManagerLog() for better security/performance.
* Compatibility: Fixed high-severity issues reported by Plugin Check.
* Fixed: translation mechanism errors in some files.
* Fixed: Outdated clipboard JS library.
* Fixed: Issues with fonts and resources rendering in CORS.
* Fixed: Refactored restore query handling for better performance and security.
* Fixed: Updated Freemius SDK library to the latest version.
* Compatibility: Moved the Freemius library to the vendors directory for better compliance with guidelines.
* Usability: Reduce the plugin's total zip file size to make it easier to use in limited shared environments.
* Security: Fixed remaining unescaped outputs in direct plugin HTML.
* Compatibility: Bump the minimum supported WordPress version to 5.2.
* Compatibility: Tested for compatibility with WordPress 7.1 release.

= 2.1.6 =

* Compatibility: Better compatibility with multisite parallel import and restore. 
* Fixed: Uncaught TypeError mysqli_num_rows(): Argument #1 ($result) must be of type mysqli_result during search replace. 
* Fixed: Missing or corrupted Freemius data after simultaneous restore. 

= 2.1.5 =

* Fixed: Interrupted copying of backups to the custom directory on resource-limited servers.  
* Fixed: Runtime error during differential restore when data contains double quotes. 

See the previous changelogs in changelog.txt.
