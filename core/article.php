<?php
if(!defined('RQ_ROOT')) exit('Access Denied');
$artarg=$host['friend_url'];
if(!isset($_GET[$artarg])) message('未定义参数');
$expr="$artarg='{$_GET[$artarg]}'";
$page=isset($_GET['page'])?intval($_GET['page']):1;//这个是评论的页数

$comment_username=isset($_COOKIE['comment_username'])?$_COOKIE['comment_username']:'';
$comment_url=isset($_COOKIE['comment_url'])?$_COOKIE['comment_url']:'';

$article=getArticle($expr);
if(empty($article)) message('该文章不存在或已被删除',RQ_HTTP.$host['host']);

//如果启用了自动缓存，先判断是否超时的
if(RQ_CACHE) cacheControl($article['lastmodified']);

//现在是对数据再做处理
$title=$article['title'];
$keywords=$article['keywords'];
$description=str_replace("<p>","",$article['excerpt']);
$description=str_replace("</p>","",$description);
$aid=$article['aid'];

//隐藏变量,方便那些做模板可以单独显示月份和号数的的朋友.

if (!empty($article['readpassword']) &&(isset($_POST['readpassword'])&&$_POST['readpassword']!=$article['readpassword'])&& (isset($_COOKIE['readpassword_'.$aid])&& $_COOKIE['readpassword_'.$aid] != $article['readpassword']) && $groupid<3) 
{
	$article['allowread'] = false;
}
else 
{
	$article['allowread'] = true;
	$DB->unbuffered_query("UPDATE ".DB_PREFIX."article SET views=views+1 WHERE aid='$aid'");

	//处理PHP高亮
	$article['content'] = preg_replace("/\s*\[php\](.+?)\[\/php\]\s*/ies", "phphighlite('\\1')", $article['content']);
	if($article['cateid']=='0')
	{
		$article['cname']=$article['carg']='';
	}
	else
	{
		$article['cname'] = $cates[$article['cateid']]['name'];
		$article['carg']= $cates[$article['cateid']]['crg'];
	}
	// 评论	
	$commentdb=array();
	if ($article['comments'])
	{
		$commentdb=getComment($aid,$page,$host['article_comment_num']);
	}
}

$cmcontent=isset($_COOKIE['cmcontent'])?$_COOKIE['cmcontent']:'';
$multipage ='';