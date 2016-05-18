<?php
/*
Plugin Name: SC file checker
Plugin URI: https://siteclean.pro/file-checker-%d0%b4%d0%bb%d1%8f-wordpress/
Description: Plugin for monitoring files integrity by checking its md5 sum; creating, managing file and database backups
Version: 0.41
Author: siteclean
Author URI: https://siteclean.pro
*/

/*  Copyright 2016  siteclean.pro  (email: mail@siteclean.pro)

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

// including main variables to avoid useless DB requests and to be able launch scrip via cron
// file is creating while installing the plugin and renewing while updating settings (function SC_filechecker_cache_settings() )

if(file_exists('settings_cache.php'))
	include_once('settings_cache.php');


// password for plugin, lets launch it from cron job or change settings via admin panel (wp-admin)
define('SC_file_checker_password', 'your_pass');


// this block lets you launch checking using cron job. For fully functionality we need to include wp-blog-header.php directly.

if(isset($_GET['check']) and isset($_GET['pass']) and $_GET['pass'] == SC_file_checker_password)
{
	include_once('includes/functions.php');
	SC_filechecker_auto_check($cache_path, $cache_filechecker_save_dir.'/FC.datafile');
	exit();	
}



if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

set_time_limit(0);

define('SC_filechecker_version', '0.41');
define('SC_file_checker_dir', plugin_dir_path(__FILE__)) ;
define('SC_file_checker_url', plugin_dir_url(__FILE__));
if(file_exists(SC_file_checker_dir.'includes/functions.php'))
{
	require_once(SC_file_checker_dir.'includes/functions.php');	
}
if(file_exists(SC_file_checker_dir.'settings_cache.php'))
	include_once(SC_file_checker_dir.'settings_cache.php');

define('SC_file_checker_dir', SC_filechecker_clear_dir_path(SC_file_checker_dir) );



global $wpdb;
//$wpdb->show_errors();

$path = $cache_path;
$path_for_backups = esc_textarea(get_option('filechecker_path_for_backup'));
$data_file = $cache_data_file;



// Hook for adding admin menus
add_action('admin_menu', 'file_checker');

// action function for above hook
function file_checker() 
{    
	add_menu_page('FC page', 'File checker', 10, 'FC_main', 'SC_filechecker_main');
	add_submenu_page('FC_main', 'Settings', 'Settings', 10, 'FC_settings', 'SC_filechecker_settings');
	add_submenu_page('FC_main', 'Settings', 'FC_backup', 10, 'FC_backup', 'SC_filechecker_backup');
}

 //////////////////////////////////////////// page=FC_main
function SC_filechecker_main()
{
	global $wpdb, $path, $data_file;
	
		
    if(isset($_POST['manual']) and wp_verify_nonce($_POST['_wpnonce'],'manual_action') )
	{
		$start = microtime(true); 
		

		$new_files = SC_filechecker_search_for_new_files($data_file, $path);
		if(count($new_files) > 0){

			echo "<h3>Found ".count($new_files)." new/changed files:</h3><br />";
			foreach ($new_files as $new_file){
				echo $new_file."<br />";
			}
			echo "<br />";
		} else {echo "<i>No new/changed files found</i><br /><br /><br />";}

			
		if(count($new_files) > 0 or count($changed) > 0){
	
		}


	}
	
	include_once(SC_file_checker_dir.'includes/html.php');

}

////////////////////////////////////////////////////////// page=FC_settings
function SC_filechecker_settings() 
{
	
    global $wpdb, $path, $data_file, $cache_mail;
    
    // updating email
	if(isset($_POST['update']) and !empty($_POST['email']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			if(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			{
				$mail = esc_textarea(get_option('filechecker_email'));
	    		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
	    		$message = "Email was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
	    		mail($mail, $subj, $message);

				update_option('filechecker_email', $_POST['email']);
				SC_filechecker_cache_settings();

			} else {echo "<script>alert('Email format incorrect')</script>";}	
		
		
	}

	// updating directory to scan
	if(isset($_POST['update']) and !empty($_POST['scan_dir']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{

				$scan_dir = SC_filechecker_clear_dir_path($_POST['scan_dir']);
				

				if(!empty($scan_dir))
				{
					update_option('filechecker_scan_dir', $scan_dir);
					$mail = esc_textarea(get_option('filechecker_email'));
	    			$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
	    			$message = "Filechecker scan dir was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
	    			mail($mail, $subj, $message);
	    			SC_filechecker_cache_settings();
					echo "<script>alert('Please, rescan your system due to changed directory')</script>";
				}
				else 
				{
					echo "<script>alert('Wrong path added')</script>";
				}
					
			
			
			
		
	}

	// updating data file dir
	if(isset($_POST['update']) and !empty($_POST['dir']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			$dir = SC_filechecker_clear_dir_path($_POST['dir']);
		
				if(!empty($dir) and is_writable($dir) )
				{
					update_option('filechecker_save_dir', $dir);			
					$mail = esc_textarea(get_option('filechecker_email'));
		    		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
		    		$message = "Filechecker directory was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
		    		SC_filechecker_cache_settings();
		    		mail($mail, $subj, $message);		
				} 
				else
				{
					echo "<script>alert('Not writable or wrong path!')</script>";
				}
		

			
						
					
	}

	// updating frequency
	if(isset($_POST['update']) and !empty($_POST['freq']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			$freq = intval($_POST['freq']);
			if($freq < 25 and $freq > 0)
			{
				update_option('filechecker_freq', $freq);
				$mail = esc_textarea(get_option('filechecker_email'));
				$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
			    $message = "Scan frequency was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
			    SC_filechecker_cache_settings();
			    mail($mail, $subj, $message);	

			}
			else
			{
				echo "<script>alert('Wrong frequency set, use digits 1-24')</script>";
			}
		
		
		
	}

	// updating files to be excluded from scan
	if(isset($_POST['update']) and !empty($_POST['files_to_exclude']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{	
		
		
			$files = explode(",", $_POST['files_to_exclude']);
			$filtered = array();

			foreach($files as $file)
			{
				if(is_file(trim($file)))
				{
					$filtered[] = $file;
				}
			}
			$files = implode(", ", $filtered);
			if(!empty($files))
			{
				if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
				{
					$files = stripslashes($files);	
				}
				
				update_option('filechecker_files_to_exclude', $files);	
				$mail = esc_textarea(get_option('filechecker_email'));
				$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
			    $message = "Files to exlcude were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
			    SC_filechecker_cache_settings();
			    mail($mail, $subj, $message);		
			}
		
		
	}




	// updating extensions to scan
	if(isset($_POST['update']) and !empty($_POST['extensions']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		$extensions = trim(htmlspecialchars($wpdb->_real_escape($_POST['extensions'])));
		update_option('filechecker_extensions_to_scan', $extensions);
		$mail = esc_textarea(get_option('filechecker_email'));
		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
		$message = "Extensions to check were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
		SC_filechecker_cache_settings();
		mail($mail, $subj, $message);
				
	}



	// updating dirs_to_exclude
	if(isset($_POST['update']) and !empty($_POST['dirs_to_exclude']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		$dir = SC_filechecker_clear_dir_path($_POST['dirs_to_exclude']);
		update_option('filechecker_dirs_to_exclude', $dir);
		$mail = esc_textarea(get_option('filechecker_email'));
		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
		$message = "Directories to exclude wew frequency was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
		SC_filechecker_cache_settings();
		mail($mail, $subj, $message);

	}

	if(isset($_POST['clear_dirs_to_exclude']) and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		update_option('filechecker_dirs_to_exclude', NULL);
		SC_filechecker_cache_settings();
	}

	if(isset($_POST['clear_files_to_exclude']) and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		update_option('filechecker_files_to_exclude', NULL);
		$mail = esc_textarea(get_option('filechecker_email'));
		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
		$message = "Files to exclude were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
		SC_filechecker_cache_settings();
		mail($mail, $subj, $message);
	}


	if(isset($_POST['rescan']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		SC_filechecker_check_password($_POST['FC_password']);
		SC_filechecker_create_data_file($path);
	}

	include_once(SC_file_checker_dir.'includes/html.php');

	


} //////////////////////////////////////////////////////////////end of page=FC_settings


///////////////////////////////////////////////////////////////page=FC_backup

function SC_filechecker_backup()

{
	
	if( isset($_POST['update_path_for_backups']) and wp_verify_nonce($_POST['_wpnonce'],'set_backup_path') )
	{
		if( isset($_POST['path_for_backups']) and is_dir($_POST['path_for_backups']) and is_writeable($_POST['path_for_backups']) and $_POST['path_for_backups'] !== ABSPATH  
			and SC_filechecker_check_password($_POST['FC_password']) !== FALSE )
		{
			$path_for_backups = $_POST['path_for_backups'];
			if( substr($path_for_backups, (strlen($path_for_backups) - 1 )) != '/')
			{
				$path_for_backups = $path_for_backups."/";
			}
			update_option('filechecker_path_for_backup', $path_for_backups );	
			SC_filechecker_cache_settings();		

			echo "<script>alert('Path for backups updated!')</script>";	
		} else { echo "<script>alert('Error while updating path')</script>";}
		
	} 

	global $path_for_backups;

	if(isset($_POST['file_backup']) and wp_verify_nonce($_POST['_wpnonce'],'create_backup') )
	{
		if( is_writable($path_for_backups) )
		{
			$start = microtime(true); 
			SC_filechecker_create_file_backup();
			echo "<h3>Backuped for ".round((microtime(true) - $start), 2)." seconds</h3>";
		}
		else
		{
			echo "You need to set backup path (or make it writable)";
		}
	}

	if(isset($_POST['db_backup']) and wp_verify_nonce($_POST['_wpnonce'],'create_backup') )
	{		
		if( is_writable($path_for_backups) )
		{
			$start = microtime(true); 
			SC_filechecker_create_mysql_dump();	
			echo "<h3>Backuped for ".round((microtime(true) - $start), 2)." seconds</h3>";
		}
		else
		{
			echo "You need to set backup path (or make it writable)";
		}
	}

	if(isset($_POST['full_backup']) and wp_verify_nonce($_POST['_wpnonce'],'create_backup') )
	{
		if( is_writable($path_for_backups) )
		{
			$start = microtime(true); 
			SC_filechecker_create_file_backup();
			SC_filechecker_create_mysql_dump();	
			echo "<h3>Backuped for ".round((microtime(true) - $start), 2)." seconds</h3>";
		}
		else
		{
			echo "You need to set backup path (or make it writable)";
		}
		
	}

	if( isset($_POST['delete_backup_file']) and wp_verify_nonce($_POST['_wpnonce'],'manage_backup') )
    {
    	//$path_for_backups = stripslashes(esc_textarea(get_option('filechecker_path_for_backup')));        	
    	if( is_file($path_for_backups.$_POST['file_backup_name']) and is_writable($path_for_backups) )
    	{        		
    		if( preg_match('/filebackup/i', $_POST['file_backup_name']) or preg_match('/DBbackup/i', $_POST['file_backup_name']) )
    		{
    			unlink( $path_for_backups.$_POST['file_backup_name'] );	
    		}
    		else
    		{
    			echo "Wrong file name!";
    		}
    		
    	}
    	else
    	{
    		echo "Error while deleting backup file!";
    	}
        
    }


    if( isset($_POST['restore_backup_file']) and wp_verify_nonce($_POST['_wpnonce'],'manage_backup') ) 
    {    	
    	$path_for_backups = stripslashes(esc_textarea(get_option('filechecker_path_for_backup')));
    	if( preg_match('/filebackup/i', $_POST['file_backup_name']) or preg_match('/DBbackup/i', $_POST['file_backup_name']) )
		{	
			//restoring file`s backup
			if( is_file( $path_for_backups.$_POST['file_backup_name'] ) and preg_match('/filebackup/i', $_POST['file_backup_name']) )
			{
				SC_filechecker_restore_file_backup($path_for_backups.$_POST['file_backup_name']);					
			}
			//restoring DB`s backup
			if( is_file( $path_for_backups.$_POST['file_backup_name'] ) and preg_match('/DBbackup/i', $_POST['file_backup_name']) )
			{
				SC_filechecker_restore_mysql_dump($path_for_backups.$_POST['file_backup_name']);					
			}
			


		}
		else
		{
			echo "Wrong file name!";
		}

    }



	include_once(SC_file_checker_dir.'includes/html.php');






}



register_activation_hook(__FILE__,'SC_filechecker_install');

register_deactivation_hook( __FILE__, 'SC_filechecker_deinstall' );
