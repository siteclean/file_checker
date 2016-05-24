=== Plugin Name ===
Contributors: mstan
Donate link: https://siteclean.pro
Tags: security, file control
Requires at least: 3.0.1
Tested up to: 4.5.2
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Control your site`s files integrity, create and manage backups. 

== Description ==

After installing the plugin, you need to check settings page first:
- Email for reports. Your email which will be used for reports. By default - admin`s email from database
- Directory for scan. Absolute path to be scanned. Must be accessible and readable for script.
- Directory for data file to be stored (must be writable for script). Where data file (file that contains hashes of your site`s files) will be stored. By default set as site`s root directory, but it is better for you to set the directory not accessible from web.
- Files with these extensions will be scanned. Only files with these extensions will be scanned. You may change it a way you need.
- Files to be excluded from scan. This options lets you exlude some files from monitoring. For example, cache files that can be changed offen. Set full absolut path using comma separator. For example: /var/www/site.com/cache.php, /var/www/site.com/log.php
- Directories to be excluded from scan. This directory will be excluded from scan. Warning: if you set /var/www/site.com/cache for exclusion, the subdirectory /var/www/site.com/cache/cache2 will be scanned! Add directories with comma separator.

In "Update settings" you may set your settings in way your need. After updating settings you will receive the letter about it.

After checking and updating settings - press button "scan" to create data file. It takes some seconds to do. After creating you will receive an email about it.
And message like 
"Scan continued for 0.95 seconds 
1279 files were scanned and added to data file!"

Now your system files can be scanned for changes. There are two ways to scan:
1) manual. Use wp-admin/admin.php?page=FC_main to access the manual scan options. Press "Check" to scan system manually
2) auto, using wp-cron function. Set your checking frequency and get reports

Manage_backups page lets you to manage your backup files. Here you can create file/database backup, download it to your home PC and restore it if you need. For working needs PHP functions to be available (any one of them): system, exec, shell_exec or passthru.

SC_filechecker lets you to set cron frequency and launch manual checking.


Thats all. Now if some files are changed or some new files appeared in your site you will receive an email about it. It will contains full path to the file, so you will know if changes are made by you (for example, WordPress plugin update) or some evil person :).
If changed are legal (update process or etc), just rescan the system to update file info in datafile.




== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/sc_filechecker` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the File checker - Settings screen to configure the plugin



== Frequently Asked Questions ==

= What is this plugin for? =

This plugins lets you control file integrity. Will be usefull for site administrators to monitor file changes. For example, if some evil man hacks your site, this plugin will let you find all changes made by hacker and restore it.
Also this plugins lets you create/restore your own file/database backups.

= Where can I get plugin`s password? =

After activating the plugin password will be sent to admin_email. You can change it manually by editing variable $cache_pass in plugin_dir/cache/settings_cache.php




== Changelog ==

Changelog:

0.5:
- added wp-crom using instead of system cron function. Now you can set checking frequency by one click (once/twice per day or once per hour), without accessing cron jobs of your server.
- now plugin generates it`s password while activation. You might change it inside plugin_dir/cache/settings_cache.php (var $cache_pass)
- some minor function changes

0.42:
- removed direct including of core files. Created settings_cache.php, which contains needed settings for script and lets reduce the number of queries to the database
- some minor function changes

0.41:
- some parametr checks added, fixed possible errors (wrong pathes, incorrect variables etc)

0.4:
- now plugin can create file`s and database backups, using nix commands tar/mysqldump. Creating dumps with Windows based servers is not supported.
- _wpnonce added for better safety.

0.3:
- most of functions were recoded for better result. Now creating data-file for site with about 1.2 thousand files continues for about 2 seconds.

0.2:
- added password for cron launch;
- some mistakes found and fixed;

0.1
- first release

