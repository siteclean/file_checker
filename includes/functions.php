<?php

//function for checking files integrity via cron job
function SC_filechecker_auto_check($path, $data_file)
{

  if(file_exists($data_file))
  {
    $report = SC_filechecker_search_for_new_files($data_file, $path);    
  }
  if(isset($report) and !empty($report))
  {
    $mail = get_option('filechecker_email');
    $subj = "Report from ".$_SERVER['HTTP_HOST'];
    $message = "Found new/changed files: \r\n";
    foreach($report as $file)
    {
      $message = $message.$file."\r\n";      
    }
    if(mail($mail, $subj, $message))
    {
      echo "Found changes. Report sent";
    } 
    else 
    {
      echo "No changes found"; 
    }
  }
  
exit();
}


if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}



// functions for file checker plugin


// recursive searching
// returns the array $files - all files with paths
function SC_filechecker_get_files($path)
{
  global $wpdb;
  
  $files_to_exclude = get_option('filechecker_files_to_exclude');
	$files_to_exclude = explode(",", $files_to_exclude);   
	
  $dirs_to_exclude = get_option('filechecker_dirs_to_exclude');
	$dirs_to_exclude = explode(",", $dirs_to_exclude);

  $to_check = get_option('filechecker_extensions_to_scan');
  
  $to_check = str_replace(" ", "", $to_check);
	$to_check = explode(",", $to_check);

    $all_files = array();  
      if ($handle = opendir($path.'/'))
      {     
        while (false !== ($item = readdir($handle))) {        
        	    if ($item !== "." and $item !== ".." and is_file($path.'/'.$item)) {
        	    	$all_files[] = $path.'/'.$item;
             }        
             elseif ($item !== "." and $item !== ".." and is_dir($path.'/'.$item)){
             	    $all_files = array_merge($all_files, SC_filechecker_get_files($path.'/'.$item));
                  }
        } 
        closedir($handle);
        $all_files = array_unique($all_files);

      } 
      else 
      {
        echo "<b>Error while opening ".$path." Check permissions</b><br />";
      }

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



// creating data file
function SC_filechecker_create_data_file($path, $data_file)
{
  global $path_for_backups;
  
  if(is_dir($path) and is_writable(get_option('filechecker_save_dir')))
  {  
  
    $start = microtime(true); 
    $file_md5 = SC_filechecker_get_current_md5($path);
    $count = count($file_md5);
    $file_md5 = serialize($file_md5);  
      $f = fopen("$data_file","w+");
      
        fwrite($f, "<?php
//data file for filechecker plugin
//https://siteclean.pro
exit(); 
?>
");

    fwrite($f, $file_md5);
    fclose($f);
    $mail = get_option('filechecker_email');    
    $subj = "Report from ".$_SERVER['HTTP_HOST']." - data file created";
    $message = "Data file was created by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
    
    if(mail($mail, $subj, $message))
    {
      echo "Found changes. Report sent";
    } 
    else 
    {
      echo "No changes found"; 
    }
    if(file_exists($data_file))
    {
      echo "<h3>Scan continued for ".round((microtime(true) - $start), 2)." seconds <br />$count files were scanned and added to data file! </h3>";
      echo "<script>alert('Data file was successfully created')</script>";    
    } 
    else {echo "An error occured while data file creation";}
 }
 
 else
 {
   echo "Set correct directory to save data file ";
 }
 

}


  // Getting current names and hashes
 // returns C:\xampp\htdocs\siteclean\googleb57423f63e8de6ad.htmlbb5c38c5e55cdebab8371f63c0ee6bf5
function SC_filechecker_get_current_md5($path)
{
    if(is_dir($path))
    {
      $file_md5 = array();
      $a = SC_filechecker_get_files($path);
      
      for($i = 0; $i < count($a); $i++)
      {
        $file_md5[] = $a[$i].md5_file($a[$i]);
      }


      return $file_md5;
    }
    else
    {
      echo "Set correct path for scanning";
    }
    
}  



function SC_filechecker_search_for_new_files($data_file, $path)
{
	if( file_exists($data_file) and is_readable($data_file) and is_dir($path) )
  {    
    $data = file($data_file);
    $data_file_hashes = unserialize($data['5']);
    $new_files = array();
    
    $current_hashes = SC_filechecker_get_current_md5($path);
    // finding new/changed files by comparing to arrays    
      $diff = array_diff($current_hashes, $data_file_hashes);
      if(!empty($diff))
      {        
        foreach($diff as $file)
        {
          $new_files[] = substr($file, 0, -32);
        }
          
      } 

        //checking for excluding files
    $files_to_exclude = explode(",", get_option('filechecker_files_to_exclude'));
    foreach($files_to_exclude as $file)
    {
      $files_to_exclude[] = trim($file);
    }
    $new_files = array_diff($new_files, $files_to_exclude);
      //checking for excluding dirs
    $dirs_to_exclude_from_bd = explode(",", get_option('filechecker_dirs_to_exclude'));
    $dirs_to_exclude_from_bd = array_unique($dirs_to_exclude_from_bd);
    $dirs_to_exclude = array();
    foreach($dirs_to_exclude_from_bd as $dirs)
    {
      $dirs_to_exclude[] = trim($dirs);
    }
    
    echo "<br />";
    $new_files_without_excludes = array();
    foreach($new_files as $file)
    {
      if( !in_array( dirname($file), $dirs_to_exclude) )
      {
        $new_files_without_excludes[] = $file;
      }
    }

    $new_files = $new_files_without_excludes;
    $new_files = array_unique($new_files);
  
    return count($new_files > 0) ? $new_files : NULL;

  }
  else 
  {
    echo "Something went wrong. Check script settings, pathes to be correct, data file to be readable etc";
  }

   
}



function SC_filechecker_install () 
{

   global $wpdb;

   $table_name = $wpdb->prefix . "options";
   add_option('filechecker_email', esc_textarea(get_option('admin_email')) );
   add_option('filechecker_save_dir', SC_filechecker_clear_dir_path(ABSPATH));
   add_option('filechecker_freq', '1');
   add_option('filechecker_dirs_to_exclude', '');
   add_option('filechecker_files_to_exclude', '');
   add_option('filechecker_extensions_to_scan', 'php, php3, php4, php5, php6, phps, pl, cgi, shtml, phtml, htaccess, js, html, htm');
   add_option('filechecker_scan_dir', SC_filechecker_clear_dir_path(ABSPATH));
   add_option('filechecker_version', SC_filechecker_version);
   add_option('filechecker_path_for_backup', '');

   // adding data_file extension to .htaccess protection
   if( is_writable(ABSPATH.".htaccess") and SC_filechecker_check_htaccess() === FALSE )
   {
    
     $d = fopen(ABSPATH.".htaccess", 'a+');
     $text = "
<Files FC.datafile> 
deny from all 
</Files>
              ";   
    fwrite($d, $text);
    fclose($d); 
   }
   
  
}



function SC_filechecker_deinstall()
{
  global $data_file;

  $names = array(
                'filechecker_email',
                'filechecker_save_dir',
                'filechecker_freq',
                'filechecker_dirs_to_exclude',
                'filechecker_files_to_exclude',
                'filechecker_extensions_to_scan',
                'filechecker_scan_dir',
                'filechecker_version',
                'filechecker_path_for_backup',
                );
  for($i = 0; $i< count($names); $i++){
    delete_option($names[$i]);  
  }
  if(file_exists($data_file))
  {
    unlink($data_file);
  }
  

}



// 
function SC_filechecker_clear_dir_path($dir_name)
{
  if( is_dir($dir_name) )
  {
    $dirs = explode(",", $dir_name);
    $clear_dir = array();

    foreach($dirs as $key)
    {      
       $key = trim($key);
      if(substr($key, -1) == "\\" or substr($key, -1) == "/" )
      {
        $key = substr($key, 0, -1);
      }
     
      if(is_dir($key))
      {
        $clear_dir[] = $key;
      }
    }
    $dirs = implode(", ", $clear_dir);
    
    if( strtolower(substr(PHP_OS, 0, 3)) === 'win' )
    {
      $dirs = str_replace('/', '\\', $dirs);
    }
    else 
    {
      $dirs = str_replace('\\', '/', $dirs);

    }
     
    return $dirs;

  }
  else
  {
    echo "Set correct path";
  }
  
  
}



function SC_filechecker_check_password($FC_pass)
{
  $FC_pass = trim($FC_pass);
  if($FC_pass === SC_file_checker_password)
  {
    return TRUE;
  } 
  else 
  { 
    echo "<script>alert('Wrong password for plugin! Enter correct password to submit changes')</script>"; 
    return FALSE;
  }
}


// creating file`s backup
function SC_filechecker_create_file_backup()
{
  set_time_limit(0);
  global $path_for_backups;

  if(is_dir($path_for_backups))
  {
      if( preg_match('/windows/i', php_uname()) )
    {
      echo "<h2>Windows OS is not supported in this version. Will be available soon!</h2>";   
    }
    else 
    {
      
      $ph = ABSPATH;
      $name = date("dmY").'_filebackup.tar.gz';
      $data = rand(0, 999999999);
      $prefix = md5($data);
      $ps = $path_for_backups."*";

         
      if( !preg_match('/system/i', ini_get('disable_functions')) )
      {      
        system("tar -zcf $path_for_backups/$prefix'_'$name --exclude=$ps $ph");      
      }
      elseif( !preg_match('/exec/i', ini_get('disable_functions')) )
      {
        exec("tar -zcf $path_for_backups/$prefix'_'$name --exclude=$ps $ph");      
      }
      elseif( !preg_match('/shell_exec/i', ini_get('disable_functions')) )
      {
        shell_exec("tar -zcf $path_for_backups/$prefix'_'$name --exclude=$ps $ph");      
      }
      elseif( !preg_match('/passthru/i', ini_get('disable_functions')) )
      {
        passthru("tar -zcf $path_for_backups/$prefix'_'$name --exclude=$ps $ph");      
      }
      else
      {
        echo "No PHP system commands available. Please, enable on of system, exec, shell_exec or passthru for creating file`s backup";
      }
    }
  }

  
}


function SC_filechecker_restore_file_backup($file_name)
{
  set_time_limit(0);
  $ph = ABSPATH;
  // to restore archive into the needed dir we must set 'strip' parametr for tar command. 
  // So we get count of "/" symbol.
  $strip = count(explode("/", $ph)) - 2;

  if( !preg_match('/system/i', ini_get('disable_functions')) )
  {      
    system("tar -zxf $file_name --directory=$ph --strip=$strip");
    echo "<br /><h2>All files restored!</h2><br />";
  }
  elseif( !preg_match('/exec/i', ini_get('disable_functions')) )
  {
    exec("tar -zxf $file_name --directory=$ph --strip=$strip");    
    echo "<br /><h2>All files restored!</h2><br />";  
  }
  elseif( !preg_match('/shell_exec/i', ini_get('disable_functions')) )
  {
    shell_exec("tar -zxf $file_name --directory=$ph --strip=$strip"); 
    echo "<br /><h2>All files restored!</h2><br />";     
  }
  elseif( !preg_match('/passthru/i', ini_get('disable_functions')) )
  {
    passthru("tar -zxf $file_name --directory=$ph --strip=$strip"); 
    echo "<br /><h2>All files restored!</h2><br />";     
  }
  else
  {
    echo "<h2>No PHP system commands available. Please, enable these system, exec, shell_exec or passthru commands for creating file`s backup</h2><br />";
  }
  
}


// creating mysql dump
function SC_filechecker_create_mysql_dump()
{  
  set_time_limit(0);
  global $path_for_backups;
  if( is_dir($path_for_backups) )
  {
    if( preg_match('/windows/i', php_uname()) )
    {
      echo "<h2>Windows OS is not supported in this version. Will be available soon!</h2>";   
    }
    else 
    {
      
      $name = date("dmY").'_DBbackup.sql';
      $data = rand(0, 999999999);
      $prefix = md5($data);
      $command = "mysqldump --single-transaction -u ".DB_USER." -p".DB_PASSWORD." ".DB_NAME." --add-drop-table > $path_for_backups/$prefix'_'$name";
      
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
        echo "No PHP system commands available. Please, enable on of system, exec, shell_exec or passthru for creating DB`s backup";
      }

    }

  }   
  

}


function SC_filechecker_restore_mysql_dump($file_name)
{
  set_time_limit(0);
  global $path_for_backups;
  if( is_dir($path_for_backups) )
  {
    $command = " mysql -u ".DB_USER." -p".DB_PASSWORD." ".DB_NAME." < $file_name ";
    
    if( !preg_match('/system/i', ini_get('disable_functions')) )
    {       
      system($command);
      echo "<h2>Database restored!</h2><br />";
    }      
    elseif( !preg_match('/exec/i', ini_get('disable_functions')) )
    {
      exec($command);      
      echo "<h2>Database restored!</h2><br />";
    }
    elseif( !preg_match('/shell_exec/i', ini_get('disable_functions')) )
    {      
      shell_exec($command);
      echo "<h2>Database restored!</h2><br />";      
    }
    elseif( !preg_match('/passthru/i', ini_get('disable_functions')) )
    {
      passthru($command);
      echo "<h2>Database restored!</h2><br />";      
    }
    else
    {
      echo "<h2>No PHP system commands available. Please, enable on of system, exec, shell_exec or passthru for creating DB`s backup<h2><br />";
    }
  }

  

}


function SC_filechecker_dir_check($dirname)
{
  if( is_dir($dirname) )
  {
    return $dirname;
  }
  else 
  {
    return "Wrong data, check it, please";
  }
  
}

//checking root dir`s htaccess file for 'Files FC.datafile' string
function SC_filechecker_check_htaccess()
{

    if(is_file(ABSPATH.'/.htaccess') and is_readable(ABSPATH.'/.htaccess'))
    {
        $root_htaccess = file_get_contents(ABSPATH.'/.htaccess');
        if( !preg_match('/Files FC.datafile/', $root_htaccess) )
        {
          return FALSE;
        } 
        else 
        {
          return TRUE;
        }
    }
    else 
    {
      return FALSE;
    }

}