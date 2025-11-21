<?php
/**
 * 数据下载文件
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 引入必要文件
require_once './inc/conn.php';
require_once './inc/pubs.php';
require_once './inc/sqls.php';

// 初始化数据库操作类
$db = new Sqls($conn);

// 启动会话
session_start();

// 检查用户登录状态
$user = check_login();
if (!$user) {
    header('Location: index.php');
    exit;
}

// 获取下载类型
$type = isset($_GET['type']) ? $_GET['type'] : '';

// 根据类型处理不同的下载
switch ($type) {
    // 下载批次数据
    case 'batch':
        $pid = isset($_GET['pid']) ? safe_input($_GET['pid']) : '';
        
        if (empty($pid)) {
            die('参数错误：缺少批次号');
        }
        
        // 查询批次数据
        $data = $db->getAll('card', '*', "pid='{$pid}'", 'id');
        
        // 设置文件名
        $filename = 'batch_' . $pid . '_' . date('Ymd') . '.txt';
        
        // 下载数据
        downloadData($data, $filename);
        break;
        
    // 下载时间段数据
    case 'time':
        $start = isset($_GET['start']) ? safe_input($_GET['start']) : '';
        $end = isset($_GET['end']) ? safe_input($_GET['end']) : '';
        
        if (empty($start) || empty($end)) {
            die('参数错误：缺少开始或结束日期');
        }
        
        // 将日期转为纯数字格式
        $start_num = str_replace('-', '', $start);
        $end_num = str_replace('-', '', $end);
        
        // 查询时间段内数据
        $data = $db->getAll('card', '*', "icha=1 AND tihe BETWEEN '{$start_num}' AND '{$end_num}'", 'hex_time DESC');
        
        // 设置文件名
        $filename = 'time_' . $start . '_to_' . $end . '.txt';
        
        // 下载数据
        downloadData($data, $filename);
        break;
        
    // 下载用户数据
    case 'user':
        $user_id = isset($_GET['user']) ? intval($_GET['user']) : 0;
        
        if ($user_id <= 0) {
            die('参数错误：缺少用户ID');
        }
        
        // 查询用户数据
        $data = $db->getAll('card', '*', "user='{$user_id}' AND icha=1", 'hex_time DESC');
        
        // 获取用户信息
        $user_info = $db->getOne('user', 'name', "id='{$user_id}'");
        $user_name = $user_info ? $user_info['name'] : 'unknown';
        
        // 设置文件名
        $filename = 'user_' . $user_name . '_' . date('Ymd') . '.txt';
        
        // 下载数据
        downloadData($data, $filename);
        break;
        
    default:
        die('参数错误：未知的下载类型');
}

/**
 * 下载数据为制表符分隔的文本文件
 * @param array $data 数据数组
 * @param string $filename 文件名
 */
function downloadData($data, $filename) {
    global $db;
    
    // 设置响应头
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // 如果没有数据，返回空文件
    if (empty($data)) {
        echo "无数据";
        exit;
    }
    
    // 定义表头
    $headers = ['ID', '批次号', '姓名', '身份证号', '准考证号', '额外信息', '报到状态', '报到用户', '报到日期', '报到时间', '添加时间'];
    echo implode("\t", $headers) . "\n";
    
    // 输出数据
    foreach ($data as $row) {
        // 解析 desc 字段中的 JSON 数据
        $desc = json_decode($row['desc'], true);
        $desc_str = '';
        
        if ($desc) {
            $desc_arr = [];
            foreach ($desc as $key => $value) {
                $desc_arr[] = $key . ':' . $value;
            }
            $desc_str = implode(', ', $desc_arr);
        }
        
        // 获取报到用户名称
        $reporter = '';
        if ($row['user']) {
            $user_info = $db->getOne('user', 'name', "id='{$row['user']}'");
            $reporter = $user_info ? $user_info['name'] : '';
        }
        
        // 格式化状态
        $status = $row['icha'] == 1 ? '已报到' : '未报到';
        
        // 组装一行数据
        $line = [
            $row['id'],
            $row['pid'],
            $row['name'],
            $row['sfzh'],
            $row['zkzh'],
            $desc_str,
            $status,
            $reporter,
            $row['tihe'],
            $row['hex_time'],
            $row['add_time']
        ];
        
        // 输出到文件
        echo implode("\t", $line) . "\n";
    }
    
    exit;
}
?>
