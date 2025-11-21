<?php
/**
 * 管理员界面入口文件
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 引入必要文件
require_once './inc/conn.php';
require_once './inc/pubs.php';
require_once './inc/sqls.php';

// 初始化数据库对象
$db = new Sqls($conn);
               
// 检查管理员登录状态
$admin = check_login('admin');
$role = "admin";
// 获取当前操作
$do = isset($_GET['do']) ? $_GET['do'] : 'login';

// 根据GET参数加载不同模块
if ($do == 'login' || $do == 'lgout') {
    // 登录和退出模块
    require_once './sys/login.php';
} else {
    // 检查是否已登录
    if (!$admin) {
        header('Location: admin.php?do=login&d=0001');
        exit;
    }
       
    // 加载对应模块
    $file = './sys/' . $do . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        echo '<div class="alert">模块 ' . $do . ' 不存在！</div>';
    }
}

// 引入公共底部
require_once './inc/foot.php';
?>
