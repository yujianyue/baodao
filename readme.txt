查立得PHP+MySQL通用新生报到系统 V1.0
===========

【简介】
查立得PHP+MySQL通用新生报到系统是一个基于PHP+MySQL的Web应用系统，用于信息查询和报到标记。系统分为管理员和普通用户（向导志愿者）两种角色。
管理员可以导入学生信息、管理用户账号、查看统计报表和系统配置等；普通用户可以查询学生信息并进行报到操作。
你可以视流程功能用于相似业务流程比如各种活动的签到。首发版本,请提前调试,并增强安全比如备份文件夹伪静态防下载处理等。

【环境要求】
- PHP版本 >= 7.1
- MySQL版本 >= 5.6
- PHP扩展：mysqli, gd, json
- 目录权限：根目录和inc目录需要可写权限

【安装步骤】
1. 将所有文件上传到服务器
2. 修改inc/conn.php文件中的数据库连接参数：
   - $db_host：数据库服务器地址，默认为localhost
   - $db_user：数据库用户名，默认为root
   - $db_pass：数据库密码，默认为空
   - $db_name：数据库名称，默认为baodao
3. 在浏览器中访问install.php文件，根据向导完成安装
4. 安装完成后，删除安装文件

【默认账户】
- 管理员账户：admin / admin123
- 普通用户账户：user / user123

【文件结构】
- index.php：系统入口文件，根据登录账号类型跳转不同页面
- admin.php：管理员界面入口文件
- user.php：用户界面入口文件
- install.php：安装向导文件
- down.php：数据下载文件
- readme.txt：说明文档

- inc/：公共文件目录
  - conn.php：数据库连接及配置文件
  - pubs.php：公共PHP函数库
  - sqls.php：数据库操作类
  - head.php：公共头部文件
  - foot.php：公共底部文件
  - css.css：公共CSS样式文件
  - js.js：公共JavaScript函数文件
  - json.php：网站设置缓存文件

- sys/：管理员功能模块目录
  - login.php：管理员登录页面
  - list.php：数据列表页面
  - liin.php：数据导入页面
  - batch.php：批量操作页面
  - tong.php：统计页面
  - user.php：用户列表页面
  - usin.php：用户导入页面
  - site.php：系统设置页面
  - baks.php：数据备份页面
  - pass.php：修改密码页面
  - help.php：使用帮助页面

- usr/：用户功能模块目录
  - head.php：用户页面头部导航
  - login.php：用户登录页面
  - xiao.php：报到页面
  - list.php：报到记录页面
  - tong.php：报到统计页面
  - pass.php：修改密码页面

【数据库结构】
1. 网站设置表[conn]
   - id：自增ID (int)
   - name：网站名称 (varchar)
   - urls：底部链接 (varchar)
   - tiao1：姓名标题 (varchar)
   - tiao2：身份证号标题 (varchar)
   - tiao3：准考证号标题 (varchar)
   - isma：是否验证码 (tinyint)
   - desc：登录页说明 (text)
   - chax：查询页说明 (text)
   - jies：无结果说明 (text)

2. 用户表[user]
   - id：自增ID (int)
   - type：用户类型 (varchar) admin管理员/user用户
   - user：用户名 (varchar)
   - pass：密码(md5) (varchar)
   - name：姓名 (varchar)
   - add_time：添加日期 (datetime)
   - log_time：最后登录 (datetime)
   - check：是否可登录 (tinyint)

3. 报道表[card]
   - id：自增ID (int)
   - pid：批次号 (varchar)
   - name：姓名 (varchar)
   - sfzh：身份证号 (varchar)
   - zkzh：准考证号 (varchar)
   - desc：备注[系别 专业 宿舍号等 json格式存] (text)
   - user：报到用户 (int)
   - tihe：报到日期Ymd (varchar)
   - icha：状态（0:未报到 1:已报到） (tinyint)
   - add_time：添加日期 (datetime)
   - hex_time：报到时间 (datetime)

【功能说明】
1. 管理员功能：
   - 数据列表：查看所有学生信息，支持搜索、删除和报到操作
   - 导入页面：通过网页粘贴或文件上传方式导入学生信息
   - 批量操作：批量删除或报到多个学生
   - 统计页面：查看批次统计、时间段统计和用户统计
   - 用户列表：管理用户账号，可以添加、重置密码和禁用/启用用户
   - 用户导入：批量导入用户账号信息
   - 系统设置：配置系统基本参数
   - 数据备份：备份数据库，支持下载备份文件
   - 修改密码：修改当前账号密码
   - 使用帮助：系统使用指南

2. 普通用户功能：
   - 报到页面：输入身份证号或准考证号查询学生信息，进行报到操作
   - 报到记录：查看自己的报到记录
   - 报到统计：查看自己的每日报到统计和月度报到统计
   - 修改密码：修改当前账号密码

【注意事项】
1. 安装完成后，建议删除install.php文件以提高安全性
2. 定期备份数据库以防数据丢失
3. 如果忘记管理员密码，可以通过数据库管理工具直接修改数据库中的密码（user表中的pass字段，使用md5加密）
4. 为保证系统安全，建议定期修改管理员密码

【问题反馈】

15058593138@qq.com (手机号同微信)
