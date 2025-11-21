<?php
/**
 * 用户报到记录页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act'])) {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    switch ($_GET['act']) {
        // 获取报到记录列表
        case 'list':
            $page = isset($post_data['page']) ? intval($post_data['page']) : 1;
            $limit = isset($post_data['limit']) ? intval($post_data['limit']) : 10;
            $search = isset($post_data['search']) ? $post_data['search'] : '';
            
            // 构建查询条件
            $where = "user='{$user['id']}' AND icha=1";
            if (!empty($search)) {
                $where .= " AND (name LIKE '%".safe_input($search)."%' OR sfzh LIKE '%".safe_input($search)."%' OR zkzh LIKE '%".safe_input($search)."%')";
            }
            
            // 获取分页数据
            $result = $db->paginate('card', $page, $limit, '*', $where, 'hex_time DESC');
            
            // 处理数据
            foreach ($result['data'] as &$item) {
                // 解析 desc 字段中的 JSON 数据
                $item['desc'] = json_decode($item['desc'], true);
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
            <h3>报到记录</h3>
            <p>查看您的报到记录</p>
        </div>
        
        <div class="panel-body">
            <div class="search-form">
                <input type="text" id="searchInput" class="form-control" placeholder="搜索姓名、身份证号或准考证号">
                <button class="btn" onclick="searchRecords()">搜索</button>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="recordsTable">
                    <thead>
                        <tr>
                            <th>姓名</th>
                            <th>身份证号</th>
                            <th>准考证号</th>
                            <th>批次号</th>
                            <th>额外信息</th>
                            <th>报到日期</th>
                            <th>报到时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 记录将通过AJAX加载 -->
                    </tbody>
                </table>
            </div>
            
            <div id="pagination" class="pagination">
                <!-- 分页将通过JS生成 -->
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let searchText = '';

// 页面加载完成后加载数据
document.addEventListener('DOMContentLoaded', function() {
    loadRecords();
});

// 加载报到记录
function loadRecords() {
    const data = {
        page: currentPage,
        limit: 10,
        search: searchText
    };
    
    ajax('?do=list&act=list', data, function(res) {
        if (res && res.data) {
            renderTable(res.data);
            renderPagination(res.current_page, res.last_page);
        } else {
            document.getElementById('recordsTable').querySelector('tbody').innerHTML = '<tr><td colspan="7" class="text-center">暂无数据</td></tr>';
            document.getElementById('pagination').innerHTML = '';
        }
    });
}

// 渲染表格
function renderTable(data) {
    const tbody = document.getElementById('recordsTable').querySelector('tbody');
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="7" class="text-center">暂无数据</td></tr>';
    } else {
        data.forEach(function(item) {
            // 处理额外信息
            let extraInfo = '';
            if (item.desc) {
                const keys = Object.keys(item.desc);
                if (keys.length > 0) {
                    extraInfo = keys.map(function(key) {
                        return key + ': ' + item.desc[key];
                    }).join(', ');
                }
            }
            
            html += `
                <tr>
                    <td>${item.name}</td>
                    <td>${maskSensitiveInfo(item.sfzh)}</td>
                    <td>${item.zkzh}</td>
                    <td>${item.pid || '-'}</td>
                    <td>${extraInfo || '-'}</td>
                    <td>${item.tihe}</td>
                    <td>${item.hex_time}</td>
                </tr>`;
        });
    }
    
    tbody.innerHTML = html;
}

// 掩盖敏感信息
function maskSensitiveInfo(info) {
    if (!info || info.length < 10) return info;
    return info.substr(0, 4) + '**********' + info.substr(info.length - 4);
}

// 渲染分页
function renderPagination(current, total) {
    const pagination = document.getElementById('pagination');
    currentPage = current;
    totalPages = total;
    
    if (total <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // 首页和上一页
    if (current > 1) {
        html += `<a href="javascript:void(0)" onclick="goToPage(1)" class="page-btn">首页</a>`;
        html += `<a href="javascript:void(0)" onclick="goToPage(${current - 1})" class="page-btn">上一页</a>`;
    } else {
        html += `<span class="page-btn disabled">首页</span>`;
        html += `<span class="page-btn disabled">上一页</span>`;
    }
    
    // 页码选择
    html += `<select class="page-select" onchange="goToPage(this.value)">`;
    for (let i = 1; i <= total; i++) {
        const selected = (i == current) ? 'selected' : '';
        html += `<option value="${i}" ${selected}>${i}/${total}</option>`;
    }
    html += `</select>`;
    
    // 下一页和最后页
    if (current < total) {
        html += `<a href="javascript:void(0)" onclick="goToPage(${current + 1})" class="page-btn">下一页</a>`;
        html += `<a href="javascript:void(0)" onclick="goToPage(${total})" class="page-btn">末页</a>`;
    } else {
        html += `<span class="page-btn disabled">下一页</span>`;
        html += `<span class="page-btn disabled">末页</span>`;
    }
    
    pagination.innerHTML = html;
}

// 翻页操作
function goToPage(page) {
    currentPage = parseInt(page);
    loadRecords();
}

// 搜索记录
function searchRecords() {
    searchText = document.getElementById('searchInput').value.trim();
    currentPage = 1;
    loadRecords();
}

// 监听回车键搜索
document.getElementById('searchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        searchRecords();
    }
});
</script>

<style>
.table-responsive {
    overflow-x: auto;
}

.text-center {
    text-align: center;
}
</style>
