<?phpif(!defined('RQ_ROOT')) exit('Access Denied');$loginerr='';if($action='logout'&&$uid&&$username)//退出系统{	$adminitem=array();	$groupid=0;	setcookie('sessionid',null);	$sessionid=getRandStr(10);	$DB->query('update '.DB_PREFIX."user set `sessionid`='$sessionid' where uid='$uid'");	ob_end_clean();	ob_start();	include(RQ_CORE.'/manager/view/header.php');}$lusername=$lpassword='';if(RQ_POST){	$lusername=$_POST['username'];	$lpassword=$_POST['password'];	$sql='Select * from '.DB_PREFIX."user where `username`='$lusername'";	$result=$DB->fetch_first($sql);	if($result)	{		if($result['password']==md5($lpassword))		{			$uid=$result['uid'];			if($result['groupid']==1) $loginerr='您是注册会员,没有权限登陆后台';			elseif($result['groupid']<4&&$result['hostid']!=$hostid) $loginerr='您无权限登陆该网站后台';//不是创始人,只能登陆一个站点			else			{				$sessionid=getRandStr(30,true);//生成那个登陆信息				$expire=isset($_POST['rememberme'])?$timestamp+31536000:0;//过期时间设置，记住我为最长时间，否则为浏览器关闭则无效				setcookie('sessionid',$sessionid,$expire,'',$host['host']);				$DB->query('update '.DB_PREFIX."user set `logincount`=`logincount`+1,`loginip`='$onlineip',`logintime`='$timestamp',`sessionid`='$sessionid',`useragent`='$useragent' where uid='$uid'");				$DB->query('insert into '.DB_PREFIX."log (`user`,`dateline`,`type`,`useragent`,`ip`,`content`) values ('$lusername','$timestamp','l','$useragent','$onlineip','成功')");				redirect('登陆成功', RQ_FILE.'?');			}		}		else $loginerr='密码错误';	}	else $loginerr='不存在的用户名';	$DB->query('insert into '.DB_PREFIX."log (`user`,`dateline`,`type`,`useragent`,`ip`,`content`) values ('$lusername','$timestamp','login','$useragent','$onlineip','$loginerr')");}if($loginerr) $loginerr='<font color="red">'.$loginerr.'</font>';if($groupid<2) $file='login';//没有登陆状态else $file='main';