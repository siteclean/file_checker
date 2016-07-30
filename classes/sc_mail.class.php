<?php
// exit if direct access
if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/**
*   Class works with mail  
*
**/

class SC_mail
{
	/**
	*
	*	Get current settings from settings_cache.php
	*	
	**/

	public function __construct()
	{
		if( file_exists(SC_file_checker_dir.'/cache/settings_cache.php') )
		{
			include(SC_file_checker_dir.'/cache/settings_cache.php');			
		}	
	}


	public function send_mail($id, $array = '')
	{
		global $report_mail, $sc_tpl;

		$data = $sc_tpl->get_mail_message($id, $array);
		$subj = $data['0'];
		$message = $data['1'];


		mail($report_mail, $subj, $message, "Content-type: text/html\r\n");

	}



}
