<?php
/**
 * 用户修改密码页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act']) && $_GET['act'] == 'change') {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    $old_password = isset($post_data['old_password']) ? $post_data['old_password'] : '';
    $new_password = isset($post_data['new_password']) ? $post_data['new_password'] : '';
    $confirm_password = isset($post_data['confirm_password']) ? $post_data['confirm_password'] : '';
    
    // 验证输入
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        echo json_msg(0, '所有字段都不能为空');
        exit;
    }
    
    // 验证新密码格式
    if (!preg_match('/^[a-zA-Z0-9]{6,16}$/', $new_password)) {
        echo json_msg(0, '新密码必须为6-16位数字和字母组合');
        exit;
    }
    
    // 验证两次密码是否一致
    if ($new_password !== $confirm_password) {
        echo json_msg(0, '两次输入的新密码不一致');
        exit;
    }
    
    // 验证旧密码
    $user_info = $db->getOne('user', '*', "id='{$user['id']}'");
    
    if (!$user_info) {
        echo json_msg(0, '用户信息不存在');
        exit;
    }
    
    if ($user_info['pass'] != md5($old_password)) {
        echo json_msg(0, '旧密码不正确');
        exit;
    }
    
    // 更新密码
    $result = $db->update('user', ['pass' => md5($new_password)], "id='{$user['id']}'");
    
    if ($result) {
        echo json_msg(1, '密码修改成功，请重新登录');
    } else {
        echo json_msg(0, '密码修改失败，请重试');
    }
    exit;
}
// 引入公共头部
require_once './inc/head.php';
?>

<div class="container">
    <div class="panel">
        <div class="panel-heading">
            <h3>修改密码</h3>
            <p>定期修改密码可以提高账号安全性</p>
        </div>
        
        <div class="panel-body">
            <form id="passwordForm" onsubmit="return false;">
                <div class="form-group">
                    <label class="form-label">旧密码：</label>
                    <input type="password" name="old_password" class="form-control" placeholder="请输入旧密码" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">新密码：</label>
                    <input type="password" name="new_password" class="form-control" placeholder="请输入新密码 (6-16位数字和字母组合)" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">确认新密码：</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="请再次输入新密码" required>
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn" onclick="changePassword()">修改密码</button>
                    <span id="resultMsg" style="margin-left: 10px;"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function changePassword() {
    const data = getFormData('passwordForm');
    
    // 前端验证
    if (!data.old_password || !data.new_password || !data.confirm_password) {
        showToast('所有字段都不能为空', 'warning');
        return;
    }
    
    if (!/^[a-zA-Z0-9]{6,16}$/.test(data.new_password)) {
        showToast('新密码必须为6-16位数字和字母组合', 'warning');
        return;
    }
    
    if (data.new_password !== data.confirm_password) {
        showToast('两次输入的新密码不一致', 'warning');
        return;
    }
    
    // 提交修改
    ajax('?do=pass&act=change', data, function(res) {
        const resultMsg = document.getElementById('resultMsg');
        
        if (res.code === 1) {
            resultMsg.innerHTML = `<span style="color: #52c41a;">${res.msg}</span>`;
            showToast(res.msg, 'success');
            
            // 清空表单
            document.getElementById('passwordForm').reset();
            
            // 3秒后退出登录
            setTimeout(function() {
                window.location.href = 'user.php?do=lgout';
            }, 3000);
        } else {
            resultMsg.innerHTML = `<span style="color: #ff4d4f;">${res.msg}</span>`;
            showToast(res.msg, 'error');
        }
    });
}
</script>

<style>
.panel {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.panel-heading {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    background-color: #fafafa;
}

.panel-heading h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.panel-body {
    padding: 15px;
}

form {
    max-width: 500px;
}
</style>
