package logic

import (
	"app/lib"
	"app/lib/db"
	"app/lib/redis"
	"app/models"
	"fmt"
	"math"
	"strconv"
	"strings"
	"sync"
	"time"
)

const REDISTABLE string = "duobao_store_num"

type DuobaoLogic struct {
}

/**
* 获取某期夺宝码数量
*
 */
func (this *DuobaoLogic) GetDuobaoNumStore(duobaoId string, periodsId string) string {
	redis := redis.GetRedis("1")
	key := REDISTABLE + ":" + duobaoId + "-" + periodsId
	if num := redis.Get(key); num != nil {
		return num.(string)
	}
	duobaoCodeModel := models.GetActModel("duobao_goods_code")
	where := map[string]interface{}{
		"duobao_goods_id":   duobaoId,
		"duobao_periods_id": periodsId,
	}
	count := duobaoCodeModel.FetchColumn(db.Select{Where: where, Columns: "COUNT(id)"}, "COUNT(id)") //->where(['is_show'=>1])->order('sort ASC')->select();
	redis.Do("SET", key, count)
	return count
}

/**发送夺宝币
 */
func (this *DuobaoLogic) SendDuobaoCode() {
	redis := redis.GetRedis("1")
	fmt.Println(redis.Get("duobao_num:1"))
	return
	orderModel := models.GetBgbModel("new_order")
	timestamp := time.Now().Unix() - 86400*30 //7200
	tm := time.Unix(timestamp, 0)
	starPayTime := tm.Format("2006-01-02 15:04:05")
	where := map[string]interface{}{
		"pay_time>=?": starPayTime,
		//"promote_id":  "DUOBAO%",
		//"status": []string{"11", "13", "14"},
	}
	page := 1
	count := 100
	for {
		offset := (page - 1) * count
		res := orderModel.FetchAll(db.Select{Count: count, Where: where, Offset: offset})
		fmt.Println(len(res))
		//fmt.Println(res)
		//fmt.Println(orderModel.GetLastSql(db.Select{Count: count, Where: where, Offset: offset}))
		if len(res) > 0 {
			wg := &sync.WaitGroup{} //并发处理
			for _, v := range res {
				go this.sendCode(v, wg)
			}
			//if page == 1 {
			//break
			//}
			page++
		} else {
			//fmt.Println(res)
			break
		}
	}
	fmt.Println("complete")
}
func (this *DuobaoLogic) sendCode(data map[string]string, wg *sync.WaitGroup) {
	wg.Add(1)
	redis := redis.GetRedis("1")
	if redis.Lock("duobao:lock_sendDuobaoCode:"+data["num_id"], 10) == false {
		return
	}
	//data["uid"] = "583"
	if CheckIsVip(data["uid"]) == false {
		redis.Delete("duobao:lock_sendDuobaoCode:" + data["num_id"])
		return
	}
	if this.isSended(data["num_id"]) == true {
		redis.Delete("duobao:lock_sendDuobaoCode:" + data["num_id"])
		return
	}
	orderMoney, _ := strconv.ParseFloat(data["policy_money"], 64)
	baseFloat := 1.0
	duobaoNum := strconv.FormatFloat(math.Ceil((orderMoney / baseFloat)), 'f', -1, 64)
	duobaoUserModel := models.GetActModel("duobao_user")
	where := map[string]interface{}{
		"uid": data["uid"],
	}
	duobaoUser := duobaoUserModel.FetchRow(db.Select{Where: where})
	var sql1 string
	now := strconv.FormatInt(time.Now().Unix(), 10)
	if len(duobaoUser) > 0 {
		sql1 = "UPDATE duobao_user SET code_num=code_num+" + duobaoNum + " WHERE uid=" + data["uid"]
	} else {
		sql1 = "INSERT INTO duobao_user (`uid`,`c_time`) VALUES (" + data["uid"] + "," + now + ")"
	}
	tx, _ := duobaoUserModel.GetAdapter().Begin()
	_, err := tx.Exec(sql1)
	if err != nil {
		redis.Delete("duobao:lock_sendDuobaoCode:" + data["num_id"])
		tx.Rollback()
		lib.LogWrite("更新用户夺宝币失败", "duobao")
	}
	sql2 := "INSERT INTO duobao_log (`uid`,  `order_id`, `code_num`, `c_time`, `remark`) VALUES (" + data["uid"] + ", " + data["num_id"] + ", " + duobaoNum + ", " + now + ",'购买订单赠送')"
	_, err2 := tx.Exec(sql2)
	if err2 != nil {
		redis.Delete("duobao:lock_sendDuobaoCode:" + data["num_id"])
		tx.Rollback()
		lib.LogWrite("插入夺宝记录表失败", "duobao")
	}
	tx.Commit()
	defer func() {
		wg.Done()
		lib.LogWrite("发送夺宝码订单:num_id"+data["num_id"], "duobao")
	}()
	//if checkOrderData(data) == false {
	//return
	//}
	//duobaoModel:=models.GetBgbModel("duobao")
	//tx, _ := duobaoModel.GetAdapter().Begin()
	//fmt.Println(data)
	//fmt.Println(tx)
}
func (this *DuobaoLogic) getInvitor(data map[string]string) string {
	promiteData := strings.Split(data["promote_id"], "_")
	//fmt.Println(promiteData)
	if len(promiteData) == 0 {
		return ""
	}
	eventKey := promiteData[0]
	d := strings.Split(eventKey, "-")
	fmt.Println(d)
	return ""
	//invitor, ok := d[2]
	//if !ok {
	//return ""
	//}
	//return invitor
}

/**是否发送过夺宝币
 */
func (this *DuobaoLogic) isSended(orderId string) bool {
	duobaoLogModel := models.GetActModel("duobao_log")
	where := map[string]interface{}{
		"order_id": orderId,
	}
	userData := duobaoLogModel.FetchRow(db.Select{Where: where})
	if len(userData) > 0 { //非VIP用户
		return true
	}
	return false
}

/**检查用户否是特权用户
 */
func CheckIsVip(uid string) bool {
	userModel := models.GetBgbModel("user")
	where := map[string]interface{}{
		"id": uid,
	}
	userData := userModel.FetchRow(db.Select{Where: where})
	if userData["is_vip_card"] == "0" { //非VIP用户
		return false
	}
	return true
}
