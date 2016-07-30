<?php
// exit if direct access
if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/**
*	Backup creating, restoring etc
*
**/

class sc_backup
{

	/**
	*
	*	creates file`s backup for ABSPATH directory
	*
	**/
	public function create_file_backup()
	{
		global $sc_tpl, $sc_file;
		set_time_limit(0);

		// if phpversion >= 5.2.0 use zipArchive to create file backup
		if( phpversion() < 5.2 )
		{
			$sc_tpl->show_Message('19');
			return false;
		}
			
		$zip = new ZipArchive();

    	$name = date("dmY").'_filebackup.zip';
		$data = rand(0, 999999999);
		$prefix = md5($data);		 
		$prefix = $prefix."_".$name; 

    	if ($zip -> open(BACKUP_PATH.$prefix, ZipArchive::CREATE) === TRUE)
    	{		   		
	   		$folders = $sc_file -> get_all_folders(ABSPATH);
	   		foreach ($folders as $dir)
	   		{
	   			// remove full path till site`s root
	   			$dir = str_replace(ABSPATH, '/', $dir);
	   			$zip -> addEmptyDir($dir);
	   		}

	   		$files = $sc_file -> get_all_files(ABSPATH);
	    	foreach ($files as $file)
	    	{ 
	            if( pathinfo($file)['dirname'].'/' != BACKUP_PATH )
	            {	
	            	// remove full path till site`s root
	            	$localname = str_replace(ABSPATH, '/', $file);
	            	$zip -> addFile($file, $localname);	
	            }
	        }	 
	    }        		        
	    
	    $zip -> close();

	}



	/**
	*
	*	restores file`s backup
	*
	**/

	public function restore_file_backup($file_name)
	{
		global $sc_tpl;
	
		// need phpversion >= 5.2.0 to use ZipArchive
		// if phpversion >= 5.2.0 use zipArchive to create file backup
		if( phpversion() < 5.2 )
		{
			$sc_tpl->show_Message('19');
			return false;
		}

	  	$zip = new ZipArchive;
		if ($zip->open(BACKUP_PATH.$file_name) === TRUE) 
		{
		    $zip->extractTo(ABSPATH);
		    $zip->close();
		    $sc_tpl -> show_Message('13');
		} 
		else 
		{
		    $sc_tpl -> show_Message('21');
		}

	  
	}

/////////////////////////////////////////////////// Database backup creates using mysqldump function not available at Windows servers
	/**
	*
	*	creates database`s backup
	*
	**/
	public function create_mysql_backup($db_name)
	{  
		global $sc_tpl;

		set_time_limit(0);
			  

		if( preg_match('/windows/i', php_uname()) )
		{
		  $sc_tpl->show_Message('6');   
		}
		else 
		{
		  
		  $name = date("dmY").'_'.$db_name.'_DBbackup.sql';
		  $data = rand(0, 999999999);
		  $prefix = md5($data);
		  $command = "mysqldump --single-transaction -u ".DB_USER." -p".DB_PASSWORD." ".$db_name." --add-drop-table > ".BACKUP_PATH."/$prefix'_'$name";
		  
		  if( !preg_match('/system/i', ini_get('disable_functions')) )
		  {       
		    system($command);
		  }      
		  elseif( !preg_match('/exec/i', ini_get('disable_functions')) )
		  {
		    exec($command);      
		  }
		  elseif( !preg_match('/shell_exec/i', ini_get('disable_functions')) )
		  {      
		    shell_exec($command);      
		  }
		  elseif( !preg_match('/passthru/i', ini_get('disable_functions')) )
		  {
		    passthru($command);      
		  }
		  else
		  {
		    $sc_tpl->show_Message('12');	
		    $sc_tpl->show_Message('13');	
		  }

		}

	       

	}

	

	/**
	*
	*	restores DB`s backup
	*
	**/
	public function SC_filechecker_restore_mysql_dump($file_name)
	{
		set_time_limit(0);


		$command = " mysql -u ".DB_USER." -p".DB_PASSWORD." ".DB_NAME." < $file_name ";		

		if( !preg_match('/system/i', ini_get('disable_functions')) )
		{       
		  system($command);
		  $sc_tpl->show_Message('14');
		}      
		elseif( !preg_match('/exec/i', ini_get('disable_functions')) )
		{
		  exec($command);      
		  $sc_tpl->show_Message('14');
		}
		elseif( !preg_match('/shell_exec/i', ini_get('disable_functions')) )
		{      
		  shell_exec($command);
		  $sc_tpl->show_Message('14');     
		}
		elseif( !preg_match('/passthru/i', ini_get('disable_functions')) )
		{
		  passthru($command);
		  $sc_tpl->show_Message('14');     
		}
		else
		{
	  	  $sc_tpl->show_Message('12');	
		  $sc_tpl->show_Message('13');
		}

	}

}