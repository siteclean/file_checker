=== Plugin Name ===
Contributors: mstan
Donate link: https://siteclean.pro
Tags: security, file control
Requires at least: 3.0.1
Tested up to: 4.5.3
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Needs PHP 5.2.0 and higher for fully functionality


Control your site`s files integrity, create and manage backups. 

== Description ==

Plugins lets you to control your site`s files integrity, create and restore files/databases from backups.

After installing the plugin, you need to check settings page first:
- Email for reports. Your email which will be used for reports. By default - admin`s email from database.
- Directory for scan. Absolute path to be scanned. Must be accessible and readable for script. By default its your wordpress`s root path.
- Files with these extensions will be scanned. Only files with these extensions will be scanned. You may change it in a way you need.
- Files to be excluded from scan. This options lets you exlude some files from monitoring. For example, cache files that can be changed offen. Set full absolut path using comma separator. For example: /var/www/site.com/cache.php, /var/www/site.com/log.php
- Directories to be excluded from scan. This directory will be excluded from scan. Warning: if you set /var/www/site.com/cache for exclusion, the subdirectory /var/www/site.com/cache/cache2 will be scanned! Add directories with comma separator.

In "Update settings" you may set your settings in a way your need. After updating settings you will receive the letter about it.


Main page admin.php?page=SC_main is the second page you need to visit after settings updated. There you have to generate data file about all files (with extensions from your settings) on your site. And after set the frequency of file checking (once per day, twice per day or once per hour). 
Now your system files can be scanned for changes. There are two ways to scan:
1) manual. Use wp-admin/admin.php?page=FC_main to access the manual scan options. Press "Check" to scan system manually
2) auto, using wp-cron function. Set your checking frequency and get reports.

!!!Don`t forget to update your data file ("generate") after installing/updating new plugins, themes, translates or core WP files.

File backups create by using php ZipArchive module (available for PHP older than 5.2.0). Database backups create by using "mysqldump" function not available at Windows servers.

!!! All backups store at /full/path/to/site/wp-content/plugins/sc_filechecker/backups/, so you can download it anytime using FTP client.
!!! Please make sure /cache/ and /backups/ directories are writable. Other way plugin will not work

Thats all. Now if some files are changed or some new files appeared in your site you will receive an email about it. It will contains full path to the file, so you will know if changes are made by you (for example, WordPress plugin update) or some evil person :).
If changed are legal (update process or etc), just rescan the system to update file info in datafile.




== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/sc_filechecker` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the File checker - Settings screen to configure the plugin
!!! Please make sure /cache/ and /backups/ directories are writable. Other way plugin will not work



== Frequently Asked Questions ==

= What is this plugin for? =

This plugins lets you control file integrity. Will be usefull for site administrators to monitor file changes. For example, if some evil man hacks your site, this plugin will let you find all changes made by hacker and restore it.
Also this plugins lets you create/restore your own file/database backups.

= Where can I get plugin`s password? =

After activating the plugin password will be sent to admin_email. You can change it manually by editing variable $cache_pass in plugin_dir/cache/settings_cache.php




== Changelog ==

Changelog:

0.6:
- now you can choose any database available for current mysql user
- Windows file backup available
- need PHP 5.2.0 and higher for file backups
- no more plugin password
- some other minor changes


0.5:
- added wp-cron using instead of system cron function. Now you can set checking frequency by one click (once/twice per day or once per hour), without accessing cron jobs of your server.
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
- most of functions were recoded for better result. Now creating data-file for site with 1.2 thousand files continues for about 2 seconds.

0.2:
- added password for cron launch;
- some mistakes found and fixed;

0.1
- first release

