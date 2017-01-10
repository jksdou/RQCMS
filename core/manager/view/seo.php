<?php
if(!defined('RQ_ROOT')) exit('Access Denied');
print <<<EOT
<div class="mainbody">
  <table border="0"  cellspacing="0" cellpadding="0" style="width:100%;">
    <tr>
      <td valign="top" style="width:150px;">
	  <div class="tableborder">
        <div class="tableheader">标签管理</div>
        <div class="leftmenubody">
          <div class="leftmenuitem">&#8226; <a href="admin.php?file=seo&action=taglist">标签管理</a></div>
        </div>
      </div>
	  <div class="tableborder">
        <div class="tableheader">网址链接</div>
        <div class="leftmenubody">
		  <div class="leftmenuitem">&#8226; <a href="admin.php?file=seo&action=addredirect">自动跳转</a></div>
          <div class="leftmenuitem">&#8226; <a href="admin.php?file=seo&action=redirect">自动跳转</a></div>
        </div>
      </div>
	  <div class="tableborder">
        <div class="tableheader">广告管理</div>
        <div class="leftmenubody">
          <div class="leftmenuitem">&#8226; <a href="admin.php?file=seo&action=addad">添加广告</a></div>
		  <div class="leftmenuitem">&#8226; <a href="admin.php?file=seo&action=adlist">广告管理</a></div>
        </div>
      </div></td>
      <td valign="top" style="width:20px;"></td>
      <td valign="top">
	  <form action="admin.php?file=seo" method="POST"><table width="100%" align="center" border="0" cellspacing="0" cellpadding="0">
	  <tr><td class="rightmainbody"><table width="100%" align="center" border="0" cellspacing="0" cellpadding="0">
EOT;
if($action == 'taglist'){print <<<EOT
    <input type="hidden" name="action" value="dodeltag">
    <tr class="tdbheader">
      <td width="34%">Tags名称</td>
      <td width="32%">使用次数</td>
      <td width="32%">操作</td>
      <td width="2%" nowrap><input name="chkall" type="checkbox" onclick="checkall(this.form)" value="on"></td>
    </tr>
EOT;
foreach($tagdb as $key => $tag){print <<<EOT
    <tr class="tablecell">
      <td><a href="admin.php?file=article&action=list&tag=$tag[url]">$tag[item]</a></td>
      <td>$tag[usenum]</td>
      <td><a href="admin.php?file=seo&action=modtag&tag=$tag[url]">修改</a></td>
      <td nowrap><input type="checkbox" name="tag[$tag[item]]" value="$tag[item]">
      </td>
    </tr>
EOT;
}print <<<EOT
        <tr class="tablecell">
          <td colspan="5" nowrap="nowrap"><div class="records">记录:$tatol</div>
                  <div class="multipage">$multipage</div></td>
        </tr>
    <tr class="tablecell">
      <td colspan="4" align="center">
        <input type="submit" value="删除所选" class="formbutton">
      </td>
    </tr>
EOT;
} elseif ($action == 'modtag') {print <<<EOT
    <input type="hidden" name="oldtag" value="$tag">
    <input type="hidden" name="action" value="domodtag">
    <tr class="tdbheader">
      <td colspan="2"><b>修改标签</b></td>
    </tr>
    <tr class="tablecell">
      <td>标签: </td>
      <td><input class="formfield" type="text" name="tag" size="35" maxlength="50" value="$tag" >
      </td>
    </tr>
    <tr class="tablecell">
      <td>使用次数: </td>
      <td>$usenum</td>
    </tr>
    <tr class="tablecell">
      <td valign="top">使用文章: </td>
      <td>
EOT;
foreach($articledb as $key => $article){print <<<EOT
<a href="admin.php?file=article&action=mod&aid=$article[aid]">$article[title]</a><br>
EOT;
}print <<<EOT
</td>
    </tr>
    <tr class="tablecell">
      <td colspan="2" align="center"><input type="submit" value="确认" class="formbutton"></td>
    </tr>
EOT;
}  elseif ($action == 'modredirect') {print <<<EOT
    <input type="hidden" name="vid" value="$vid">
    <input type="hidden" name="action" value="domodredirect">
    <tr class="tdbheader">
      <td colspan="2"><b>修改转向网址</b></td>
    </tr>
    <tr class="tablecell">
      <td>原网址: </td>
      <td><input class="formfield" type="text" name="title" size="35" maxlength="50" value="{$redirectdb['title']}" >
      </td>
    </tr>
    <tr class="tablecell">
      <td>转向地址: </td>
      <td><input class="formfield" type="text" name="value" size="35" maxlength="50" value="{$redirectdb['value']}" ></td>
    </tr>
EOT;
print <<<EOT
    </tr>
    <tr class="tablecell">
      <td colspan="2" align="center"><input type="submit" value="确认" class="formbutton"></td>
    </tr>
EOT;
} elseif ($action == 'redirect') {print <<<EOT
    <input type="hidden" name="action" value="dodelredirect">
    <tr class="tdbheader">
      <td width="34%">原网址</td>
      <td width="32%">转向地址</td>
      <td width="32%">操作</td>
      <td width="2%" nowrap><input name="chkall" type="checkbox" onclick="checkall(this.form)" value="on"></td>
    </tr>
EOT;
foreach($redirectdb as $key => $rdb){print <<<EOT
    <tr class="tablecell">
      <td><a href="admin.php?file=article&action=redirect&tag=$rdb[vid]">$rdb[title]</a></td>
      <td>$rdb[value]</td>
      <td><a href="admin.php?file=seo&action=modredirect&vid=$rdb[vid]">修改</a></td>
      <td nowrap><input type="checkbox" name="vid[$rdb[vid]]" value="$rdb[vid]">
      </td>
    </tr>
EOT;
}print <<<EOT
        <tr class="tablecell">
          <td colspan="5" nowrap="nowrap"><div class="records">记录:$tatol</div>
                  <div class="multipage">$multipage</div></td>
        </tr>
    <tr class="tablecell">
      <td colspan="4" align="center">
        <input type="submit" value="删除所选" class="formbutton">
      </td>
    </tr>
EOT;
}elseif ($action == 'tagclear') {print <<<EOT
    <input type="hidden" name="action" value="dotagclear">
    <tr class="tdbheader">
      <td>清理Tags</td>
    </tr>
    <tr>
      <td class="alertbox">
	  <p>当您对数据库进行批量操作后,可能统计信息将不会准确，本功能是重新统计各个Tag的使用次数和清理不使用的Tag。</p>
      <p>为了使Tags数据最准确，本次操作将清空Tags数据表，并读取每篇文章的关键字，重新写入Tags数据表，过程较久，请耐心等候。</p>
      <p>建议定期执行。</p>
	  <p>每次处理文章数: <input class="formfield" type="text" name="percount" size="15" maxlength="50" value="200"></p>
      <p><input type="submit" value="确认" class="formbutton"></p>
	  </td>
    </tr>
EOT;
}print <<<EOT
    <tr>
      <td class="tablebottom" colspan="4"></td>
    </tr>
      </table></td>
    </tr>
  </table>
</form></td>
    </tr>
  </table>
</div>

EOT;
?>