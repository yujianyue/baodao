<?php
/**
 * 查立得PHP+MySQL通用新生报到系统 V1.0
 * 数据库连接及配置文件
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 数据库连接参数
$db_host = 'localhost';
$db_user = 'baodao2_chalide';
$db_pass = 'iWxJjsaNnh7rJRZx';
$db_name = 'baodao2_chalide';
$db_charset = 'utf8';

// 启动会话
session_start();

// 创建数据库连接
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("数据库连接失败: " . mysqli_connect_error());
}
mysqli_set_charset($conn, $db_charset);

// 版本号
define('VERSION', 'k1.0.' . date("YmdHis"));

// 菜单配置
$menu = [
    'admin' => [
        ['name' => '数据列表', 'do' => 'list'],
        ['name' => '导入页面', 'do' => 'liin'],
        ['name' => '批量操作', 'do' => 'batch'],
        ['name' => '统计页面', 'do' => 'tong'],
        ['name' => '用户列表', 'do' => 'user'],
        ['name' => '用户导入', 'do' => 'usin'],
        ['name' => '系统设置', 'do' => 'site'],
        ['name' => '数据备份', 'do' => 'baks'],
        ['name' => '修改密码', 'do' => 'pass'],
        ['name' => '使用帮助', 'do' => 'help'],
    ],
    'user' => [
        ['name' => '报到页面', 'do' => 'xiao'],
        ['name' => '报到记录', 'do' => 'list'],
        ['name' => '报到统计', 'do' => 'tong'],
        ['name' => '修改密码', 'do' => 'pass'],
    ]
];

// 默认网站设置
$default_settings = [
    'id' => 1,
    'name' => '新生报到系统',
    'urls' => '技术支持: 15058593138@qq.com',
    'tiao1' => '姓名',
    'tiao2' => '身份证号',
    'tiao3' => '准考证号',
    'isma' => 0,
    'desc' => '欢迎使用新生报到系统，请输入账号密码登录',
    'chax' => '请输入身份证号或准考证号进行查询',
    'jies' => '未找到相关信息，请检查输入是否正确'
];

// 文件上传大小限制 (2MB)
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);
?>
