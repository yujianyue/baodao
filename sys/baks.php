<?php
/**
 * 管理员数据备份页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理AJAX请求
if (isset($_GET['act']) && $_GET['act'] == 'backup') {
    header('Content-Type: application/json; charset=utf-8');
    
    // 执行备份
    $backup_file = backup_database($conn, $db_name);
    
    if ($backup_file) {
        echo json_msg(1, '数据库备份成功', ['file' => $backup_file]);
    } else {
        echo json_msg(0, '数据库备份失败，请重试');
    }
    
    exit;
}

// 如果是下载请求
if (isset($_GET['act']) && $_GET['act'] == 'download' && isset($_GET['file'])) {
    $file = './backup/' . basename($_GET['file']);
    
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        echo '备份文件不存在';
        exit;
    }
}

/**
 * 备份数据库
 * @param mysqli $conn 数据库连接
 * @param string $db_name 数据库名
 * @return string|bool 成功返回文件名，失败返回false
 */
function backup_database($conn, $db_name) {
    // 确保备份目录存在
    $backup_dir = './backup';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    // 生成备份文件名
    $backup_file = 'backup_' . date('YmdHis') . '.sql';
    $backup_path = $backup_dir . '/' . $backup_file;
    
    // 获取所有表
    $tables = [];
    $result = mysqli_query($conn, 'SHOW TABLES');
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    // 开始写入备份文件
    $handle = fopen($backup_path, 'w');
    
    if (!$handle) {
        return false;
    }
    
    // 写入头部信息
    fwrite($handle, "-- 数据库备份\n");
    fwrite($handle, "-- 时间: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "-- 数据库: {$db_name}\n\n");
    
    // 逐个表备份
    foreach ($tables as $table) {
        // 获取建表语句
        $result = mysqli_query($conn, "SHOW CREATE TABLE {$table}");
        $row = mysqli_fetch_row($result);
        $create_table = $row[1];
        
        fwrite($handle, "-- 表结构 {$table}\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, "{$create_table};\n\n");
        
        // 获取表数据
        $result = mysqli_query($conn, "SELECT * FROM {$table}");
        $num_fields = mysqli_num_fields($result);
        $num_rows = mysqli_num_rows($result);
        
        if ($num_rows > 0) {
            fwrite($handle, "-- 表数据 {$table}\n");
            
            while ($row = mysqli_fetch_row($result)) {
                fwrite($handle, "INSERT INTO `{$table}` VALUES (");
                
                for ($i = 0; $i < $num_fields; $i++) {
                    if (is_null($row[$i])) {
                        fwrite($handle, "NULL");
                    } else {
                        fwrite($handle, "'" . mysqli_real_escape_string($conn, $row[$i]) . "'");
                    }
                    
                    if ($i < $num_fields - 1) {
                        fwrite($handle, ", ");
                    }
                }
                
                fwrite($handle, ");\n");
            }
            
            fwrite($handle, "\n");
        }
    }
    
    fclose($handle);
    
    return $backup_file;
}

// 获取现有备份文件
function get_backup_files() {
    $backup_dir = './backup';
    $files = [];
    
    if (is_dir($backup_dir)) {
        $dir_handle = opendir($backup_dir);
        
        while (($file = readdir($dir_handle)) !== false) {
            if ($file != '.' && $file != '..' && substr($file, -4) == '.sql') {
                $files[] = [
                    'name' => $file,
                    'size' => filesize($backup_dir . '/' . $file),
                    'time' => filemtime($backup_dir . '/' . $file)
                ];
            }
        }
        
        closedir($dir_handle);
    }
    
    // 按时间倒序排序
    usort($files, function($a, $b) {
        return $b['time'] - $a['time'];
    });
    
    return $files;
}

// 引入公共头部
require_once './inc/head.php';
$backup_files = get_backup_files();
?>

<div class="container">
    <div class="panel">
        <div class="panel-heading">
            <h3>数据备份</h3>
            <p>备份和下载数据库</p>
        </div>
        
        <div class="panel-body">
            <div class="form-group">
                <button type="button" class="btn" onclick="backupDatabase()">立即备份</button>
                <span id="backupResult" style="margin-left: 10px;"></span>
            </div>
            
            <div class="backup-list">
                <h4>备份文件列表</h4>
                
                <?php if (empty($backup_files)): ?>
                <div class="alert alert-info">暂无备份文件</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>大小</th>
                                <th>备份时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backup_files as $file): ?>
                            <tr>
                                <td><?php echo $file['name']; ?></td>
                                <td><?php echo format_size($file['size']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', $file['time']); ?></td>
                                <td>
                                    <a href="?do=baks&act=download&file=<?php echo urlencode($file['name']); ?>" class="btn btn-sm">下载</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function backupDatabase() {
    const backupResult = document.getElementById('backupResult');
    backupResult.innerHTML = '<span style="color: #1890ff;">备份中，请稍候...</span>';
    
    ajax('?do=baks&act=backup', {}, function(res) {
        if (res.code === 1) {
            backupResult.innerHTML = `<span style="color: #52c41a;">${res.msg}</span>`;
            showToast(res.msg, 'success');
            
            // 刷新页面以显示新备份
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        } else {
            backupResult.innerHTML = `<span style="color: #ff4d4f;">${res.msg}</span>`;
            showToast(res.msg, 'error');
        }
    });
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

.backup-list {
    margin-top: 20px;
}

.backup-list h4 {
    margin-bottom: 15px;
    font-size: 16px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 10px;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
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
</style>

<?php
/**
 * 格式化文件大小
 * @param int $size 文件大小（字节）
 * @return string 格式化后的大小
 */
function format_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}
?>
