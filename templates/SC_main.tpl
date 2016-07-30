<p><b><h4>Launch manual check files</h4></b></p>

<div>
<form action='' method=POST>
<p><input class="button-primary" type="submit" name="manual" value="Manual check"></input></p>
{wp_nonce_check}
</form>
</div>

<form action='' method=POST>
<p>Data file was created at {data file date}</p>
Create new data file?  
<input class="button-primary" type="submit" name="rescan" value="generate"></input>
{wp_nonce_check}
</form>

<p><b><h4>Set auto launch frequency (automatic integrity checking):</h4></b></p>

<form action = '' method = 'POST'>
<p><input type="radio" name="select_cron_freq" value="1"> Once per day</p>
<p><input type="radio" name="select_cron_freq" value="2"> Twice per day</p>
<p><input type="radio" name="select_cron_freq" value="24"> Once per hour</p>
<p class="submit"><input type="submit" class="button-primary" value="Set frequency" name="set_freq"></p>
{wp_nonce_check2}
</form>

<p>Next cron starts at {var1}, after {var2}</p>
<p>Current server`s time is {var3}</p>
