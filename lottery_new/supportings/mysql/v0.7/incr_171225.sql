/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-12-25
 */


-- ----------------------------
--开奖结果表删除字段 （张悦玲）
-- ----------------------------
ALTER TABLE lottery_record DROP COLUMN parity_ratio;
ALTER TABLE lottery_record DROP COLUMN size_ratio;


-- ----------------------------
--开奖结果表新增字段 （张悦玲）（已执行）
-- ----------------------------

Alter table `lottery_record` Add COLUMN test_numbers VARCHAR(25) NULL COMMENT '试机号' AFTER `lottery_numbers` ;


-- ----------------------------
--走势图新增字段 （张悦玲）（已执行）
-- ----------------------------
Alter table `group_trend_chart` Add COLUMN test_nums VARCHAR(25) NULL COMMENT '试机号' AFTER `open_code` ;


-- ----------------------------
--门店表新增权重字段 （张悦玲）（已执行）
-- ----------------------------
alter table `store` Add COLUMN weight TINYINT(4) DEFAULT 1 NULL COMMENT '权重配比';