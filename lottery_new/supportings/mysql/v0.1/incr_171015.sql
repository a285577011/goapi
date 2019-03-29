/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 10 15

-- 数字彩订单兑奖 陈启炜
DROP PROCEDURE IF EXISTS CheckMainOrder; 
create PROCEDURE CheckMainOrder()
BEGIN
   #更新彩票订单中奖情况及金额 Added by zyr 2017-10-15
   #lottery_order.status: 3：待开奖， 4:中奖， 5：未中奖
   declare UpdateRowCount int;
   update lottery_order 
     set lottery_order.win_amount = ( select sum(b.win_amount) from betting_detail b where b.lottery_order_id = lottery_order.lottery_order_id ),
         lottery_order.deal_status = 1,
         lottery_order.status=4
   where lottery_order.lottery_id = Flottery_id and lottery_order.periods = Fperiods and lottery_order.status = 3 and lottery_order.deal_status = 0
         and EXISTS(select * from betting_detail b where b.lottery_order_id = lottery_order.lottery_order_id  and b.status=4)
         and not exists(select * from betting_detail b where b.lottery_order_id = lottery_order.lottery_order_id  and b.deal_status=0);
   #set UpdateRowCount = Row_Count();

   update lottery_order 
     set lottery_order.win_amount = ( select sum(b.win_amount) from betting_detail b where b.lottery_order_id = lottery_order.lottery_order_id ),
         lottery_order.deal_status = 1,
         lottery_order.status=5
   where lottery_order.lottery_id = Flottery_id and lottery_order.periods = Fperiods and lottery_order.status = 3 and lottery_order.deal_status = 0
         and not EXISTS(select * from betting_detail b where b.lottery_order_id = lottery_order.lottery_order_id  and b.status=4)
         and not exists(select * from betting_detail b where b.lottery_order_id = lottery_order.lottery_order_id  and b.deal_status=0);  
   #set UpdateRowCount = UpdateRowCount +  Row_Count();
   #SELECT UpdateRowCount; 
END


-- 大乐透兑奖过程 陈启炜
DROP PROCEDURE IF EXISTS CheckDLT; 
create PROCEDURE CheckDLT()
BEGIN 
   declare UpdateRowCount int;
   set UpdateRowCount = 0;
   #1.计算中奖情况,更新等级
   update betting_detail
   set win_level = 
     case (case when FIND_IN_SET(Q1,SubStr(bet_val,1,14)) >0 then 1 else 0 end + 
      case when FIND_IN_SET(Q2,SubStr(bet_val,1,14)) >0 then 1 else 0 end + 
      case when FIND_IN_SET(Q3,SubStr(bet_val,1,14)) >0 then 1 else 0 end + 
      case when FIND_IN_SET(Q4,SubStr(bet_val,1,14)) >0 then 1 else 0 end +
      case when FIND_IN_SET(Q5,SubStr(bet_val,1,14)) >0 then 1 else 0 end ) * 10 +  
      case when FIND_IN_SET(H1,SubStr(bet_val,16,5)) >0 then 1 else 0 end + 
      case when FIND_IN_SET(H2,SubStr(bet_val,16,5)) >0 then 1 else 0 end 
       when 52 then 1
       when 51 then 2 
       when 50 then 3 when 42 then 3
       when 41 then 4 when 32 then 4
       when 40 then 5 when 31 then 5 when 22 then 5
       when 30 then 6 when 12 then 6 when 21 then 6 when 2 then 6 else 0 end
   where lottery_id = 2001 and periods=FPeriods and `status` = 3  and deal_status=0;   
   set UpdateRowCount = Row_Count();

   #2.算奖金
  update betting_detail
  set  deal_status=1, 
       win_amount=  case when win_level = 0 then 0 else 
                        case when is_bet_add = 1 then (case when win_level=1 then JJ1*bet_double*1.6
                                                            when win_level=2 then JJ2*bet_double*1.6  
                                                            when win_level=3 then JJ3*bet_double*1.6  
                                                            when win_level=4 then 200*bet_double*1.5  
                                                            when win_level=5 then  10*bet_double*1.5  
                                                            when win_level=6 then   5*bet_double else 0 
                                                       END) 
                             else (case when win_level=1 then JJ1*bet_double
                                        when win_level=2 then JJ2*bet_double  
                                        when win_level=3 then JJ3*bet_double 
                                        when win_level=4 then 200*bet_double
                                        when win_level=5 then  10*bet_double
                                        when win_level=6 then   5*bet_double else 0 
                                  END) 
                        end 
                    end,
       `status`= case when  win_level > 0 then 4 else 5 end
  where lottery_id = 2001 and periods=FPeriods and `status` = 3  and deal_status=0
       and win_level is not null;
  set UpdateRowCount = UpdateRowCount + Row_Count();
  select UpdateRowCount ; 
  
  call CheckMainOrder(2001, FPeriods);
END


-- 排列三兑奖过程 陈启炜
DROP PROCEDURE IF EXISTS CheckPL3; 
create PROCEDURE CheckPL3()
BEGIN
   #排列三对奖处理 Added by zyr 2017-10-11
   #status :状态（1未支付 2处理中 3待开奖、4中奖、5未中奖、6出票失败
   #deal_status: 0:未兑奖 1：已兑奖 2：订单已兑奖
   #bet_double:投注倍数
   #win_amount: 中奖金额
   
   declare ZJ varchar(100);
   declare UpdateRowCount int;
   set UpdateRowCount  = 0;
   #1.处理直选
   set ZJ=CONCAT(F1,',',F2,',',F3);
   #select case when bet_val = ZJ then '中奖' else '无' end as '中奖情况' ,bet_val, bet_double,win_amount, 1040 * bet_double as win_amount2 from  betting_detail
   update  betting_detail
      set win_level=case when bet_val = ZJ then 1 else 0 end ,
          deal_status=1, 
          win_amount= case when bet_val = ZJ then 1040 * bet_double else 0 end,
          `status`= case when bet_val = ZJ then 4 else 5 end
      where lottery_id = 2002 and play_code in(200201 ,200211) and periods=FPeriods and `status` = 3  and deal_status=0; 
    set UpdateRowCount = ROW_COUNT();
   #2.处理组3 
   if (F1 = F2 and F1<>F3) or (F2=F3 and F1<>F2) or (F1=F3 and F1<>F2) then   
   begin #检测是否有两个相同的，有才比对组三是否中奖  
     if (F1=F2) then 
       set ZJ=CONCAT(F1,F1,F3, ',', F1, F3, F1, ',', F3,F1, F1);
     elseif (F1=F3) then 
       set ZJ=CONCAT(F1,F1,F2, ',', F1, F2, F1, ',', F2,F1, F1);
     else 
       set ZJ=CONCAT(F2,F2,F1, ',', F2, F1, F2, ',', F1,F2, F2);
     end if;
     #select  case when FIND_IN_SET(replace(bet_val,',',''), zj)>0 then '中奖' else '无' end as '中奖情况' ,bet_val ,ZJ from  betting_detail
     update  betting_detail
       set win_level=case when FIND_IN_SET(replace(bet_val,',',''), zj)>0 then 1 else 0 end ,
           deal_status=1,
           win_amount=case when FIND_IN_SET(replace(bet_val,',',''), zj)>0 then 173*bet_double else 0 end ,
          `status`= 5
       where lottery_id = 2002 and play_code = 200212 and periods=FPeriods and `status` = 3  and deal_status=0; 
     set UpdateRowCount = UpdateRowCount + ROW_COUNT();
   end;
   ELSE #没相同的，则所有组三都没中奖
     #select  '无' as '中奖情况' ,bet_val  from  betting_detail
      update  betting_detail 
       set win_level=0 ,
           deal_status=1,
           win_amount=0,
          `status`= case when FIND_IN_SET(replace(bet_val,',',''), zj)>0 then 4 else 5 end
      where lottery_id = 2002 and play_code = 200212 and periods=FPeriods and `status` = 3  and deal_status=0; 
      set UpdateRowCount = UpdateRowCount + ROW_COUNT();
   end if;

   #3.处理组6(该步骤也可处理组3的情况)      
   set ZJ=CONCAT(F1,F2,F3, ',', F1, F3, F2, ',', F2, F1, F3, ',', F2, F3, F1,',', F3, F1, F2,',',F3, F2,F1); 
   #select  case when FIND_IN_SET(replace(bet_val,',',''), zj)>0 then '中奖' else '无' end as '中奖情况' ,bet_val  from  betting_detail   
   update  betting_detail 
      set win_level=case when FIND_IN_SET(replace(bet_val,',',''), zj)>0 then 1 else 0 end ,
           deal_status=1,
           win_amount=case when FIND_IN_SET(replace(bet_val,',',''), zj)>0 then 346*bet_double else 0 end ,
          `status`= case when FIND_IN_SET(replace(bet_val,',',''), zj)>0 then 4 else 5 end
      where lottery_id = 2002 and play_code = 200203 and periods=FPeriods and `status` = 3  and deal_status=0;   
   set UpdateRowCount = UpdateRowCount + ROW_COUNT();   
   SELECT UpdateRowCount; 
  
  call CheckMainOrder(2002, FPeriods);
  
END


-- 排列五兑奖过程 陈启炜
DROP PROCEDURE IF EXISTS CheckPL5; 
create PROCEDURE CheckPL5()
BEGIN
   #排列五对奖处理 Added by zyr 2017-10-11
   #status :状态（1未支付 2处理中 3待开奖、4中奖、5未中奖、6出票失败
   #deal_status: 0:未兑奖 1：已兑奖 2：订单已兑奖
   #bet_double:投注倍数
   #win_amount: 中奖金额
    
   #1.处理直选
   #select case when bet_val = ZJ then '中奖' else '无' end as '中奖情况' ,bet_val, bet_double,win_amount, 1040 * bet_double as win_amount2 from  betting_detail
   update  betting_detail 
      set win_level=case when bet_val = ZJ then 1 else 0 end ,
          deal_status=1, 
          win_amount= case when bet_val = ZJ then 100000 * bet_double else 0 end,
          `status`= case when bet_val = ZJ then 4 else 5 end
      where lottery_id = 2003 and periods=FPeriods and `status` = 3  and deal_status=0;  
   SELECT ROW_COUNT() as UpdateRowCount; 
call CheckMainOrder(2003, FPeriods);
END



-- 七星彩兑奖过程 陈启炜
DROP PROCEDURE IF EXISTS CheckQXC; 
create PROCEDURE CheckQXC()
BEGIN 
   #2004   1,9,7,0,8,1,7
   declare i INT;
   declare lv1 varchar(20);    declare lv2 varchar(20);    declare lv3 varchar(20); 
   declare lv4 varchar(20);    declare lv5 varchar(20);    declare lv6 varchar(20); 
   declare Q1 char(1); declare Q2 char(1); declare Q3 Char(1); 
   declare Q4 char(1); declare Q5 char(1); declare Q6 char(1); 
   declare Q7 char(1);  
   declare UpdateRowCount int;
   set UpdateRowCount = 0;
   #declare ZJ char(7);
   set Q1=MID(ZJ1, 1, 1); 
   set Q2=MID(ZJ1, 3, 1); 
   set Q3=MID(ZJ1, 5, 1); 
   set Q4=MID(ZJ1, 7, 1); 
   set Q5=MID(ZJ1, 9, 1); 
   set Q6=MID(ZJ1, 11, 1); 
   set Q7=MID(ZJ1, 13, 1);
   #set ZJ=CONCAT(Q1,Q2,Q3, Q4, Q5, Q6);
   set lv1 = ZJ1;
   #1.一等奖   
   /*set i=1;   set lv1 ='';
   while i<length(ZJ) do 
      if i=1 then 
        set  lv1 = mid(ZJ, i, 1);
      ELSE
        set  lv1 = concat( lv1 , ',' ,mid(ZJ, i, 1));   
      end if   ;
    set i = i + 1;
   end while; */

   set lv1 = ZJ1;
   update  betting_detail
      set win_level=1 ,
           deal_status=1,
           win_amount=0,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and bet_val=lv1;    
   set UpdateRowCount = Row_Count();
   #2.二等奖  
   set lv2=MID(lv1, 1,11);  
   update  betting_detail
      set win_level=2 ,
           deal_status=1,
           win_amount=0,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv2)>0;  
     
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv2=MID(lv1, 3,11); 
   update  betting_detail
      set win_level=2 ,
           deal_status=1,
           win_amount=0,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv2)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

  #3.三等奖 12345XX(1,2,3,4,5))、X23456X(X,2,3,4,5,6,x)、XX34567
   set lv3=MID(lv1, 1,9); 
   update  betting_detail
      set win_level=3 ,
           deal_status=1,
           win_amount=1800*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv3)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv3=MID(lv1, 3,9); 
   update  betting_detail 
      set win_level=3 ,
           deal_status=1,
           win_amount=1800*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv3)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv3=MID(lv1, 5,9); 
   update  betting_detail
      set win_level=3 ,
           deal_status=1,
           win_amount=1800*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv3)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();
   
   #4.四等奖 1234XXX、X2345XX、XX3456X、XXX4567
   set lv4=MID(lv1, 1,7); 
   update  betting_detail
      set win_level=4 ,
           deal_status=1,
           win_amount=300*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv4)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv4=MID(lv1, 3,7); 
   update  betting_detail
      set win_level=4 ,
           deal_status=1,
           win_amount=300*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv4)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv4=MID(lv1, 5,7); 
   update  betting_detail
      set win_level=4 ,
           deal_status=1,
           win_amount=300*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv4)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv4=MID(lv1, 7,7); 
   update  betting_detail
      set win_level=4 ,
           deal_status=1,
           win_amount=300*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv4)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();


   #5.五等奖 123XXXX、X234XXX、XX345XX、XXX456X、XXXX567
   set lv5=MID(lv1, 1,5); 
   update  betting_detail
      set win_level=5 ,
           deal_status=1,
           win_amount=20*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv5)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv5=MID(lv1, 3,5);  
   update  betting_detail
      set win_level=5 ,
           deal_status=1,
           win_amount=20*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv5)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv5=MID(lv1, 5,5); 
   update  betting_detail
      set win_level=5 ,
           deal_status=1,
           win_amount=20*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv5)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv5=MID(lv1, 7,5);  
   update  betting_detail
      set win_level=5 ,
           deal_status=1,
           win_amount=20*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv5)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv5=MID(lv1, 9,5);  
   update  betting_detail
      set win_level=5 ,
           deal_status=1,
           win_amount=20*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv5)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();


   #6.六等奖 123XXXX、X234XXX、XX345XX、XXX456X、XXXX567
   set lv6=MID(lv1, 1,3); 
   update  betting_detail
      set win_level=6 ,
           deal_status=1,
           win_amount=5*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv6)>0; 
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv6=MID(lv1, 3,3); 
   update  betting_detail
      set win_level=6 ,
           deal_status=1,
           win_amount=5*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv6)>0;  
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv6=MID(lv1, 5,3);
   update  betting_detail_1011 
      set win_level=6 ,
           deal_status=1,
           win_amount=5*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv6)>0;  
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv6=MID(lv1, 7,3);
   update  betting_detail
      set win_level=6 ,
           deal_status=1,
           win_amount=5*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv6)>0;  
   set UpdateRowCount = UpdateRowCount + Row_Count();
 
   set lv6=MID(lv1, 9,3); 
   update  betting_detail
      set win_level=6 ,
           deal_status=1,
           win_amount=5*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv6)>0;  
   set UpdateRowCount = UpdateRowCount + Row_Count();

   set lv6=MID(lv1, 11,3); 
   update  betting_detail
      set win_level=6 ,
           deal_status=1,
           win_amount=5*bet_double,
          `status`= 4
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 and INSTR(bet_val,lv6)>0;  
   set UpdateRowCount = UpdateRowCount + Row_Count();

   #7.处理未中奖
   update  betting_detail
      set win_level=0 ,
           deal_status=1,
           win_amount=0,
          `status`= 5
      where lottery_id = 2004 and periods=FPeriods and `status` = 3  and deal_status=0 ;  
   set UpdateRowCount = UpdateRowCount + Row_Count();
   select UpdateRowCount ;

  call CheckMainOrder(2004, FPeriods);
END