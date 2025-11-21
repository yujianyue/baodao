<?php
/**
 * 管理员登录页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理退出登录
if ($do == 'lgout') {
    // 清除会话
    session_destroy();
    // 跳转到登录页
    header('Location: admin.php?do=login');
    exit;
}

// 处理AJAX登录请求
if (isset($_GET['act']) && $_GET['act'] == 'login') {
    // 获取POST数据
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    $username = isset($post_data['username']) ? $post_data['username'] : '';
    $password = isset($post_data['password']) ? $post_data['password'] : '';
    $code = isset($post_data['code']) ? $post_data['code'] : '';
    
    // 简单验证
    if (empty($username) || empty($password)) {
        echo json_msg(0, '用户名或密码不能为空');
        exit;
    }
    
    // 验证码验证
    if ($settings['isma'] == 1) {
        if (empty($code)) {
            echo json_msg(0, '验证码不能为空');
            exit;
        }
        
        if (!isset($_SESSION['captcha']) || strtolower($code) != strtolower($_SESSION['captcha'])) {
            echo json_msg(0, '验证码错误');
            exit;
        }
    }
    
    // 验证用户名和密码
    $user_info = $db->getOne('user', '*', "user='".safe_input($username)."' AND type='admin'");
    
    if (!$user_info) {
        echo json_msg(0, '用户不存在');
        exit;
    }
    
    if ($user_info['pass'] != md5($password)) {
        echo json_msg(0, '密码错误');
        exit;
    }
    
    if ($user_info['check'] != 1) {
        echo json_msg(0, '账号已被禁用，请联系管理员');
        exit;
    }
    
    // 更新登录时间
    $db->update('user', ['log_time' => date('Y-m-d H:i:s')], "id='{$user_info['id']}'");
    
    // 保存会话
    $_SESSION['user_id'] = $user_info['id'];
    $_SESSION['user_type'] = $user_info['type'];
    $_SESSION['username'] = $user_info['user'];
    $_SESSION['name'] = $user_info['name'];
    
    echo json_msg(1, '登录成功');
    exit;
}

// 验证码生成
if (isset($_GET['act']) && $_GET['act'] == 'captcha') {
    // 创建验证码图片
    $width = 120;
    $height = 40;
    $image = imagecreatetruecolor($width, $height);
    
    // 填充背景
    $bg_color = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $bg_color);
    
    // 绘制干扰线
    for ($i = 0; $i < 6; $i++) {
        $line_color = imagecolorallocate($image, rand(0, 200), rand(0, 200), rand(0, 200));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
    }
    
    // 绘制干扰点
    for ($i = 0; $i < 100; $i++) {
        $pixel_color = imagecolorallocate($image, rand(0, 200), rand(0, 200), rand(0, 200));
        imagesetpixel($image, rand(0, $width), rand(0, $height), $pixel_color);
    }
    
    // 生成验证码
    $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 4; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    // 保存验证码到会话
    $_SESSION['captcha'] = $code;
    
    // 绘制验证码
    for ($i = 0; $i < 4; $i++) {
        $text_color = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
        imagestring($image, 5, 20 + $i * 20, 10, $code[$i], $text_color);
    }
    
    // 输出图片
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
    exit;
}

// 显示登录表单
// 引入公共头部
require_once './inc/head.php';
?>
<div class="login-container">
    <h2 class="login-title"><?php echo $settings['name']; ?> - 管理员登录</h2>
    
    <div class="login-desc"><?php echo $settings['desc']; ?></div>
    
    <form id="loginForm" onsubmit="return false;">
        <div class="form-group">
            <label class="form-label">用户名：</label>
            <input type="text" name="username" class="form-control" placeholder="请输入管理员用户名" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">密码：</label>
            <input type="password" name="password" class="form-control" placeholder="请输入密码" required>
        </div>
        
        <?php if ($settings['isma'] == 1): ?>
        <div class="form-group">
            <label class="form-label">验证码：</label>
            <div style="display: flex;">
                <input type="text" name="code" class="form-control" placeholder="请输入验证码" required style="width: 60%;">
                <img src="?act=captcha" id="captchaImg" style="width: 40%; height: 38px; margin-left: 10px; cursor: pointer;" onclick="this.src='?act=captcha&t='+Math.random()">
            </div>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <button type="button" class="btn" style="width: 100%;" onclick="doLogin()">登录</button>
        </div>
        
        <div class="form-group" style="text-align: center;">
            <a href="user.php">前往用户登录</a>
        </div>
    </form>
</div>

<script>
function doLogin() {
    const data = getFormData('loginForm');
    
    ajax('?do=login&act=login', data, function(res) {
        if (res.code == 1) {
            showToast(res.msg, 'success');
            setTimeout(function() {
                window.location.href = 'admin.php?do=list';
            }, 1000);
        } else {
            showToast(res.msg, 'error');
            <?php if ($settings['isma'] == 1): ?>
            document.getElementById('captchaImg').src = '?act=captcha&t=' + Math.random();
            <?php endif; ?>
        }
    });
}
</script>
