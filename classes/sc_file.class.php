<?php
// exit if direct access
if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/**
*	All about file: options, functions etc
*
**/


class SC_file
{
	/**
	*	recursive searching files
	*	returns array $all_files with all files like /var/www/index.php
	*	
	**/
	public function get_all_files($path)
	{		

		global $sc_tpl;

		$path = $this->format_dir($path);
		$all_files = array();  
		if ($handle = opendir($path.'/'))
		{     
			while (false !== ($item = readdir($handle))) 
			{        
			    if ($item !== "." and $item !== ".." and is_file($path.'/'.$item)) 
			    {
			    	// remove double slashes / back slashes
			    	$new_item = $this->format_dir($path.'/'.$item); 
			    	$all_files[] = $new_item;
		    	}        
		   		elseif ($item !== "." and $item !== ".." and is_dir($path.'/'.$item))
		   		{
		     		$all_files = array_merge($all_files, $this->get_all_files($path.'/'.$item));
		        }
			} 

			closedir($handle);
			$all_files = array_unique($all_files);
		} 
		else 
		{
			$sc_tpl->show_Message('19', $path.'/');			
		}

		return $all_files;
	}




	/**
	*	recursive searching files
	*	returns array $files with all files like /var/www/index.php
	*	files are filtered by extensions, exclusions from settings_cache.php
	**/
	public function get_files($path)
	{
		$path = $this->format_dir($path); 
		include(SC_file_checker_dir.'/cache/settings_cache.php');

		global $sc_tpl, $sc_config;

		$files_to_exclude = explode(",", $files_to_exclude);

		$dirs_to_exclude = explode(",", $dirs_to_exclude);

		$to_check = str_replace(" ", "", $to_check);
		$to_check = explode(",", $to_check);

		$all_files = $this->get_all_files($path);

		// remove exclusions from $all_files

		$all_files = array_diff($all_files, $files_to_exclude);


		$file_info = array();
		$dirs = array();

		foreach($all_files as $value)
		{
			$file_info[] = pathinfo($value);
		}

		$files = array();
		for($i = 0; $i < count($file_info); $i++)
		{
			for($j = 0; $j < count($to_check); $j++)
			{
				if(isset($file_info[$i]['extension']) and strtolower($file_info[$i]['extension']) == $to_check[$j] and !in_array($file_info[$i]['dirname'].'/', $dirs_to_exclude) )
				{
					$files[] = $file_info[$i]['dirname'].'/'.$file_info[$i]['basename'];
				} 
			}
		}
		return $files;
	}







    /**
	*	creates datafile
	*	uses get_files()
	*	returns nothing
	**/
	public function create_data_file()
	{
		global $data_file, $scan_path, $sc_tpl;
		$start = microtime(true); 
		$files = $this->get_files($scan_path);
		$data = array();
		$count = 0;
		foreach($files as $file)
		{
			$data[] = $file.md5_file($file);
			$count++;
		}
		// writes data file with $data
		file_put_contents($data_file, serialize($data) );		
		$sc_tpl->show_Message('10', $count, (round((microtime(true) - $start), 2)));
	}

 	/**
	*
	*	returns filesize of file. $file - full path to the file like /var/www/file.txt
	*
	**/
	public function get_filesize($file)
	{	
		$filesize = filesize($file);
        if($filesize >= 1024 and $filesize <= 1048576)
        {
            return $filesize = round(($filesize / 1024), 2)."Kb";
        }
        elseif($filesize > 1048576 and $filesize <= 1073741824)
        {
            return $filesize = round(($filesize / (1024*1024)), 2)."Mb";
        }
        elseif($filesize > 1073741824)
        {
            return $filesize = round(($filesize / (1024*1024*1024)), 2)."Gb";
        }
        elseif($filesize < 1024)
        {
            return $filesize = round($filesize, 2)."b";
        }
	}       


	/**
	*
	*	manual check for file`s integrity. 
	*	returns new/changed files or empty array if no changes
	**/
	public function manual_check()
	{
		global $scan_path, $data_file, $sc_config;

		set_time_limit(0);

		// get current files
		$current_files = $this->get_files($scan_path);
				
		foreach($current_files as $file)
		{
			// delete excluded files/dirs from $current_files
			$dir = dirname($file);
			$file = trim($file);			
			
			if( !in_array($dir,     $sc_config->get_config('dirs_to_exclude')) and 
				!in_array($dir."/", $sc_config->get_config('dirs_to_exclude')) and
				!in_array($file,    $sc_config->get_config('files_to_exclude'))
				 )
			{					
				$current_hashes[] = $file.md5_file($file);	
			}
			
		}


		
		// get info from data_file
		if( file_exists($data_file) )
		{
			$data_stored = file_get_contents($data_file);
			$data_stored = unserialize($data_stored);
		}
		
		// all new/changed files
		$changed = array_diff($current_hashes, $data_stored);

		//remove hash info and create new array $changed_files
		$changed_files = array();
		foreach($changed as $file)
		{
			$file = substr($file, 0, -32);
			$file = str_replace('//', '/', $file);
			$changed_files[] = $file;
		}

		// return $changed_files
		return $changed_files;

		
	}


	/**
	*	
	*	returns array $empty_folders with ABSPATH
	*	because zipArchive does not zip empty folders we must add them manually
	**/
	public function get_all_folders($path)
	{		
	
		$all_folders = array();  
		if ($handle = opendir($path.'/'))
		{   
			while (false !== ($item = readdir($handle))) 
			{        
			    if ($item !== "." and $item !== ".." and is_dir($path.'/'.$item)) 
			    {
			    	$all_folders[] = $path.'/'.$item;			    	
			    	$all_folders = array_merge($all_folders, $this->get_all_folders($path.'/'.$item));			    	
		    	}        
		   		
			} 

			closedir($handle);
			$all_folders = array_unique($all_folders);
		} 
		
		return $all_folders;
	}


	/**
	*	
	*	returns string $path, removing double slashes, backslashes, last slash
	*	all dir/file pathes must have the same format
	**/
	public function format_dir($path)
	{
		$path = str_replace('\\', '/', $path);
		$path = str_replace('//', '/', $path);		
		if( substr($path, strlen($path) -1 ) == '/' )
		{			
			$path = substr($path, 0, (strlen($path) - 1) );
		}
		
		return $path;
	}

}
