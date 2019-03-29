/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-5-15
 */
--自动出票表新增字段（张悦玲）（已执行）
ALTER TABLE `auto_out_order` ADD COLUMN `source` VARCHAR(25) NULL DEFAULT 'ZMF' COMMENT '出票方' AFTER `zmf_award_money`;

