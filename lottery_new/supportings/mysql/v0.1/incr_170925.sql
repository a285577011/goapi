/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 09 25

-- 合买认购表新增字段 （张悦玲）
ALTER TABLE store ADD sale_lottery VARCHAR(100) NULL  COMMENT '可售彩种' 