<?php
/**
 * 管理员帮助页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */
// 引入公共头部
require_once './inc/head.php';
?>

<div class="container">
    <div class="panel">
        <div class="panel-heading">
            <h3>使用帮助</h3>
            <p>新生报到系统使用指南</p>
        </div>
        
        <div class="panel-body">
            <div class="help-section">
                <h4>系统概述</h4>
                <p>新生报到系统是一个用于管理新生报到信息的平台，分为管理员和普通用户（向导志愿者）两种角色。</p>
                <p>管理员可以导入学生信息、管理用户账号、查看统计报表和系统配置；普通用户可以查询学生信息并进行报到操作。</p>
            </div>
            
            <div class="help-section">
                <h4>管理员功能</h4>
                <div class="help-item">
                    <h5>数据列表</h5>
                    <p>展示所有学生信息，支持搜索、删除和报到操作。</p>
                </div>
                
                <div class="help-item">
                    <h5>导入页面</h5>
                    <p>通过网页粘贴或文件上传方式导入学生信息。</p>
                    <p>数据格式：第一列为姓名，第二列为身份证号，第三列为准考证号，后续列为其他信息（如系别、专业、班级、宿舍号等）。</p>
                </div>
                
                <div class="help-item">
                    <h5>批量操作</h5>
                    <p>批量删除或报到多个学生，输入身份证号或准考证号即可。</p>
                </div>
                
                <div class="help-item">
                    <h5>统计页面</h5>
                    <p>查看批次统计、时间段统计和用户统计，支持下载数据。</p>
                </div>
                
                <div class="help-item">
                    <h5>用户列表</h5>
                    <p>管理用户账号，可以添加、重置密码和禁用/启用用户。</p>
                </div>
                
                <div class="help-item">
                    <h5>用户导入</h5>
                    <p>批量导入用户账号信息。</p>
                </div>
                
                <div class="help-item">
                    <h5>系统设置</h5>
                    <p>配置系统基本参数，如网站名称、标题、说明文字等。</p>
                </div>
                
                <div class="help-item">
                    <h5>数据备份</h5>
                    <p>备份数据库，支持下载备份文件。</p>
                </div>
            </div>
            
            <div class="help-section">
                <h4>普通用户功能</h4>
                <div class="help-item">
                    <h5>报到页面</h5>
                    <p>输入身份证号或准考证号查询学生信息，进行报到操作。</p>
                </div>
                
                <div class="help-item">
                    <h5>报到记录</h5>
                    <p>查看自己的报到记录，支持搜索和分页。</p>
                </div>
                
                <div class="help-item">
                    <h5>报到统计</h5>
                    <p>查看自己的每日报到统计和月度报到统计。</p>
                </div>
            </div>
            
            <div class="help-section">
                <h4>常见问题</h4>
                <div class="help-item">
                    <h5>如何导入学生信息？</h5>
                    <p>进入"导入页面"，可以通过网页粘贴或文件上传方式导入学生信息。网页粘贴方式支持从Excel复制数据粘贴到文本框中；文件上传方式支持上传TXT或CSV格式文件。</p>
                </div>
                
                <div class="help-item">
                    <h5>如何添加用户账号？</h5>
                    <p>进入"用户列表"，点击"添加用户"按钮，填写用户名、密码和姓名，选择用户类型（管理员或普通用户）即可。</p>
                </div>
                
                <div class="help-item">
                    <h5>如何批量导入用户账号？</h5>
                    <p>进入"用户导入"，从Excel复制包含用户名、密码、姓名和类型的数据，粘贴到文本框中即可。</p>
                </div>
                
                <div class="help-item">
                    <h5>如何备份数据？</h5>
                    <p>进入"数据备份"，点击"立即备份"按钮，等待备份完成后可以下载备份文件。</p>
                </div>
                
                <div class="help-item">
                    <h5>忘记密码怎么办？</h5>
                    <p>普通用户忘记密码可以联系管理员重置；管理员忘记密码可以通过数据库管理工具直接修改数据库中的密码。</p>
                </div>
            </div>
            
            <div class="help-section">
                <h4>联系我们</h4>
                <p>如果您在使用过程中遇到任何问题，请联系系统管理员或发送邮件至：15058593138@qq.com</p>
            </div>
        </div>
    </div>
</div>

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

.help-section {
    margin-bottom: 30px;
}

.help-section h4 {
    font-size: 18px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.help-item {
    margin-bottom: 20px;
}

.help-item h5 {
    font-size: 16px;
    margin-bottom: 10px;
    color: #1890ff;
}

.help-item p {
    margin-bottom: 5px;
    line-height: 1.6;
}
</style>
