<?php
/////////////////////////////////////////////////////////////////// install/uninstall ////////////////////////////


function SC_filechecker_install() 
{

   global $wpdb;

   $table_name = $wpdb->prefix . "options";
   
   add_option('filechecker_email', esc_textarea(get_option('admin_email')) );
   add_option('filechecker_scan_dir', ROOT_PATH);
   add_option('filechecker_dirs_to_exclude', '');
   add_option('filechecker_files_to_exclude', '');
   add_option('filechecker_extensions_to_scan', 'php, php3, php4, php5, php6, phps, pl, cgi, shtml, phtml, htaccess, js, html, htm');   
   add_option('filechecker_version', SC_filechecker_version);  

   if( file_exists(SC_file_checker_dir.'/cache/settings_cache.php') )
   {
      unlink(SC_file_checker_dir.'/cache/settings_cache.php');
   }

  
}



function SC_filechecker_deinstall()
{
  global $data_file;

  $names = array(
                'filechecker_email',
                'filechecker_scan_dir',                
                'filechecker_dirs_to_exclude',
                'filechecker_files_to_exclude',
                'filechecker_extensions_to_scan',
                'filechecker_version',                
                );
  for($i = 0; $i< count($names); $i++)
  {
    delete_option($names[$i]);  
  }

  if(file_exists($data_file))
  {
    unlink($data_file);
  }

  if(file_exists(SC_file_checker_dir.'/cache/settings_cache.php'))
  {
    unlink(SC_file_checker_dir.'/cache/settings_cache.php');  
  }
  
  // removing cron job if set
  if( wp_next_scheduled( 'start_auto_check' ) ) 
  {
    $timestamp = wp_next_scheduled('start_auto_check');
    wp_unschedule_event($timestamp, 'start_auto_check');    
  }
  


}