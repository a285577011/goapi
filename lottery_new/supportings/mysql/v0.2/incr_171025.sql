/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Administrator
 * Created: 2017-10-25
 */
--专家文章表新增（置顶）字段 （李家能）
ALTER TABLE expert_articles ADD stick varchar(15) NOT NULL  COMMENT '0：否 其他：是' ;

