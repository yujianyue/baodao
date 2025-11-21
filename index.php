<?php
/**
 * 系统入口文件
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 启动会话
session_start();

// 引入必要文件
require_once './inc/conn.php';
require_once './inc/pubs.php';
require_once './inc/sqls.php';

// 初始化数据库操作类
$db = new Sqls($conn);

// 检查用户登录状态
$user = check_login();

// 根据用户类型跳转到对应页面
if ($user) {
    if ($user['type'] == 'admin') {
        header('Location: admin.php');
        exit;
    } else {
        header('Location: user.php');
        exit;
    }
} else {
    // 默认跳转到用户登录页面
    header('Location: user.php');
    exit;
}
?>
