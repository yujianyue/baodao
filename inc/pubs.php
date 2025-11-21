<?php
/**
 * 公共函数库
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

/**
 * 返回JSON格式的提示
 * @param int $code 状态码（0:失败, 1:成功）
 * @param string $msg 提示信息
 * @param array $data 返回数据
 * @return string
 */
function json_msg($code, $msg, $data = []) {
    $result = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ];
    header('Content-Type: application/json; charset=utf-8');
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

/**
 * 安全过滤输入
 * @param string $str 需要过滤的字符串
 * @return string
 */
function safe_input($str) {
    $str = trim($str);
    $str = htmlspecialchars($str, ENT_QUOTES);
    return $str;
}

/**
 * 读取CSV文件并转为二维数组
 * @param string $file 文件路径
 * @param string $delimiter 分隔符
 * @return array
 */
function read_csv_to_array($file, $delimiter = "\t") {
    $data = [];
    $encoding = detect_encoding($file);
    
    if ($encoding && $encoding != 'UTF-8') {
        convert_to_utf8($file, $encoding);
    }
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

/**
 * 检测文件编码
 * @param string $file 文件路径
 * @return string|bool
 */
function detect_encoding($file) {
    $data = file_get_contents($file);
    if (!$data) return false;
if(!empty($data)){ 
$fileType = mb_detect_encoding($data , array('GB2312','GBK','UTF-8','LATIN1','BIG5')) ; 
if( $fileType != 'UTF-8'){ $data = mb_convert_encoding($data ,'UTF-8' , $fileType); } 
} 
return $data; 
}
/**
 * 将文件转换为UTF-8编码
 * @param string $file 文件路径
 * @param string $from_encoding 原编码
 * @return bool
 */
function convert_to_utf8($file, $from_encoding) {
    $data = file_get_contents($file);
    if (!$data) return false;      
  if(!empty($data)){ 
$fileType = mb_detect_encoding($data , array('GB2312','GBK','UTF-8','LATIN1','BIG5')) ; 
if( $fileType != 'UTF-8'){ $data = mb_convert_encoding($data ,'UTF-8' , $fileType); } 
}
    file_put_contents($file, $data);
    return true;
}

/**
 * 生成分页HTML
 * @param int $total 总记录数
 * @param int $page 当前页码
 * @param int $limit 每页记录数
 * @param string $url 分页链接
 * @return string
 */
function pagination($total, $page, $limit, $url = '') {
    $total_pages = ceil($total / $limit);
    if ($total_pages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // 起始页
    if ($page > 1) {
        $html .= '<a href="javascript:void(0)" onclick="goPage(1)" class="page-btn">首页</a>';
        $html .= '<a href="javascript:void(0)" onclick="goPage('.($page-1).')" class="page-btn">上一页</a>';
    } else {
        $html .= '<span class="page-btn disabled">首页</span>';
        $html .= '<span class="page-btn disabled">上一页</span>';
    }
    
    // 页码选择
    $html .= '<select class="page-select" onchange="goPage(this.value)">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $selected = ($i == $page) ? 'selected' : '';
        $html .= '<option value="'.$i.'" '.$selected.'>'.$i.'/'.$total_pages.'</option>';
    }
    $html .= '</select>';
    
    // 下一页和最后页
    if ($page < $total_pages) {
        $html .= '<a href="javascript:void(0)" onclick="goPage('.($page+1).')" class="page-btn">下一页</a>';
        $html .= '<a href="javascript:void(0)" onclick="goPage('.$total_pages.')" class="page-btn">末页</a>';
    } else {
        $html .= '<span class="page-btn disabled">下一页</span>';
        $html .= '<span class="page-btn disabled">末页</span>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * 验证身份证号码
 * @param string $id 身份证号码
 * @return bool
 */
function validate_id_card($id) {
    $id = strtoupper($id);
    $pattern = '/(^\d{15}$)|(^\d{17}([0-9]|X)$)/';
    return preg_match($pattern, $id) ? true : false;
}

/**
 * 验证准考证号
 * @param string $zkzh 准考证号
 * @return bool
 */
function validate_exam_number($zkzh) {
    // 准考证号一般为数字，长度通常为14-15位
    return preg_match('/^\d{8,18}$/', $zkzh);
}

/**
 * 获取网站设置
 * @return array
 */
function get_site_settings() {
    global $default_settings, $conn;
    
    $json_file = __DIR__ . '/json.php';
    
    // 如果json.php文件不存在，则创建默认设置
    if (!file_exists($json_file)) {
        file_put_contents($json_file, json_encode($default_settings, JSON_UNESCAPED_UNICODE));
        return $default_settings;
    }
    
    // 读取json.php文件内容
    $settings = json_decode(file_get_contents($json_file), true);
    if (!$settings) {
        return $default_settings;
    }
    
    return $settings;
}

/**
 * 保存网站设置
 * @param array $settings 设置数组
 * @return bool
 */
function save_site_settings($settings) {
    $json_file = __DIR__ . '/json.php';
    return file_put_contents($json_file, json_encode($settings, JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * 检查用户登录状态
 * @param string $type 用户类型 (admin|user)
 * @return array|bool
 */
function check_login($type = '') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    
    if ($type && $_SESSION['user_type'] != $type) {
        return false;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'type' => $_SESSION['user_type'],
        'user' => $_SESSION['username'],
        'name' => $_SESSION['name'] ?? '',
    ];
}

/**
 * 生成随机密码
 * @param int $length 密码长度
 * @return string
 */
function generate_password($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}
?>
