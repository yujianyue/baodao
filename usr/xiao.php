<?php
/**
 * 用户报到页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act'])) {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    switch ($_GET['act']) {
        // 搜索学生信息
        case 'search':
            $search = isset($post_data['search']) ? $post_data['search'] : '';
            
            if (empty($search) || strlen($search) < 6) {
                echo json_msg(0, '请输入至少6位的身份证号或准考证号');
                exit;
            }
            
            // 查询学生信息
            $student = $db->getOne('card', '*', "sfzh='" . safe_input($search) . "' OR zkzh='" . safe_input($search) . "'");
            
            if (!$student) {
                echo json_msg(0, $settings['jies']);
                exit;
            }
            
            // 如果已报到，返回报到信息
            if ($student['icha'] == 1) {
                $reporter = $db->getOne('user', 'name', "id='" . $student['user'] . "'");
                $student['reporter'] = $reporter ? $reporter['name'] : '未知';
            }
            
            // 解析 desc 字段中的 JSON 数据
            $student['desc'] = json_decode($student['desc'], true);
            
            echo json_msg(1, '查询成功', $student);
            exit;
            
        // 搜索建议
        case 'suggest':
            $search = isset($post_data['search']) ? $post_data['search'] : '';
            
            if (empty($search) || strlen($search) < 6) {
                echo json_encode([]);
                exit;
            }
            
            // 查询包含该字符串的学生信息（尾号包含匹配）
            $students = $db->getAll('card', 'id, name, sfzh, zkzh', "sfzh LIKE '%" . safe_input($search) . "%' OR zkzh LIKE '%" . safe_input($search) . "%'", '', '10');
            
            $suggestions = [];
            foreach ($students as $student) {
                $suggestions[] = [
                    'id' => $student['id'],
                    'name' => $student['name'],
                    'sfzh' => $student['sfzh'],
                    'zkzh' => $student['zkzh']
                ];
            }
            
            echo json_encode($suggestions);
            exit;
            
        // 报到操作
        case 'register':
            $id = isset($post_data['id']) ? intval($post_data['id']) : 0;
            
            if ($id <= 0) {
                echo json_msg(0, '无效的学生ID');
                exit;
            }
            
            // 查询学生信息
            $student = $db->getOne('card', '*', "id='" . $id . "'");
            
            if (!$student) {
                echo json_msg(0, '学生信息不存在');
                exit;
            }
            
            // 检查是否已报到
            if ($student['icha'] == 1) {
                echo json_msg(0, '该学生已报到');
                exit;
            }
            
            // 更新报到状态
            $update_data = [
                'icha' => 1,
                'user' => $user['id'],
                'tihe' => date('Ymd'),
                'hex_time' => date('Y-m-d H:i:s')
            ];
            
            $result = $db->update('card', $update_data, "id='" . $id . "'");
            
            if ($result) {
                echo json_msg(1, '报到成功');
            } else {
                echo json_msg(0, '报到失败，请重试');
            }
            exit;
    }
    
    echo json_msg(0, '未知操作');
    exit;
}
// 引入公共头部
require_once './inc/head.php';
?>

<div class="container">
    <div class="panel">
        <div class="panel-heading">
            <h3>学生报到</h3>
            <p><?php echo $settings['chax']; ?></p>
        </div>
        
        <div class="panel-body">
            <div class="search-form">
                <input type="text" id="searchInput" class="form-control" placeholder="请输入身份证号或准考证号" autocomplete="off">
                <button class="btn" onclick="searchStudent()">查询</button>
            </div>
            
            <div id="suggestionsContainer" style="display: none;" class="suggestions-container"></div>
            
            <div id="resultContainer" style="display: none;" class="result-container">
                <!-- 结果将在这里显示 -->
            </div>
        </div>
    </div>
</div>

<script>
let timer = null;
let currentStudent = null;

// 监听输入框变化，实时搜索
document.getElementById('searchInput').addEventListener('input', function() {
    const searchValue = this.value.trim();
    
    // 清除之前的定时器
    clearTimeout(timer);
    
    // 清空搜索结果
    document.getElementById('resultContainer').style.display = 'none';
    
    // 输入内容少于6位时，隐藏建议
    if (searchValue.length < 6) {
        document.getElementById('suggestionsContainer').style.display = 'none';
        return;
    }
    
    // 延迟300ms发送请求，避免频繁请求
    timer = setTimeout(function() {
        // 发送建议请求
        ajax('?do=xiao&act=suggest', { search: searchValue }, function(res) {
            const suggestionsContainer = document.getElementById('suggestionsContainer');
            
            if (res && res.length > 0) {
                // 清空之前的建议
                suggestionsContainer.innerHTML = '';
                
                // 添加新的建议
                res.forEach(function(item) {
                    const suggestion = document.createElement('div');
                    suggestion.className = 'suggestion-item';
                    suggestion.innerHTML = `<strong>${item.name}</strong> - 身份证: ${maskIdCard(item.sfzh)}, 准考证: ${item.zkzh}`;
                    suggestion.onclick = function() {
                        document.getElementById('searchInput').value = item.sfzh;
                        suggestionsContainer.style.display = 'none';
                        searchStudent();
                    };
                    suggestionsContainer.appendChild(suggestion);
                });
                
                suggestionsContainer.style.display = 'block';
            } else {
                suggestionsContainer.style.display = 'none';
            }
        });
    }, 300);
});

// 掩盖身份证号中间部分
function maskIdCard(idCard) {
    if (!idCard || idCard.length < 10) return idCard;
    return idCard.substr(0, 4) + '**********' + idCard.substr(idCard.length - 4);
}

// 点击其他地方时隐藏建议
document.addEventListener('click', function(e) {
    if (e.target.id !== 'searchInput' && !e.target.closest('#suggestionsContainer')) {
        document.getElementById('suggestionsContainer').style.display = 'none';
    }
});

// 搜索学生信息
function searchStudent() {
    const searchValue = document.getElementById('searchInput').value.trim();
    
    if (searchValue.length < 6) {
        showToast('请输入至少6位的身份证号或准考证号', 'warning');
        return;
    }
    
    // 发送搜索请求
    ajax('?do=xiao&act=search', { search: searchValue }, function(res) {
        if (res.code === 1) {
            // 保存当前学生信息
            currentStudent = res.data;
            
            // 显示学生信息
            let html = `
                <div class="student-info">
                    <h3>${res.data.name}</h3>
                    <div class="info-row">
                        <span class="info-label">${settings.tiao2}：</span>
                        <span class="info-value">${res.data.sfzh}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">${settings.tiao3}：</span>
                        <span class="info-value">${res.data.zkzh}</span>
                    </div>`;
            
            // 添加额外信息（系别、专业、班级、宿舍号等）
            if (res.data.desc) {
                for (const key in res.data.desc) {
                    html += `
                    <div class="info-row">
                        <span class="info-label">${key}：</span>
                        <span class="info-value">${res.data.desc[key]}</span>
                    </div>`;
                }
            }
            
            // 添加报到状态
            if (res.data.icha == 1) {
                html += `
                    <div class="info-row status">
                        <span class="info-label">报到状态：</span>
                        <span class="info-value registered">已报到</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">报到人：</span>
                        <span class="info-value">${res.data.reporter || '未知'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">报到时间：</span>
                        <span class="info-value">${res.data.hex_time}</span>
                    </div>`;
            } else {
                html += `
                    <div class="info-row status">
                        <span class="info-label">报到状态：</span>
                        <span class="info-value unregistered">未报到</span>
                    </div>
                    <div class="action-row">
                        <button class="btn btn-success" onclick="registerStudent(${res.data.id})">确认报到</button>
                    </div>`;
            }
            
            html += `</div>`;
            
            document.getElementById('resultContainer').innerHTML = html;
            document.getElementById('resultContainer').style.display = 'block';
        } else {
            showToast(res.msg, 'error');
            document.getElementById('resultContainer').style.display = 'none';
        }
    });
}

// 报到操作
function registerStudent(id) {
    if (!id) return;
    
    confirm('确认为学生 ' + currentStudent.name + ' 报到吗？', function() {
        ajax('?do=xiao&act=register', { id: id }, function(res) {
            if (res.code === 1) {
                showToast(res.msg, 'success');
                // 重新搜索，刷新信息
                searchStudent();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

// 全局设置
const settings = {
    tiao1: '<?php echo $settings['tiao1']; ?>',
    tiao2: '<?php echo $settings['tiao2']; ?>',
    tiao3: '<?php echo $settings['tiao3']; ?>'
};
</script>

<style>
.panel {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    overflow: hidden;
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

.search-form {
    display: flex;
    margin-bottom: 15px;
}

.search-form .form-control {
    flex: 1;
    margin-right: 10px;
}

.suggestions-container {
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #fff;
    max-height: 200px;
    overflow-y: auto;
    margin-bottom: 15px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.suggestion-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: #f5f5f5;
}

.student-info {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #eee;
}

.student-info h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.info-row {
    margin-bottom: 10px;
    display: flex;
}

.info-label {
    width: 100px;
    font-weight: bold;
}

.info-value {
    flex: 1;
}

.status {
    margin: 15px 0;
}

.registered {
    color: #52c41a;
    font-weight: bold;
}

.unregistered {
    color: #ff4d4f;
    font-weight: bold;
}

.action-row {
    margin-top: 15px;
    text-align: center;
}
</style>
