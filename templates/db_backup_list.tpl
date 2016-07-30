<u>Database backup for <b>{db_name}</b> database:</u><br />Created {creation time}, filesize: {filesize}<form action="" method="POST">
                <input type="submit" class="button-primary" value="Delete?" name="delete_backup_db"/>
                <input type="submit" class="button-primary" value="Restore?" name="restore_backup_file"/>                
                <input type="hidden" name="db_backup_name" value={filename} ></input>
                {wp_nonce_check}
</form>