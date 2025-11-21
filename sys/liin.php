<?php
/**
 * 管理员数据导入页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */
$pida = date("YmdHis");
// 处理AJAX请求
if (isset($_GET['act'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_GET['act']) {
        // 网页粘贴导入
        case 'paste_import':
            $post_data = json_decode(file_get_contents('php://input'), true);
            $content = isset($post_data['content']) ? $post_data['content'] : '';
            $pid = isset($post_data['pid']) ? trim($post_data['pid']) : "H".$pida;
            
            if (empty($content)) {
                echo json_msg(0, '导入内容不能为空');
                exit;
            }
            
            if (empty($pid)) {
                echo json_msg(0, '批次号不能为空');
                exit;
            }
            
            // 保存到临时文件
            $temp_file = './tmp/paste_' . time() . '.txt';
            
            // 确保目录存在
            if (!is_dir('./tmp')) {
                mkdir('./tmp', 0777, true);
            }
            
            file_put_contents($temp_file, $content);
            
            // 处理导入
            $result = process_import($temp_file, $pid, $db);
            
            // 删除临时文件
            @unlink($temp_file);
            
            echo json_encode($result);
            exit;
            
        // 文件上传导入
        case 'file_upload':
            if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
                echo json_msg(0, '文件上传失败');
                exit;
            }
            
            $pid = isset($_POST['pid']) ? trim($_POST['pid']) : "F".$pida;
            
            if (empty($pid)) {
                echo json_msg(0, '批次号不能为空');
                exit;
            }
            
            $file = $_FILES['file'];
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // 检查文件类型
            if ($file_type != 'txt' && $file_type != 'csv') {
                echo json_msg(0, '只支持TXT或CSV文件');
                exit;
            }
            
            // 检查文件大小
            if ($file['size'] > MAX_UPLOAD_SIZE) {
                echo json_msg(0, '文件大小不能超过2MB');
                exit;
            }
            
            // 确保目录存在
            if (!is_dir('./tmp')) {
                mkdir('./tmp', 0777, true);
            }
            
            // 保存上传文件
            $temp_file = './tmp/upload_' . time() . '.' . $file_type;
            move_uploaded_file($file['tmp_name'], $temp_file);
            
            // 检查文件编码并转换为UTF-8:视乎处理异常
            $encoding = detect_encoding($temp_file);
            if ($encoding && $encoding != 'UTF-8') {
                convert_to_utf8($temp_file, $encoding);
            }
            
            // 处理导入
            $result = process_import($temp_file, $pid, $db);
            
            // 删除临时文件
            @unlink($temp_file);
            
            echo json_encode($result);
            exit;
    }
    
    echo json_msg(0, '未知操作');
    exit;
}

/**
 * 处理导入
 * @param string $file 文件路径
 * @param string $pid 批次号
 * @param Sqls $db 数据库对象
 * @return array
 */
function process_import($file, $pid, $db) {
  	if(stristr($file."|",".csv|")){ $tb=","; }else{ $tb="\t"; }
    // 尝试使用制表符分隔
    $data = read_csv_to_array($file, $tb);
    
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
        
        // 提取姓名、身份证号、准考证号
        $name = isset($row[0]) ? trim($row[0]) : '';
        $sfzh = isset($row[1]) ? trim($row[1]) : '';
        $zkzh = isset($row[2]) ? trim($row[2]) : '';
        
        // 验证必填字段
        if (empty($name) || (empty($sfzh) && empty($zkzh))) {
            $result['failed']++;
            $result['failed_list'][] = "行 " . ($i + 1) . ": 姓名和身份证号/准考证号至少填写两项";
            continue;
        }
        
        // 检查是否已存在相同记录
        $exist = $db->getOne('card', 'id', "(sfzh='{$sfzh}' OR zkzh='{$zkzh}') AND sfzh!='' AND zkzh!=''");
        if ($exist) {
            $result['failed']++;
            $result['failed_list'][] = "行 " . ($i + 1) . ": 身份证号或准考证号已存在";
            continue;
        }
        
        // 构建额外信息
        $desc = [];
        for ($j = 3; $j < count($row); $j++) {
            if (!empty($row[$j])) {
                $key = '信息' . ($j - 2);
                $desc[$key] = $row[$j];
            }
        }
        
        // 插入数据
        $insert_data = [
            'pid' => $pid,
            'name' => $name,
            'sfzh' => $sfzh,
            'zkzh' => $zkzh,
            'desc' => json_encode($desc, JSON_UNESCAPED_UNICODE),
            'icha' => 0,
            'add_time' => date('Y-m-d H:i:s')
        ];
        
        $insert_result = $db->insert('card', $insert_data);
        
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
            <h3>数据导入</h3>
            <p>导入学生信息数据</p>
        </div>
        
        <div class="panel-body">
            <div class="tabs">
                <div class="tab-nav">
                    <div id="tab-paste" class="tab-item active" onclick="switchTab('paste')">网页粘贴导入</div>
                    <div id="tab-file" class="tab-item" onclick="switchTab('file')">文件上传导入</div>
                </div>
                
                <!-- 网页粘贴导入 -->
                <div id="content-paste" class="tab-content active">
                    <div class="form-group">
                        <label class="form-label">批次号：</label>
                        <input type="text" id="pasteFormPid" class="form-control" placeholder="请输入批次号" value="<?php echo "H".$pida;?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">粘贴数据：</label>
                        <textarea id="pasteContent" class="form-control" rows="10" placeholder="请粘贴从Excel复制的数据">
张三	110101199001011234	12345678901	计算机系	软件工程	1班	男1201
李四	110101199001011235	12345678902	公共管理系	社会福利	2班	女0612
</textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn" onclick="submitPasteImport()">提交导入</button>
                    </div>
                      <div class="form-group">
                            <label class="form-label">导入说明：</label>
                            <div class="alert alert-info">
                               请从Excel复制包含姓名、身份证号、准考证号及其他信息的数据，粘贴到下方文本框。<br>
                            </div>
                        </div>
                </div>
                
                <!-- 文件上传导入 -->
                <div id="content-file" class="tab-content">
                    <form id="fileUploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">批次号：</label>
                            <input type="text" name="pid" class="form-control" placeholder="请输入批次号" value="<?php echo "F".$pida;?>" required>
                        </div>
                         
                        <div class="form-group">
                            <label class="form-label">选择文件：</label>
                            <input type="file" name="file" class="form-control" accept=".txt,.csv" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="button" class="btn" onclick="submitFileImport()">提交导入</button>
                        </div>
                                             
                        <div class="form-group">
                            <label class="form-label">导入说明：</label>
                            <div class="alert alert-info">
                                支持上传TXT或CSV格式文件，文件内容格式同网页粘贴导入。<br>
                                文件大小不超过2MB，文件编码会自动识别并转换为UTF-8。
                            </div>
                        </div>
                    </form>
                  
                </div>
                
                    
                    <div class="form-group">
                        <label class="form-label">公共说明：</label>
                        <div class="alert alert-info">                            
                            数据格式：第一列为姓名，第二列为身份证号，第三列为准考证号，后续列为其他信息（如系别、专业、班级、宿舍号等）<br>
                            数据各行的字段:网页粘贴|.txt格式，制表符隔开；.csv格式，英文逗号隔开。<br>
                            数据示例：见网页粘贴方式自带填写内容(可Excel空白表全选设置单元格为文本后粘贴该内容查看)。
                        </div>
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
                    
                    <button type="button" class="btn" onclick="hideImportResult()">返回</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 切换选项卡
function switchTab(tabId) {
    // 隐藏导入结果
    document.getElementById('importResult').style.display = 'none';
    
    // 隐藏所有内容
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(function(content) {
        content.classList.remove('active');
    });
    
    // 取消所有选项卡激活状态
    const tabs = document.querySelectorAll('.tab-item');
    tabs.forEach(function(tab) {
        tab.classList.remove('active');
    });
    
    // 激活选中的选项卡和内容
    const selectedTab = document.getElementById('tab-' + tabId);
    const selectedContent = document.getElementById('content-' + tabId);
    
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    if (selectedContent) {
        selectedContent.classList.add('active');
    }
}

// 网页粘贴导入
function submitPasteImport() {
    const pid = document.getElementById('pasteFormPid').value.trim();
    const content = document.getElementById('pasteContent').value.trim();
    
    if (!pid) {
        showToast('请输入批次号', 'warning');
        return;
    }
    
    if (!content) {
        showToast('请粘贴导入数据', 'warning');
        return;
    }
    
    // 显示加载提示
    showToast('正在处理导入数据，请稍候...', 'info');
    
    // 发送导入请求
    ajax('?do=liin&act=paste_import', { pid: pid, content: content }, function(res) {
        if (res.code === 1) {
            showImportResult(res);
        } else {
            showToast(res.msg, 'error');
        }
    });
}

// 文件上传导入
function submitFileImport() {
    const form = document.getElementById('fileUploadForm');
    const pid = form.querySelector('input[name="pid"]').value.trim();
    const file = form.querySelector('input[name="file"]').files[0];
    
    if (!pid) {
        showToast('请输入批次号', 'warning');
        return;
    }
    
    if (!file) {
        showToast('请选择文件', 'warning');
        return;
    }
    
    // 检查文件类型
    const fileType = file.name.split('.').pop().toLowerCase();
    if (fileType !== 'txt' && fileType !== 'csv') {
        showToast('只支持TXT或CSV文件', 'warning');
        return;
    }
    
    // 检查文件大小
    if (file.size > 2 * 1024 * 1024) {
        showToast('文件大小不能超过2MB', 'warning');
        return;
    }
    
    // 显示加载提示
    showToast('正在上传和处理文件，请稍候...', 'info');
    
    // 构建FormData
    const formData = new FormData(form);
    
    // 发送上传请求
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '?do=liin&act=file_upload', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.code === 1) {
                        showImportResult(res);
                    } else {
                        showToast(res.msg, 'error');
                    }
                } catch (e) {
                    showToast('解析响应失败', 'error');
                }
            } else {
                showToast('请求失败，状态码：' + xhr.status, 'error');
            }
        }
    };
    xhr.send(formData);
}

// 显示导入结果
function showImportResult(result) {
    // 隐藏选项卡内容
    document.getElementById('content-paste').style.display = 'none';
    document.getElementById('content-file').style.display = 'none';
    
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

// 隐藏导入结果
function hideImportResult() {
    document.getElementById('importResult').style.display = 'none';
    
    // 重置表单并显示选项卡内容
    document.getElementById('pasteFormPid').value = '';
    document.getElementById('pasteContent').value = '';
    document.getElementById('fileUploadForm').reset();
    
    // 显示当前激活的选项卡内容
    const activeTab = document.querySelector('.tab-item.active');
    if (activeTab) {
        const tabId = activeTab.id.split('-')[1];
        document.getElementById('content-' + tabId).style.display = 'block';
    } else {
        switchTab('paste');
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
