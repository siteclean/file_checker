<?php
/*
Plugin Name: SC filechecker
Plugin URI: https://siteclean.pro/wordpress-file_checker/
Description: Control your site`s files integrity, create and manage backups.
Version: 0.5
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


if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}




define('SC_filechecker_version', '0.5');
define('SC_file_checker_dir', plugin_dir_path(__FILE__)) ;
define('SC_file_checker_url', plugin_dir_url(__FILE__));

if(file_exists(SC_file_checker_dir.'includes/functions.php'))
{
	require_once(SC_file_checker_dir.'includes/functions.php');	
}


if(file_exists(SC_file_checker_dir.'cache/settings_cache.php'))
{
	include_once(SC_file_checker_dir.'cache/settings_cache.php');
	$path = $cache_path;
	$path_for_backups = esc_textarea(get_option('filechecker_path_for_backup'));
	$data_file = $cache_data_file;
}
else
{
	SC_filechecker_cache_settings();
}

	
// Hook for adding admin menus
add_action('admin_menu', 'SC_filechecker_file_checker');

// action function for above hook
function SC_filechecker_file_checker() 
{    
	add_menu_page('SC main page', 'SC filechecker', 'administrator', 'SC_main', 'SC_filechecker_main');
	add_submenu_page('SC_main', 'Settings', 'Settings', 'administrator', 'SC_settings', 'SC_filechecker_settings');
	add_submenu_page('SC_main', 'Backup page', 'Manage backups', 'administrator', 'SC_backup', 'SC_filechecker_backup');	
	wp_enqueue_style('style', SC_file_checker_url.'/assets/css/style.css' );
}

/////////////////////////////////////////////////////////////////////////////////////////// page=SC_main
function SC_filechecker_main()
{
	global $wpdb, $path, $data_file;

	$current_cron_jobs = _get_cron_array();
	
		
    if(isset($_POST['manual']) and wp_verify_nonce($_POST['_wpnonce'],'manual_action') )
	{
		$start = microtime(true); 
		

		$new_files = SC_filechecker_search_for_new_files($data_file, $path);
		if(count($new_files) > 0){

			echo "<h3><div id=\"message\" class=\"updated notice is-dismissible\">Found ".count($new_files)." new/changed files:</div></h3><br />";
			foreach ($new_files as $new_file){
				echo $new_file."<br />";
			}
			echo "<br />";
		} else {echo "<div id=\"message\" class=\"updated notice is-dismissible\"><i>No new/changed files found</i></div><br /><br /><br />";}

			
		if(count($new_files) > 0 or count($changed) > 0){
	
		}


	}
	
	if(isset($_POST['set_freq']) and wp_verify_nonce($_POST['_wpnonce'],'set_freq'))
	{

		$freq = intval($_POST['select_cron_freq']);
		if($freq == 1 or $freq == 2 or $freq == 24)
		{	
			SC_filechecker_set_cron($freq);			
		}	
					
	}




	include_once(SC_file_checker_dir.'includes/html.php');

}

/////////////////////////////////////////////////////////////////////////////////////////// page=SC_main end

/////////////////////////////////////////////////////////////////////////////////////////// page=SC_settings
function SC_filechecker_settings() 
{
		
    global $wpdb, $path, $data_file, $cache_mail, $cache_path;

    $mail = esc_textarea(get_option('filechecker_email'));
	$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
    
    // updating email
	if(isset($_POST['update']) and !empty($_POST['email']) and SC_filechecker_check_password($_POST['FC_password2']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			if(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			{
				
	    		$message = "Email was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
	    		mail($mail, $subj, $message);
				
				update_option('filechecker_email', sanitize_email($_POST['email']));
				SC_filechecker_cache_settings();

			} else {echo "<script>alert('Email format incorrect')</script>";}	
		
		
	}

	// updating directory to scan
	if(isset($_POST['update']) and !empty($_POST['scan_dir']) and SC_filechecker_check_password($_POST['FC_password2']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{

				$scan_dir = SC_filechecker_clear_dir_path($_POST['scan_dir']);
				

				if(!empty($scan_dir))
				{
					update_option('filechecker_scan_dir', $scan_dir);
					
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
	if(isset($_POST['update']) and !empty($_POST['dir']) and SC_filechecker_check_password($_POST['FC_password2']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			$dir = SC_filechecker_clear_dir_path($_POST['dir']);
		
				if(!empty($dir) and is_writable($dir) )
				{
					update_option('filechecker_save_dir', $dir);			
					
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
	if(isset($_POST['update']) and !empty($_POST['freq']) and SC_filechecker_check_password($_POST['FC_password2']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			$freq = intval($_POST['freq']);
			if($freq < 25 and $freq > 0)
			{
				update_option('filechecker_freq', $freq);
				
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
	if(isset($_POST['update']) and !empty($_POST['files_to_exclude']) and SC_filechecker_check_password($_POST['FC_password2']) !== FALSE 
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
				
			    $message = "Files to exlcude were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
			    SC_filechecker_cache_settings();
			    mail($mail, $subj, $message);		
			}
		
		
	}




	// updating extensions to scan
	if(isset($_POST['update']) and !empty($_POST['extensions']) and SC_filechecker_check_password($_POST['FC_password2']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		$extensions = trim(htmlspecialchars($wpdb->_real_escape($_POST['extensions'])));
		update_option('filechecker_extensions_to_scan', $extensions);
		
		$message = "Extensions to check were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
		SC_filechecker_cache_settings();
		mail($mail, $subj, $message);
				
	}



	// updating dirs_to_exclude
	if(isset($_POST['update']) and !empty($_POST['dirs_to_exclude']) and SC_filechecker_check_password($_POST['FC_password2']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		$dir = SC_filechecker_clear_dir_path($_POST['dirs_to_exclude']);
		update_option('filechecker_dirs_to_exclude', $dir);
		
		$message = "Directories to exclude was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
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
		
		$message = "Files to exclude were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
		SC_filechecker_cache_settings();
		mail($mail, $subj, $message);
	}


	if(isset($_POST['rescan']) and SC_filechecker_check_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{			
		SC_filechecker_create_data_file($cache_path);
	}

	include_once(SC_file_checker_dir.'includes/html.php');	


} 
//////////////////////////////////////////////////////////////end of page=SC_settings


///////////////////////////////////////////////////////////////page=SC_backup

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
			SC_filechecker_create_file_backup();			
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
			 SC_filechecker_create_mysql_dump();				
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
			
			SC_filechecker_create_file_backup();
			SC_filechecker_create_mysql_dump();				
		}
		else
		{
			echo "You need to set backup path (or make it writable)";
		}
		
	}

	if( isset($_POST['delete_backup_file']) and wp_verify_nonce($_POST['_wpnonce'],'manage_backup') )
    {
    	        	
    	if( is_file($path_for_backups.$_POST['file_backup_name']) and is_writable($path_for_backups) )
    	{        		
    		if( preg_match('/filebackup/i', $_POST['file_backup_name']) or preg_match('/DBbackup/i', $_POST['file_backup_name']) )
    		{
    			unlink( $path_for_backups.sanitize_file_name($_POST['file_backup_name']) );	
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
				SC_filechecker_restore_file_backup($path_for_backups.sanitize_file_name($_POST['file_backup_name']) );					
			}
			//restoring DB`s backup
			if( is_file( $path_for_backups.$_POST['file_backup_name'] ) and preg_match('/DBbackup/i', $_POST['file_backup_name']) )
			{
				SC_filechecker_restore_mysql_dump($path_for_backups.sanitize_file_name($_POST['file_backup_name']));					
			}
			


		}
		else
		{
			echo "Wrong file name!";
		}

    }



	include_once(SC_file_checker_dir.'includes/html.php');

}

////////////////////////////////////////////////////////////////////////////////////////// page=SC_backup end


////////////////////////////////////////////////////////////////////////////////////////// page cron_settings start







////////////////////////////////////////////////////////////////////////////////////////// page cron_settings end


// hook for cron job autocheck
add_action('start_auto_check', 'SC_filechecker_cron_report');


register_activation_hook(__FILE__,'SC_filechecker_install');

register_deactivation_hook( __FILE__, 'SC_filechecker_deinstall' );


