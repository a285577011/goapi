/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 09 21

-- 合买认购表新增字段 （张悦玲）
ALTER TABLE programme_user ADD user_id INT(11) NULL  COMMENT '认购会员ID' 

-- 合买表新增字段 （张悦玲）
ALTER TABLE programme ADD user_id INT(11) NULL  COMMENT '发起人ID' 