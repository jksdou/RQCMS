<?php
if(!defined('RQ_ROOT')) exit('Access Denied');
include RQ_CORE.'/include/article.php';
if(empty($action)) $action='list';

$uquery = '';
if ($groupid < 3) {
	$uquery = " AND a.uid='$uid'";
}
$hidden=$DB->fetch_first("SELECT count(*) as ct FROM ".DB_PREFIX."article a WHERE a.visible=0 and a.hostid=$hostid".$uquery);
$hiddenCount=$hidden['ct'];
if(RQ_POST)
{
	if(in_array($action,array('add','mod')))
	{
		$title       = trim($_POST['title']);
		$cateid      = intval($_POST['cid']);
		$url         =  $_POST['url'];
		$excerpt     =  $_POST['excerpt'];
		$content     = $_POST['content'];
		$password    =  $_POST['password'];
		$keywords    = trim($_POST['keywords']);
		$tags        = getTagArr(trim($_POST['tag']));
		$closed      = isset($_POST['closed'])? intval($_POST['closed']):0;
		$visible     = isset($_POST['visible'])?intval($_POST['visible']):0;
		$stick       = isset($_POST['stick'])?intval($_POST['stick']):0;
		$dateline    = isset($_POST['edittime'])?getDateLine():time();
		$attachments = '';//一个序列化的结果,附件名,Id,大小
		$attachInfo=array();//
		$tag=!empty($tags)?implode(',',$tags):'';
		saveCookie();
	}
	$attachments=array();
	switch($action)
	{
		case 'add':
			if(empty($title)) redirect('标题不得为空','admin.php?file=article&action=add');
			if(empty($content)) redirect('内容不得为空','admin.php?file=article&action=add');
			$oidarr=$DB->fetch_first('Select max(oid) as oid from '.DB_PREFIX.'article where hostid='.$hostid);
			$oid=$oidarr['oid']+1;
			
			// 插入数据部分
			$attachments=getAttach();
			$DB->query("INSERT INTO ".DB_PREFIX."article (hostid,oid,cateid, userid,username, title, excerpt, content, keywords,tag, dateline,modified, closed, visible, stick, password,url) VALUES ('$hostid','$oid','$cateid', '$uid','$username', '$title', '$excerpt', '$content', '$keywords','$tag', '$dateline','$dateline','$closed', '$visible', '$stick', '$password','$url')");
			$articleid = $DB->insert_id();

			if($attachments&&is_array($attachments))
			{
				$fileidarr=array();
				foreach($attachments as $key=>$attachment)
				{
					$DB->unbuffered_query("Insert into ".DB_PREFIX."attachment (`articleid`,`dateline`,`filename`,`filetype`,`filesize`,`filepath`,`thumb_filepath`,`thumb_width`,`thumb_height`,`isimage`,`hostid`) values ('$articleid','$dateline','$attachment[filename]','$attachment[filetype]','$attachment[filesize]','$attachment[filepath]','$attachment[thumb_filepath]','$attachment[thumb_width]','$attachment[thumb_height]','$attachment[isimage]','$hostid')");
					$attachments[$key]['aid']=$DB->insert_id();
					$fileidarr[$attachments[$key]['localid']]=$attachments[$key]['aid'];
					unset($attachments[$key]['filepath']);
					unset($attachments[$key]['thumb_filepath']);
				}
				foreach($fileidarr as $localid=>$fileid)
				{
					if($content!='') $content=str_replace('[localfile='.$localid.']','[attach='.$fileid.']',$content);
				}
			}
			
			$attachstr=addslashes(serialize($attachments));
			$DB->query('update '.DB_PREFIX."article set attachments='$attachstr',`content`='$content' where aid='$articleid'");
			//添加tags
			$DB->query('delete from '.DB_PREFIX."tag where articleid='$articleid' and hostid='$hostid'");
			if($tags)
			{
				for($i=0; $i<count($tags); $i++)
				{
					$tags[$i] = trim($tags[$i]);
					if ($tags[$i]) 
					{
						$tag  = $DB->fetch_first("SELECT tag FROM ".DB_PREFIX."tag WHERE tag='$tags[$i]' and articleid=$articleid");
						if(!$tag) $DB->query("INSERT INTO ".DB_PREFIX."tag (tag,articleid,hostid) VALUES ('$tags[$i]','$articleid','$hostid')");
					}
				}
			}
			$DB->unbuffered_query("UPDATE ".DB_PREFIX."user SET articles=articles+1 WHERE uid='$uid'");
			clearCookie();
			if(!$visible)
			{
				stick_recache();
				rss_recache();
				stick_recache();
				pics_recache();
				latest_recache();
			}
			redirect('添加文章成功', RQ_FILE.'?file=article&action=add'.($visible?'':'&view=hidden'));
			break;
		case 'mod'://修改文章
			$aid=intval($_POST['aid']);
			$old=$DB->fetch_first('Select * from '.DB_PREFIX."article where aid=$aid and hostid=$hostid");
			if(!$old) redirect('不存在的记录','admin.php?file=article&action=list');
			if($old['userid']!=$uid&&$groupid<3) redirect('您无权修改别人的文章','admin.php?file=article&action=list');
			if(empty($title)) redirect('标题不得为空','admin.php?file=article&action=mod&aid='.$aid);
			if(empty($content)) redirect('内容不得为空','admin.php?file=article&action=mod&aid='.$aid);
			
			//附件先处理
			$attachments=getAttach();
			$oldattach=unserialize($old['attachments']);
			$oldattachids=array();
			foreach($oldattach as $k=>$v)
			{
				$oldattachids[]=$v['aid'];
			}
			$keepattach=isset($_POST['keep'])?$_POST['keep']:array();

			if(!empty($keepattach)&&is_array($keepattach)&&count($keepattach)<count($oldattachids))
			{
				$diff=array_diff($oldattachids,$keepattach);
				foreach($diff as $key=>$attid)
				{
					foreach($attachments as $k=>$v)
					{
						foreach($oldattach as $o=>$d)
						{
							if($d['aid']==$attid)//删除的是这条记录
							{
								if($d['filename']==$v['filename'])//这里是就是更新了.
								{
									$DB->query("update ".DB_PREFIX."attachment set `filesize`='$attachment[filesize]',`filepath`='$attachment[filepath]',`thumb_filepath`='$attachment[thumb_filepath]',`thumb_width`='$attachment[thumb_width]',`thumb_height`='$attachment[thumb_height]' where articleid=$aid and aid=$attid and hostid=$hostid");
									$oldattach[o]=$attachments[$v];
									$oldattach[o]['aid']=$attid;
									unset($attachments[$v]);
									unset($diff[$key]);
									break;
								}
							}
						}
					}
				}
				$diffids=implode(',',$diff);
				if($diffids) 
				{
					$dquery=$DB->query('select * from '.DB_PREFIX."attachment where aid in ($diffids) and articleid=$aid and hostid=$hostid");
					while($dfetch=$DB->fetch_array($dquery))
					{
						$filepath=RQ_DATA.'/files/'.$dfetch['filepath'];
						if(file_exists($filepath)) @unlink($filepath);
						$thumbpath=RQ_DATA.'/files/'.$dfetch['thumb_filepath'];
						if(file_exists($filepath)) @unlink($thumbpath);
					}
					$DB->query('Delete from '.DB_PREFIX."attachment where aid in ($diffids) and articleid=$aid and hostid=$hostid");
				}
			}
			if($attachments)
			{
				$fileidarr=array();
				foreach($attachments as $attachment)
				{
					$DB->unbuffered_query("Insert into ".DB_PREFIX."attachment (`articleid`,`dateline`,`filename`,`filetype`,`filesize`,`filepath`,`thumb_filepath`,`thumb_width`,`thumb_height`,`isimage`,`hostid`,`modified`) values ('$aid','$dateline','$attachment[filename]','$attachment[filetype]','$attachment[filesize]','$attachment[filepath]','$attachment[thumb_filepath]','$attachment[thumb_width]','$attachment[thumb_height]','$attachment[isimage]','$hostid','$timestamp')");
					$attachment['aid']=$DB->insert_id();
					$fileidarr[$attachment['localid']]=$attachment['aid'];
					unset($attachment['filepath']);
					unset($attachment['thumb_filepath']);
					$oldattach[]=$attachment;
				}
				foreach($fileidarr as $localid=>$fileid)
				{
					if($content!='') $content=str_replace('[localfile='.$localid.']','[attach='.$fileid.']',$content);
				}
			}
			
			$attachstr=addslashes(serialize($oldattach));
			// 插入数据部分
			$DB->query("Update ".DB_PREFIX."article set `cateid`='$cateid',`title`='$title',`excerpt`='$excerpt',`content`='$content',`keywords`='$keywords',`tag`='$tag',`modified`='$timestamp',`attachments`='$attachstr',`closed`='$closed',`visible`='$visible',`stick`='$stick',`password`='$password',`url`='$url' where aid=$aid");
			
			//添加tags
			$DB->query('delete from '.DB_PREFIX."tag where articleid='$aid' and hostid='$hostid'");
			if($tags)
			{
				for($i=0; $i<count($tags); $i++)
				{
					$tags[$i] = trim($tags[$i]);
					if ($tags[$i]) 
					{
						$DB->query("INSERT INTO ".DB_PREFIX."tag (tag,articleid,hostid) VALUES ('$tags[$i]','$aid','$hostid')");
					}
				}
			}
			clearCookie();
			stick_recache();
			rss_recache();
			pics_recache();
			latest_recache();
			redirect('修改文章成功', RQ_FILE.'?file=article&action=list'.($visible?'':'&view=hidden'));
		break;
		case 'domore':
			if(isset($_POST['aids'])&&is_array($_POST['aids']))
			{
				$view=isset($_POST['view'])?'hidden':'';
				$aids=implode_ids($_POST['aids']);
				$aquery=$DB->query('Select aid from '.DB_PREFIX."article a where a.aid in ($aids) and a.hostid='$hostid' $uquery");
				$aidarr=array();
				while($ainfo=$DB->fetch_array($aquery))
				{
					$aidarr[]=$ainfo['aid'];
				}
				$aids=implode_ids($aidarr);
				if(in_array($do,array('delete','move')))
				{
					$query=$DB->query('Select * from '.DB_PREFIX."article where aid in ($aids)");
					while($article=$DB->fetch_array($query))
					{
						$articledb[]=$article;
					}
				}
				else
				{
					$view=isset($_POST['view'])?'hidden':'';
					switch($do)
					{
						case 'dodelete':
							$query=$DB->query('Select count(userid) as cu,userid from '.DB_PREFIX."article where aid in ($aids) group by userid");
							while($uinfo=$DB->fetch_array($query))
							{
								$DB->query('update '.DB_PREFIX."user set articles=articles-{$uinfo['cu']} where uid={$uinfo['userid']} and hostid='$hostid'");//更新用户文章统计数
							}
							$DB->query('delete from '.DB_PREFIX."article where aid in ($aids) and hostid='$hostid'");
							$DB->query('delete from '.DB_PREFIX."tag where articleid in ($aids) and hostid='$hostid'");
							$DB->query('delete from '.DB_PREFIX."attachment where articleid in ($aids) and hostid='$hostid'");
							stick_recache();
							rss_recache();
							stick_recache();
							pics_recache();
							redirect('您选择的文章已成功删除', RQ_FILE.'?file=article&action=list'.($view?'&view='.$view:''));
							break;
						case 'domove':
							$cid=$_POST['cid'];
							$cateinfo=$DB->fetch_first('select * from '.DB_PREFIX."category where cid='$cid' and hostid='$hostid'");
							if($cateinfo)
							{
								$DB->query('update '.DB_PREFIX."article set cateid='$cid' where aid in ($aids) and hostid='$hostid'");
								stick_recache();
								rss_recache();
								stick_recache();
								pics_recache();
								latest_recache();
								redirect('您选择的文章成功移动', RQ_FILE.'?file=article&action=list&cid='.$cid.($view?'&view='.$view:''));
							}
							else redirect('您选择的栏目不存在', RQ_FILE.'?file=article&action=list');
						break;
					}
				}
			}
			else redirect('请选择要操作的文章', RQ_FILE.'?file=article&action=list');
			break;
		case 'list':
			if ($do == 'search') 
			{
				$searchsql='Select a.*,c.name as cname from '.DB_PREFIX.'article a,'.DB_PREFIX."category c where a.hostid=$hostid";
				$keywords = !empty($_POST['keywords'])?trim($_POST['keywords']):'';
				if ($keywords) 
				{
					$keywords = str_replace("_","\_",$keywords);
					$keywords = str_replace("%","\%",$keywords);
					if(preg_match("(AND|\+|&|\s)", $keywords) && !preg_match("(OR|\|)", $keywords)) {
						$andor = ' AND ';
						$sqltxtsrch = '1';
						$keywords = preg_replace("/( AND |&| )/is", "+", $keywords);
					} else {
						$andor = ' OR ';
						$sqltxtsrch = '0';
						$keywords = preg_replace("/( OR |\|)/is", "+", $keywords);
					}
					$keywords = str_replace('*', '%', addcslashes($keywords, '%_'));
					foreach(explode("+", $keywords) AS $text) {
						$text = trim($text);
						if($text) {
							$sqltxtsrch .= $andor;
							$sqltxtsrch .= "(a.content LIKE '%".str_replace('_', '\_', $text)."%' OR a.excerpt LIKE '%".$text."%' OR a.title LIKE '%".$text."%')";
						}
					}
					$searchsql .= " AND ($sqltxtsrch)";
				}
				if(!empty($_POST['cateid']))
				{
					$searchsql .= " AND a.cateid='".intval($_POST['cateid'])."' and a.cateid=c.cateid";
				}
				$searchsql .= !empty($_POST['startdate']) ? " AND dateline < '".strtotime($_POST['startdate'])."'" : '';
				$searchsql .= !empty($_POST['enddate'] )? " AND dateline > '".strtotime($_POST['enddate'])."'" : '';
				$squery=$DB->query($searchsql.$uquery);
				$multipage='';
				$articledb = array();
				while ($article = $DB->fetch_array($squery)) {
					if ($article['attachments']) {
						$article['attachments'] = count(unserialize($article['attachments']));
						$article['attachment'] = '<a href="admin.php?file=attachment&action=list&amp;aid='.$article['aid'].'">操作</a>('.$article['attachments'].')';
					} else {
						$article['attachment'] = '<a href="admin.php?file=attachment&action=list&amp;aid='.$article['aid'].'"><span class="yes">上传</span></a>';
					}
					$article['dateline'] = date('Y-m-d H:i',$article['dateline']);
					$articledb[] = $article;
				}
				$tatol=count($articledb);
			}
			else redirect('请指定搜索条件', RQ_FILE.'?file=article&action=list');
			break;
		default:
		redirect('未定义操作', RQ_FILE.'?file=article&action=list');
	}
}
else
{
	if($action=='add'||$action=='mod')
	{
		$attachdb=array();//上传的附件数据
		$aid=isset($_GET['aid'])?intval($_GET['aid']):0;
		$article=$DB->fetch_first('Select * from '.DB_PREFIX."article where aid=$aid and hostid=$hostid");
		$time=empty($article['dateline'])?time():$article['dateline'];
		list($newyear,$newmonth,$newday,$newhour,$newmin,$newsec)=explode('-',date("Y-m-d-H-i-s",$time));
		//类别
		if(!$article)
		{
			$closecomment_check=$stick_check='';
			$visible_check='checked';
		}
		else
		{
			$visible_check=$article['visible']?'checked':'';
			$closecomment_check=$article['closed']?'checked':'';
			$stick_check=$article['stick']?'checked':'';
			$aquery=$DB->query("Select * from ".DB_PREFIX."attachment where articleid=$aid and hostid=$hostid");
			while($ath=$DB->fetch_array($aquery))
			{
				$ath['dateline']=date('Y-m-d',$ath['dateline']);
				$ath['filesize']=sizecount($ath['filesize']);
				$attachdb[]=$ath;
			}
		}
		//调用编辑器主体
		include RQ_CORE.'/manager/editor/fckeditor.php';
		//设置描述区域
		$oFCKeditor = new FCKeditor('excerpt');
		$oFCKeditor->Value = $article['excerpt'];
		$oFCKeditor->Height = '200';
		$oFCKeditor->ToolbarSet = 'Basic';
		//描述区域的模板变量
		$descriptionarea = $oFCKeditor->CreateHtml();

		//设置内容区域
		$oFCKeditor = new FCKeditor('content');
		$oFCKeditor->Value = $article['content'];
		//内容区域的模板变量
		$contentarea = $oFCKeditor->CreateHtml();
		$tdtitle='添加内容';
	}
	else if($action=='list')
	{
		$searchsql='';
		$addquery='';
		$pagelink='';
		$view=isset($_GET['view'])?$_GET['view']:'';
		$tag=isset($_GET['tag'])?$_GET['tag']:'';
		$cid=isset($_GET['cid'])?intval($_GET['cid']):'';
		if ($view == 'stick') {
			$addquery = " AND a.stick='1' and a.visible=1";
			$pagelink = '&view=stick';
		} elseif ($view == 'hidden') {
			$addquery = " AND a.visible='0'";
			$pagelink = '&view=hidden';
		} elseif ($cid) {
			$cate = $DB->fetch_first("SELECT name FROM ".DB_PREFIX."category WHERE cid='$cid' and hostid=$hostid");
			$addquery = " AND a.cateid='$cid' and a.visible=1";
			$pagelink = '&cid='.$cid;
		} 
		else $addquery = " and a.visible=1";		
		
		if($page) {
		$start_limit = ($page - 1) * 30;
		} else {
			$start_limit = 0;
			$page = 1;
		}
		$articledb = array();
		if(empty($tag))
		{
			$rs = $DB->fetch_first("SELECT count(*) AS articles FROM ".DB_PREFIX."article a WHERE 1 $searchsql $addquery $uquery and hostid=$hostid");
			$tatol = $rs['articles'];
			$multipage = multi($tatol, 30, $page, 'admin.php?file=article&action=list'.$pagelink);
			$query = $DB->query("SELECT a.aid,a.title,a.cateid,a.dateline,a.comments,a.attachments,a.visible,a.views,a.stick,a.hostid,c.name as cname FROM ".DB_PREFIX."article a 
			LEFT JOIN ".DB_PREFIX."category c ON c.cid=a.cateid
			WHERE a.hostid='$hostid' $searchsql $addquery $uquery ORDER BY a.aid DESC LIMIT $start_limit, 30");
		}
		else
		{
			$item = addslashes($tag);
			$tagarray=array();
			$tagquery=$DB->query('select distinct articleid from '.DB_PREFIX."tag  WHERE tag='$item' and hostid='$hostid'");
			while($result=$DB->fetch_array($tagquery))
			{
				$tagarray[]=$result['articleid'];
			}
			$tatol=count($tagarray);
			if (!$tatol) 
			{
				redirect('标签不存在', 'admin.php?file=article&action=list');
			}
			$pagelink = '&tag='.urlencode($item);
			$multipage = multi($tatol, 30, $page, 'admin.php?file=article&action=list'.$pagelink);
			
			$articleids=implode(',',$tagarray);
			$query=$DB->query('Select a.aid,a.title,a.cateid,a.dateline,a.comments,a.attachments,a.visible,a.views,a.stick,c.name as cname from '.DB_PREFIX."article a LEFT JOIN ".DB_PREFIX."category c ON c.cid=a.cateid where a.aid in ($articleids) and a.hostid='$hostid'");
			$subnav = 'Tags:'.$item;
		}
		while ($article = $DB->fetch_array($query)) {
				if ($article['attachments']) {
					$article['attachments'] = count(unserialize($article['attachments']));
					$article['attachment'] = '<a href="admin.php?file=attachment&action=list&amp;aid='.$article['aid'].'">操作</a>('.$article['attachments'].')';
				} else {
					$article['attachment'] = '<a href="admin.php?file=attachment&action=list&amp;aid='.$article['aid'].'"><span class="yes">上传</span></a>';
				}
				$article['dateline'] = date('Y-m-d H:i',$article['dateline']);
				$articledb[] = $article;
			}
		unset($article);
		$DB->free_result($query);
	}
}
?>