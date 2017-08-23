package logic

import (
	"app/lib"
	"app/lib/common"
	"app/lib/db"
	"app/lib/redis"
	"app/models"
	"encoding/json"
	//"fmt"
	"strconv"
	"strings"
	"time"
)

type TurnTableLogic struct {
	EventKey        string
	LimitType       string
	MaxNum          string
	ProbabilityType string
}

/**
* 大转盘转
*
 */
func (this *TurnTableLogic) Play(tel string, openId string) map[string]interface{} {
	data := make(map[string]interface{})
	userModel := models.GetBgbModel("user")
	userWhere := map[string]interface{}{
		"tel": tel,
	}
	userData := userModel.FetchRow(db.Select{Columns: "is_lock,id,promote_id", Where: userWhere})
	if len(userData) == 0 || userData["is_lock"] == "1" {
		data["code"] = "1002"
		data["msg"] = "用户不存在或已锁定"
		return data
	}
	redis := redis.GetRedis("1")
	if redis.Lock(this.EventKey+"turntable:lock_play:"+tel, 10) == false {
		data["code"] = "1003"
		data["msg"] = "palying...."
		return data
	}
	defer func() {
		redis.Delete(this.EventKey + "turntable:lock_play:" + tel)
	}()
	checkData := this.isLottery(tel, openId)
	if checkData["code"] != "0" {
		data["code"] = "1004"
		data["msg"] = checkData["msg"]
		return data
	}
	awardModel := models.GetActModel("award")
	awardWhere := map[string]interface{}{
		"event_key": this.EventKey,
		"status":    "1",
	}
	awardData := awardModel.FetchAll(db.Select{Where: awardWhere})
	stock := 0
	for _, v := range awardData {
		stockInt, _ := strconv.Atoi(v["stock"])
		stock += stockInt
	}
	if stock == 0 {
		data["code"] = "1005"
		data["msg"] = "奖品已抽完"
		return data
	}
	var isNewUser string
	isNewUser = "2"
	if strings.Index(userData["promote_id"], this.EventKey) == 0 {
		isNewUser = "1"
	}
	ratioKey := this.GetRatioKey(isNewUser)
	if ratioKey == "" {
		data["code"] = "1007"
		data["msg"] = "概率类型设置有误"
		return data
	}
	pData := this.getAwardRand(awardData, ratioKey, tel)
	pCode := pData["code"].(string)
	if pCode != "0" {
		pMsg := pData["msg"].(string)
		data["code"] = "1006"
		data["msg"] = pMsg
		return data
	}
	return this.doPlay(tel, openId, pData["data"].(map[string]string), isNewUser)
}
func (this *TurnTableLogic) isLottery(tel string, openId string) map[string]string {
	//limitTypes := lib.GetConfig("turntable")["playRule."+this.EventKey+".limitType"].String()
	maxNum, err := strconv.Atoi(this.MaxNum)
	if err != nil {
		return map[string]string{
			"code": "1101",
			"msg":  "未设置活动抽奖规则限制数量",
		}
	}
	if this.LimitType == "" {
		return map[string]string{
			"code": "1101",
			"msg":  "未设置活动抽奖规则限制类型",
		}
	}
	if maxNum == 0 {
		return map[string]string{
			"code": "1101",
			"msg":  "限制数量不能为0",
		}
	}
	var recordWhere map[string]interface{}
	var recordWhere2 map[string]interface{}
	canPlayTimes := "0"
	playRecordModel := models.GetActModel("play_record")
	switch this.LimitType {
	case "1": //每个用户总抽奖次数(手机号为单位)
		recordWhere = map[string]interface{}{
			"event_key": this.EventKey,
			"mobile":    tel,
		}
		break
	case "2": //type 2 每个用户总抽奖次数(手机号微信号为单位)
		if openId == "" {
			return map[string]string{
				"code": "1103",
				"msg":  "openId不能为空",
			}
		}
		recordWhere = map[string]interface{}{
			"event_key": this.EventKey,
			"mobile":    tel,
		}
		recordWhere2 = map[string]interface{}{
			"event_key": this.EventKey,
			"wx_openid": openId,
		}
		break

	}
	playTimes, _ := strconv.Atoi(playRecordModel.FetchColumn(db.Select{Where: recordWhere, Columns: "COUNT(id)"}, "COUNT(id)"))
	if playTimes >= maxNum {
		return map[string]string{
			"code": "1102",
			"msg":  "亲爱的，您的手机号抽奖次数已用完~",
		}
	}
	if this.LimitType == "2" {
		playTimes, _ := strconv.Atoi(playRecordModel.FetchColumn(db.Select{Where: recordWhere2, Columns: "COUNT(id)"}, "COUNT(id)"))
		if playTimes >= maxNum {
			return map[string]string{
				"code": "1101",
				"msg":  "亲爱的，您的微信号抽奖次数已用完~",
			}
		}
		canPlayTimes = strconv.Itoa(maxNum - playTimes)
	}
	canPlayTimes = strconv.Itoa(maxNum - playTimes)
	return map[string]string{
		"code": "0",
		"msg":  "有剩余抽取次数",
		"data": canPlayTimes,
	}
}
func (this *TurnTableLogic) doPlay(tel string, openId string, prize map[string]string, isNewUser string) map[string]interface{} {
	data := make(map[string]interface{})
	playRecordModel := models.GetActModel("play_record")
	tx, _ := playRecordModel.GetAdapter().Begin()
	now := strconv.FormatInt(time.Now().Unix(), 10)
	sql1 := "INSERT INTO play_record SET is_received=1,event_key='" + this.EventKey + "',award_name='" + prize["name"] + "',is_done=0,mobile='" + tel + "',money='" + prize["alias"] + "',create_time='" + now + "',update_time='" + now + "',is_newuser='" + isNewUser + "'"
	if openId != "" {
		sql1 += ",wx_openid='" + openId + "'"
	}
	_, err := tx.Exec(sql1)
	if err != nil {
		data["code"] = "1103"
		data["msg"] = "插入PlayRecord表失败"
		tx.Rollback()
		lib.LogWrite("插入PlayRecord表失败:"+sql1, "turntable")
		return data
	}
	LastInsertId := ""
	switch prize["type"] {
	case "3": //白鸽现金
		break
	case "4": //微信现金
		sql4 := "INSERT INTO play_record_lidong SET type=0,is_received=0,order_id=0,is_rebate=0,event_key='" + this.EventKey + "',award_rank=1,award_name='" + prize["name"] + "',mobile='" + tel + "',create_time='" + now + "',update_time='" + now + "',money='" + prize["alias"] + "',is_newuser='" + isNewUser + "'"
		if openId != "" {
			sql4 += ",wx_openid='" + openId + "'"
		}
		res, err := tx.Exec(sql4)
		if err != nil {
			data["code"] = "1104"
			data["msg"] = "插入play_record_lidong表失败"
			tx.Rollback()
			lib.LogWrite("插入play_record_lidong表失败:"+sql4, "turntable")
			return data
		}
		recordId, _ := res.LastInsertId()
		LastInsertId = strconv.FormatInt(recordId, 10)
		break
	case "5": //白鸽保险
		break
	default:
	}
	if models.AwardModel.DecrStockById(prize["id"]) == -1 {
		data["code"] = "1105"
		data["msg"] = "奖品已抽完"
		tx.Rollback()
		lib.LogWrite("库存减少失败(超出库存)", "turntable")
		return data
	}
	tx.Commit()
	if prize["type"] == "4" { //微信红包写REDIS
		redis := redis.GetRedis("1")
		jsonResult, _ := json.Marshal(map[string]string{
			"event_key": this.EventKey,
			"send_id":   LastInsertId,
		})
		redis.ComDo("LPUSH", "QUEUE_SENDREDPACK_INFO", jsonResult)

	}

	return map[string]interface{}{
		"code": "0",
		"msg":  "抽奖成功",
		"data": prize,
	}
}

/**
 * 获取随机奖品
 *
 * @param string $awardData 奖品
 * @param string $ratioKey 概率key值
 * @return array
 */
func (this *TurnTableLogic) getAwardRand(awardData []map[string]string, ratioKey string, tel string) map[string]interface{} {
	prizeArr := make(map[string]string)
	idToData := make(map[string]map[string]string)
	playRecordModel := models.GetActModel("play_record")
	var recordWhere map[string]interface{}
	for _, v := range awardData {
		if v["limit"] != "0" { //每个用户奖品限制次数
			limit, _ := strconv.Atoi(v["limit"])
			recordWhere = map[string]interface{}{
				"mobile":    tel,
				"event_key": this.EventKey,
				"money":     v["alias"],
			}
			getNum, _ := strconv.Atoi(playRecordModel.FetchColumn(db.Select{Where: recordWhere, Columns: "COUNT(id)"}, "COUNT(id)"))
			if getNum >= limit {
				continue
			}
		}
		if models.AwardModel.GetStockById(v["id"]) > 0 {
			prizeArr[v["id"]] = v[ratioKey]
			idToData[v["id"]] = v
		}
	}
	pid := this.GetRand(prizeArr)
	if pid != "" {
		return map[string]interface{}{
			"code": "0",
			"data": idToData[pid],
		}
	}
	return map[string]interface{}{
		"code": "1001",
		"msg":  "无可用奖品",
	}
}
func (this *TurnTableLogic) GetRand(prize map[string]string) string {
	if len(prize) <= 0 {
		return ""
	}
	result := ""
	proSum := 0
	for _, v := range prize {
		vInt, _ := strconv.Atoi(v)
		proSum += vInt
	}
	randNum := 0
	for k, v := range prize {
		randNum = common.RangeNum(1, proSum)
		vInt, _ := strconv.Atoi(v)
		if randNum < vInt {
			result = k
			break
		} else {
			proSum -= vInt
		}
	}

	return result
}

/**
*获取概率Key
 */
func (this *TurnTableLogic) GetRatioKey(isNewUser string) string {
	//probabilityType := lib.GetConfig("turntable")["playRule."+this.EventKey+".probabilityType"].String()
	if this.ProbabilityType == "" {
		return ""
	}
	switch this.ProbabilityType {
	case "1":
		return "ratio_0"
		break
	case "2":
		if isNewUser == "1" {
			return "ratio_0"
		}
		return "ratio_1"
		break
	}
	return ""
}
