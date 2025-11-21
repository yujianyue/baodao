/**
 * 公共JavaScript函数文件
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// AJAX请求函数
function ajax(url, data, callback, method = 'POST') {
    // 创建XHR对象
    const xhr = new XMLHttpRequest();
    
    // 处理回调
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(response);
                } catch (e) {
                    console.error('解析JSON失败:', e);
                    showToast('请求发生错误，请稍后再试', 'error');
                }
            } else {
                showToast('请求发生错误，状态码: ' + xhr.status, 'error');
            }
        }
    };
    
    // 打开连接
    xhr.open(method, url, true);
    
    // 设置请求头
    xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    // 发送请求
    xhr.send(JSON.stringify(data));
}

// 获取表单数据
function getFormData(formId) {
    const form = document.getElementById(formId);
    if (!form) return {};
    
    const data = {};
    const elements = form.elements;
    
    for (let i = 0; i < elements.length; i++) {
        const element = elements[i];
        if (element.name) {
            if (element.type === 'checkbox') {
                data[element.name] = element.checked ? 1 : 0;
            } else if (element.type === 'radio') {
                if (element.checked) {
                    data[element.name] = element.value;
                }
            } else {
                data[element.name] = element.value;
            }
        }
    }
    
    return data;
}

// 分页函数
function goPage(page, callback) {
    if (typeof callback === 'function') {
        callback(page);
    } else {
        // 默认行为：修改URL参数并刷新
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    }
}

// 显示遮罩层
function showModal(title, content, buttons = []) {
    // 创建遮罩层
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    
    // 创建模态框
    const modal = document.createElement('div');
    modal.className = 'modal';
    
    // 创建模态框头部
    const header = document.createElement('div');
    header.className = 'modal-header';
    
    const titleEl = document.createElement('div');
    titleEl.className = 'modal-title';
    titleEl.textContent = title;
    
    const closeBtn = document.createElement('div');
    closeBtn.className = 'modal-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.onclick = function() {
        document.body.removeChild(overlay);
    };
    
    header.appendChild(titleEl);
    header.appendChild(closeBtn);
    
    // 创建模态框内容
    const body = document.createElement('div');
    body.className = 'modal-body';
    
    if (typeof content === 'string') {
        body.innerHTML = content;
    } else {
        body.appendChild(content);
    }
    
    // 创建模态框底部
    const footer = document.createElement('div');
    footer.className = 'modal-footer';
    
    // 添加按钮
    buttons.forEach(function(btn) {
        const button = document.createElement('button');
        button.className = 'btn ' + (btn.className || '');
        button.textContent = btn.text;
        button.onclick = function() {
            if (typeof btn.callback === 'function') {
                btn.callback();
            }
            document.body.removeChild(overlay);
        };
        footer.appendChild(button);
    });
    
    // 组装模态框
    modal.appendChild(header);
    modal.appendChild(body);
    modal.appendChild(footer);
    overlay.appendChild(modal);
    
    // 添加到页面
    document.body.appendChild(overlay);
    
    // 返回模态框对象，方便外部操作
    return {
        close: function() {
            document.body.removeChild(overlay);
        },
        getBody: function() {
            return body;
        }
    };
}

// 显示提示信息
function showToast(message, type = 'info', duration = 3000) {
    // 移除现有提示
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(function(toast) {
        document.body.removeChild(toast);
    });
    
    // 创建提示元素
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.textContent = message;
    
    // 添加到页面
    document.body.appendChild(toast);
    
    // 显示动画
    setTimeout(function() {
        toast.style.opacity = '1';
    }, 10);
    
    // 定时关闭
    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, duration);
}

// 确认弹窗
function confirm(message, callback) {
    return showModal('确认', message, [
        {
            text: '取消',
            className: ''
        },
        {
            text: '确认',
            className: 'btn-danger',
            callback: callback
        }
    ]);
}

// 三次确认弹窗
function tripleConfirm(message, callback) {
    const firstConfirm = function() {
        showModal('二次确认', '请再次确认是否要执行此操作？', [
            {
                text: '取消',
                className: ''
            },
            {
                text: '确认',
                className: 'btn-danger',
                callback: secondConfirm
            }
        ]);
    };
    
    const secondConfirm = function() {
        showModal('最终确认', '此操作不可逆，确定要继续吗？', [
            {
                text: '取消',
                className: ''
            },
            {
                text: '确认',
                className: 'btn-danger',
                callback: callback
            }
        ]);
    };
    
    showModal('确认', message, [
        {
            text: '取消',
            className: ''
        },
        {
            text: '确认',
            className: 'btn-danger',
            callback: firstConfirm
        }
    ]);
}

// 批量选择与删除
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

// 切换选项卡
function switchTab(tabId, callback) {
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
    
    // 回调函数
    if (typeof callback === 'function') {
        callback(tabId);
    }
}
