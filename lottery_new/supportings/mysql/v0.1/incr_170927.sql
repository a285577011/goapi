/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 09 25

-- 提现表新增字段 （张悦玲）
ALTER TABLE withdraw ADD cardholder VARCHAR(50) NULL  COMMENT '提现账户持卡人' 
ALTER TABLE withdraw ADD bank_name VARCHAR(100) NULL  COMMENT '银行名' 