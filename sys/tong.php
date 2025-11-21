<?php
/**
 * 管理员统计页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act'])) {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    switch ($_GET['act']) {
        // 获取批次列表
        case 'get_batches':
            $batches = $db->getAll('card', 'DISTINCT pid', 'pid IS NOT NULL AND pid != ""', 'pid');
            echo json_encode($batches);
            exit;
            
        // 获取批次统计
        case 'batch_stats':
            $pid = isset($post_data['pid']) ? safe_input($post_data['pid']) : '';
            
            if (empty($pid)) {
                echo json_msg(0, '请选择批次号');
                exit;
            }
            
            // 获取该批次的总数量和已报到数量
            $total = $db->count('card', "pid='{$pid}'");
            $registered = $db->count('card', "pid='{$pid}' AND icha=1");
            
            $result = [
                'code' => 1,
                'pid' => $pid,
                'total' => $total,
                'registered' => $registered,
                'unregistered' => $total - $registered,
                'percentage' => $total > 0 ? round(($registered / $total) * 100, 2) : 0
            ];
            
            echo json_encode($result);
            exit;
            
        // 清空批次数据
        case 'clear_batch':
            $pid = isset($post_data['pid']) ? safe_input($post_data['pid']) : '';
            
            if (empty($pid)) {
                echo json_msg(0, '请选择批次号');
                exit;
            }
            
            // 删除该批次的所有数据
            $result = $db->delete('card', "pid='{$pid}'");
            
            if ($result) {
                echo json_msg(1, '批次数据清空成功');
            } else {
                echo json_msg(0, '批次数据清空失败，请重试');
            }
            exit;
            
        // 获取时间段统计
        case 'time_stats':
            $start_date = isset($post_data['start_date']) ? safe_input($post_data['start_date']) : '';
            $end_date = isset($post_data['end_date']) ? safe_input($post_data['end_date']) : '';
            
            if (empty($start_date) || empty($end_date)) {
                echo json_msg(0, '请选择开始和结束日期');
                exit;
            }
            
            // 将日期转为纯数字格式
            $start_num = str_replace('-', '', $start_date);
            $end_num = str_replace('-', '', $end_date);
            
            // 查询时间段内各用户的报到数量
            $sql = "SELECT u.name, COUNT(c.id) AS total FROM card c LEFT JOIN user u ON c.user = u.id WHERE c.icha=1 AND c.tihe BETWEEN '{$start_num}' AND '{$end_num}' GROUP BY c.user ORDER BY total DESC";
            $stats = $db->raw($sql);
            
            echo json_encode([
                'code' => 1,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'stats' => $stats
            ]);
            exit;
            
        // 获取用户统计
        case 'user_stats':
            $user_id = isset($post_data['user_id']) ? intval($post_data['user_id']) : 0;
            
            if ($user_id <= 0) {
                echo json_msg(0, '请选择用户');
                exit;
            }
            
            // 查询该用户各月份的报到数量
            $sql = "SELECT SUBSTRING(tihe, 1, 6) AS month, COUNT(*) AS total FROM card WHERE user='{$user_id}' AND icha=1 GROUP BY month ORDER BY month";
            $monthly_stats = $db->raw($sql);
            
            // 格式化月份显示
            foreach ($monthly_stats as &$item) {
                $year = substr($item['month'], 0, 4);
                $month = substr($item['month'], 4, 2);
                $item['month_format'] = $year . '年' . $month . '月';
            }
            
            // 获取用户信息
            $user_info = $db->getOne('user', 'name', "id='{$user_id}'");
            
            echo json_encode([
                'code' => 1,
                'user_id' => $user_id,
                'user_name' => $user_info ? $user_info['name'] : '未知',
                'stats' => $monthly_stats
            ]);
            exit;
            
        // 获取用户列表
        case 'get_users':
            $users = $db->getAll('user', 'id, name', "type='user'", 'id');
            echo json_encode($users);
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
            <h3>统计页面</h3>
            <p>查看学生报到统计信息</p>
        </div>
        
        <div class="panel-body">
            <div class="tabs">
                <div class="tab-nav">
                    <div id="tab-batch" class="tab-item active" onclick="switchTab('batch')">批次统计</div>
                    <div id="tab-time" class="tab-item" onclick="switchTab('time')">时间统计</div>
                    <div id="tab-user" class="tab-item" onclick="switchTab('user')">用户统计</div>
                </div>
                
                <!-- 批次统计 -->
                <div id="content-batch" class="tab-content active">
                    <div class="form-group">
                        <div class="inline-form">
                            <select id="batchSelect" class="form-control">
                                <option value="">请选择批次号</option>
                                <!-- 批次列表将通过AJAX加载 -->
                            </select>
                            <button class="btn" onclick="loadBatchStats()">查询</button>
                            <button class="btn" onclick="downloadBatch()">下载</button>
                            <button class="btn btn-danger" onclick="clearBatch()">清空</button>
                        </div>
                    </div>
                    
                    <div id="batchStatsContainer" style="display: none;">
                        <div class="stats-card">
                            <div class="stats-header">
                                <h4>批次: <span id="batchPid"></span></h4>
                            </div>
                            <div class="stats-body">
                                <div class="stats-item">
                                    <div class="stats-label">总数量:</div>
                                    <div class="stats-value" id="batchTotal">0</div>
                                </div>
                                <div class="stats-item">
                                    <div class="stats-label">已报到:</div>
                                    <div class="stats-value registered" id="batchRegistered">0</div>
                                </div>
                                <div class="stats-item">
                                    <div class="stats-label">未报到:</div>
                                    <div class="stats-value unregistered" id="batchUnregistered">0</div>
                                </div>
                                <div class="stats-item">
                                    <div class="stats-label">报到率:</div>
                                    <div class="stats-value" id="batchPercentage">0%</div>
                                </div>
                            </div>
                            <div class="progress">
                                <div id="batchProgress" class="progress-bar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 时间统计 -->
                <div id="content-time" class="tab-content">
                    <div class="form-group">
                        <div class="inline-form">
                            <input type="date" id="startDate" class="form-control" placeholder="开始日期">
                            <span>至</span>
                            <input type="date" id="endDate" class="form-control" placeholder="结束日期">
                            <button class="btn" onclick="loadTimeStats()">查询</button>
                            <button class="btn" onclick="downloadTimeStats()">下载</button>
                        </div>
                    </div>
                    
                    <div id="timeStatsContainer" style="display: none;">
                        <div class="table-responsive">
                            <table class="table" id="timeStatsTable">
                                <thead>
                                    <tr>
                                        <th>用户</th>
                                        <th>报到数量</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 数据将通过AJAX加载 -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- 用户统计 -->
                <div id="content-user" class="tab-content">
                    <div class="form-group">
                        <div class="inline-form">
                            <select id="userSelect" class="form-control">
                                <option value="">请选择用户</option>
                                <!-- 用户列表将通过AJAX加载 -->
                            </select>
                            <button class="btn" onclick="loadUserStats()">查询</button>
                            <button class="btn" onclick="downloadUserStats()">下载</button>
                        </div>
                    </div>
                    
                    <div id="userStatsContainer" style="display: none;">
                        <h4>用户: <span id="statsUserName"></span></h4>
                        
                        <div class="chart-container">
                            <canvas id="userStatsChart"></canvas>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table" id="userStatsTable">
                                <thead>
                                    <tr>
                                        <th>月份</th>
                                        <th>报到数量</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 数据将通过AJAX加载 -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 加载批次列表
    loadBatches();
    
    // 加载用户列表
    loadUsers();
    
    // 设置默认日期范围（上个月）
    setDefaultDateRange();
});

// 切换选项卡
function switchTab(tabId) {
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

// 加载批次列表
function loadBatches() {
    ajax('?do=tong&act=get_batches', {}, function(res) {
        if (res && res.length > 0) {
            const select = document.getElementById('batchSelect');
            
            // 清空原有选项
            select.innerHTML = '<option value="">请选择批次号</option>';
            
            // 添加新选项
            res.forEach(function(batch) {
                const option = document.createElement('option');
                option.value = batch.pid;
                option.textContent = batch.pid;
                select.appendChild(option);
            });
        }
    });
}

// 加载用户列表
function loadUsers() {
    ajax('?do=tong&act=get_users', {}, function(res) {
        if (res && res.length > 0) {
            const select = document.getElementById('userSelect');
            
            // 清空原有选项
            select.innerHTML = '<option value="">请选择用户</option>';
            
            // 添加新选项
            res.forEach(function(user) {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                select.appendChild(option);
            });
        }
    });
}

// 设置默认日期范围（上个月）
function setDefaultDateRange() {
    const now = new Date();
    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const lastMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0);
    
    // 格式化日期为YYYY-MM-DD
    const formatDate = function(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };
    
    document.getElementById('startDate').value = formatDate(lastMonth);
    document.getElementById('endDate').value = formatDate(lastMonthEnd);
}

// 加载批次统计
function loadBatchStats() {
    const pid = document.getElementById('batchSelect').value;
    
    if (!pid) {
        showToast('请选择批次号', 'warning');
        return;
    }
    
    ajax('?do=tong&act=batch_stats', { pid: pid }, function(res) {
        if (res.code === 1) {
            // 更新统计数据
            document.getElementById('batchPid').textContent = res.pid;
            document.getElementById('batchTotal').textContent = res.total;
            document.getElementById('batchRegistered').textContent = res.registered;
            document.getElementById('batchUnregistered').textContent = res.unregistered;
            document.getElementById('batchPercentage').textContent = res.percentage + '%';
            
            // 更新进度条
            document.getElementById('batchProgress').style.width = res.percentage + '%';
            
            // 显示统计容器
            document.getElementById('batchStatsContainer').style.display = 'block';
        } else {
            showToast(res.msg, 'error');
        }
    });
}

// 清空批次数据
function clearBatch() {
    const pid = document.getElementById('batchSelect').value;
    
    if (!pid) {
        showToast('请选择批次号', 'warning');
        return;
    }
    
    // 三次确认
    tripleConfirm('确认要清空批次 ' + pid + ' 的所有数据吗？此操作不可恢复！', function() {
        ajax('?do=tong&act=clear_batch', { pid: pid }, function(res) {
            if (res.code === 1) {
                showToast(res.msg, 'success');
                // 重新加载批次列表
                loadBatches();
                // 隐藏统计容器
                document.getElementById('batchStatsContainer').style.display = 'none';
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

// 下载批次数据
function downloadBatch() {
    const pid = document.getElementById('batchSelect').value;
    
    if (!pid) {
        showToast('请选择批次号', 'warning');
        return;
    }
    
    // 跳转到下载页面
    window.location.href = 'down.php?type=batch&pid=' + encodeURIComponent(pid);
}

// 加载时间段统计
function loadTimeStats() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        showToast('请选择开始和结束日期', 'warning');
        return;
    }
    
    ajax('?do=tong&act=time_stats', { start_date: startDate, end_date: endDate }, function(res) {
        if (res.code === 1) {
            const tbody = document.getElementById('timeStatsTable').querySelector('tbody');
            
            if (res.stats && res.stats.length > 0) {
                let html = '';
                
                res.stats.forEach(function(item) {
                    html += `
                        <tr>
                            <td>${item.name || '未知'}</td>
                            <td>${item.total}</td>
                        </tr>`;
                });
                
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center">所选时间段内无数据</td></tr>';
            }
            
            // 显示统计容器
            document.getElementById('timeStatsContainer').style.display = 'block';
        } else {
            showToast(res.msg, 'error');
        }
    });
}

// 下载时间段统计数据
function downloadTimeStats() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        showToast('请选择开始和结束日期', 'warning');
        return;
    }
    
    // 跳转到下载页面
    window.location.href = 'down.php?type=time&start=' + encodeURIComponent(startDate) + '&end=' + encodeURIComponent(endDate);
}

// 加载用户统计
function loadUserStats() {
    const userId = document.getElementById('userSelect').value;
    
    if (!userId) {
        showToast('请选择用户', 'warning');
        return;
    }
    
    ajax('?do=tong&act=user_stats', { user_id: userId }, function(res) {
        if (res.code === 1) {
            // 设置用户名
            document.getElementById('statsUserName').textContent = res.user_name;
            
            // 渲染表格
            const tbody = document.getElementById('userStatsTable').querySelector('tbody');
            
            if (res.stats && res.stats.length > 0) {
                let html = '';
                
                res.stats.forEach(function(item) {
                    html += `
                        <tr>
                            <td>${item.month_format}</td>
                            <td>${item.total}</td>
                        </tr>`;
                });
                
                tbody.innerHTML = html;
                
                // 绘制图表
                const labels = res.stats.map(item => item.month_format);
                const data = res.stats.map(item => parseInt(item.total));
                
                drawBarChart('userStatsChart', labels, data, res.user_name + ' 每月报到数量统计');
            } else {                
                drawBarChart('userStatsChart', [], [], res.user_name + ' 每月报到数量统计');
                tbody.innerHTML = '<tr><td colspan="2" class="text-center">该用户暂无报到数据</td></tr>';
            }
            
            // 显示统计容器
            document.getElementById('userStatsContainer').style.display = 'block';
        } else {
            showToast(res.msg, 'error');
        }
    });
}

// 下载用户统计数据
function downloadUserStats() {
    const userId = document.getElementById('userSelect').value;
    
    if (!userId) {
        showToast('请选择用户', 'warning');
        return;
    }
    
    // 跳转到下载页面
    window.location.href = 'down.php?type=user&user=' + encodeURIComponent(userId);
}

// 绘制柱状图
function drawBarChart(canvasId, labels, data, title) {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');
    
    // 清空画布
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // 设置画布大小
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = 300;
    
    // 定义图表参数
    const chartWidth = canvas.width - 80;
    const chartHeight = canvas.height - 60;
    const barWidth = Math.min(40, chartWidth / data.length - 10);
    const maxValue = Math.max(...data) || 1;
    const scale = chartHeight / maxValue;
    
    // 绘制标题
    ctx.fillStyle = '#333';
    ctx.font = 'bold 16px Arial';
    ctx.textAlign = 'center';
    ctx.fillText(title, canvas.width / 2, 20);
    
    // 绘制Y轴
    ctx.beginPath();
    ctx.moveTo(50, 40);
    ctx.lineTo(50, chartHeight + 40);
    ctx.stroke();
    
    // 绘制X轴
    ctx.beginPath();
    ctx.moveTo(50, chartHeight + 40);
    ctx.lineTo(chartWidth + 50, chartHeight + 40);
    ctx.stroke();
    
    // 绘制Y轴刻度
    const yStep = Math.ceil(maxValue / 5);
    for (let i = 0; i <= maxValue; i += yStep) {
        const y = chartHeight + 40 - i * scale;
        
        ctx.beginPath();
        ctx.moveTo(45, y);
        ctx.lineTo(50, y);
        ctx.stroke();
        
        ctx.fillStyle = '#666';
        ctx.font = '12px Arial';
        ctx.textAlign = 'right';
        ctx.fillText(i, 40, y + 5);
    }
    
    // 绘制柱状图和X轴标签
    for (let i = 0; i < data.length; i++) {
        const x = 60 + i * (barWidth + 10);
        const barHeight = data[i] * scale;
        const y = chartHeight + 40 - barHeight;
        
        // 绘制柱形
        ctx.fillStyle = 'rgba(24, 144, 255, 0.7)';
        ctx.fillRect(x, y, barWidth, barHeight);
        
        // 绘制数值
        ctx.fillStyle = '#333';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(data[i], x + barWidth / 2, y - 5);
        
        // 绘制X轴标签
        ctx.fillStyle = '#666';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        
        // 由于标签可能很长，尝试旋转或截断
        const label = labels[i];
        if (label.length > 8) {
            // 旋转标签
            ctx.save();
            ctx.translate(x + barWidth / 2, chartHeight + 45);
            ctx.rotate(-Math.PI / 4);
            ctx.fillText(label, 0, 0);
            ctx.restore();
        } else {
            ctx.fillText(label, x + barWidth / 2, chartHeight + 55);
        }
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

.inline-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.inline-form span {
    margin: 0 5px;
}

.stats-card {
    background-color: #fafafa;
    border: 1px solid #f0f0f0;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.stats-header {
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.stats-header h4 {
    margin: 0;
    font-size: 16px;
}

.stats-body {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.stats-item {
    flex: 1;
    min-width: 120px;
    background-color: #fff;
    border: 1px solid #eee;
    border-radius: 4px;
    padding: 10px;
    text-align: center;
}

.stats-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.stats-value {
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.stats-value.registered {
    color: #52c41a;
}

.stats-value.unregistered {
    color: #ff4d4f;
}

.progress {
    height: 10px;
    background-color: #f5f5f5;
    border-radius: 5px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background-color: #1890ff;
    border-radius: 5px;
    transition: width 0.3s ease;
}

.chart-container {
    margin: 20px 0;
    width: 100%;
    height: 300px;
}

.table-responsive {
    overflow-x: auto;
}

.text-center {
    text-align: center;
}

@media screen and (max-width: 768px) {
    .inline-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .stats-body {
        flex-direction: column;
    }
    
    .stats-item {
        width: 100%;
    }
}
</style>
