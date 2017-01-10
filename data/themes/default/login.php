<?php
if(!defined('RQ_ROOT')) exit('Access Denied');
print <<<EOT
<div id=main>
<div id=fullbox>
<div class=full>
<h2 class="title">登陆</h2>
<p>登陆后, 可以使用您的专署名字发表评论<br />系统在您发表评论时自动填写个人信息.</p>
<form action="profile.php" method="post" onsubmit="return checkloginform();">
<input type="hidden" name="action" value="dologin" />
<input type="hidden" name="formhash" value="$formhash" />
<div class="formbox">
  <p>
    <label for="username">名字(*):
	<input name="username" id="username" type="text" size="24" tabindex="1" maxlength="100" value="" class="formfield" />
	</label>
  </p>
  <p>
    <label for="password">密码(*):
	<input name="password" id="password" type="password" size="24" tabindex="2" maxlength="100" value="" class="formfield" />
	</label>
  </p>

EOT;
if ($options['seccode_enable']) {print <<<EOT

  <p>
    <label for="clientcode">验证码(*):
	<input name="clientcode" id="clientcode" value="" tabindex="3" class="formfield" size="6" maxlength="6" /> <img id="seccode" class="codeimg" src="include/seccode.php" alt="单击图片换张图片" border="0" onclick="this.src='include/seccode.php?update=' + Math.random()" />(不要输入第二个数字)
	</label>
  </p>

EOT;
}print <<<EOT

  <p>
    <button type="submit" class="formbutton">确定</button>
  </p>
</div>
</form>
</div>
</div>
</div>

EOT;
include RQ_DATA."/themes/$theme/footer.php";
?>