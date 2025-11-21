<?php
/**
 * 数据库操作类
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

class Sqls {
    private $conn;
    
    /**
     * 构造函数
     * @param mysqli $conn 数据库连接对象
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * 执行SQL查询
     * @param string $sql SQL语句
     * @return mysqli_result|bool
     */
    public function query($sql) {
        return mysqli_query($this->conn, $sql);
    }
    
    /**
     * 获取单条记录
     * @param string $table 表名
     * @param string $fields 字段列表
     * @param string $where 条件
     * @return array|null
     */
    public function getOne($table, $fields = '*', $where = '') {
        $sql = "SELECT {$fields} FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }
    
    /**
     * 获取多条记录
     * @param string $table 表名
     * @param string $fields 字段列表
     * @param string $where 条件
     * @param string $order 排序
     * @param string $limit 限制
     * @return array
     */
    public function getAll($table, $fields = '*', $where = '', $order = '', $limit = '') {
        $sql = "SELECT {$fields} FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($order) {
            $sql .= " ORDER BY {$order}";
        }
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $result = $this->query($sql);
        $data = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }
    
    /**
     * 获取记录数
     * @param string $table 表名
     * @param string $where 条件
     * @return int
     */
    public function count($table, $where = '') {
        $sql = "SELECT COUNT(*) AS total FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return intval($row['total']);
        }
        return 0;
    }
    
    /**
     * 插入记录
     * @param string $table 表名
     * @param array $data 数据
     * @return int|bool 插入ID或失败
     */
    public function insert($table, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "`{$field}`";
            $values[] = "'" . mysqli_real_escape_string($this->conn, $value) . "'";
        }
        
        $fields_str = implode(', ', $fields);
        $values_str = implode(', ', $values);
        
        $sql = "INSERT INTO {$table} ({$fields_str}) VALUES ({$values_str})";
        
        if ($this->query($sql)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }
    
    /**
     * 更新记录
     * @param string $table 表名
     * @param array $data 数据
     * @param string $where 条件
     * @return bool
     */
    public function update($table, $data, $where) {
        $set = [];
        
        foreach ($data as $field => $value) {
            $set[] = "`{$field}` = '" . mysqli_real_escape_string($this->conn, $value) . "'";
        }
        
        $set_str = implode(', ', $set);
        
        $sql = "UPDATE {$table} SET {$set_str}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        return $this->query($sql);
    }
    
    /**
     * 删除记录
     * @param string $table 表名
     * @param string $where 条件
     * @return bool
     */
    public function delete($table, $where) {
        $sql = "DELETE FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        return $this->query($sql);
    }
    
    /**
     * 执行自定义SQL
     * @param string $sql SQL语句
     * @return array|bool
     */
    public function raw($sql) {
        $result = $this->query($sql);
        
        if (is_bool($result)) {
            return $result;
        }
        
        $data = [];
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }
    
    /**
     * 获取分页数据
     * @param string $table 表名
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $fields 字段列表
     * @param string $where 条件
     * @param string $order 排序
     * @return array
     */
    public function paginate($table, $page = 1, $limit = 10, $fields = '*', $where = '', $order = '') {
        $page = max(1, intval($page));
        $limit = max(1, intval($limit));
        $offset = ($page - 1) * $limit;
        
        // 获取总记录数
        $total = $this->count($table, $where);
        
        // 获取当前页数据
        $data = $this->getAll($table, $fields, $where, $order, "{$offset}, {$limit}");
        
        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => ceil($total / $limit),
            'data' => $data
        ];
    }
    
    /**
     * 开始事务
     * @return bool
     */
    public function begin() {
        return mysqli_begin_transaction($this->conn);
    }
    
    /**
     * 提交事务
     * @return bool
     */
    public function commit() {
        return mysqli_commit($this->conn);
    }
    
    /**
     * 回滚事务
     * @return bool
     */
    public function rollback() {
        return mysqli_rollback($this->conn);
    }
    
    /**
     * 获取最后一次执行的SQL错误
     * @return string
     */
    public function error() {
        return mysqli_error($this->conn);
    }
    
    /**
     * 获取最后一次插入的ID
     * @return int
     */
    public function lastId() {
        return mysqli_insert_id($this->conn);
    }
    
    /**
     * 转义字符串
     * @param string $str 字符串
     * @return string
     */
    public function escape($str) {
        return mysqli_real_escape_string($this->conn, $str);
    }
}
?>
