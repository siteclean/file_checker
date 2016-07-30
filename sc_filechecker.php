<?php
/*
Plugin Name: SC filechecker
Plugin URI: https://siteclean.pro/wordpress-file_checker/
Description: Control your site`s files integrity, create and manage backups.
Version: 0.6
Author: siteclean
Author URI: https://siteclean.pro

	Copyright 2016  https://siteclean.pro  (email: mail@siteclean.pro)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// exit if direct access
if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}


/*
Define constants
*/

define('SC_filechecker_version', '0.6');
define('SC_file_checker_dir', str_replace('\\','/', plugin_dir_path(__FILE__)) ); // str_replace made for capability with windows pathes
define('SC_file_checker_url', plugin_dir_url(__FILE__));
define('ROOT_PATH', str_replace('\\','/', ABSPATH)); // str_replace made for capability with windows pathes
define('BACKUP_PATH', SC_file_checker_dir.'backups/');



/*
* Include cached settings from /cache/settings_cache.php if exist
*/
if( file_exists(SC_file_checker_dir.'/cache/settings_cache.php') )
{
	include(SC_file_checker_dir.'/cache/settings_cache.php');
}


/*
Class auto loading function
$class_name - must be like sc_classname.class.php
*/
function autoLoader($class_name) 
{
	$class_name = strtolower($class_name);
	if( file_exists(SC_file_checker_dir.'/classes/'.$class_name.'.class.php') )
	{
		include_once(SC_file_checker_dir.'/classes/'.$class_name.'.class.php');		
	}
	
}
spl_autoload_register('autoLoader');

// creating class objects
$sc_file = new sc_file;
$sc_tpl = new sc_tpl;
$sc_config = new sc_config;
$sc_cron = new sc_cron;
$sc_mail = new sc_mail;




// Hook for adding admin menus
add_action('admin_menu', 'SC_filechecker_file_checker');



// action function for above hook
function SC_filechecker_file_checker() 
{    
	add_menu_page('SC main page', 'SC filechecker', 'administrator', 'SC_main', 'SC_filechecker_main');
	add_submenu_page('SC_main', 'Settings', 'Settings', 'administrator', 'SC_settings', 'SC_filechecker_settings');
	add_submenu_page('SC_main', 'Backup page', 'Manage backups', 'administrator', 'SC_backup', 'SC_filechecker_backup');		
}




// hook for cron job autocheck
function launch_manual_check()
{
	global $sc_file;
	$sc_file->manual_check();
}
add_action( 'start_auto_check', 'launch_manual_check' );




/////////////////////////////////////////////////////////////////// main page ////////////////////////////

function SC_filechecker_main()
{	
	global $sc_tpl, $sc_file, $sc_cron, $sc_config, $sc_mail, $scan_path;
	


	/*
	*
	* work with user`s request
	*/
	
	if(isset($_POST['manual']) and wp_verify_nonce($_POST['_wpnonce'],'form_check') )
	{		
		global $scan_path;
		$start = microtime(true); 
		$manual = $sc_file->manual_check();		
		
		if( count($manual) < 1 )
		{
			$sc_tpl->show_Message('7');						
		}
		else
		{
			// sending mail report about changes			
			$sc_mail->send_mail('found changes', $manual);


			$sc_tpl->show_Message('8', count($manual));
			foreach ($manual as $file)	
			{				
				$sc_tpl->show_Message('0', $file);
				
			}			
		}
		$sc_tpl->show_Message('9', count($sc_file->get_files($scan_path)), (round((microtime(true) - $start), 2)) );

	}

	// starting data file regeneration
	if( isset($_POST['rescan']) )
	{
		if( wp_verify_nonce($_POST['_wpnonce'], 'form_check') )
		{	
			$sc_file->create_data_file();
			// collecting data for mail report
			$current_user = wp_get_current_user();
			$login = $current_user->user_login;
			$ip = $_SERVER['REMOTE_ADDR'];
			$ua = $_SERVER['HTTP_USER_AGENT'];
			
			$array = array($login, $ip, $ua);

			// sending mail report about changes

			$sc_mail->send_mail('data file created', $array);
		}	
	}

	// settings cron job`s frequency
	if( isset($_POST['set_freq']) and wp_verify_nonce($_POST['_wpnonce2'],'cron_freq_check') )
	{		
		$freq = intval($_POST['select_cron_freq']);			
		switch($freq)
		{
			case 1:
			$sc_cron->set_cron('1');
			break;

			case 2:
			$sc_cron->set_cron('2');
			break;

			case 24:
			$sc_cron->set_cron('24');
			break;
		}

	}

	

	// generating html
	$sc_tpl->show_Main_Page();
	$sc_tpl->show_Footer();
	
}

/////////////////////////////////////////////////////////////////// settings page ////////////////////////////

function SC_filechecker_settings()
{
	global $sc_tpl, $sc_config, $sc_file, $sc_mail, $wpdb, $report_mail, $files_to_exclude, $dirs_to_exclude, $to_check;
	$mail = $report_mail;
	$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
	wp_enqueue_style('style', SC_file_checker_url.'/assets/css/table.css' );
		

	/*
	* work with user`s request
	*/

		
	// starting settings update
	if( isset($_POST['update']) )
	{

		// collecting some data for mail report
		$current_user = wp_get_current_user();
		$login = $current_user->user_login;
		$ip = $_SERVER['REMOTE_ADDR'];
		$ua = $_SERVER['HTTP_USER_AGENT'];

		if( wp_verify_nonce($_POST['_wpnonce'], 'settings_page') )
		{
			// updating report_email
			if(!empty($_POST['email']) )
			{
				if(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
				{					
		    		// and some more data for mail report
		    		$setting_name = 'Email';
		    		$old_setting = $mail;
		    		$new_setting = sanitize_email($_POST['email']);
		    		$array = array($login, $ip, $ua, $setting_name, $old_setting, $new_setting);
					$sc_mail->send_mail('settings changed', $array);
					update_option('filechecker_email', $new_setting);


				} 
				else 
				{
					echo $sc_tpl->show_Message('2');	
				}	
			}
			
			// updating scan directory
			if(!empty($_POST['scan_dir']) and is_dir($_POST['scan_dir']))
			{
				// and some more data for mail report
				$setting_name = 'Scan dir';
	    		$old_setting = $scan_dir;
	    		$new_setting = trim(htmlspecialchars($wpdb->_real_escape($_POST['scan_dir'])));
	    		$new_setting = $sc_file -> format_dir($new_setting);
	    		$array = array($login, $ip, $ua, $setting_name, $old_setting, $new_setting);
				$sc_mail->send_mail('settings changed', $array);
				
				update_option('filechecker_scan_dir', $new_setting);    			
				$sc_tpl->show_Message('4');
			}
			

			
			// updating files to be excluded from scan
			if( !empty($_POST['files_to_exclude']) )
			{				
				$files = $_POST['files_to_exclude'];
				$files = $sc_file -> format_dir($files);

				if( preg_match('/,/', $files) )
				{
					// have more than 1 file in POST request
					$files = explode(",", $_POST['files_to_exclude']);
					$filtered = array();
					foreach($files as $file)
					{
						if(is_file(trim($file)))
						{
							$filtered[] = trim($file);							
						}
					}
					$files = implode(",", $filtered);

					// and some more data for mail report
					$setting_name = 'Files to exclude';
		    		$old_setting = $files_to_exclude;
		    		$new_setting = $files;	    		
		    		$array = array($login, $ip, $ua, $setting_name, $old_setting, $new_setting);

					update_option('filechecker_files_to_exclude', $files);	
					$sc_mail->send_mail('settings changed', $array);	

				}
				else
				{
					// have one file in POST request
					if(is_file(trim($files)))
						{						
							// and some more data for mail report
							$setting_name = 'Files to exclude';
				    		$old_setting = $files_to_exclude;
				    		$new_setting = trim($files);	    		
				    		$array = array($login, $ip, $ua, $setting_name, $old_setting, $new_setting);	
							update_option('filechecker_files_to_exclude', $new_setting);
							$sc_mail->send_mail('settings changed', $array);
						}
				}
			
			    

			}

			// updating extensions to scan
			if( !empty($_POST['extensions']) )
			{
				$extensions = trim(htmlspecialchars($wpdb->_real_escape($_POST['extensions'])));
				update_option('filechecker_extensions_to_scan', $extensions);

				// and some more data for mail report
				$setting_name = 'File extensions to be scanned';
	    		$old_setting = $to_check;
	    		$new_setting = $extensions;	    		
	    		$array = array($login, $ip, $ua, $setting_name, $old_setting, $new_setting);
		
				$sc_mail->send_mail('settings changed', $array);
			}

			// updating dirs_to_exclude
			if( !empty($_POST['dirs_to_exclude']) )
			{
				$dir_name = $_POST['dirs_to_exclude'];
				$dir_name = $sc_file -> format_dir($dir_name);
			

				if( preg_match('/,/', $dir_name) ) // so we have some directories in POST
      			{      				
					$dirs = explode(",", $dir_name);
					$verified_dirs = array();
					foreach($dirs as $dir)
					{
						$dir = trim($dir);
						if(is_dir($dir))
						{													
							$verified_dirs[] = $dir;
						}
					}
					$dirs = implode(", ", $verified_dirs);
					// and some more data for mail report
					$setting_name = 'Excluded directories';
		    		$old_setting = $dirs_to_exclude;
		    		$new_setting = $dirs;	    		
		    		$array = array($login, $ip, $ua, $setting_name, $old_setting, $new_setting);
					$sc_mail->send_mail('settings changed', $array);
					update_option('filechecker_dirs_to_exclude', $dirs);

					
				}
				else
				{
					if( is_dir($dir_name) )
					{	
						// and some more data for mail report
						$setting_name = 'Excluded directories';
			    		$old_setting = $dirs_to_exclude;
			    		$new_setting = $dir_name;	    		
			    		$array = array($login, $ip, $ua, $setting_name, $old_setting, $new_setting);
						$sc_mail->send_mail('settings changed', $array);					
						update_option('filechecker_dirs_to_exclude', $dir_name);
						
					}
				}
				
			}

		}	


		$sc_config->cache_Config();
	}

	// clear files to exclude
	if( isset($_POST['clear_files_to_exclude']) and wp_verify_nonce($_POST['_wpnonce'], 'settings_page') )
	{
		update_option('filechecker_files_to_exclude', NULL);
		$sc_config->cache_Config();
	}

	// clear dirs to exclude
	if( isset($_POST['clear_dirs_to_exclude']) and wp_verify_nonce($_POST['_wpnonce'], 'settings_page') )
	{
		update_option('filechecker_dirs_to_exclude', NULL);
		$sc_config->cache_Config();
	}

	// generating html
	
	$sc_tpl->show_Settings_Page();
	$sc_tpl->show_Footer();
}


/////////////////////////////////////////////////////////////////// backup page ////////////////////////////

function SC_filechecker_backup()
{
	global $sc_tpl, $sc_config, $wpdb;
	$sc_backup = new sc_backup;

       
	/*
	* work with user`s request
	*/

	
	// create file backup
	if( isset($_POST['file_backup']) and wp_verify_nonce($_POST['_wpnonce2'], 'create_backup') )
	{		
		$sc_backup->create_file_backup();
		$sc_tpl->show_Message('13');
	}

	// create DB backup
	if( isset($_POST['db_backup']) and wp_verify_nonce($_POST['_wpnonce2'], 'create_backup') )
	{
		$db_selected = $wpdb->_real_escape($_POST['db_selected']);
		$sc_backup->create_mysql_backup($db_selected);
		$sc_tpl->show_Message('13');
	}

	// create full backup
	if( isset($_POST['full_backup']) and wp_verify_nonce($_POST['_wpnonce2'], 'create_backup') )
	{
		$sc_backup->create_file_backup();		
		$sc_backup->create_mysql_backup(DB_NAME);
		$sc_tpl->show_Message('13');
	}

	// restoring backups
	if( isset($_POST['restore_backup_file']) and wp_verify_nonce($_POST['_wpnonce'], 'set_path') and file_exists(BACKUP_PATH.'/'.$_POST['file_backup_name']) )
	{
		$file_name = $wpdb->_real_escape($_POST['file_backup_name']);
		$sc_backup->restore_file_backup($file_name);
	}

	// delete file backup
	if( isset($_POST['delete_backup_file']) and wp_verify_nonce($_POST['_wpnonce'], 'set_path') and file_exists(BACKUP_PATH.'/'.$_POST['file_backup_name']) )
	{
		$file_name = $wpdb->_real_escape($_POST['file_backup_name']);
		if( is_writable(BACKUP_PATH) )
		{
			unlink(BACKUP_PATH.'/'.$file_name);
			$sc_tpl->show_Message('17');
		}
		else
		{
			$sc_tpl->show_Message('16');
		}
	}

	// delete DB backup
	if( isset($_POST['delete_backup_db']) and wp_verify_nonce($_POST['_wpnonce'], 'set_path') and file_exists(BACKUP_PATH.'/'.$_POST['db_backup_name']) )
	{
		$file_name = $wpdb->_real_escape($_POST['db_backup_name']);
		if( is_writable(BACKUP_PATH) )
		{
			unlink(BACKUP_PATH.'/'.$file_name);
			$sc_tpl->show_Message('17');
		}
		else
		{
			$sc_tpl->show_Message('16');
		}
	}

	// generating html
	$sc_tpl->show_Backup_Page();
	$sc_tpl->show_Footer();
	
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




/*
* Include install/uninstall functions
*/
include_once(SC_file_checker_dir.'/includes/functions.php');
register_activation_hook(__FILE__,'SC_filechecker_install');
register_deactivation_hook( __FILE__, 'SC_filechecker_deinstall' );
