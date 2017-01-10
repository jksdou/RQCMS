<?php
/*
Plugin Name: 访问统计
Version: 1.0
Description: 这是世界上第一个RQCMS插件，通过它我们可以很方便的添加统计代码。
Author: RQ204
Author URL: http://www.rqcms.com
*/

/*插件可以处理的位置和方法
doAction('before_router');在没有加载处理文件之前的处理，可以用来处理url
doAction('before_output',$output); 在输出之前对输出的内容进行处理
doAction('404_before_output');对出现404结果后的情况进行再处理
doAction('article_not_find');在没有找到文章时的处理方法
doAction('article_before_view');在程序处理完数据后显示前的处理
doAction('attachment_before_download');在下载前的处理，可以做下载页显示多次广告的效果
doAction('captcha_create_myself'); 创建自己的验证码图形，处理后注意要exit
doAction('comment_post_check'); 对回复保存时的检查用
doAction('comment_data_view',$commentdb);对回复显示的数据进行处理
doAction('index_before_view');首页显示内容前的处理工作
doAction('rss_before_output',$rssdb);输出rss结果前的处理工作
doAction('category_before_view');列表页显示前的处理
doAction('profile_reg_check');注册用户前的检查
doAction('search_before_featch');搜索页搜索前检查
doAction('search_before_view');搜索结果显示前的处理
doAction('tag_before_view');显示tag前的处理
doAction('admin_plugin_add_item');添加插件处理菜单，要处理数组$pluginitem
doAction('admin_plugin_setting_save');插件配置保存设置
doAction('admin_plugin_setting_view');插件设置界面
*/

!defined('RQ_DATA') && exit('access deined!');

//添加一个菜单在插件菜单中
function stat_add_item()
{
	global $pluginitem;
	$pluginitem['添加统计']='stat';
}
addAction('admin_plugin_add_item','stat_add_item');

//配置设置界面,需要做一个tr，界面代码参考core\manager\view\plugin.php
function stat_html_view()
{
	global $DB,$hostid;
	$arr=$DB->fetch_first('select * from '.DB_PREFIX."plugin where `hostid`=$hostid and `file`='stat'");
	$code=isset($arr['config'])?$arr['config']:'';
print <<<EOT
<form action="admin.php?file=plugin&action=setting" method="post">
	<tr class="tdbheader">
    <td colspan="2">统计代码设置</td>
	</tr>
  <tr class="tablecell">
	<td width="20%"><b>统计代码:</b><td><textarea id="stat_code" class="formarea" type="text" name="stat_code" style="width:400px;height:80px;">$code</textarea></td>
  </tr>
    <tr class="tablecell">
	<td colspan="2" align="center"><input type="submit" value="提交" class="formbutton"></td>
  </tr>
  </form>
EOT;
}
addAction('admin_plugin_setting_view','stat_html_view');

//保存统计代码
function stat_code_save()
{
	global $DB,$hostid;
	$code=$_POST['stat_code'];
	$DB->query('update '.DB_PREFIX."plugin set `config`='$code' where hostid=$hostid and `file`='stat'");
	redirect('统计代码已成功更新','admin.php?file=plugin&action=setting');
}
addAction('admin_plugin_setting_save','stat_code_save');

function stat_footer_add($output)
{
	global $host,$PluginsConfig,$Files;
	$pos=strrpos($output,'</body>');
	if($pos&&$Files[RQ_FILE][0]!='admin.php')
	{
		$output=substr($output,0,$pos).$PluginsConfig['stat'].substr($output,$pos);
		if($host['gzipcompress']&& function_exists('ob_gzhandler'))
		{
			ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}
		echo $output;
		ob_flush();
		exit;
	}
}
addAction('before_output','stat_footer_add');
