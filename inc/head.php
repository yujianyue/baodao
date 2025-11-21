<?php
/**
 * 公共头部文件
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 获取网站设置
$settings = get_site_settings();

// 检查登录状态
$user = check_login();

// 获取当前页面
$do = isset($_GET['do']) ? $_GET['do'] : 'login';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $settings['name']; ?></title>
    <link rel="stylesheet" href="inc/css.css?v=<?php echo VERSION; ?>">
    <script src="inc/js.js?v=<?php echo VERSION; ?>"></script>
</head>
<body>
    <div class="container">
      <?php
          if ($do == 'login' || $do == 'lgout') {
			}else{
            ?>
        <div class="header">
            <div class="header-top">
                <div class="header-title"><?php echo $settings['name']; ?> - 功能演示站</div>
                <?php if ($user): ?>
                <div class="header-user">
                    <!--span><?php echo $user['name']; ?></span-->
                    <a href="?do=lgout" class="btn">退出登录</a>
                </div>
                <?php endif; ?>
            </div>
          <?php
              // 输出导航栏
    echo '<div class="header-nav">';
    foreach ($menu[$role] as $item) {
        $active = $do == $item['do'] ? 'active' : '';
        echo '<a href="?do=' . $item['do'] . '" class="nav-item ' . $active . '">' . $item['name'] . '</a>';
    }
    echo '</div>';
            }
          ?>
        </div>
        <div class="content">
