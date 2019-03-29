/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-3-5
 */
--订单详情  betting_detail 表新增字段  (张悦玲)
ALTER TABLE `betting_detail` ADD COLUMN `deal_odds_sche` VARCHAR(200) NULL DEFAULT 'ODDS_' COMMENT '赔率修改赛程' AFTER `fen_json`;

--多位数走势图新增字段  multidigit_trend_chart （张悦玲）
ALTER TABLE `multidigit_trend_chart` ADD COLUMN `analysis` VARCHAR(255) NULL   COMMENT '开奖结果分析' AFTER `million_omission`;
ALTER TABLE `multidigit_trend_chart` ADD COLUMN `sum_omission` VARCHAR(255) NULL   COMMENT '和值遗漏数' AFTER `analysis`;
ALTER TABLE `multidigit_trend_chart` ADD COLUMN `span_omission` VARCHAR(255) NULL   COMMENT '跨度遗漏数' AFTER `sum_omission`;
ALTER TABLE `multidigit_trend_chart` ADD COLUMN `sumtail_omission` VARCHAR(255) NULL   COMMENT '和值尾数遗漏数' AFTER `span_omission`;
