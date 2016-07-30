<h2>Create new backup</h2>
<p>All backups are stored within 'backups' in plugin`s directory </p>
<form action ="" method = "POST">
<input class="button-primary" type="submit" name='file_backup' value="Create files backup"></input> Files from {path} will be backuped<br /><br />

<select name="db_selected">
  {set_db_name}
</select>
<input class="button-primary" type="submit" name='db_backup' value="Create database backup"></input><br />
<p><input class="button-primary" type="submit" name='full_backup' value="Create full (files + current DB) backup"></input><br /></p>
{wp_nonce_check2} 
</form>
<h2>Availiable backups</h2>



