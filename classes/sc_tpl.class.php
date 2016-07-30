<?php
// exit if direct access
if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/**
*  Class works with all kind of messages, forms, tables etc. 
*
**/
class SC_Tpl
{

	public function __construct()
	{
		if( file_exists(SC_file_checker_dir.'/cache/settings_cache.php') )
		{
			include(SC_file_checker_dir.'/cache/settings_cache.php');			
		}		
	}


	/**
	*
	*	creates html form for admin.php?page=SC_main
	*	returns HTML
	**/
	public function show_Main_Page()
	{
		global $data_file;
		ob_start();
		include( SC_file_checker_dir.'/templates/SC_main.tpl' );
		$form_check = wp_nonce_field('form_check', '_wpnonce');
		$form_check2 = wp_nonce_field('cron_freq_check', '_wpnonce2');
		// cron`s info
		$next_launch_time = date("H:i:s", wp_next_scheduled( 'start_auto_check' ));
	    $current_time = date("H:i:s");        
	    $when_launch = date ("H:i:s", strtotime($next_launch_time) - strtotime($current_time));
		$template = ob_get_clean();		

		// if data file exists getting its time creation
		if( file_exists($data_file) )
		{
			$t = stat($data_file)['mtime'];
    		$time = date('H:i:s d-m-Y', $t);    		

    		$template = str_replace(array(
									'{wp_nonce_check}',										
									'{wp_nonce_check2}',
									'{var1}',
									'{var2}',
									'{var3}',
									'{data file date}',
									),
								array(
									$form_check,										
									$form_check2,
									$next_launch_time,
									$when_launch,
									$current_time,		
									$time								
									),
								$template);
    		echo $template;

		}
		
		else
		{
		////////////////////////////////////////////////	
		$template = str_replace(array(
									'{wp_nonce_check}',										
									'{wp_nonce_check2}',
									'{var1}',
									'{var2}',
									'{var3}',
									),
								array(
									$form_check,										
									$form_check2,
									$next_launch_time,
									$when_launch,
									$current_time,										
									),
								$template);
		echo $template;	
		}

	}


	

	
	/**
	*
	*	creates html form for admin.php?page=SC_settings
	*	returns HTML
	*/

	public function show_Settings_Page()
	{		
		// get current settings from cache file
		if( file_exists(SC_file_checker_dir.'/cache/settings_cache.php') )
		{			
			include(SC_file_checker_dir.'/cache/settings_cache.php');			
		}
		// include tpl file
		
		ob_start();
		include(SC_file_checker_dir.'/templates/SC_settings.tpl');
		$template = ob_get_clean();
		$form_check = wp_nonce_field('settings_page', '_wpnonce');

		$files_to_exclude = str_replace(',', '<br />', $files_to_exclude);
		$dirs_to_exclude = str_replace(',', '<br />', $dirs_to_exclude);


		$template = str_replace(array(								
								'{wp_nonce}',
								'{email}',
								'{scan_dir}',									
								'{files_to_scan}',
								'{excluded_files}',
								'{excluded_dirs}',										
								),
							array(
								$form_check,											
								$report_mail,
								$scan_path,
								$to_check,
								$files_to_exclude,
								$dirs_to_exclude,										
								),
							$template);



		if( !empty($files_to_exclude) )
		{
			$template = str_replace('{delete excluded files}', '<p><input type="submit" class="button-primary" name="clear_files_to_exclude" value="Clear excluded files?"></input></p>', $template);
		}
		else
		{
			$template = str_replace('{delete excluded files}', '', $template);
		}
		if( !empty($dirs_to_exclude) )
		{
			$template = str_replace('{delete excluded dirs}', '<p><input type="submit" class="button-primary" name="clear_dirs_to_exclude" value="Clear excluded directories?"></input></p>', $template);
		}
		else
		{
			$template = str_replace('{delete excluded dirs}', '', $template);
		}
		echo $template;
		
			
	}



	/**
	*
	*	creates html form for admin.php?page=SC_backup
	*	returns HTML
	*/

	public function show_Backup_Page()
	{
				
		global $sc_file, $wpdb, $sc_tpl;

		// available DB`s
		$count_db = $wpdb->get_var('SELECT count(schema_name) FROM information_schema.schemata');
		$db_name = '';
		for( $i = 1; $i < $count_db; $i++)
		{
			// get all available DBs as string
			$name = $wpdb->get_var("SELECT schema_name FROM information_schema.schemata LIMIT $i, 1");
			$db_name .= '<option name = "'.$name.'">'.$name.'</option>';
		}

		// get current settings from cache file
		if( file_exists(SC_file_checker_dir.'/cache/settings_cache.php') )
		{			
			include(SC_file_checker_dir.'/cache/settings_cache.php');			
		}

		// including tpl file
		if( file_exists(SC_file_checker_dir.'/templates/SC_backup.tpl') )
		{
			ob_start();
			include(SC_file_checker_dir.'/templates/SC_backup.tpl');
			$template = ob_get_clean();
			$form_check = wp_nonce_field('set_path', '_wpnonce');
			$form_check2 = wp_nonce_field('create_backup', '_wpnonce2');
			$template = str_replace(array(										
										'{wp_nonce_check}',
										'{wp_nonce_check2}',	
										'{set_db_name}',	
										'{path}',
										),
									array(										
										$form_check,																				
										$form_check2,
										$db_name,
										ABSPATH,
										),
									$template);
			echo $template;
		}


		// checking if backups exist within BACKUP_PATH
		$files = scandir(BACKUP_PATH);
		foreach($files as $file)
		{

			if( stristr($file, 'filebackup') ) // if true - its file backup
			{				
				// get filesize, filename, creation time 
				$filesize = $sc_file->get_filesize(BACKUP_PATH.'/'.$file);
				$file_date = filemtime(BACKUP_PATH.'/'.$file);
            	$file_date = date("d/M/Y, G:i", $file_date);
            	$filename = pathinfo($file)['filename'];
            	
            	
            	//including template for file backup list
            	if( file_exists(SC_file_checker_dir.'/templates/file_backup_list.tpl') )
				{

					ob_start();
					include(SC_file_checker_dir.'/templates/file_backup_list.tpl');
					$file_list = ob_get_clean();  
					$file_list = str_replace(array(
										'{creation time}',
										'{filesize}',										
										'{wp_nonce_check}',	
										'{filename}',
										),
									array(
										$file_date,
										$filesize,	
										$form_check,	
										$file,									
										),
									$file_list);      
				} 
				echo $file_list;				 	
			}

			if( stristr($file, 'DBbackup') ) // if true - its DB backup
			{				
				// get filesize, filename, creation time 
				$filesize = $sc_file->get_filesize(BACKUP_PATH.'/'.$file);
				$file_date = filemtime(BACKUP_PATH.'/'.$file);
            	$file_date = date("d/M/Y, G:i", $file_date);
            	$db_backuped_name = substr($file, 42);
            	$db_backuped_name = substr($db_backuped_name, 0, -13);
            	
            	//including template for DB backup list
            	if( file_exists(SC_file_checker_dir.'/templates/db_backup_list.tpl') )
				{

					ob_start();
					include(SC_file_checker_dir.'/templates/db_backup_list.tpl');
					$db_list = ob_get_clean();  
					$db_list = str_replace(array(
										'{creation time}',
										'{filesize}',
										'{wp_nonce_check}',			
										'{db_name}',	
										'{filename}',																
										),
									array(
										$file_date,
										$filesize,		
										$form_check,
										$db_backuped_name,
										$file,
										),
									$db_list);      
				} 
				echo $db_list;				 	
			}
			
		}
		
		
	}


	/**
	*	shows footer from footer.tpl
	*	returns html code
	*
	*/
	public function show_Footer()
	{
		if( file_exists(SC_file_checker_dir.'/templates/footer.tpl') )
		{
			ob_start();
			include(SC_file_checker_dir.'/templates/footer.tpl');
			$template = ob_get_clean();
			echo str_replace('{version}', SC_filechecker_version, $template);
		}

	}	


	/**
	*	shows message from message.tpl, where $id is a row number from message.tpl
	*	$var - something to replace within message with PHP variable
	*	returns html/js code
	*
	*/
	public function show_Message($id, $var1='', $var2='')
	{
		if( $var1 != '' and  $var2 == '' and file_exists(SC_file_checker_dir.'/templates/messages.tpl') )
		{			
			$id = intval($id);
			$message = file(SC_file_checker_dir.'/templates/messages.tpl');
			$message = $message[$id];			
			echo str_replace('{var1}', $var1, $message);
		}

		elseif( $var1 != '' and  $var2 != '' and file_exists(SC_file_checker_dir.'/templates/messages.tpl') )
		{			
			$id = intval($id);
			$message = file(SC_file_checker_dir.'/templates/messages.tpl');
			$message = $message[$id];			
			echo str_replace(array('{var1}', '{var2}'), array($var1, $var2), $message);			
		}

		elseif( file_exists(SC_file_checker_dir.'/templates/messages.tpl') )
		{				
			$messages = file(SC_file_checker_dir.'/templates/messages.tpl');
			$id = intval($id);		
			echo $messages[$id];
		}
	}


	/**
	*	
	*	Generates mail`s message
	*	int $id	
	*	returns string($subj)
	*	
	**/

	public function get_mail_subj($id)
	{
		$mail_tpl = file(SC_file_checker_dir.'/templates/mail.tpl');
		switch($id)
		{
			case 1: $subj = $mail_tpl['0']; break;
			case 2: $subj = $mail_tpl['1']; break;
			case 3: $subj = $mail_tpl['2']; break;
			default: $subj = "Unknown message"; break;
		}

		$url = $_SERVER['HTTP_HOST'];
		return $subj = str_replace('{url}', $url, $subj);
		

	}

	/**
	*	
	*	Generates mail`s message
	*	int $id	
	*	array($array) - additional info for message
	*	returns array($subj, $message)
	*	
	**/

	public function get_mail_message($id, $array = '')
	{		

		// get templates
		$mail_tpl = file(SC_file_checker_dir.'/templates/mail/subj.tpl');
		$mail_footer = file_get_contents(SC_file_checker_dir.'/templates/mail/footer.tpl');

		$url = $_SERVER['HTTP_HOST'];
		

		// depending on $id get template`s data
		switch($id)
		{
			case 'found changes': 
				$message = file_get_contents(SC_file_checker_dir.'/templates/mail/report_man_check.tpl'); 

				$var = '';
				// make array readable for letter
				for($i = 0; $i < count($array); $i++ )
				{			
					$var .= $array[$i]."\r\n";			
				}
				// create message
				$message = str_replace('{files}', $var, $message);
				$subj = $mail_tpl['0']; 
				break;

			case 'settings changed': 
				$message = file_get_contents(SC_file_checker_dir.'/templates/mail/report_settings.tpl'); 
				$subj = $mail_tpl['1']; 
				$message = str_replace('{username}', $array['0'], $message);
				$message = str_replace('{ip}', $array['1'], $message);
				$message = str_replace('{browser}', $array['2'], $message);
				$message = str_replace('{setting_name}', $array['3'], $message);
				$message = str_replace('{old}', $array['4'], $message);
				$message = str_replace('{new}', $array['5'], $message);
				break;

			case 'data file created': 
				$message = file_get_contents(SC_file_checker_dir.'/templates/mail/report_data_file.tpl'); 
				$subj = $mail_tpl['2']; 
				$message = str_replace('{username}', $array['0'], $message);
				$message = str_replace('{ip}', $array['1'], $message);
				$message = str_replace('{browser}', $array['2'], $message);
				break;

			default: 
				$message = "Unknown message"; 
				$subj = 'Unknown subject';
				break;
		}

		// replacing variables inside templates
		$subj = str_replace('{url}', $url, $subj);
			
		
		
		// get full message (message + footer)
		$message .= $mail_footer;

		return array($subj, $message);

	}




}