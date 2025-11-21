<?php
/**
 * 管理员用户列表页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act'])) {
    header('Content-Type: application/json; charset=utf-8');
    $post_data = json_decode(file_get_contents('php://input'), true);
    
    switch ($_GET['act']) {
        // 获取用户列表
        case 'list':
            $page = isset($post_data['page']) ? intval($post_data['page']) : 1;
            $limit = isset($post_data['limit']) ? intval($post_data['limit']) : 10;
            $search = isset($post_data['search']) ? $post_data['search'] : '';
            
            // 构建查询条件
            $where = "1=1";
            if (!empty($search)) {
                $where .= " AND (user LIKE '%".safe_input($search)."%' OR name LIKE '%".safe_input($search)."%')";
            }
            
            // 获取分页数据
            $result = $db->paginate('user', $page, $limit, '*', $where, 'id DESC');
            
            echo json_encode($result);
            exit;
            
        // 重置密码
        case 'reset_password':
            $id = isset($post_data['id']) ? intval($post_data['id']) : 0;
            
            if ($id <= 0) {
                echo json_msg(0, '无效的用户ID');
                exit;
            }
            
            // 查询用户信息
            $user_info = $db->getOne('user', '*', "id='{$id}'");
            
            if (!$user_info) {
                echo json_msg(0, '用户不存在');
                exit;
            }
            
            // 生成新密码
            $new_password = generate_password(8);
            
            // 更新密码
            $result = $db->update('user', ['pass' => md5($new_password)], "id='{$id}'");
            
            if ($result) {
                echo json_msg(1, '密码重置成功', ['password' => $new_password]);
            } else {
                echo json_msg(0, '密码重置失败，请重试');
            }
            exit;
            
        // 修改用户状态
        case 'toggle_status':
            $id = isset($post_data['id']) ? intval($post_data['id']) : 0;
            $status = isset($post_data['status']) ? intval($post_data['status']) : 0;
            
            if ($id <= 0) {
                echo json_msg(0, '无效的用户ID');
                exit;
            }
            
            // 禁止修改当前登录用户的状态
            if ($id == $user['id']) {
                echo json_msg(0, '不能修改当前登录用户的状态');
                exit;
            }
            
            // 更新状态
            $result = $db->update('user', ['check' => $status], "id='{$id}'");
            
            if ($result) {
                echo json_msg(1, '状态修改成功');
            } else {
                echo json_msg(0, '状态修改失败，请重试');
            }
            exit;
            
        // 添加用户
        case 'add':
            $username = isset($post_data['username']) ? trim($post_data['username']) : '';
            $password = isset($post_data['password']) ? trim($post_data['password']) : '';
            $name = isset($post_data['name']) ? trim($post_data['name']) : '';
            $type = isset($post_data['type']) ? trim($post_data['type']) : 'user';
            
            // 验证输入
            if (empty($username) || empty($password) || empty($name)) {
                echo json_msg(0, '用户名、密码和姓名不能为空');
                exit;
            }
            
            // 验证用户名和密码格式
            if (!preg_match('/^[a-zA-Z0-9]{5,20}$/', $username)) {
                echo json_msg(0, '用户名必须为5-20位数字和字母组合');
                exit;
            }
            
            if (!preg_match('/^[a-zA-Z0-9]{6,16}$/', $password)) {
                echo json_msg(0, '密码必须为6-16位数字和字母组合');
                exit;
            }
            
            // 检查用户名是否已存在
            $exist = $db->getOne('user', 'id', "user='{$username}'");
            if ($exist) {
                echo json_msg(0, '用户名已存在');
                exit;
            }
            
            // 插入用户
            $insert_data = [
                'user' => $username,
                'pass' => md5($password),
                'name' => $name,
                'type' => $type,
                'check' => 1,
                'add_time' => date('Y-m-d H:i:s'),
                'log_time' => ''
            ];
            
            $result = $db->insert('user', $insert_data);
            
            if ($result) {
                echo json_msg(1, '用户添加成功');
            } else {
                echo json_msg(0, '用户添加失败，请重试');
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
            <h3>用户列表</h3>
        </div>
        
        <div class="panel-body">
            <div class="search-form">
                <div>
                    <input type="text" id="searchInput" class="form-control" placeholder="搜索用户名或姓名">
                    <button class="btn" onclick="searchUsers()">搜索</button>
                </div>
                <div>
                    <button class="btn" onclick="showAddUserModal()">添加用户</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户类型</th>
                            <th>用户名</th>
                            <th>姓名</th>
                            <th>添加时间</th>
                            <th>最后登录</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 用户列表将通过AJAX加载 -->
                    </tbody>
                </table>
            </div>
            
            <div id="pagination" class="pagination">
                <!-- 分页将通过JS生成 -->
            </div>
        </div>
    </div>
</div>

<!-- 添加用户模态框HTML模板 -->
<div id="addUserModalTemplate" style="display: none;">
    <form id="addUserForm">
        <div class="form-group">
            <label class="form-label">用户类型：</label>
            <div class="radio-group">
                <label class="radio-wrapper">
                    <input type="radio" name="type" value="user" checked>
                    <span class="radio-text">普通用户</span>
                </label>
                <label class="radio-wrapper">
                    <input type="radio" name="type" value="admin">
                    <span class="radio-text">管理员</span>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">用户名：</label>
            <input type="text" name="username" class="form-control" placeholder="5-20位数字和字母组合" required>
        </div>
        <div class="form-group">
            <label class="form-label">密码：</label>
            <input type="password" name="password" class="form-control" placeholder="6-16位数字和字母组合" required>
        </div>
        <div class="form-group">
            <label class="form-label">姓名：</label>
            <input type="text" name="name" class="form-control" placeholder="请输入姓名" required>
        </div>
    </form>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let searchText = '';

// 页面加载完成后加载数据
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});

// 加载用户列表
function loadUsers() {
    const data = {
        page: currentPage,
        limit: 10,
        search: searchText
    };
    
    ajax('?do=user&act=list', data, function(res) {
        if (res && res.data) {
            renderTable(res.data);
            renderPagination(res.current_page, res.last_page);
        } else {
            document.getElementById('userTable').querySelector('tbody').innerHTML = '<tr><td colspan="8" class="text-center">暂无数据</td></tr>';
            document.getElementById('pagination').innerHTML = '';
        }
    });
}

// 渲染表格
function renderTable(data) {
    const tbody = document.getElementById('userTable').querySelector('tbody');
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="8" class="text-center">暂无数据</td></tr>';
    } else {
        data.forEach(function(item) {
            const userType = item.type === 'admin' ? '管理员' : '普通用户';
            const status = item.check == 1 ? 
                '<span class="status-tag enabled">正常</span>' : 
                '<span class="status-tag disabled">禁用</span>';
            
            const statusToggleBtn = item.id == <?php echo $user['id']; ?> ? 
                '' : 
                `<button class="btn btn-sm ${item.check == 1 ? 'btn-danger' : 'btn-success'}" onclick="toggleStatus(${item.id}, ${item.check == 1 ? 0 : 1})">
                    ${item.check == 1 ? '禁用' : '启用'}
                </button>`;
            
            html += `
                <tr>
                    <td>${item.id}</td>
                    <td>${userType}</td>
                    <td>${item.user}</td>
                    <td>${item.name}</td>
                    <td>${item.add_time}</td>
                    <td>${item.log_time || '-'}</td>
                    <td>${status}</td>
                    <td>
                        <button class="btn btn-sm" onclick="resetPassword(${item.id})">重置密码</button>
                        ${statusToggleBtn}
                    </td>
                </tr>`;
        });
    }
    
    tbody.innerHTML = html;
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
    loadUsers();
}

// 搜索用户
function searchUsers() {
    searchText = document.getElementById('searchInput').value.trim();
    currentPage = 1;
    loadUsers();
}

// 监听回车键搜索
document.getElementById('searchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        searchUsers();
    }
});

// 重置密码
function resetPassword(id) {
    confirm('确认重置该用户的密码吗？', function() {
        ajax('?do=user&act=reset_password', { id: id }, function(res) {
            if (res.code === 1) {
                showModal('密码重置成功', `
                    <p>用户密码已重置为：<strong>${res.data.password}</strong></p>
                    <p>请记下此密码并通知用户。</p>
                `, [
                    {
                        text: '确定',
                        className: 'btn-success'
                    }
                ]);
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

// 修改用户状态
function toggleStatus(id, status) {
    const action = status === 1 ? '启用' : '禁用';
    
    confirm(`确认${action}该用户吗？`, function() {
        ajax('?do=user&act=toggle_status', { id: id, status: status }, function(res) {
            if (res.code === 1) {
                showToast(res.msg, 'success');
                loadUsers();
            } else {
                showToast(res.msg, 'error');
            }
        });
    });
}

// 显示添加用户模态框
function showAddUserModal() {
    // 创建模态框内容
    const template = document.getElementById('addUserModalTemplate').innerHTML;
    
    // 显示模态框
    const modal = showModal('添加用户', template, [
        {
            text: '取消',
            className: ''
        },
        {
            text: '添加',
            className: 'btn-success',
            callback: function() {
                // 获取表单数据
                const form = document.getElementById('addUserForm');
                const data = {
                    username: form.querySelector('input[name="username"]').value.trim(),
                    password: form.querySelector('input[name="password"]').value.trim(),
                    name: form.querySelector('input[name="name"]').value.trim(),
                    type: form.querySelector('input[name="type"]:checked').value
                };
                
                // 验证表单
                if (!data.username || !data.password || !data.name) {
                    showToast('所有字段都不能为空', 'warning');
                    return false; // 阻止模态框关闭
                }
                
                if (!/^[a-zA-Z0-9]{5,20}$/.test(data.username)) {
                    showToast('用户名必须为5-20位数字和字母组合', 'warning');
                    return false;
                }
                
                if (!/^[a-zA-Z0-9]{6,16}$/.test(data.password)) {
                    showToast('密码必须为6-16位数字和字母组合', 'warning');
                    return false;
                }
                
                // 提交数据
                ajax('?do=user&act=add', data, function(res) {
                    if (res.code === 1) {
                        showToast(res.msg, 'success');
                        loadUsers();
                    } else {
                        showToast(res.msg, 'error');
                    }
                });
                
                return true; // 允许模态框关闭
            }
        }
    ]);
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

.search-form > div {
    display: flex;
}

.search-form input {
    margin-right: 10px;
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
    margin-right: 5px;
}

.status-tag {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.enabled {
    background-color: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.disabled {
    background-color: #fff2f0;
    color: #ff4d4f;
    border: 1px solid #ffccc7;
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
</style>
