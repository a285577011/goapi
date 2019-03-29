/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-11-10
 */

--专家表新增字段 （张悦玲）已执行 
ALTER TABLE expert ADD even_back_nums INT(11) NULL  COMMENT '最近连黑数';
ALTER TABLE expert ADD oppid VARCHAR(50) NULL  COMMENT '处理Id串'

--文章期数表新增字段 （张悦玲）已执行
ALTER TABLE articles_periods ADD deal_status TINYINT(4) NULL  COMMENT '处理状态 0：已处理 1：已处理 ';

--专家文章表新增字段 (张悦玲)已执行
ALTER TABLE expert_articles ADD oppid VARCHAR(50) NULL  COMMENT '处理Id串'

--专家文章表新增字段 (张悦玲)已执行
ALTER TABLE expert add expert_source TINYINT(4) default 1 COMMENT '专家来源:1、咕啦专家 2、即嗨专家 3、网易专家 ';