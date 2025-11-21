<?php
/**
 * 管理员系统设置页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act']) && $_GET['act'] == 'save') {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($post_data)) {
        echo json_msg(0, '提交数据不能为空');
        exit;
    }
    
    // 更新设置
    $result = save_site_settings($post_data);
    
    if ($result) {
        echo json_msg(1, '设置保存成功');
    } else {
        echo json_msg(0, '设置保存失败，请重试');
    }
    
    exit;
}

// 获取当前设置
$settings = get_site_settings();
// 引入公共头部
require_once './inc/head.php';
?>

<div class="container">
    <div class="panel">
        <div class="panel-heading">
            <h3>系统设置</h3>
            <p>配置系统基本参数</p>
        </div>
        
        <div class="panel-body">
            <form id="settingsForm" onsubmit="return false;">
                <div class="form-group">
                    <label class="form-label">网站名称：</label>
                    <input type="text" name="name" class="form-control" value="<?php echo $settings['name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">底部链接：</label>
                    <input type="text" name="urls" class="form-control" value="<?php echo $settings['urls']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">姓名标题：</label>
                    <input type="text" name="tiao1" class="form-control" value="<?php echo $settings['tiao1']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">身份证号标题：</label>
                    <input type="text" name="tiao2" class="form-control" value="<?php echo $settings['tiao2']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">准考证号标题：</label>
                    <input type="text" name="tiao3" class="form-control" value="<?php echo $settings['tiao3']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">是否启用验证码：</label>
                    <div class="switch">
                        <input type="checkbox" name="isma" id="isma" <?php echo $settings['isma'] == 1 ? 'checked' : ''; ?>>
                        <label for="isma"></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">登录页说明：</label>
                    <textarea name="desc" class="form-control" rows="3"><?php echo $settings['desc']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">查询页说明：</label>
                    <textarea name="chax" class="form-control" rows="3"><?php echo $settings['chax']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">无结果说明：</label>
                    <textarea name="jies" class="form-control" rows="3"><?php echo $settings['jies']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn" onclick="saveSettings()">保存设置</button>
                    <span id="saveResult" style="margin-left: 10px;"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function saveSettings() {
    const form = document.getElementById('settingsForm');
    const data = {
        name: form.querySelector('input[name="name"]').value.trim(),
        urls: form.querySelector('input[name="urls"]').value.trim(),
        tiao1: form.querySelector('input[name="tiao1"]').value.trim(),
        tiao2: form.querySelector('input[name="tiao2"]').value.trim(),
        tiao3: form.querySelector('input[name="tiao3"]').value.trim(),
        isma: form.querySelector('input[name="isma"]').checked ? 1 : 0,
        desc: form.querySelector('textarea[name="desc"]').value.trim(),
        chax: form.querySelector('textarea[name="chax"]').value.trim(),
        jies: form.querySelector('textarea[name="jies"]').value.trim()
    };
    
    // 验证必填字段
    if (!data.name || !data.tiao1 || !data.tiao2 || !data.tiao3) {
        showToast('网站名称、姓名标题、身份证号标题和准考证号标题不能为空', 'warning');
        return;
    }
    
    // 提交保存
    ajax('?do=site&act=save', data, function(res) {
        const saveResult = document.getElementById('saveResult');
        
        if (res.code === 1) {
            saveResult.innerHTML = `<span style="color: #52c41a;">${res.msg}</span>`;
            showToast(res.msg, 'success');
        } else {
            saveResult.innerHTML = `<span style="color: #ff4d4f;">${res.msg}</span>`;
            showToast(res.msg, 'error');
        }
        
        // 3秒后清除结果提示
        setTimeout(function() {
            saveResult.innerHTML = '';
        }, 3000);
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

.form-group {
    margin-bottom: 15px;
}

/* 开关样式 */
.switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 20px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.switch label {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 20px;
}

.switch label:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.switch input:checked + label {
    background-color: #1890ff;
}

.switch input:checked + label:before {
    transform: translateX(20px);
}
</style>
