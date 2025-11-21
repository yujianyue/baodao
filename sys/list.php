<?php
/**
 * 管理员数据列表页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act'])) {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    switch ($_GET['act']) {
        // 获取数据列表
        case 'list':
            $page = isset($post_data['page']) ? intval($post_data['page']) : 1;
            $limit = isset($post_data['limit']) ? intval($post_data['limit']) : 10;
            $search_field = isset($post_data['search_field']) ? $post_data['search_field'] : '';
            $search_value = isset($post_data['search_value']) ? $post_data['search_value'] : '';
            
            // 构建查询条件
            $where = "1=1";
            if (!empty($search_field) && !empty($search_value)) {
                $where .= " AND {$search_field} LIKE '%".safe_input($search_value)."%'";
            }
            
            // 获取分页数据
            $result = $db->paginate('card', $page, $limit, '*', $where, 'id DESC');
            
            // 处理数据
            foreach ($result['data'] as &$item) {
                // 解析 desc 字段中的 JSON 数据
                $item['desc'] = json_decode($item['desc'], true);
                
                // 获取报到人信息
                if ($item['icha'] == 1 && $item['user']) {
                    $reporter = $db->getOne('user', 'name', "id='{$item['user']}'");
                    $item['reporter_name'] = $reporter ? $reporter['name'] : '未知';
                } else {
                    $item['reporter_name'] = '-';
                }
            }
            
            echo json_encode($result);
            exit;
            
        // 删除数据
        case 'delete':
            $id = isset($post_data['id']) ? intval($post_data['id']) : 0;
            
            if ($id <= 0) {
                echo json_msg(0, '无效的记录ID');
                exit;
            }
            
            // 删除记录
            $result = $db->delete('card', "id='{$id}'");
            
            if ($result) {
                echo json_msg(1, '删除成功');
            } else {
                echo json_msg(0, '删除失败，请重试');
            }
            exit;
            
        // 批量删除
        case 'batch_delete':
            $ids = isset($post_data['ids']) ? $post_data['ids'] : [];
            
            if (empty($ids)) {
                echo json_msg(0, '未选择任何记录');
                exit;
            }
            
            // 将ID数组转为字符串
            $id_str = implode(',', array_map('intval', $ids));
            
            // 批量删除记录
            $result = $db->delete('card', "id IN ({$id_str})");
            
            if ($result) {
                echo json_msg(1, '批量删除成功');
            } else {
                echo json_msg(0, '批量删除失败，请重试');
            }
            exit;
            
        // 报到操作
        case 'register':
            $id = isset($post_data['id']) ? intval($post_data['id']) : 0;
            
            if ($id <= 0) {
                echo json_msg(0, '无效的记录ID');
                exit;
            }
            
            // 查询学生信息
            $student = $db->getOne('card', '*', "id='{$id}'");
            
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
            
            $result = $db->update('card', $update_data, "id='{$id}'");
            
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
            <h3>数据列表</h3>
        </div>
        
        <div class="panel-body">
            <div class="search-form">
                <div style="display: flex; align-items: center;">
                    <select id="searchField" class="form-control" style="width: 120px; margin-right: 10px;">
                        <option value="name">姓名</option>
                        <option value="sfzh">身份证号</option>
                        <option value="zkzh">准考证号</option>
                        <option value="pid">批次号</option>
                    </select>
                    <input type="text" id="searchValue" class="form-control" placeholder="请输入搜索内容" style="margin-right: 10px;">
                    <button class="btn" onclick="searchRecords()">搜索</button>
                </div>
                <div>
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="recordsTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll('selectAll', 'record-checkbox')">
                                    <span class="checkmark"></span>
                                </label>
                            </th>
                            <th>姓名</th>
                            <th>身份证号</th>
                            <th>准考证号</th>
                            <th>批次号</th>
                            <th>系别专业</th>
                            <th>状态</th>
                            <th>报到人</th>
                            <th>报到时间</th>
                            <th>操作</th>
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
let searchField = 'name';
let searchValue = '';

// 页面加载完成后加载数据
document.addEventListener('DOMContentLoaded', function() {
    loadRecords();
});

// 加载数据列表
function loadRecords() {
    const data = {
        page: currentPage,
        limit: 10,
        search_field: searchField,
        search_value: searchValue
    };
    
    ajax('?do=list&act=list', data, function(res) {
        if (res && res.data) {
            renderTable(res.data);
            renderPagination(res.current_page, res.last_page);
        } else {
            document.getElementById('recordsTable').querySelector('tbody').innerHTML = '<tr><td colspan="10" class="text-center">暂无数据</td></tr>';
            document.getElementById('pagination').innerHTML = '';
        }
    });
}

// 渲染表格
function renderTable(data) {
    const tbody = document.getElementById('recordsTable').querySelector('tbody');
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="10" class="text-center">暂无数据</td></tr>';
    } else {
        data.forEach(function(item) {
            // 提取系别专业信息
            let extraInfo = '';
            if (item.desc) {
                const keys = Object.keys(item.desc);
                if (keys.length > 0) {
                    extraInfo = keys.map(function(key) {
                        return key + ': ' + item.desc[key];
                    }).join('<br>');
                }
            }
            
            // 生成状态和操作按钮
            let status = item.icha == 1 ? 
                '<span class="status-tag registered">已报到</span>' : 
                '<span class="status-tag unregistered">未报到</span>';
            
            let actionBtn = item.icha == 1 ?
                '' :
                `<button class="btn btn-sm btn-success" onclick="registerStudent(${item.id})">报到</button>`;
            
            html += `
                <tr>
                    <td>
                        <label class="checkbox-wrapper">
                            <input type="checkbox" class="record-checkbox" value="${item.id}">
                            <span class="checkmark"></span>
                        </label>
                    </td>
                    <td>${item.name}</td>
                    <td>${item.sfzh}</td>
                    <td>${item.zkzh}</td>
                    <td>${item.pid || '-'}</td>
                    <td>${extraInfo || '-'}</td>
                    <td>${status}</td>
                    <td>${item.reporter_name}</td>
                    <td>${item.hex_time || '-'}</td>
                    <td>
                        ${actionBtn}
                        <button class="btn btn-sm btn-danger" onclick="deleteRecord(${item.id})">删除</button>
                    </td>
                </tr>`;
        });
    }
    
    tbody.innerHTML = html;
    
    // 重置全选框
    document.getElementById('selectAll').checked = false;
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
    searchField = document.getElementById('searchField').value;
    searchValue = document.getElementById('searchValue').value.trim();
    currentPage = 1;
    loadRecords();
}

// 监听回车键搜索
document.getElementById('searchValue').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        searchRecords();
    }
});

// 删除记录
function deleteRecord(id) {
    confirm('确认删除该记录吗？此操作不可恢复。', function() {
        ajax('?do=list&act=delete', { id: id }, function(res) {
            if (res.code === 1) {
                showToast(res.msg, 'success');
                loadRecords();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

// 报到操作
function registerStudent(id) {
    confirm('确认将该学生标记为已报到吗？', function() {
        ajax('?do=list&act=register', { id: id }, function(res) {
            if (res.code === 1) {
                showToast(res.msg, 'success');
                loadRecords();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

// 批量删除
function batchDelete() {
    const selectedIds = getSelectedIds('record-checkbox');
    
    if (selectedIds.length === 0) {
        showToast('请选择要删除的记录', 'warning');
        return;
    }
    
    confirm('确认删除选中的 ' + selectedIds.length + ' 条记录吗？此操作不可恢复。', function() {
        ajax('?do=list&act=batch_delete', { ids: selectedIds }, function(res) {
            if (res.code === 1) {
                showToast(res.msg, 'success');
                loadRecords();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

// 全选/取消全选
function toggleSelectAll(checkboxId, targetClass) {
    const mainCheckbox = document.getElementById(checkboxId);
    const targetCheckboxes = document.querySelectorAll('.' + targetClass);
    
    targetCheckboxes.forEach(function(checkbox) {
        checkbox.checked = mainCheckbox.checked;
    });
}

// 获取选中的ID列表
function getSelectedIds(className) {
    const checkboxes = document.querySelectorAll('.' + className + ':checked');
    return Array.from(checkboxes).map(function(checkbox) {
        return checkbox.value;
    });
}
</script>

<style>
.search-form {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    background-color: #fff;
    padding: 15px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table-responsive {
    overflow-x: auto;
}

.text-center {
    text-align: center;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.status-tag {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.registered {
    background-color: #e6f7ff;
    color: #1890ff;
    border: 1px solid #91d5ff;
}

.unregistered {
    background-color: #fff2e8;
    color: #fa541c;
    border: 1px solid #ffbb96;
}
</style>
