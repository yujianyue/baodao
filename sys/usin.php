<?php
/**
 * 管理员用户导入页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act']) && $_GET['act'] == 'import') {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    $content = isset($post_data['content']) ? $post_data['content'] : '';
    
    if (empty($content)) {
        echo json_msg(0, '导入内容不能为空');
        exit;
    }
    
    // 保存到临时文件
    $temp_file = './tmp/user_import_' . time() . '.txt';
    
    // 确保目录存在
    if (!is_dir('./tmp')) {
        mkdir('./tmp', 0777, true);
    }
    
    file_put_contents($temp_file, $content);
    
    // 处理导入
    $result = process_user_import($temp_file, $db);
    
    // 删除临时文件
    @unlink($temp_file);
    
    echo json_encode($result);
    exit;
}

/**
 * 处理用户导入
 * @param string $file 文件路径
 * @param Sqls $db 数据库对象
 * @return array
 */
function process_user_import($file, $db) {
    // 读取数据（默认制表符分隔）
    $data = read_csv_to_array($file, "\t");
    
    // 如果行数为1，可能是逗号分隔的CSV
    if (count($data) == 1 && strpos($data[0][0], ',') !== false) {
        $data = read_csv_to_array($file, ',');
    }
    
    // 初始化结果
    $result = [
        'code' => 1,
        'total' => count($data),
        'success' => 0,
        'failed' => 0,
        'failed_list' => []
    ];
    
    // 处理每一行数据
    foreach ($data as $i => $row) {
        // 跳过空行
        if (empty($row) || count($row) < 3) {
            $result['failed']++;
            $result['failed_list'][] = "行 " . ($i + 1) . ": 数据格式不正确";
            continue;
        }
        
        // 提取用户名、密码、姓名
        $username = isset($row[0]) ? trim($row[0]) : '';
        $password = isset($row[1]) ? trim($row[1]) : '';
        $name = isset($row[2]) ? trim($row[2]) : '';
        $type = isset($row[3]) && strtolower(trim($row[3])) == 'admin' ? 'admin' : 'user';
        
        // 验证必填字段
        if (empty($username) || empty($password) || empty($name)) {
            $result['failed']++;
            $result['failed_list'][] = "行 " . ($i + 1) . ": 用户名、密码和姓名不能为空";
            continue;
        }
        
        // 验证用户名和密码格式
        if (!preg_match('/^[a-zA-Z0-9]{5,20}$/', $username)) {
            $result['failed']++;
            $result['failed_list'][] = "行 " . ($i + 1) . ": 用户名必须为5-20位数字和字母组合";
            continue;
        }
        
        if (!preg_match('/^[a-zA-Z0-9]{6,16}$/', $password)) {
            $result['failed']++;
            $result['failed_list'][] = "行 " . ($i + 1) . ": 密码必须为6-16位数字和字母组合";
            continue;
        }
        
        // 检查是否已存在相同用户名
        $exist = $db->getOne('user', 'id', "user='{$username}'");
        if ($exist) {
            $result['failed']++;
            $result['failed_list'][] = "行 " . ($i + 1) . ": 用户名 {$username} 已存在";
            continue;
        }
        
        // 插入数据
        $insert_data = [
            'user' => $username,
            'pass' => md5($password),
            'name' => $name,
            'type' => $type,
            'check' => 1,
            'add_time' => date('Y-m-d H:i:s'),
            'log_time' => ''
        ];
        
        $insert_result = $db->insert('user', $insert_data);
        
        if ($insert_result) {
            $result['success']++;
        } else {
            $result['failed']++;
            $result['failed_list'][] = "行 " . ($i + 1) . ": 数据库插入失败";
        }
    }
    
    // 返回结果
    return $result;
}
// 引入公共头部
require_once './inc/head.php';
?>

<div class="container">
    <div class="panel">
        <div class="panel-heading">
            <h3>用户导入</h3>
            <p>批量导入用户信息</p>
        </div>
        
        <div class="panel-body">
            <div class="form-group">
                <label class="form-label">导入说明：</label>
                <div class="alert alert-info">
                    请从Excel复制包含用户名、密码、姓名和类型的数据，粘贴到下方文本框。<br>
                    数据格式：第一列为用户名（5-20位数字和字母组合），第二列为密码（6-16位数字和字母组合），第三列为姓名，第四列为类型（可选，填写admin表示管理员，默认为普通用户）<br>
                    示例：user001 &lt;Tab&gt; password123 &lt;Tab&gt; 张三 &lt;Tab&gt; user
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">粘贴数据：</label>
                <textarea id="importContent" class="form-control" rows="10" placeholder="请粘贴从Excel复制的数据"></textarea>
            </div>
            
            <div class="form-group">
                <button type="button" class="btn" onclick="submitImport()">提交导入</button>
            </div>
            
            <!-- 导入结果 -->
            <div id="importResult" style="display: none;">
                <div class="alert alert-success">
                    <h4>导入结果</h4>
                    <p>总提交数量：<span id="resultTotal">0</span></p>
                    <p>导入成功数量：<span id="resultSuccess">0</span></p>
                    <p>导入失败数量：<span id="resultFailed">0</span></p>
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
// 提交导入
function submitImport() {
    const content = document.getElementById('importContent').value.trim();
    
    if (!content) {
        showToast('请粘贴导入数据', 'warning');
        return;
    }
    
    // 显示加载提示
    showToast('正在处理导入数据，请稍候...', 'info');
    
    // 发送导入请求
    ajax('?do=usin&act=import', { content: content }, function(res) {
        if (res.code === 1) {
            showImportResult(res);
        } else {
            showToast(res.msg, 'error');
        }
    });
}

// 显示导入结果
function showImportResult(result) {
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
    document.getElementById('importResult').style.display = 'block';
    
    // 显示成功提示
    if (result.success > 0) {
        showToast('成功导入 ' + result.success + ' 条数据', 'success');
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

#importResult {
    margin-top: 20px;
}

#failedList {
    margin: 0;
    padding-left: 20px;
    max-height: 200px;
    overflow-y: auto;
}
</style>
