<?php
/**
 * 安装文件 - 创建数据库表
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 引入数据库连接文件
require_once './inc/conn.php';

// 检查是否为AJAX请求
if (isset($_GET['act']) && $_GET['act'] == 'install') {
    header('Content-Type: application/json; charset=utf-8');
    
    $import_demo = isset($_POST['demo']) ? intval($_POST['demo']) : 0;
    
    // 开始执行安装
    $result = [
        'code' => 1,
        'msg' => '安装成功',
        'tables' => []
    ];
    
    // 创建网站设置表
    $sql_conn = "CREATE TABLE IF NOT EXISTS `conn` (
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
        `name` varchar(100) NOT NULL COMMENT '网站名称',
        `urls` varchar(255) NOT NULL COMMENT '底部链接',
        `tiao1` varchar(50) NOT NULL COMMENT '姓名标题',
        `tiao2` varchar(50) NOT NULL COMMENT '身份证号标题',
        `tiao3` varchar(50) NOT NULL COMMENT '准考证号标题',
        `isma` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否验证码',
        `desc` text NOT NULL COMMENT '登录页说明',
        `chax` text NOT NULL COMMENT '查询页说明',
        `jies` text NOT NULL COMMENT '无结果说明',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站设置表'";
    
    $conn_result = mysqli_query($conn, $sql_conn);
    $result['tables'][] = [
        'name' => 'conn',
        'status' => $conn_result ? 'success' : 'error',
        'message' => $conn_result ? '创建成功' : mysqli_error($conn)
    ];
    
    // 创建用户表
    $sql_user = "CREATE TABLE IF NOT EXISTS `user` (
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
        `type` varchar(20) NOT NULL COMMENT '用户类型: admin管理员/user用户',
        `user` varchar(50) NOT NULL COMMENT '用户名',
        `pass` varchar(32) NOT NULL COMMENT '密码(md5)',
        `name` varchar(50) NOT NULL COMMENT '姓名',
        `add_time` datetime NOT NULL COMMENT '添加日期',
        `log_time` datetime DEFAULT NULL COMMENT '最后登录',
        `check` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否可登录',
        PRIMARY KEY (`id`),
        UNIQUE KEY `user` (`user`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表'";
    
    $user_result = mysqli_query($conn, $sql_user);
    $result['tables'][] = [
        'name' => 'user',
        'status' => $user_result ? 'success' : 'error',
        'message' => $user_result ? '创建成功' : mysqli_error($conn)
    ];
    
    // 创建报道表
    $sql_card = "CREATE TABLE IF NOT EXISTS `card` (
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
        `pid` varchar(50) DEFAULT NULL COMMENT '批次号',
        `name` varchar(50) NOT NULL COMMENT '姓名',
        `sfzh` varchar(18) DEFAULT NULL COMMENT '身份证号',
        `zkzh` varchar(20) DEFAULT NULL COMMENT '准考证号',
        `desc` text COMMENT '备注[系别 专业 宿舍号等 json格式存]',
        `user` int(11) DEFAULT NULL COMMENT '报到用户',
        `tihe` varchar(8) DEFAULT NULL COMMENT '报到日期Ymd',
        `icha` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态（0:未报到 1:已报到）',
        `add_time` datetime NOT NULL COMMENT '添加日期',
        `hex_time` datetime DEFAULT NULL COMMENT '报到时间',
        PRIMARY KEY (`id`),
        KEY `sfzh` (`sfzh`),
        KEY `zkzh` (`zkzh`),
        KEY `pid` (`pid`),
        KEY `user` (`user`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='报道表'";
    
    $card_result = mysqli_query($conn, $sql_card);
    $result['tables'][] = [
        'name' => 'card',
        'status' => $card_result ? 'success' : 'error',
        'message' => $card_result ? '创建成功' : mysqli_error($conn)
    ];
    
    // 创建默认管理员账号
    $admin_exist = mysqli_query($conn, "SELECT id FROM `user` WHERE user='admin'");
    if (mysqli_num_rows($admin_exist) == 0) {
        $sql_admin = "INSERT INTO `user` (`type`, `user`, `pass`, `name`, `add_time`, `check`) VALUES ('admin', 'admin', '".md5('admin123')."', '管理员', '".date('Y-m-d H:i:s')."', 1)";
        $admin_result = mysqli_query($conn, $sql_admin);
        $result['tables'][] = [
            'name' => '默认管理员',
            'status' => $admin_result ? 'success' : 'error',
            'message' => $admin_result ? '创建成功 (用户名: admin, 密码: admin123)' : mysqli_error($conn)
        ];
    }
    
    // 创建默认用户账号
    $user_exist = mysqli_query($conn, "SELECT id FROM `user` WHERE user='user'");
    if (mysqli_num_rows($user_exist) == 0) {
        $sql_default_user = "INSERT INTO `user` (`type`, `user`, `pass`, `name`, `add_time`, `check`) VALUES ('user', 'user', '".md5('user123')."', '普通用户', '".date('Y-m-d H:i:s')."', 1)";
        $default_user_result = mysqli_query($conn, $sql_default_user);
        $result['tables'][] = [
            'name' => '默认用户',
            'status' => $default_user_result ? 'success' : 'error',
            'message' => $default_user_result ? '创建成功 (用户名: user, 密码: user123)' : mysqli_error($conn)
        ];
    }
    
    // 插入默认网站设置
    $settings_exist = mysqli_query($conn, "SELECT id FROM `conn`");
    if (mysqli_num_rows($settings_exist) == 0) {
        $sql_settings = "INSERT INTO `conn` (`name`, `urls`, `tiao1`, `tiao2`, `tiao3`, `isma`, `desc`, `chax`, `jies`) VALUES (
            '新生报到系统',
            '技术支持: 15058593138@qq.com',
            '姓名',
            '身份证号',
            '准考证号',
            1,
            '欢迎使用新生报到系统，请输入账号密码登录',
            '请输入身份证号或准考证号进行查询',
            '未找到相关信息，请检查输入是否正确'
        )";
        $settings_result = mysqli_query($conn, $sql_settings);
        $result['tables'][] = [
            'name' => '默认设置',
            'status' => $settings_result ? 'success' : 'error',
            'message' => $settings_result ? '创建成功' : mysqli_error($conn)
        ];
    }
    
    // 导入演示数据
    if ($import_demo) {
        $demo_result = import_demo_data($conn);
        $result['tables'][] = [
            'name' => '演示数据',
            'status' => $demo_result ? 'success' : 'error',
            'message' => $demo_result ? '导入成功' : '导入失败'
        ];
    }
    
    // 检查是否有失败的表
    foreach ($result['tables'] as $table) {
        if ($table['status'] === 'error') {
            $result['code'] = 0;
            $result['msg'] = '安装过程中出现错误，请检查错误信息';
            break;
        }
    }
    
    echo json_encode($result);
    exit;
}

/**
 * 导入演示数据
 * @param mysqli $conn 数据库连接
 * @return bool
 */
function import_demo_data($conn) {
    // 清空现有数据
    mysqli_query($conn, "TRUNCATE TABLE `card`");
    
    // 生成随机批次号
    $batch_ids = ['B2025001', 'B2025002', 'B2025003'];
    
    // 系别和专业
    $departments = [
        '计算机系' => ['软件工程', '计算机科学与技术', '人工智能', '大数据'],
        '电子系' => ['电子信息工程', '通信工程', '集成电路设计'],
        '经济管理系' => ['工商管理', '会计学', '市场营销'],
        '外语系' => ['英语', '日语', '翻译']
    ];
    
    // 宿舍号
    $dorms = ['A101', 'A102', 'A103', 'A201', 'A202', 'A203', 'B101', 'B102', 'B103', 'B201', 'B202', 'B203'];
    
    // 生成随机身份证号
    function generate_id_card() {
        // 随机生成一个地区码
        $area_code = rand(110000, 659000);
        
        // 随机生成出生日期 (1990-2005)
        $year = rand(1990, 2005);
        $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $birth_date = $year . $month . $day;
        
        // 随机生成顺序码
        $seq_code = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // 生成前17位
        $id_card = $area_code . $birth_date . $seq_code;
        
        // 计算校验码
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $parity = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += intval($id_card[$i]) * $factor[$i];
        }
        
        $check_code = $parity[$sum % 11];
        
        return $id_card . $check_code;
    }
    
    // 生成随机准考证号
    function generate_exam_number() {
        return '2025' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    // 插入演示数据
    $success = true;
    $name_prefix = ['张', '李', '王', '赵', '陈', '刘', '杨', '黄', '周', '吴'];
    $name_suffix = ['明', '华', '军', '强', '伟', '杰', '芳', '娟', '静', '敏', '燕', '晓', '涛', '霞', '磊'];
    
    for ($i = 0; $i < 30; $i++) {
        // 生成随机姓名
        $name = $name_prefix[array_rand($name_prefix)] . $name_suffix[array_rand($name_suffix)];
        if (rand(0, 1) == 1) {
            $name .= $name_suffix[array_rand($name_suffix)];
        }
        
        // 生成随机身份证号和准考证号
        $id_card = generate_id_card();
        $exam_number = generate_exam_number();
        
        // 随机选择批次号
        $batch_id = $batch_ids[array_rand($batch_ids)];
        
        // 随机选择系别和专业
        $department = array_rand($departments);
        $major = $departments[$department][array_rand($departments[$department])];
        
        // 随机选择宿舍号
        $dorm = $dorms[array_rand($dorms)];
        
        // 生成额外信息
        $desc = [
            '系别' => $department,
            '专业' => $major,
            '班级' => substr($batch_id, -3) . str_pad(rand(1, 5), 2, '0', STR_PAD_LEFT),
            '宿舍号' => $dorm
        ];
        
        // 随机设置是否已报到
        $is_registered = rand(0, 1);
        $user_id = $is_registered ? 2 : NULL; // 假设用户ID为2
        $register_date = $is_registered ? date('Ymd', strtotime('-' . rand(0, 10) . ' days')) : NULL;
        $register_time = $is_registered ? date('Y-m-d H:i:s', strtotime('-' . rand(0, 10) . ' days -' . rand(0, 23) . ' hours')) : NULL;
        
        // 构建SQL语句
        $sql = "INSERT INTO `card` (`pid`, `name`, `sfzh`, `zkzh`, `desc`, `user`, `tihe`, `icha`, `add_time`, `hex_time`) VALUES (
            '{$batch_id}',
            '{$name}',
            '{$id_card}',
            '{$exam_number}',
            '" . json_encode($desc, JSON_UNESCAPED_UNICODE) . "',
            " . ($user_id ? $user_id : 'NULL') . ",
            " . ($register_date ? "'{$register_date}'" : 'NULL') . ",
            {$is_registered},
            '" . date('Y-m-d H:i:s', strtotime('-' . rand(10, 30) . ' days')) . "',
            " . ($register_time ? "'{$register_time}'" : 'NULL') . "
        )";
        
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            $success = false;
            break;
        }
    }
    
    return $success;
}

// 检查PHP和MySQL版本
$php_version = phpversion();
$php_check = version_compare($php_version, '7.1', '>=');

$mysql_version = '';
$mysql_check = false;
if ($conn) {
    $mysql_version = mysqli_get_server_info($conn);
    $mysql_check = version_compare($mysql_version, '5.6', '>=');
}

// 检查数据库连接
$db_check = $conn ? true : false;

// 检查目录可写权限
$writable_dirs = [
    './' => is_writable('./'),
    './inc/' => is_dir('./inc/') && is_writable('./inc/'),
    './tmp/' => is_dir('./tmp/') || mkdir('./tmp/', 0777)
];
$writable_check = !in_array(false, $writable_dirs);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新生报到系统 - 安装向导</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            padding: 20px;
            background-color: #1890ff;
            color: #fff;
            text-align: center;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .content {
            padding: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h2 {
            font-size: 18px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .check-list {
            list-style: none;
        }
        .check-list li {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            justify-content: space-between;
        }
        .check-result {
            font-weight: bold;
        }
        .success {
            color: #52c41a;
        }
        .error {
            color: #ff4d4f;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1890ff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            background-color: #40a9ff;
        }
        .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group input {
            margin-right: 5px;
        }
        .result-container {
            margin-top: 20px;
            display: none;
        }
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .result-table th,
        .result-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .result-table th {
            background-color: #fafafa;
        }
        .alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #f6ffed;
            border: 1px solid #b7eb8f;
            color: #52c41a;
        }
        .alert-error {
            background-color: #fff2f0;
            border: 1px solid #ffccc7;
            color: #ff4d4f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>新生报到系统 - 安装向导</h1>
            <p>本向导将帮助您完成系统的安装</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h2>环境检查</h2>
                <ul class="check-list">
                    <li>
                        <span>PHP版本 (>= 7.1)</span>
                        <span class="check-result <?php echo $php_check ? 'success' : 'error'; ?>">
                            <?php echo $php_version . ' ' . ($php_check ? '通过' : '不通过'); ?>
                        </span>
                    </li>
                    <li>
                        <span>MySQL版本 (>= 5.6)</span>
                        <span class="check-result <?php echo $mysql_check ? 'success' : 'error'; ?>">
                            <?php echo $mysql_version . ' ' . ($mysql_check ? '通过' : '不通过'); ?>
                        </span>
                    </li>
                    <li>
                        <span>数据库连接</span>
                        <span class="check-result <?php echo $db_check ? 'success' : 'error'; ?>">
                            <?php echo $db_check ? '通过' : '不通过，请检查数据库配置'; ?>
                        </span>
                    </li>
                    <li>
                        <span>目录可写权限</span>
                        <span class="check-result <?php echo $writable_check ? 'success' : 'error'; ?>">
                            <?php echo $writable_check ? '通过' : '不通过，请设置目录权限'; ?>
                        </span>
                    </li>
                </ul>
            </div>
            
            <div class="section">
                <h2>安装选项</h2>
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="importDemo" name="importDemo">
                    <label for="importDemo">导入演示数据</label>
                </div>
                
                <button id="installBtn" class="btn" <?php echo (!$php_check || !$mysql_check || !$db_check || !$writable_check) ? 'disabled' : ''; ?>>开始安装</button>
            </div>
            
            <div id="resultContainer" class="result-container">
                <div id="resultAlert" class="alert">
                    安装结果
                </div>
                
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>表名</th>
                            <th>状态</th>
                            <th>消息</th>
                        </tr>
                    </thead>
                    <tbody id="resultTableBody">
                        <!-- 结果将通过JavaScript动态添加 -->
                    </tbody>
                </table>
                
                <div class="form-group">
                    <a href="index.php" class="btn">进入系统</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('installBtn').addEventListener('click', function() {
            const importDemo = document.getElementById('importDemo').checked ? 1 : 0;
            const btn = this;
            
            // 禁用按钮并显示加载中状态
            btn.disabled = true;
            btn.textContent = '安装中...';
            
            // 发送安装请求
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '?act=install', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            showResult(response);
                        } catch (e) {
                            showError('解析响应失败: ' + e.message);
                        }
                    } else {
                        showError('请求失败: ' + xhr.status);
                    }
                    
                    // 恢复按钮状态
                    btn.disabled = false;
                    btn.textContent = '开始安装';
                }
            };
            
            xhr.send('demo=' + importDemo);
        });
        
        function showResult(result) {
            const resultContainer = document.getElementById('resultContainer');
            const resultAlert = document.getElementById('resultAlert');
            const resultTableBody = document.getElementById('resultTableBody');
            
            // 显示结果容器
            resultContainer.style.display = 'block';
            
            // 设置提示信息
            if (result.code === 1) {
                resultAlert.className = 'alert alert-success';
                resultAlert.textContent = result.msg;
            } else {
                resultAlert.className = 'alert alert-error';
                resultAlert.textContent = result.msg;
            }
            
            // 清空表格
            resultTableBody.innerHTML = '';
            
            // 填充表格
            result.tables.forEach(function(table) {
                const row = document.createElement('tr');
                
                const nameCell = document.createElement('td');
                nameCell.textContent = table.name;
                
                const statusCell = document.createElement('td');
                statusCell.textContent = table.status === 'success' ? '成功' : '失败';
                statusCell.className = table.status === 'success' ? 'success' : 'error';
                
                const messageCell = document.createElement('td');
                messageCell.textContent = table.message;
                
                row.appendChild(nameCell);
                row.appendChild(statusCell);
                row.appendChild(messageCell);
                
                resultTableBody.appendChild(row);
            });
            
            // 滚动到结果区域
            resultContainer.scrollIntoView({ behavior: 'smooth' });
        }
        
        function showError(message) {
            const resultContainer = document.getElementById('resultContainer');
            const resultAlert = document.getElementById('resultAlert');
            
            resultContainer.style.display = 'block';
            resultAlert.className = 'alert alert-error';
            resultAlert.textContent = message;
            
            document.getElementById('resultTableBody').innerHTML = '';
        }
    </script>
</body>
</html>
