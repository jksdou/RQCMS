<?php
if(!defined('RQ_ROOT')) exit('Access Denied');
print <<<EOT
<div id="simpleHeader"></div>
<div class="loginBox">
  <table border="0" cellpadding="5" cellspacing="1">
    <form method="post" action="admin.php?file=login">
      <input type="hidden" name="action" value="login" />
      <tr>
        <td nowrap="nowrap">帐号:<br />
          <input class="formfield" name="username" value="" style="width:150px" /></td>
      </tr>
      <tr>
        <td nowrap="nowrap">密码:<br />
          <input class="formfield" type="password" name="password" value="" style="width:150px" /></td>
      </tr>
	  <tr>
        <td> <input name="rememberme" type="checkbox" value="1">记住我</td>
      </tr>
      <tr>
        <td><input type="submit" class="formbutton" value="登陆" /><br>{$loginerr}</td>
      </tr>
    </form>
  </table>
</div>
EOT;
?>