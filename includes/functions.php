<?php

if (!defined('ABSPATH')) 
{
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    die();
}

/*
File consists functions for SC_filechecker plugin
*/


// recursive searching
// returns the array $files - all files with paths
function SC_filechecker_get_files($path)
{
  global $cache_files_to_exclude, $cache_dirs_to_exclude, $cache_mail, $cache_filechecker_save_dir, $cache_to_check;

  $files_to_exclude = explode(",", $cache_files_to_exclude);
	
  $dirs_to_exclude = explode(",", $cache_dirs_to_exclude);

  $to_check = str_replace(" ", "", $cache_to_check);
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
        echo "<div id=\"message\" class=\"updated notice is-dismissible\"><b>Error while opening ".$path." Check permissions</b></div><br />";
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
function SC_filechecker_create_data_file($path)
{
  global $cache_files_to_exclude, $cache_dirs_to_exclude, $cache_mail, $cache_filechecker_save_dir, $cache_to_check, $cache_data_file;
  
  if( is_dir($path) and is_writable($cache_filechecker_save_dir) )
  {   
    $start = microtime(true); 
    $file_md5 = SC_filechecker_get_current_md5($path);
    $count = count($file_md5);
    $file_md5 = serialize($file_md5);  
      $f = fopen("$cache_data_file","w+");
      
        fwrite($f, "<?php
//data file for filechecker plugin
//https://siteclean.pro
exit(); 
?>
");

    fwrite($f, $file_md5);
    fclose($f);
    $subj = "Report from ".$_SERVER['HTTP_HOST']." - data file created";
    $message = "Data file was created by user with IP = ".$_SERVER['REMOTE_ADDR'].", using browser ".esc_textarea($_SERVER['HTTP_USER_AGENT']);
    
    if(mail($cache_mail, $subj, $message))
    {
      echo "<div id=\"message\" class=\"updated notice is-dismissible\">Found changes. Report sent</div>";
    } 
    else 
    {
      echo "<div id=\"message\" class=\"updated notice is-dismissible\">No changes found</div>"; 
    }
    if(file_exists($cache_data_file))
    {
      echo "<div id=\"message\" class=\"updated notice is-dismissible\">Scan continued for ".round((microtime(true) - $start), 2)." seconds <br />$count files were scanned and added to data file! </div>";
      echo "<script>alert('Data file was successfully created')</script>";    
    } 
    else {echo "<div id=\"message\" class=\"updated notice is-dismissible\">An error occured while data file creation</div>";}
 }
 
 else
 {
   echo "<div id=\"message\" class=\"updated notice is-dismissible\">Set correct directory to save data file</div>";
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


// search for new/changed files within $cache_path
function SC_filechecker_search_for_new_files($data_file, $path)
{
  global $cache_files_to_exclude, $cache_dirs_to_exclude, $cache_mail, $cache_filechecker_save_dir, $cache_to_check;


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
    $files_to_exclude = explode(",", $cache_files_to_exclude);
    foreach($files_to_exclude as $file)
    {
      $files_to_exclude[] = trim($file);
    }
    $new_files = array_diff($new_files, $files_to_exclude);
      //checking for excluding dirs
    $dirs_to_exclude_from_bd = explode(",", $cache_dirs_to_exclude);
    $dirs_to_exclude_from_bd = array_unique($dirs_to_exclude_from_bd);
    $dirs_to_exclude = array();
    foreach($dirs_to_exclude_from_bd as $dirs)
    {
      $dirs_to_exclude[] = trim($dirs);
    }
        
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
    return FALSE;
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

   // creating cache_settings.php file
   SC_filechecker_cache_settings();
  
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
  for($i = 0; $i< count($names); $i++)
  {
    delete_option($names[$i]);  
  }
  if(file_exists($data_file))
  {
    unlink($data_file);
  }
  if(file_exists(SC_file_checker_dir.'cache/settings_cache.php'))
  {
    unlink(SC_file_checker_dir.'cache/settings_cache.php');  
  }
  if(file_exists(SC_file_checker_dir.'cache/cron_cache.php'))
  {
    unlink(SC_file_checker_dir.'cache/cron_cache.php');  
  }
  // removing cron job if set
  if( wp_next_scheduled( 'start_auto_check' ) ) 
  {
    $timestamp = wp_next_scheduled('start_auto_check');
    wp_unschedule_event($timestamp, 'start_auto_check');    
  }
  


}



// checking directory to be set correct, formats it
function SC_filechecker_clear_dir_path($dir_name)
{
  if(!strtolower(substr(PHP_OS, 0, 3)) === 'win')
  {
    $dir_name = str_replace('//', '/', $dir_name);
    if(preg_match('/,/', $dir_name))
      {
        // we have some directoriers 
        $dirs_array = explode(",", $dir_name);
        $verified_directories = array();
        foreach($dirs_array as $dir)
        {
          $dir = trim($dir);
          if(is_dir($dir))
          {
            $verified_directories[] = $dir;
          }
        }
        return $dir_name = implode(", ", $verified_directories);
      }
      else
      {       
        if(is_dir($dir_name)) 
          return $dir_name;
      }

  }
  else

    // For windows servers formatting
  {
    $dir_name = str_replace('\\\\', '\\', $dir_name);
    if(preg_match('/,/', $dir_name))
      {
        // we have some directoriers        
        $dirs_array = explode(",", $dir_name);
        $verified_directories = array();
        foreach($dirs_array as $dir)
        {
          $dir = trim($dir);
          if(is_dir($dir))
          {            
            $dir = rtrim($dir, '\\\\');
            $dir = rtrim($dir, '\\');
            $verified_directories[] = $dir; 
                       
          }
        }       
        return $dir_name = implode(", ", $verified_directories);
      }
      else
      {
        $dir_name = rtrim($dir_name, '\\');
        if(is_dir($dir_name)) 
          return $dir_name;
      }

  }  
  
}




function SC_filechecker_check_password($FC_pass)
{
  global $cache_pass;
  $FC_pass = trim($FC_pass);
  if($FC_pass === $cache_pass)
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
  $start = microtime(true);
  global $path_for_backups;

  if(is_dir($path_for_backups))
  {
      if( preg_match('/windows/i', php_uname()) )
    {
      echo "<h2><div id=\"message\" class=\"updated notice is-dismissible\">Windows OS is not supported in this version. Will be available soon!</div></h2>";   
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
        echo "<div id=\"message\" class=\"updated notice is-dismissible\">No PHP system commands available. Please, enable on of system, exec, shell_exec or passthru for creating file`s backup</div>";
      }
    }
  }

  ?>
      <div id="message" class="updated notice is-dismissible">Files backuped for <?php echo round((microtime(true) - $start), 2); ?> seconds</div>
      <?php

}


function SC_filechecker_restore_file_backup($file_name)
{
  set_time_limit(0);
  $start = microtime(true); 
  $ph = ABSPATH;
  // to restore archive into the needed dir we must set 'strip' parametr for tar command. 
  // So we get count of "/" symbol.
  $strip = count(explode("/", $ph)) - 2;
  $success_message = "<br /><h2><div id=\"message\" class=\"updated notice is-dismissible\">All files restored successfully!</div></h2><br />";

  if( !preg_match('/system/i', ini_get('disable_functions')) )
  {      
    system("tar -zxf $file_name --directory=$ph --strip=$strip");
    echo $success_message;
  }
  elseif( !preg_match('/exec/i', ini_get('disable_functions')) )
  {
    exec("tar -zxf $file_name --directory=$ph --strip=$strip");    
    echo $success_message;  
  }
  elseif( !preg_match('/shell_exec/i', ini_get('disable_functions')) )
  {
    shell_exec("tar -zxf $file_name --directory=$ph --strip=$strip"); 
    echo $success_message;     
  }
  elseif( !preg_match('/passthru/i', ini_get('disable_functions')) )
  {
    passthru("tar -zxf $file_name --directory=$ph --strip=$strip"); 
    echo $success_message;    
  }
  else
  {
    echo "<h2><div id=\"message\" class=\"updated notice is-dismissible\">No PHP system commands available. Please, enable these system, exec, shell_exec or passthru commands for creating file`s backup</div></h2><br />";
  }

  
}


// creating mysql dump
function SC_filechecker_create_mysql_dump()
{  
  set_time_limit(0);
  $start = microtime(true);
  global $path_for_backups;
  if( is_dir($path_for_backups) )
  {
    if( preg_match('/windows/i', php_uname()) )
    {
      echo "<h2><div id=\"message\" class=\"updated notice is-dismissible\">Windows OS is not supported in this version. Will be available soon!</div></h2>";   
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
        echo "<h2><div id=\"message\" class=\"updated notice is-dismissible\">No PHP system commands available. Please, enable these system, exec, shell_exec or passthru commands for creating file`s backup</div></h2><br />";
      }

    }

  }   

  ?>
      <div id="message" class="updated notice is-dismissible">Database backuped for <?php echo round((microtime(true) - $start), 2); ?> seconds</div>
      <?php
  

}


function SC_filechecker_restore_mysql_dump($file_name)
{
  set_time_limit(0);
  global $path_for_backups;
  if( is_dir($path_for_backups) )
  {
    $command = " mysql -u ".DB_USER." -p".DB_PASSWORD." ".DB_NAME." < $file_name ";
    $success_message = "<h2><div id=\"message\" class=\"updated notice is-dismissible\">Database restored successfully!</div></h2><br />";
    
    if( !preg_match('/system/i', ini_get('disable_functions')) )
    {       
      system($command);
      echo $success_message;
    }      
    elseif( !preg_match('/exec/i', ini_get('disable_functions')) )
    {
      exec($command);      
      echo $success_message;
    }
    elseif( !preg_match('/shell_exec/i', ini_get('disable_functions')) )
    {      
      shell_exec($command);
      echo $success_message;      
    }
    elseif( !preg_match('/passthru/i', ini_get('disable_functions')) )
    {
      passthru($command);
      echo $success_message;      
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


// function creates plugin_dir/cache/settings_cache.php file where plugins keeps some its variables
function SC_filechecker_cache_settings()
{
  global $wpdb, $cache_pass;

  $name = '/FC.datafile';
  $cache_path = get_option('filechecker_scan_dir');
  $cache_files_to_exclude = get_option('filechecker_files_to_exclude');
  $cache_dirs_to_exclude = get_option('dirs_files_to_exclude');
  $cache_mail = get_option('filechecker_email');
  $cache_filechecker_save_dir = get_option('filechecker_save_dir');
  $cache_to_check = get_option('filechecker_extensions_to_scan');
  $cache_data_file = $cache_filechecker_save_dir.$name;  


     //genereting plugin`s password

  if(!$cache_pass)
  {
    $symbols = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $pass_length = 8;
    $password = array();
    for($i = 0; $i < $pass_length; $i++)
    {
      $password[$i] = substr($symbols, rand(0, strlen($symbols)), 1 );
    }
    $password = implode("",$password);  

    $data = "<?php
\$name = \"$name\";
\$cache_path = '$cache_path';
\$cache_files_to_exclude = '$cache_files_to_exclude';
\$cache_dirs_to_exclude = '$cache_dirs_to_exclude';
\$cache_mail = '$cache_mail';
\$cache_filechecker_save_dir = '$cache_filechecker_save_dir';
\$cache_to_check = '$cache_to_check';
\$cache_data_file = '$cache_data_file';
\$cache_pass = '$password';
  ";

  $message = "Your plugin password is $password";
  mail($cache_mail, 'SC_filechecker plugin password', $message);
  }
  else
  {
    $data = "<?php
\$name = \"$name\";
\$cache_path = '$cache_path';
\$cache_files_to_exclude = '$cache_files_to_exclude';
\$cache_dirs_to_exclude = '$cache_dirs_to_exclude';
\$cache_mail = '$cache_mail';
\$cache_filechecker_save_dir = '$cache_filechecker_save_dir';
\$cache_to_check = '$cache_to_check';
\$cache_data_file = '$cache_data_file';
\$cache_pass = '$cache_pass';
  ";
  }
  
  
  if( is_dir(SC_file_checker_dir.'/cache/') and is_writable(SC_file_checker_dir.'/cache/') )
  {
    file_put_contents(SC_file_checker_dir.'/cache/settings_cache.php', $data);  
  }

  
  
}


//setting cron job`s frquency
function SC_filechecker_cron_settings()
{

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


// cron job via wp_cron; $value is check frequency

function SC_filechecker_set_cron($value)
{ 
  
  if( wp_next_scheduled( 'start_auto_check' ) ) 
  {
    $timestamp = wp_next_scheduled('start_auto_check');
    wp_unschedule_event($timestamp, 'start_auto_check');    
  }
  
  
  switch($value)
  {
    case 1: wp_schedule_event( time(), 'daily', 'start_auto_check' ); break;
    case 2: wp_schedule_event( time(), 'twicedaily', 'start_auto_check' ); break;
    case 24: wp_schedule_event( time(), 'hourly', 'start_auto_check' ); break;    
  }


}



// mail report after cron launch
function SC_filechecker_cron_report()
{
 
  global $cache_files_to_exclude, $cache_dirs_to_exclude, $cache_mail, $cache_filechecker_save_dir, $cache_to_check, $cache_path, $cache_data_file;  

  if(file_exists($cache_data_file))
  {
    $report = SC_filechecker_search_for_new_files($cache_data_file, $cache_path);     
  }
  if(isset($report) and count($report) > 0)
  {

    $site_url = get_option('siteurl');    
    $subj = "SC_filechecker report from $site_url - cron check";
    $message = "
<html>
<head>
<title>Report from $site_url</title>
</head>
<body>
<p><b>New/changed files found while last check:</b></p>";
foreach($report as $file)
    {
      $message = $message.$file."<br />";      
    }
    $message = $message."</body>
</html>
";

    $message = $message."<br /><br /><br /><i>https://siteclean.pro - your site safety</i>";


    $headers= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    
    mail($cache_mail, $subj, $message, $headers);
   
  }
  else 
    {
      echo "No changes found"; 
    }
  
exit();

}


