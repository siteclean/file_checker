<?php
// exit if direct access
if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/**
*	Config class. Sets constants, variables etc
*
**/


class SC_config
{
	/**
	*
	*	Get information from cache_file
	*
	**/

	public function __construct()
	{
		global $sc_tpl;

		// check if backup/cache directories are writable
		if( !is_writable(BACKUP_PATH) )	
		{
			$sc_tpl->show_Message('16', BACKUP_PATH);
		}

		if( !is_writable(SC_file_checker_dir.'/cache/') )
		{
			$sc_tpl->show_Message('16', SC_file_checker_dir.'/cache/');
		}


		if( !file_exists(SC_file_checker_dir.'/cache/settings_cache.php') )
		{
			$this->cache_Config();
		}

		include(SC_file_checker_dir.'/cache/settings_cache.php');		


		
	}

		

	/**
	*
	*	caching all settings within plugin_dir/cache/settings_cache.php file
	*
	**/
	public function cache_Config()
	{
		global $wpdb, $sc_file;

		if(!$sc_file)
		{
			$sc_file = new sc_file;
		}

		// all variables stored in DB
		$scan_path = get_option('filechecker_scan_dir');
		$scan_path = $sc_file -> format_dir($scan_path);

		$files_to_exclude = get_option('filechecker_files_to_exclude');
		$files_to_exclude = $sc_file -> format_dir($files_to_exclude);

		$dirs_to_exclude = get_option('filechecker_dirs_to_exclude');
		$dirs_to_exclude = $sc_file -> format_dir($dirs_to_exclude);

		$report_mail = get_option('filechecker_email');


		$files_to_check = get_option('filechecker_extensions_to_scan');		
		// data file name can be changed here
		$data_file = SC_file_checker_dir.'cache/FC.datafile';
		

		// writing settings into /cache/settings_cache.php
		$data = "<?php
\$scan_path = '$scan_path';
\$files_to_exclude = '$files_to_exclude';
\$dirs_to_exclude = '$dirs_to_exclude';
\$report_mail = '$report_mail';
\$to_check = '$files_to_check';
\$data_file = '$data_file';
";
	
	    file_put_contents(SC_file_checker_dir.'/cache/settings_cache.php', $data);  
	    file_put_contents(SC_file_checker_dir.'/cache/.htaccess', 'Deny from all');
	  	
	}


	
	/**
	*
	*	returns formatted config data as array  
	*
	**/
	
	public function get_config($config_name)
	{
		include(SC_file_checker_dir.'/cache/settings_cache.php');
		
		if($config_name == 'to_check')
		{
			if( preg_match('/\,/', $to_check) )
			{					
				$to_check = str_replace(" ", "", $to_check);
				return explode(",", $to_check);
			}
			else
			{
				return str_split($to_check, 10);
			}
		}
		// returns files_to_exclude as array
		if($config_name == 'files_to_exclude')
		{
			if( preg_match('/\,/', $files_to_exclude) )
			{					
				$to_check = str_replace(" ", "", $files_to_exclude);				
				return explode(",", $files_to_exclude);

			}
			else
			{
				return str_split($files_to_exclude, 1000);
			}
		}
		// returns dirs_to_exclude as array
		if($config_name == 'dirs_to_exclude')
		{
			if( preg_match('/\,/', $dirs_to_exclude) )
			{					
				$to_check = str_replace(" ", "", $dirs_to_exclude);
				return explode(",", $dirs_to_exclude);
			}
			else
			{
				return str_split($dirs_to_exclude, 1000);
			}
		}
		
		
	}
	




}