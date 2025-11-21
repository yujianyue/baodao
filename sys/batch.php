<?php
/**
 * 管理员批量操作页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act']) && $_GET['act'] == 'batch') {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    $content = isset($post_data['content']) ? $post_data['content'] : '';
    $action = isset($post_data['action']) ? $post_data['action'] : '';
    
    if (empty($content)) {
        echo json_msg(0, '请输入身份证号或准考证号');
        exit;
    }
    
    if (empty($action) || !in_array($action, ['delete', 'register'])) {
        echo json_msg(0, '请选择有效的操作类型');
        exit;
    }
    
    // 提取身份证号或准考证号
    preg_match_all('/[0-9X]{10,18}/', $content, $matches);
    $codes = $matches[0];
    
    if (empty($codes)) {
        echo json_msg(0, '未能从输入内容中提取有效的身份证号或准考证号');
        exit;
    }
    
    // 初始化结果
    $result = [
        'code' => 1,
        'total' => count($codes),
        'success' => 0,
        'failed' => 0,
        'failed_list' => []
    ];
    
    foreach ($codes as $code) {
        // 查询记录
        $record = $db->getOne('card', '*', "sfzh='".safe_input($code)."' OR zkzh='".safe_input($code)."'");
        
        if (!$record) {
            $result['failed']++;
            $result['failed_list'][] = $code . ': 记录不存在';
            continue;
        }
        
        if ($action == 'delete') {
            // 删除记录
            $delete_result = $db->delete('card', "id='{$record['id']}'");
            
            if ($delete_result) {
                $result['success']++;
            } else {
                $result['failed']++;
                $result['failed_list'][] = $code . ': 删除失败';
            }
        } else if ($action == 'register') {
            // 检查是否已报到
            if ($record['icha'] == 1) {
                $result['failed']++;
                $result['failed_list'][] = $code . ': 已报到，无需重复操作';
                continue;
            }
            
            // 更新报到状态
            $update_data = [
                'icha' => 1,
                'user' => $user['id'],
                'tihe' => date('Ymd'),
                'hex_time' => date('Y-m-d H:i:s')
            ];
            
            $update_result = $db->update('card', $update_data, "id='{$record['id']}'");
            
            if ($update_result) {
                $result['success']++;
            } else {
                $result['failed']++;
                $result['failed_list'][] = $code . ': 报到操作失败';
            }
        }
    }
    
    echo json_encode($result);
    exit;
}
// 引入公共头部
require_once './inc/head.php';
?>

<div class="container">
    <div class="panel">
        <div class="panel-heading">
            <h3>批量操作</h3>
            <p>批量处理学生数据</p>
        </div>
        
        <div class="panel-body">
            <div class="form-group">
                <label class="form-label">操作说明：</label>
                <div class="alert alert-info">
                    请在下方文本框中输入需要批量操作的身份证号或准考证号，每行一个。<br>
                    系统将自动提取输入内容中的有效号码，并执行相应操作。
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">操作类型：</label>
                <div class="radio-group">
                    <label class="radio-wrapper">
                        <input type="radio" name="action" value="delete" checked>
                        <span class="radio-text">批量删除</span>
                    </label>
                    <label class="radio-wrapper">
                        <input type="radio" name="action" value="register">
                        <span class="radio-text">批量报到</span>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">输入数据：</label>
                <textarea id="batchContent" class="form-control" rows="10" placeholder="请输入身份证号或准考证号，每行一个"></textarea>
            </div>
            
            <div class="form-group">
                <button type="button" class="btn" onclick="submitBatchOperation()">提交处理</button>
            </div>
            
            <!-- 操作结果 -->
            <div id="batchResult" style="display: none;">
                <div class="alert alert-success">
                    <h4>操作结果</h4>
                    <p>总提交数量：<span id="resultTotal">0</span></p>
                    <p>操作成功数量：<span id="resultSuccess">0</span></p>
                    <p>操作失败数量：<span id="resultFailed">0</span></p>
                </div>
                
                <div id="failedListContainer" style="display: none;">
                    <h4>失败信息列表：</h4>
                    <div class="alert alert-danger">
                        <ul id="failedList"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 提交批量操作
function submitBatchOperation() {
    const content = document.getElementById('batchContent').value.trim();
    const action = document.querySelector('input[name="action"]:checked').value;
    
    if (!content) {
        showToast('请输入身份证号或准考证号', 'warning');
        return;
    }
    
    // 确认操作
    const actionText = action === 'delete' ? '删除' : '报到';
    
    confirm('确认要批量' + actionText + '这些记录吗？', function() {
        // 显示加载提示
        showToast('正在处理数据，请稍候...', 'info');
        
        // 发送请求
        ajax('?do=batch&act=batch', { content: content, action: action }, function(res) {
            if (res.code === 1) {
                showBatchResult(res, actionText);
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

// 显示操作结果
function showBatchResult(result, actionText) {
    // 填充结果数据
    document.getElementById('resultTotal').textContent = result.total;
    document.getElementById('resultSuccess').textContent = result.success;
    document.getElementById('resultFailed').textContent = result.failed;
    
    // 显示失败列表
    const failedListContainer = document.getElementById('failedListContainer');
    const failedList = document.getElementById('failedList');
    
    if (result.failed > 0 && result.failed_list && result.failed_list.length > 0) {
        failedList.innerHTML = '';
        result.failed_list.forEach(function(item) {
            const li = document.createElement('li');
            li.textContent = item;
            failedList.appendChild(li);
        });
        failedListContainer.style.display = 'block';
    } else {
        failedListContainer.style.display = 'none';
    }
    
    // 显示结果区域
    document.getElementById('batchResult').style.display = 'block';
    
    // 显示成功提示
    if (result.success > 0) {
        showToast('成功' + actionText + ' ' + result.success + ' 条记录', 'success');
    }
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

.alert {
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 4px;
}

.alert-info {
    background-color: #e6f7ff;
    border: 1px solid #91d5ff;
    color: #1890ff;
}

.alert-success {
    background-color: #f6ffed;
    border: 1px solid #b7eb8f;
    color: #52c41a;
}

.alert-danger {
    background-color: #fff2f0;
    border: 1px solid #ffccc7;
    color: #ff4d4f;
}

.radio-group {
    display: flex;
    margin-bottom: 10px;
}

.radio-wrapper {
    margin-right: 20px;
    display: flex;
    align-items: center;
}

.radio-text {
    margin-left: 5px;
}

#batchResult {
    margin-top: 20px;
}

#failedList {
    margin: 0;
    padding-left: 20px;
    max-height: 200px;
    overflow-y: auto;
}
</style>
