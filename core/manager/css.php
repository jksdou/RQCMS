<?php
if(!defined('RQ_ROOT')) exit('Access Denied');
$tempView=$coreView;//������ȥ����ģ����
$ContentType='Content-Type: text/css; charset=UTF-8';
include RQ_CORE.'/manager/view/css.php';
$csstime=strtotime(date("y-m-d"));
doAction('admin_addcss');
cacheControl($csstime);
?>