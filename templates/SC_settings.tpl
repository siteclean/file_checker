<div><h4>Main options for file checker</h4></div>
<div class="tg-wrap"><table class="tg">
  <tr>
    <th class="tg-d0j2">Option</th>
    <th class="tg-d0j2">Value</th>
  </tr>
  <tr>
    <td class="tg-vn4c">Email for reports</td>
    <td class="tg-vn4c">{email}</td>
  </tr>
  <tr>
    <td class="tg-yw4l">Directory for scan</td>
    <td class="tg-yw4l">{scan_dir}</td>
  </tr>
  <tr>
    <td class="tg-6k2t">Files with these extensions will be scanned</td>
    <td class="tg-6k2t">{files_to_scan}</td>
  </tr>
  <tr>
    <td class="tg-yw4l">Files excluded from scan</td>
    <td class="tg-yw4l">{excluded_files}{delete excluded files}</td>
  </tr>
  <tr>
    <td class="tg-6k2t">Directories excluded from scan</td>
    <td class="tg-6k2t">{excluded_dirs}{delete excluded dirs}

</td>
  </tr>
  
</table></div>

<div>
<h3>Update settings</h3>
<form action="" method="POST">
<br />Enter your email for reports: <input type="text" name = "email" > </input>
<br /> <br />
Directory for scan: <input type="text" size="70" name = "scan_dir" > </input><br />
<br /><br />

Files with these extensions will be scanned: <input type="text" size="70" name = "extensions" > </input><br />
<i>( Recommended value: <b>php, php3, php4, php5, php6, phps, pl, cgi, shtml, phtml, htaccess, js, html, htm )</b></i><br /><br />


Files to be excluded from scan (separated by comma): <input type="text" size="70" name = "files_to_exclude" > </input><br />

Directories to be excluded from scan (separated by comma): <input type="text" size="70" name = "dirs_to_exclude" > </input><br /><br />

<b>Update settings? (<i>current settings will be overwritten!</i>)</b> <input class="button-primary" type="submit" name="update" value="update"></input><br /><br />
{wp_nonce}

</form>