<?php
if(empty($action)) $action = 'list';
$groupdb=array(4=>'创始人',3=>'管理员',2=>'编辑',1=>'注册会员',0=>'游客');
	elseif($action == 'list') 
		if($page) {
			$start_limit = ($page - 1) * 30;
		} else {
			$start_limit = 0;
			$page = 1;
		}
		$sqladd = " WHERE hostid='$hostid' ";
		$pagelink = '';
		//察看是否发表过评论
		$lastpost = (!isset($_GET['lastpost']))?'':$_GET['lastpost'] ;
		if ($lastpost == 'already') {
			$sqladd .= " AND lastpost <> '0'";
			$pagelink .= '&lastpost=already';
			$subnav = '发表过评论的用户';
		}
		elseif ($lastpost == 'never') {
			$sqladd .= " AND lastpost='0'";
			$pagelink .= '&lastpost=never';
			$subnav = '从未发表过评论的用户';
		}
		//察看用户组
		if ($showgid && in_array($showgid,array_flip($groupdb))) {
			$sqladd .= " AND groupid='$gid'";
			$pagelink .= '&groupid='.$showgid;
			$subnav = $groupdb[$showgid].'的用户';
		}
		//察看IP段
		$ip =isset($_GET['ip'])? char_cv($_GET['ip']):'';
		if ($ip)
			$frontlen = strrpos($ip, '.');
			$ipc = substr($ip, 0, $frontlen);
			$sqladd .= " AND (loginip LIKE '%".$ipc."%')";
			$pagelink .= '&ip='.$ip;
			$subnav  = '上次登陆IP为['.$ip.']同一C段的相关用户';
		}
		//搜索用户
		$srhname =isset($_GET['srhname'])?( char_cv($_GET['srhname'] ? $_GET['srhname'] : $_POST['srhname'])):'';
		if ($srhname) {
			$sqladd .= " AND (BINARY username LIKE '%".str_replace('_', '\_', $srhname)."%' OR username='$srhname')";
			$pagelink .= '&srhname='.$srhname;
		}

		//排序
		$order =isset($_GET['order'])? $_GET['order']:'';
		if ($order && in_array($order,array('username','logincount','regdateline'))) {
			$orderby = $order;
			$orderdb = array('username'=>'用户名','logincount'=>'登陆次数','regdateline'=>'注册时间');
			$subnav = '以'.$orderdb[$order].'降序察看全部用户';
			$pagelink .= '&order='.$order;
		} else {
			$orderby = 'uid';
		}
		$tatol     = $DB->num_rows($DB->query("SELECT uid FROM ".DB_PREFIX."user ".$sqladd));
		$multipage = multi($tatol, 30, $page, 'admin.php?file=user&action=list'.$pagelink);
		$query = $DB->query("SELECT * FROM ".DB_PREFIX."user $sqladd ORDER BY $orderby DESC LIMIT $start_limit, 30");
		$userdb = array();
		while ($user = $DB->fetch_array($query))
			$user['lastpost']    = $user['lastpost'] ? date('Y-m-d H:i',$user['lastpost']) : '从未发表';
			$user['regdateline'] = date('Y-m-d',$user['regdateline']);
			$user['url']         = $user['url'] ? '<a href="'.$user['url'].'" target="_blank">'.$user['url'].'</a>': '<font color="#FF0000">Null</font>';
			$user['logintime'] = $user['logintime'] ? date('Y-m-d H:i',$user['logintime']) : '从未登陆';
			$user['loginip']   = $user['loginip'] ? $user['loginip'] : '从未登陆';
			$user['group'] = $groupdb[$user['groupid']];
			$user['disabled'] = $user['groupid'] >= $groupid ? 'disabled' : '';
			$userdb[] = $user;
		}
		unset($user);
		$DB->free_result($query);
	} //end list