<?php
/**
 * 用户报到统计页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act'])) {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    switch ($_GET['act']) {
        // 获取每日报到统计
        case 'daily':
            // 获取所有报到数据
            $sql = "SELECT tihe, COUNT(*) AS total FROM card WHERE user='{$user['id']}' AND icha=1 GROUP BY tihe ORDER BY tihe DESC";
            $result = $db->raw($sql);
            
            echo json_encode($result);
            exit;
            
        // 获取月度报到统计
        case 'monthly':
            // 提取年月并统计
            $sql = "SELECT SUBSTRING(tihe, 1, 6) AS month, COUNT(*) AS total FROM card WHERE user='{$user['id']}' AND icha=1 GROUP BY month ORDER BY month DESC";
            $result = $db->raw($sql);
            
            // 格式化月份显示
            foreach ($result as &$item) {
                $year = substr($item['month'], 0, 4);
                $month = substr($item['month'], 4, 2);
                $item['month_format'] = $year . '年' . $month . '月';
            }
            
            echo json_encode($result);
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
            <h3>报到统计</h3>
            <p>查看您的报到统计信息</p>
        </div>
        
        <div class="panel-body">
            <div class="tabs">
                <div class="tab-nav">
                    <div id="tab-daily" class="tab-item active" onclick="switchTab('daily')">每日统计</div>
                    <div id="tab-monthly" class="tab-item" onclick="switchTab('monthly')">月度统计</div>
                </div>
                
                <div id="content-daily" class="tab-content active">
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="dailyTable">
                            <thead>
                                <tr>
                                    <th>日期</th>
                                    <th>报到人数</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 数据将通过AJAX加载 -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="content-monthly" class="tab-content">
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="monthlyTable">
                            <thead>
                                <tr>
                                    <th>月份</th>
                                    <th>报到人数</th>
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

<!-- 引入Chart.js -->
<script>
// 简单的图表绘制函数
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
        ctx.fillText(labels[i], x + barWidth / 2, chartHeight + 55);
    }
}

// 加载每日统计数据
function loadDailyStats() {
    ajax('?do=tong&act=daily', {}, function(res) {
        if (res && res.length > 0) {
            // 提取数据用于图表
            const labels = res.slice(0, 10).map(item => formatDate(item.tihe));
            const data = res.slice(0, 10).map(item => parseInt(item.total));
            
            // 绘制图表
            drawBarChart('dailyChart', labels, data, '最近10天报到人数统计');
            
            // 渲染表格
            let html = '';
            res.forEach(function(item) {
                html += `
                    <tr>
                        <td>${formatDate(item.tihe)}</td>
                        <td>${item.total}</td>
                    </tr>`;
            });
            
            document.getElementById('dailyTable').querySelector('tbody').innerHTML = html;
        } else {
            document.getElementById('dailyTable').querySelector('tbody').innerHTML = '<tr><td colspan="2" class="text-center">暂无数据</td></tr>';
        }
    });
}

// 加载月度统计数据
function loadMonthlyStats() {
    ajax('?do=tong&act=monthly', {}, function(res) {
        if (res && res.length > 0) {
            // 提取数据用于图表
            const labels = res.slice(0, 6).map(item => item.month_format);
            const data = res.slice(0, 6).map(item => parseInt(item.total));
            
            // 绘制图表
            drawBarChart('monthlyChart', labels, data, '最近6个月报到人数统计');
            
            // 渲染表格
            let html = '';
            res.forEach(function(item) {
                html += `
                    <tr>
                        <td>${item.month_format}</td>
                        <td>${item.total}</td>
                    </tr>`;
            });
            
            document.getElementById('monthlyTable').querySelector('tbody').innerHTML = html;
        } else {
            document.getElementById('monthlyTable').querySelector('tbody').innerHTML = '<tr><td colspan="2" class="text-center">暂无数据</td></tr>';
        }
    });
}

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
    
    // 加载对应数据
    if (tabId === 'daily') {
        loadDailyStats();
    } else if (tabId === 'monthly') {
        loadMonthlyStats();
    }
}

// 格式化日期显示（YYYYMMDD -> YYYY-MM-DD）
function formatDate(dateStr) {
    if (!dateStr || dateStr.length !== 8) return dateStr;
    return dateStr.substr(0, 4) + '-' + dateStr.substr(4, 2) + '-' + dateStr.substr(6, 2);
}

// 页面加载完成后加载初始数据
document.addEventListener('DOMContentLoaded', function() {
    loadDailyStats();
});
</script>

<style>
.chart-container {
    margin: 20px 0;
    width: 100%;
    height: 300px;
}

.tab-content {
    padding: 15px 0;
}
</style>
