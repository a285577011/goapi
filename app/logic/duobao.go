package logic

import (
	"app/lib"
	"app/lib/db"
	"app/lib/redis"
	"app/models"
	"fmt"
	//"math"
	//"runtime"
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
	redis.ComDo("SET", key, count)
	return count
}

/**发送夺宝币
 */
func (this *DuobaoLogic) SendDuobaoCode() {
	orderModel := models.GetBgbModel("new_order")
	timestamp := time.Now().Unix() - 7200 //抓取两个小时前的订单
	tm := time.Unix(timestamp, 0)
	starPayTime := tm.Format("2006-01-02 15:04:05")
	where := map[string]interface{}{
		"pay_time>=?": starPayTime,
		//"promote_id":  "DUOBAO%",
		"status": []string{"11", "13", "14"},
	}
	page := 1
	count := 100
	for {
		offset := (page - 1) * count
		res := orderModel.FetchAll(db.Select{Count: count, Where: where, Offset: offset})
		//lib.LogWrite(orderModel.GetLastSql(db.Select{Count: count, Where: where, Offset: offset}), "duobao")
		//fmt.Println(runtime.NumGoroutine())
		//fmt.Println(len(res))
		//fmt.Println(orderModel.GetLastSql(db.Select{Count: count, Where: where, Offset: offset}))
		//lib.LogWrite(len(res), "duobao")
		if len(res) > 0 {
			var err error
			wg := &sync.WaitGroup{} //并发处理
			for _, v := range res {
				err = this.initDuobaoUser(v["uid"]) //初始化用户防止并发重复
				if err != nil {
					lib.LogWrite("initDuobaoUser_Faile:"+v["uid"], "duobao")
					continue
				}
				wg.Add(1)
				go this.sendCode(v, wg)
			}
			wg.Wait()
			//if page == 1 {
			//break
			//}
			page++
		} else {
			//fmt.Println(res)
			break
		}
	}
	lib.LogWrite("夺宝币发送完成", "duobao")
}
func (this *DuobaoLogic) sendCode(data map[string]string, wg *sync.WaitGroup) {
	//data["uid"] = "583"
	if CheckIsVip(data["uid"]) == false {
		wg.Done()
		return
	}
	if this.isSended(data["num_id"]) == true {
		wg.Done()
		return
	}
	redis := redis.GetRedis("1")
	if redis.Lock("duobao:lock_sendDuobaoCode:"+data["num_id"], 10) == false { //锁定用户 防止初始化失败
		wg.Done()
		return
	}
	defer func() {
		redis.Delete("duobao:lock_sendDuobaoCode:" + data["num_id"])
		wg.Done()
	}()
	//orderMoney, _ := strconv.ParseFloat(data["policy_money"], 64)
	//baseFloat := 1.0
	duobaoNum := data["money"] //strconv.FormatFloat(math.Ceil((orderMoney / baseFloat)), 'f', -1, 64)
	duobaoUserModel := models.GetActModel("duobao_user")
	var sql1 string
	now := strconv.FormatInt(time.Now().Unix(), 10)
	sql1 = "UPDATE duobao_user SET code_num=code_num+" + duobaoNum + " WHERE uid=" + data["uid"]
	tx, _ := duobaoUserModel.GetAdapter().Begin()
	_, err := tx.Exec(sql1)
	if err != nil {
		tx.Rollback()
		lib.LogWrite("更新用户夺宝币失败:"+sql1, "duobao")
		return
	}
	sql2 := "INSERT INTO duobao_log (`uid`,  `order_id`, `code_num`, `c_time`, `remark`) VALUES (" + data["uid"] + ", " + data["num_id"] + ", " + duobaoNum + ", " + now + ",'购买订单赠送')"
	_, err2 := tx.Exec(sql2)
	if err2 != nil {
		tx.Rollback()
		lib.LogWrite("插入夺宝记录表失败:"+sql2, "duobao")
		return
	}
	tx.Commit()
	return
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

/**兑换夺宝码
 */
func (this *DuobaoLogic) Exchange(uid string, peridosIds string, exNum int) map[string]string {
	data := make(map[string]string)
	redis := redis.GetRedis("1")
	if redis.Lock("duobao:lock_exchangeCode:"+uid, 10) == false {
		data["code"] = "1005"
		data["msg"] = "兑换中..请勿重复操作"
		return data
	}
	defer func() {
		redis.Delete("duobao:lock_exchangeCode:" + uid)
	}()
	checkRes, duobaoId := this.isCanEx(uid, peridosIds, exNum)
	if checkRes["code"] != "0" {
		return checkRes
	}
	duobaoUserModel := models.GetActModel("duobao_user")
	tx, _ := duobaoUserModel.GetAdapter().Begin()
	sql1 := "UPDATE duobao_user SET code_num=code_num-" + strconv.Itoa(exNum) + " WHERE uid=" + uid
	_, err := tx.Exec(sql1)
	if err != nil {
		data["code"] = "1008"
		data["msg"] = "更新夺宝相关数据库失败"
		tx.Rollback()
		lib.LogWrite("更新用户夺宝币失败:"+sql1, "duobao")
		this.decrDuobaoNum(duobaoId, peridosIds, exNum)
		return data
	}
	//now := strconv.FormatInt(time.Now().Unix(), 10)
	nowMillisecond := strconv.FormatInt(time.Now().UnixNano()/1e6, 10)
	now := string([]byte(nowMillisecond)[:10])
	millisecond := string([]byte(nowMillisecond)[10:])
	sql2 := "INSERT INTO duobao_log (`uid`,  `type`, `code_num`, `c_time`, `remark`, `periods_id`) VALUES (" + uid + ", 2, " + strconv.Itoa(exNum) + " , " + now + ",'兑换夺宝码'," + peridosIds + ")"
	_, err2 := tx.Exec(sql2)
	if err2 != nil {
		data["code"] = "1009"
		data["msg"] = "更新夺宝相关数据库失败1"
		tx.Rollback()
		lib.LogWrite("插入夺宝记录表失败:"+sql2, "duobao")
		this.decrDuobaoNum(duobaoId, peridosIds, exNum)
		return data
	}
	nowNum, _ := strconv.Atoi(this.GetDuobaoNumStore(duobaoId, peridosIds))
	//fmt.Println(nowNumStr)
	//fmt.Println(nowNum)
	var code int
	userModel := models.GetBgbModel("user")
	where := map[string]interface{}{
		"id": uid,
	}
	tel := userModel.FetchColumn(db.Select{Where: where, Columns: "tel"}, "tel")
	sql3 := "INSERT INTO duobao_goods_code (`uid`,`tel`,`duobao_goods_id`,`duobao_periods_id`,`code`,`c_time`,`millisecond`) VALUES "
	for i := 0; i < exNum; i++ {
		code = nowNum + i + 10000001 - exNum //这里增加过数量 要再减回去
		sql3 = sql3 + "(" + uid + ", " + tel + ", " + duobaoId + "," + peridosIds + "," + strconv.Itoa(code) + ", " + now + "," + millisecond + "),"
	}
	sql3 = strings.TrimRight(sql3, ",")
	_, err3 := tx.Exec(sql3)
	if err3 != nil {
		data["code"] = "1010"
		data["msg"] = "更新夺宝相关数据库失败2"
		tx.Rollback()
		lib.LogWrite("插入夺宝码表失败:"+sql3, "duobao")
		this.decrDuobaoNum(duobaoId, peridosIds, exNum)
		return data
	}
	tx.Commit()
	data["code"] = "0"
	data["msg"] = "兑换成功"
	return data
}

/**检查是否可兑换夺宝
 */
func (this *DuobaoLogic) isCanEx(uid string, peridosIds string, exNum int) (map[string]string, string) {
	data := map[string]string{
		"code": "0",
	}
	duobaoPerModel := models.GetActModel("duobao_goods_periods")
	where := map[string]interface{}{
		"id":     peridosIds,
		"status": 0,
	}
	periodsData := duobaoPerModel.FetchRow(db.Select{Where: where})
	if len(periodsData) == 0 {
		data["code"] = "1002"
		data["msg"] = "夺宝期数不存在"
		return data, ""
	}
	duobaoModel := models.GetActModel("duobao_goods")
	whereG := map[string]interface{}{
		"id":     periodsData["duobao_goods_id"],
		"status": 0,
	}
	duobaoData := duobaoModel.FetchRow(db.Select{Where: whereG, Columns: "need_number,user_limit,status,start_time"})
	if duobaoData["status"] == "0" { //关闭状态
		data["code"] = "1002"
		data["msg"] = "夺宝已关闭"
		return data, ""
	}
	now := int(time.Now().Unix())
	startTime, _ := strconv.Atoi(duobaoData["start_time"])
	if startTime > now {
		data["code"] = "1102"
		data["msg"] = "夺宝还未开始"
		return data, ""
	}
	duobaoUserModel := models.GetActModel("duobao_user")
	whereU := map[string]interface{}{
		"uid": uid,
	}
	codeNum := duobaoUserModel.FetchColumn(db.Select{Where: whereU}, "code_num") //浮点数字符串
	nonFractionalPart := strings.Split(codeNum, ".")
	userCodeNum, _ := strconv.Atoi(nonFractionalPart[0])
	//fmt.Println(userCodeNum)
	if userCodeNum < exNum {
		data["code"] = "1003"
		data["msg"] = "夺宝币不足"
		return data, ""
	}
	whereC := map[string]interface{}{
		"duobao_periods_id": peridosIds,
		"uid":               uid,
	}
	maxNum, _ := strconv.Atoi(duobaoData["user_limit"])
	//fmt.Println(maxNum)
	if maxNum > 0 {
		duobaoCodeModel := models.GetActModel("duobao_goods_code")
		buyNum, _ := strconv.Atoi(duobaoCodeModel.FetchColumn(db.Select{Where: whereC, Columns: "COUNT(id)"}, "COUNT(id)"))
		if buyNum+exNum > maxNum {
			data["code"] = "1004"
			data["msg"] = "超过单个用户最大兑换次数"
			return data, ""
		}
	}
	needNumber, _ := strconv.Atoi(duobaoData["need_number"])
	nowBuyNum, _ := strconv.Atoi(this.GetDuobaoNumStore(periodsData["duobao_goods_id"], peridosIds))
	if (nowBuyNum + exNum) > needNumber {
		data["code"] = "1006"
		data["msg"] = "剩余兑换次数不足"
		data["data"] = strconv.Itoa(needNumber - nowBuyNum)
		return data, ""
	}
	mixNum, rErr := this.incrDuobaoNum(periodsData["duobao_goods_id"], peridosIds, exNum)
	if rErr != nil {
		data["code"] = "1007"
		data["msg"] = "服务器繁忙,请稍后再试"
		return data, ""
	}
	if mixNum > needNumber {
		data["code"] = "1008"
		data["msg"] = "超过夺宝可兑换的数量"
		this.decrDuobaoNum(periodsData["duobao_goods_id"], peridosIds, exNum)
		return data, ""
	}
	return data, periodsData["duobao_goods_id"]

}

/**增加某期夺宝数量
 */
func (this *DuobaoLogic) incrDuobaoNum(duobaoId string, peridosIds string, exNum int) (int, error) {
	redis := redis.GetRedis("1")
	key := REDISTABLE + ":" + duobaoId + "-" + peridosIds
	num, err := redis.Incr(key, exNum)
	return num, err

}

/**减少某期夺宝数量
 */
func (this *DuobaoLogic) decrDuobaoNum(duobaoId string, peridosIds string, exNum int) (int, error) {
	redis := redis.GetRedis("1")
	key := REDISTABLE + ":" + duobaoId + "-" + peridosIds
	num, err := redis.Decr(key, exNum)
	return num, err
}

/**初始化夺宝用户
 */
func (this *DuobaoLogic) initDuobaoUser(uid string) error {
	duobaoUserModel := models.GetActModel("duobao_user")
	where := map[string]interface{}{
		"uid": uid,
	}
	duobaoUser := duobaoUserModel.FetchRow(db.Select{Where: where})
	if len(duobaoUser) <= 0 {
		userModel := models.GetBgbModel("user")
		whereU := map[string]interface{}{
			"id": uid,
		}
		tel := userModel.FetchColumn(db.Select{Where: whereU, Columns: "tel"}, "tel")
		insertData := map[string]string{
			"uid":    uid,
			"c_time": strconv.FormatInt(time.Now().Unix(), 10),
			"tel":    tel,
		}
		_, err := duobaoUserModel.Insert(insertData)
		return err
	}
	return nil
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
	if userData["is_vip_card"] == "0" || len(userData) == 0 { //非VIP用户
		return false
	}
	return true
}
