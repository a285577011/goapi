/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-12-12
 */

-- direct_trend_chart 表新增字段 （张悦玲）（已执行）
ALTER TABLE `gl_lottery_php`.`direct_trend_chart` ADD COLUMN `red_analysis` VARCHAR(255) DEFAULT NULL COMMENT '红球区分析' AFTER `blue_omission`;
ALTER TABLE `gl_lottery_php`.`direct_trend_chart` ADD COLUMN `blue_analysis` VARCHAR(255) DEFAULT NULL COMMENT '蓝球区分析' AFTER `red_analysis`;
ALTER TABLE `gl_lottery_php`.`direct_trend_chart` ADD COLUMN `redtail_omission` VARCHAR(255) DEFAULT NULL COMMENT '红球尾号遗漏数' AFTER `red_analysis`;

-- group_trend_chart 表新增字段 （张悦玲）（已执行）
ALTER TABLE `gl_lottery_php`.`group_trend_chart` ADD COLUMN `analysis` VARCHAR(255) DEFAULT NULL COMMENT '开奖结果分析' AFTER `group_omission`;
ALTER TABLE `gl_lottery_php`.`group_trend_chart` ADD COLUMN `sum_omission` VARCHAR(255) DEFAULT NULL COMMENT '和值遗漏数' AFTER `analysis`;
ALTER TABLE `gl_lottery_php`.`group_trend_chart` ADD COLUMN `span_omission` VARCHAR(255) DEFAULT NULL COMMENT '跨度遗漏数' AFTER `sum_omission`;
ALTER TABLE `gl_lottery_php`.`group_trend_chart` ADD COLUMN `sumtail_omission` VARCHAR(255) DEFAULT NULL COMMENT '和值尾数遗漏数' AFTER `span_omission`;

-- eleven_trend_chart 表新增字段 （张悦玲）（已执行）
ALTER TABLE `gl_lottery_php`.`eleven_trend_chart` ADD COLUMN `analysis` VARCHAR(255) DEFAULT NULL COMMENT '开奖结果分析' AFTER `qthree_group_omission`;
ALTER TABLE `gl_lottery_php`.`eleven_trend_chart` ADD COLUMN `span_omission` VARCHAR(255) DEFAULT NULL COMMENT '任选跨度遗漏数' AFTER `analysis`;