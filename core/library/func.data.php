<?php
if(!defined('RQ_ROOT')) exit('Access Denied');

//得到友情链接$num条
function getLink($num=null)
{
	global $host;
	$linkarr=array();
	$linkarray=@include RQ_DATA.'/cache/links.php';
	if($linkarray&&is_array($linkarray)&&isset($linkarray[$host['host']])) $linkarr=$linkarray[$host['host']];
	if($num>0&&count($linkarr)>$num) $linkarr=array_slice($linkarr, 0, $num); 
	return $linkarr;
}

//得到最新$num条$cateid分类的文章
function getLatestArticle($num,$cateid=0)
{
	global $host;
	$articledb=$ids=array();
	$latestarray=@include RQ_DATA.'/cache/latest_'.$host['host'].'.php';
	if(!empty($latestarray)&&isset($latestarray['cateids'][$cateid]))
	{
		$aids=$latestarray['cateids'][$cateid];
		if(!empty($aids))
		{
			if(count($aids)>$num) $aids=array_slice($aids, 0, $num); 
			foreach($aids as $aid) $articledb[]=$latestarray['article'][$aid];
		}
	}
	return $articledb;
}

//得到图片文章
function getPicArticle($num)
{
	global $host;
	$picarray=@include RQ_DATA.'/cache/pic_'.$host['host'].'.php';
	if(!$picarray) $picarray=array();
	if($num>0&&count($picarray)>$num) $picarray=array_slice($picarray, 0, $num); 
	return $picarray;
}

//error_log("You messed up!", 3, RQ_DATA."\logs\dd.txt");

//得到置顶的$num条$cateid分类文章
function getStickArticle($num,$cateid=null)
{
	global $host;
	$stickdata=@include RQ_DATA.'/cache/stick_'.$host['host'].'.php';
	if(!$stickdata) $stickdata=array();
	if(count($stickdata)>$num) $stickdata=array_slice($stickdata, 0, $num); 
	return $stickdata;
}

//得到最新的$num条$cateid分类文章评论
function getLatestComment($num,$cateid=null)
{
	global $host;
	$commentdata=@include RQ_DATA.'/cache/comment_'.$host['host'].'.php';
	if(!$commentdata) $commentdata=array();
	if(count($commentdata)>$num) $commentdata=array_slice($commentdata, 0, $num); 
	return $commentdata;
}

//得到热门文章
function getHotArticle($num,$cateid=null)
{
	global $DB,$hostid,$host;
	if($cateid==null) $cate='';
	else $cate='and cateid='.$cateid;
	$query=$DB->query('Select * from '.DB_PREFIX."article where hostid=$hostid $cate and visible=1 order by views desc limit $num");
	$articledb=array();
	while($article=$DB->fetch_array($query))
	{
		$articledb[]=showArticle($article);
	}
	return $articledb;
}

//得到随机文章
function getRndArticle($num,$cateid=null)
{
	global $DB,$hostid,$host;
	if($cateid==null) $cate=" where hostid=$hostid";
	else $cate=" where hostid=$hostid and cateid=$cateid";
	$query=$DB->query('Select * from '.DB_PREFIX."article $cate and visible=1 order by rand() limit $num");
	$articledb=array();
	while($article=$DB->fetch_array($query))
	{
		$articledb[]=showArticle($article);
	}
	return $articledb;
}

//得到相关文章
function getRelatedArticle($aid,$tagarr,$num)
{
	global $DB,$hostid,$host;
	$articledb=array();
	$tag="'".implode("','",$tagarr)."'";
	$query=$DB->query('Select distinct articleid from '.DB_PREFIX."tag where tag in ($tag) and articleid!=$aid");
	$aidarr=array();
	while($aq=$DB->fetch_array($query))
	{
		$aidarr[]=$aq['articleid'];
	}
	if(!empty($aidarr))
	{
		$aids=implode_ids($aidarr);
		$query=$DB->query('Select * from '.DB_PREFIX."article where hostid=$hostid and aid in ($aids) and visible=1 order by rand() limit $num");
		while($article=$DB->fetch_array($query))
		{
			$articledb[]=showArticle($article);
		}
	}	
	return $articledb;
}

//得到某个分类的某页符合$expr的文章列表
function getCateArticle($expr,$page)
{
	global $DB,$hostid,$host;
	$pagenum = intval($host['list_shownum']);
	$start_limit = ($page - 1) * $pagenum;
	$sql = "SELECT a.*,c.name,c.cid,c.url as curl FROM ".DB_PREFIX."article a,".DB_PREFIX."category c WHERE c.hostid=$hostid and a.visible='1' and c.cid=a.cateid And $expr ORDER BY aid DESC LIMIT $start_limit, ".$pagenum;
	$articledb=array();
	$query=$DB->query($sql);
	$arg1=$arg2=$host['friend_url'];
	if($arg1=='aid') $arg1=$arg2='cid';
	else if($arg1=='url') $arg2='curl';
	while($article=$DB->fetch_array($query))
	{
		$article['crg']=$arg1.'='.$article[$arg2];
		$articledb[]=showArticle($article);
	}
	return $articledb;
}

//得到符合条件的文章，包含附件
function getArticle($expr)
{
	global $DB,$hostid,$host;
	$sql = "SELECT * FROM ".DB_PREFIX."article WHERE visible='1' and hostid=$hostid And $expr limit 1";
	$article=$DB->fetch_first($sql);
	if(!empty($article))
	{
		$article=showArticle($article);
		//处理附件
		if (!empty($article['attachments'])) 
		{
			if (is_array($article['attachments'])) 
			{
				$aidarr=array();
				foreach($article['attachments'] as $k=>$v)
				{
					$aidarr[]=$v['aid'];
				}
				$aids=implode_ids($aidarr);	
				$downloads=$DB->query('select aid,downloads from '.DB_PREFIX."attachment where aid in (".$aids.')');
				while($dds=$DB->fetch_array($downloads))
				{
					$attacharr[$dds['aid']]=$dds['downloads'];
				}

				foreach($article['attachments'] as $k=>$attach)
				{
					$aid=$attach['aid'];
					$article['attachments'][$k]['downloads']=$attacharr[$aid];
					$article['attachments'][$k]['filesize']=(int)($article['attachments'][$k]['filesize']/1024);
					$arg=argUrlRewrite('attachment.php','aid');
					if($attach['isimage'])
					{
						$file="<a href='attachment.php?$arg=$aid' target='_blank'><img src='attachment.php?$arg=$aid' alt='{$attach['filename']}'></a>";
					}
					else
					{
						$file="<a href='attachment.php?$arg=$aid' target='_blank'>{$attach['filename']}</a>";
					}

					if(strpos($article['content'],"[attach=$aid]")!==false)
					{
						$article['content']=str_replace("[attach=$aid]",$file,$article['content']);
						unset($article['attachments'][$k]);//加在文章中后就不用在后边显示了.
					}
					else
					{
						$article['attachments'][$k]['arg']=$arg.'='.$aid;
					}
				}
			}
		}
		if(!empty($article['tag'])) $article['tag']=explode(',',$article['tag']);
	}
	return $article;
}

//按文章的aid，当前页码和每页的条数得到符合条件的评论
function getComment($aid,$page,$pagenum)
{
	global $DB,$hostid,$host;
	$start_limit = ($page - 1) * $pagenum;
	$cmtorder=$host['comment_order'] ? 'ASC' : 'DESC';
	$sql="SELECT * FROM ".DB_PREFIX."comment WHERE articleid='$aid' AND visible='1' ORDER BY cid $cmtorder limit $start_limit,$pagenum";
	
	$commentdb=array();
	$query=$DB->query($sql);
	while($comment=$DB->fetch_array($query))
	{
		$comment['dateline']=date($host['time_comment_format'], $comment['dateline']);
		$commentdb[]=$comment;
	}
	return $commentdb;
}

//得到热门评论文章
function getHotComment($num,$cateid=null)
{
	global $DB,$host;
	if($cateid==null) $cate='';
	else $cate=' where cateid='.$cateid;
	$query=$DB->query('Select * from '.DB_PREFIX."article where visible=1 order by views desc limit $num");
	$articledb=array();
	while($article=$DB->fetch_array($query))
	{
		$articledb[]=showArticle($article);
	}
	return $articledb;
}