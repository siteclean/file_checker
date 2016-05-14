<?php

if (!defined('ABSPATH')) 
{
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    die();
}
//main page block



if(isset($_GET['page']) and $_GET['page'] == 'FC_main')
{
    if(!file_exists($data_file)){
        echo "<h4>Base file for check was not found. Please, scan the system</h4><br />";
        exit();
    }
    ?>
    <form action='' method=POST>
    <b>Manual check </b><input type="submit" name="manual" value="check"></input><br /><br />
    <?php wp_nonce_field('manual_action', '_wpnonce'); ?>
    </form>
    <?php 
}
//end of the main page block


//settings page block
if(isset($_GET['page']) and $_GET['page'] == 'FC_settings')
{

    ?>

    <h2>Main options for file checker</h2>

    

    <link href="<?php echo FS_url.'assets/css/'; ?>style.css" rel="stylesheet">

    <form action='' method=POST>

    <b>Enter the file_checker password for changes to be applied (not your admin`s pass for site):</b> <br />
    <input type="password" size="25" name = "FC_password" > </input><br /><br />


    <?php
    if(file_exists($data_file))
    {
        $t = stat($data_file)['mtime'];
        $time = date('H:i:s d-m-Y', $t);
        echo '<b>Data file created '.$time.'. <br />Regenerate data file?</b> (fill the "file_checker password" field to continue)<br /><input type="submit" name="rescan" value="rescan"></input><br />';
    } 
    else 
    {
        echo '<b>Create data file? </b><input type="submit" name="rescan" value="scan"></input><br />';
    }
    ?>

    <br /><br />
    <div class="settings" >
                    <table >
                        <tr>
                            <td>
                                Current settings
                            </td>
                            <td >
                                Value
                            </td>
                           
                        </tr>
                        <tr>
                            <td >
                                Email for reports
                            </td>                        
                            <td>
                                <?php echo get_option('filechecker_email'); ?>                            
                            </td>
                            
                        </tr>
                        <tr>
                            <td >
                                Directory for scan
                            </td>
                            <td>
                                <?php echo get_option('filechecker_scan_dir'); ?>
                   
                        </tr>
                        <tr>
                            <td >
                                Directory for data file to be stored (must be writable for script)
                            </td>                        
                            <td>
                                <?php echo get_option('filechecker_dir'); ?>
                            </td>
                            
                        </tr>
                        <tr>
                            <td >
                                Check frequency per day (1 - 24)
                            </td>                        
                            <td>
                                <?php echo get_option('filechecker_freq'); ?>
                            </td>
                            
                        </tr>
                        <tr>
                            <td >
                                Files with these extensions will be scanned
                            </td>                        
                            <td>
                                <?php echo get_option('filechecker_extensions_to_scan'); ?>
                            </td>
                            
                        </tr>
                        <tr>
                            <td >
                                Files to be excluded from scan
                            </td>                        
                            <td>
                                <?php echo $f_t_e = get_option('filechecker_files_to_exclude'); 
                                if( !empty($f_t_e) )
                                {
                                   echo '<br /><input type="submit" name = "clear_files_to_exclude" value = "Clear excluded files?" > </input>';

                                }
                                ?>                            
                            </td>
                            
                        </tr>
                        <tr>
                            <td >
                                Directories to be excluded from scan
                            </td>                        
                            <td>
                               <?php echo $d_t_e = get_option('filechecker_dirs_to_exclude'); 
                                if( !empty($d_t_e) )
                                {
                                    echo '<br /><input type="submit" name = "clear_dirs_to_exclude" value = "Clear excluded dirs?" > </input>';
                                }
                               ?>        
                               <br />                  
                            </td>
                            
                        </tr>
                    
                        </tr>
                    </table>
                </div>
                
                


    <h3>Update settings</h3>
    <br />Enter your email for reports: <input type="text" name = "email" > </input>
    <br /> <br />
    Directory for scan: <input type="text" size="70" name = "scan_dir" > </input><br />
    <br /><br />

    Directory for data file to be stored (must be writable for script): <input type="text" size="70" name = "dir" > </input><br />
    <br /><br />

    Check frequency per day (1 - 24): <input type="text" name = "freq" ></input>
    <br /><br />

    Files with these extensions will be scanned: <input type="text" size="70" name = "extensions" > </input><br />
    <i>( Recommended value: <b>php, php3, php4, php5, php6, phps, pl, cgi, shtml, phtml, htaccess, js, html, htm )</b></i><br /><br />


    Files to be excluded from scan: <input type="text" size="70" name = "files_to_exclude" > </input><br />

    Directories to be excluded from scan: <input type="text" size="70" name = "dirs_to_exclude" > </input><br /><br />

    <b>Update settings? (<i>current settings will be overwritten!</i>)</b> <input type="submit" name='update' value="update"></input><br /><br /><br />
    <?php
    if(!file_exists($data_file)){
            echo "<h4>Base file for check was not found. Please, scan the system</h4><br />";
        }
    wp_nonce_field('update_settings', '_wpnonce');
    ?>


    </form>
    </div>


    <?php

}
//end of the settings page block



// back page block

if( isset($_GET['page']) and $_GET['page'] == 'FC_backup' )
{

    $path_for_backups = get_option('filechecker_path_for_backups');
?>
   <form action="" method="POST" >
   <br /> <br /> Current path for backups: <b><?php echo $path_for_backups; ?></b><br /><br />
   Path for backups to be stored: <input type="text" size="70" name = "path_for_backups"  value = "" ></input><br /><br />

   <b>Enter the file_checker password (not your admin`s pass for site):</b> <input type="password" size="25" name = "FC_password" > </input><br /><br /> 

   <b>Update backup path? (<i>current backups will not be deleted, just move them to new directory</i>)</b> <input type="submit" name='update_path_for_backups' value="set path"></input><br /><br /><br />
   <?php wp_nonce_field('set_backup_path', '_wpnonce'); ?>
   </form>

<?php 
/////////////////////////////////////////////////// backup block
?>
   <form action ="" method = "POST">
       <input type="submit" name='file_backup' value="Create files backup"></input><br />
       <input type="submit" name='db_backup' value="Create database backup"></input><br />
       <input type="submit" name='full_backup' value="Create full (files + DB) backup"></input><br /><br />
       <?php wp_nonce_field('create_backup', '_wpnonce'); ?>
   </form>

   <h2>Availiable backups</h2><br />
<?php    
    $all_backups_array = scandir($path_for_backups);
    $file_backups_array = array();
    $db_backups_array = array();
    foreach($all_backups_array as $file)
    {
        if( preg_match('/filebackup/i', $file) )
        {
            $file_backups_array[] = $file;
        }
        elseif( preg_match('/DBbackup/i', $file) )
        {
            $db_backups_array[] = $file;
        }
        else
        {
            continue;
        }
    }

    echo "<u>File backups:</u><br />";
    // displaing file backups
    foreach($file_backups_array as $file_backup)
    {      
        $filesize = filesize($path_for_backups.$file_backup);
        if($filesize >= 1024 and $filesize <= 1048576)
        {
            $filesize = round(($filesize / 1024), 2)."Kb";
        }
        elseif($filesize > 1048576 and $filesize <= 1073741824)
        {
            $filesize = round(($filesize / (1024*1024)), 2)."Mb";
        }
        elseif($filesize > 1073741824)
        {
            $filesize = round(($filesize / (1024*1024*1024)), 2)."Gb";
        }
        elseif($filesize < 1024)
        {
            $filesize = round($filesize, 2)."b";
        }

        $file_date = filemtime($path_for_backups.$file_backup);
        $file_date = date("d/M/Y, G:i", $file_date);
        echo "Created $file_date, filesize: $filesize";
        // download link shown only if backup accessable through web
        if(FALSE != stristr(get_option('filechecker_path_for_backups'), ABSPATH))
        {
            echo "<a href=".get_option('siteurl')."/backups/$db_backup target=\"_blank\"  > Download </a>";
        }
        ?>
        <form action="" method="POST">
            <input type="submit" name="delete_backup_file" value="Delete?"> </input>
            <input type="submit" name="restore_backup_file" value="Restore?"> </input>
            <input type="hidden" name="file_backup_name" value="<?php echo $file_backup; ?>" ></input>
            <?php wp_nonce_field('manage_backup', '_wpnonce'); ?>
        </form>
        <?php
       
        
    }

    echo "<br /><u>DB backups:</u><br />";
    
    foreach($db_backups_array as $db_backup)
    {   
        $filesize = filesize($path_for_backups.$db_backup);

        if($filesize >= 1024 and $filesize <= 1048576)
        {
            $filesize = round(($filesize / 1024), 2)."Kb";
        }
        elseif($filesize > 1048576 and $filesize <= 1073741824)
        {
            $filesize = round(($filesize / (1024*1024)), 2)."Mb";
        }
        elseif($filesize > 1073741824)
        {
            $filesize = round(($filesize / (1024*1024*1024)), 2)."Gb";
        }
        elseif($filesize < 1024)
        {
            $filesize = round($filesize, 2)."b";
        }

        $file_date = filemtime($path_for_backups.$db_backup);
        $file_date = date("d/M/Y, G:i", $file_date);
        echo "Created $file_date, filesize: $filesize";
        // download link shown only if backup accessable through web
        if(FALSE != stristr(get_option('filechecker_path_for_backups'), ABSPATH))
        {
            echo "<a href=".get_option('siteurl')."/backups/$db_backup target=\"_blank\"  > Download </a>";
        }
        
        ?>
        <form action="" method="POST">
            <input type="submit" name="delete_backup_file" value="Delete?"> </input>
            <input type="submit" name="restore_backup_file" value="Restore?"> </input>
            <input type="hidden" name="file_backup_name" value="<?php echo $db_backup; ?>" ></input>
            <?php wp_nonce_field('manage_backup', '_wpnonce'); ?>
        </form>
        <?php
    }

    echo "<br /><br /><br />";
    
/////////////////////////////////////////////////// end of backup block
}

?>


<div id="footer">
&copy; <a href='https://siteclean.pro' >Created by siteclean.pro</a><br />
</div>
<?php exit();