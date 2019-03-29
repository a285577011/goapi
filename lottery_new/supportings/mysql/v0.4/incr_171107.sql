/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-11-7
 */
--订单表新增字段 (张悦玲) 已执行
ALTER TABLE lottery_order ADD build_code INT(25) NULL  COMMENT '组合玩法编号';
ALTER TABLE lottery_order ADD build_name INT(50) NULL  COMMENT '组合玩法名称';
