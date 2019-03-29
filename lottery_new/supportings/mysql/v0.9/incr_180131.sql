/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Administrator
 * Created: 2018-1-31
 */
-- expert_articles 表新增字段 （李家能）
ALTER TABLE `expert_articles`
ADD COLUMN `report_num`  tinyint(4) NULL DEFAULT 0 COMMENT '被举报次数' AFTER `oppid`;

-- articles_report_record 新建表 专家方案举报记录表 （李家能）
CREATE TABLE `articles_report_record` (
`articles_report_record_id`  int(10) NOT NULL AUTO_INCREMENT COMMENT '文章举报记录ID' ,
`cust_no`  varchar(30) NOT NULL DEFAULT '' COMMENT '举报人咕啦编号' ,
`article_id`  int(10) NOT NULL COMMENT '被举报文章ID' ,
`report_reasons`  varchar(255) NOT NULL DEFAULT '' COMMENT '举报原因（json串）:1->广告 2->重复、旧闻 3->低俗 4->与事实不符 5->内容质量差 6->抄袭' ,
`create_time`  datetime NOT NULL COMMENT '创建时间' ,
PRIMARY KEY (`articles_report_record_id`)
);
-- articles_report_record 表新增字段 （李家能）
ALTER TABLE `articles_report_record`
ADD COLUMN `expert_id`  int(10) NOT NULL COMMENT '文章所属专家ID' AFTER `article_id`;

-- 创建门店电视屏表 （kevi）
CREATE TABLE `store_screen` (
  `store_screen_id` int(11) NOT NULL AUTO_INCREMENT,
  `screen_key` varchar(45) NOT NULL,
  `store_code` varchar(10) DEFAULT NULL,
  `is_login` tinyint(4) NOT NULL DEFAULT '0',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`store_screen_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

