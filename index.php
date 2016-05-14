<?php
/*
Plugin Name: File integrity checker
Plugin URI: https://siteclean.pro/file-checker-%d0%b4%d0%bb%d1%8f-wordpress/
Description: Plugin for monitoring files integrity by checking its md5 sum; creating, managing file and database backups
Version: 0.4
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


// password for plugin, lets launch it from cron job or change settings via admin panel (wp-admin)
define('FS_password', 'your_pass');


// this block lets you launch checking using cron job. For fully functionality we need to include wp-blog-header.php directly.

if( isset($_GET['check']) and $_GET['pass'] == FS_password )
{

	if( file_exists('../../../wp-blog-header.php') )
		{
			require_once('../../../wp-blog-header.php');	
		}
	elseif ( file_exists($_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php') ) 
	{
		require_once($_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php');
	}

	
	define('FS_dir', plugin_dir_path(__FILE__)) ;
	define('FS_url', plugin_dir_url(__FILE__));
	if(file_exists(FS_dir.'includes/functions.php'))
	{
		require_once(FS_dir.'includes/functions.php');	
	}
	
	$path = get_option('filechecker_scan_dir');
	$data_file = get_option('filechecker_dir').'/FC.'.'datafile';
	auto_check($path, $data_file);	
	exit();
}



if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

set_time_limit(0);



$FC_version = '0.4';

define('FS_dir', plugin_dir_path(__FILE__)) ;
define('FS_url', plugin_dir_url(__FILE__));
if(file_exists(FS_dir.'includes/functions.php'))
{
	require_once(FS_dir.'includes/functions.php');	
}
define('FS_dir', clear_dir_path(FS_dir) );

global $wpdb;
$wpdb->show_errors();

$path = get_option('filechecker_scan_dir');

$data_file = get_option('filechecker_dir').'/'.'FC.'.'datafile';

// Hook for adding admin menus
add_action('admin_menu', 'file_checker');

// action function for above hook
function file_checker() 
{    
	add_menu_page('FC page', 'File checker', 10, 'FC_main', 'FC_main');
	add_submenu_page('FC_main', 'Settings', 'Settings', 10, 'FC_settings', 'FC_settings');
	add_submenu_page('FC_main', 'Settings', 'FC_backup', 10, 'FC_backup', 'FC_backup');
}

 //////////////////////////////////////////// page=FC_main
function FC_main()
{
	global $wpdb, $path, $data_file;
	
		
    if(isset($_POST['manual']) and wp_verify_nonce($_POST['_wpnonce'],'manual_action') )
	{
		$start = microtime(true); 
		

		$new_files = search_for_new_files($data_file, $path);
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
	
	include_once(FS_dir.'includes/html.php');

}

////////////////////////////////////////////////////////// page=FC_settings
function FC_settings() 
{
	
    global $wpdb, $path, $data_file;
    
    // updating email
	if(isset($_POST['update']) and !empty($_POST['email']) and check_FC_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			if(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			{
				$mail = get_option('filechecker_email');
	    		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
	    		$message = "Email was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
	    		mail($mail, $subj, $message);

				update_option('filechecker_email', $_POST['email']);

			} else {echo "<script>alert('Email format incorrect')</script>";}	
		
		
	}

	// updating directory to scan
	if(isset($_POST['update']) and !empty($_POST['scan_dir']) and check_FC_password($_POST['FC_password']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
			

				$scan_dir = clear_dir_path($_POST['scan_dir']);
				if(!empty($scan_dir))
				{
					update_option('filechecker_scan_dir', $scan_dir);
					$mail = get_option('filechecker_email');
	    			$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
	    			$message = "Filechecker scan dir was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
	    			mail($mail, $subj, $message);
					echo "<script>alert('Please, rescan your system due to changed directory')</script>";
				}
				else 
				{
					echo "<script>alert('Wrong path added')</script>";
				}
					
			
			
			
		
	}

	// updating data file dir
	if(isset($_POST['update']) and !empty($_POST['dir']) and check_FC_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			$dir = clear_dir_path($_POST['dir']);
		
				if(!empty($dir) and is_writable($dir) )
				{
					update_option('filechecker_dir', $dir);			
					$mail = get_option('filechecker_email');
		    		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
		    		$message = "Filechecker directory was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
		    		mail($mail, $subj, $message);		
				} 
				else
				{
					echo "<script>alert('Not writable or wrong path!')</script>";
				}
		

			
						
					
	}

	// updating frequency
	if(isset($_POST['update']) and !empty($_POST['freq']) and check_FC_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		
			$freq = intval($_POST['freq']);
			if($freq < 25 and $freq > 0)
			{
				update_option('filechecker_freq', $freq);
				$mail = get_option('filechecker_email');
				$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
			    $message = "Scan frequency was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
			    mail($mail, $subj, $message);	

			}
			else
			{
				echo "<script>alert('Wrong frequency set, use digits 1-24')</script>";
			}
		
		
		
	}

	// updating files to be excluded from scan
	if(isset($_POST['update']) and !empty($_POST['files_to_exclude']) and check_FC_password($_POST['FC_password']) !== FALSE 
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
				$mail = get_option('filechecker_email');
				$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
			    $message = "Files to exlcude were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
			    mail($mail, $subj, $message);		
			}
		
		
	}




	// updating extensions to scan
	if(isset($_POST['update']) and !empty($_POST['extensions']) and check_FC_password($_POST['FC_password']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		$extensions = trim(htmlspecialchars($wpdb->_real_escape($_POST['extensions'])));
		update_option('filechecker_extensions_to_scan', $extensions);
		$mail = get_option('filechecker_email');
		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
		$message = "Extensions to check were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
		mail($mail, $subj, $message);
				
	}



	// updating dirs_to_exclude
	if(isset($_POST['update']) and !empty($_POST['dirs_to_exclude']) and check_FC_password($_POST['FC_password']) !== FALSE 
		and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		
		$dir = clear_dir_path($_POST['dirs_to_exclude']);
		if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			$dir = stripslashes($dir);
		}
		update_option('filechecker_dirs_to_exclude', $dir);
		$mail = get_option('filechecker_email');
		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
		$message = "Directories to exclude wew frequency was changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
		mail($mail, $subj, $message);

	}

	if(isset($_POST['clear_dirs_to_exclude']) and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		update_option('filechecker_dirs_to_exclude', NULL);
	}

	if(isset($_POST['clear_files_to_exclude']) and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		update_option('filechecker_files_to_exclude', NULL);
		$mail = get_option('filechecker_email');
		$subj = "Report from ".$_SERVER['HTTP_HOST']." - settings changed";
		$message = "Files to exclude were changed by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".$_SERVER['HTTP_USER_AGENT'];
		mail($mail, $subj, $message);
	}


	if(isset($_POST['rescan']) and check_FC_password($_POST['FC_password']) !== FALSE and wp_verify_nonce($_POST['_wpnonce'],'update_settings') )
	{
		check_FC_password($_POST['FC_password']);
		create_data_file($path, $data_file);
	}

	include_once(FS_dir.'includes/html.php');

	


} //////////////////////////////////////////////////////////////end of page=FC_settings


///////////////////////////////////////////////////////////////page=FC_backup

function FC_backup()

{
	if( isset($_POST['update_path_for_backups']) and wp_verify_nonce($_POST['_wpnonce'],'set_backup_path') )
	{
		if( isset($_POST['path_for_backups']) and is_dir($_POST['path_for_backups']) and is_writeable($_POST['path_for_backups']) 
			and check_FC_password($_POST['FC_password']) !== FALSE )
		{
			$path_for_backups = htmlspecialchars($_POST['path_for_backups']);
			if( substr($path_for_backups, (strlen($path_for_backups) - 1 )) != '/')
			{
				$path_for_backups = $path_for_backups."/";
			}
			update_option('filechecker_path_for_backups', $path_for_backups );
			file_put_contents($path_for_backups.'.htaccess', 'deny from all');

			echo "<script>alert('Path for backups updated!')</script>";	
		} else { echo "<script>alert('Error while updating path')</script>";}
		
	} 

	if(isset($_POST['file_backup']) and wp_verify_nonce($_POST['_wpnonce'],'create_backup') )
	{
		$start = microtime(true); 
		FC_create_file_backup();
		echo "<h3>Backuped for ".round((microtime(true) - $start), 2)." seconds</h3>";
	}

	if(isset($_POST['db_backup']) and wp_verify_nonce($_POST['_wpnonce'],'create_backup') )
	{		
		$start = microtime(true); 
		FC_create_mysql_dump();	
		echo "<h3>Backuped for ".round((microtime(true) - $start), 2)." seconds</h3>";
	}

	if(isset($_POST['full_backup']) and wp_verify_nonce($_POST['_wpnonce'],'create_backup') )
	{
		$start = microtime(true); 
		FC_create_file_backup();
		FC_create_mysql_dump();
		echo "<h3>Backuped for ".round((microtime(true) - $start), 2)." seconds</h3>";
	}

	if( isset($_POST['delete_backup_file']) and wp_verify_nonce($_POST['_wpnonce'],'manage_backup') )
    {
    	$path_for_backups = stripslashes(get_option('filechecker_path_for_backups'));        	
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
    	$path_for_backups = stripslashes(get_option('filechecker_path_for_backups'));
    	if( preg_match('/filebackup/i', $_POST['file_backup_name']) or preg_match('/DBbackup/i', $_POST['file_backup_name']) )
		{	
			//restoring file`s backup
			if( is_file( $path_for_backups.$_POST['file_backup_name'] ) and preg_match('/filebackup/i', $_POST['file_backup_name']) )
			{
				FC_restore_file_backup($path_for_backups.$_POST['file_backup_name']);					
			}
			//restoring DB`s backup
			if( is_file( $path_for_backups.$_POST['file_backup_name'] ) and preg_match('/DBbackup/i', $_POST['file_backup_name']) )
			{
				FC_restore_mysql_dump($path_for_backups.$_POST['file_backup_name']);					
			}
			


		}
		else
		{
			echo "Wrong file name!";
		}

    }



	include_once(FS_dir.'includes/html.php');






}




register_activation_hook(__FILE__,'FC_install');

register_deactivation_hook( __FILE__, 'FC_deinstall' );
