/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-1-5
 */

--热门赛事表新增字段 (张悦玲)（已执行）
ALTER TABLE `schedule_hot_sina` ADD COLUMN `schedule_type`  TINYINT(4) DEFAULT 1 COMMENT '赛程类型 1：足球 2：篮球' AFTER `schedule_mid`;