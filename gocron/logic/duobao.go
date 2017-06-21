package logic

import (
	"fmt"
	"gocron/models"
	"gocron/lib/db"
	"time"
	"gocron/lib/redis"
	"strings"
	"gocron/lib"
)

func sendDuobaoCode(){
	productModel:=models.GetBgbModel("new_product")
	proNum:=[]string{"111001","109003","109002","107003","110005","110010","106007","106005"}
	wherep:=map[string]interface{}{
		"pro_num":proNum,
	}
	proIds:=productModel.FetchAll(db.Select{Columns:"id",Where:wherep});
	if len(proIds)==0 {
		return
	}
	var proid []string
	for _,v := range proIds{
			proid=append(proid,v["id"])
	}
	orderModel:=models.GetBgbModel("new_order")
	timestamp := time.Now().Unix()
	tm := time.Unix(timestamp, 0)
	payTime:=tm.Format("2006-01-02 15:04:05")
	where:=map[string]interface{}{
		"pay_time>=?":payTime,
		"pro_id":proid,
		"promote_id":"DUOBAO%",
		"status" : []string{"11","13","14"},
	}
	where=map[string]interface{}{
		"promote_id":"DUOBAO%",
		"status" : []string{"11","13","14"},
	}
	page:=1
	count:=1
	for{
		offset:=(page-1)*count
		res:=orderModel.FetchAll(db.Select{Count:count,Where:where,Offset:offset})
		if len(res)>0 {
			for _,v := range res{
				sendCode(v)
			}
			if page==1 {
				break
			}
			page++
		}else{
			fmt.Println(res)
			break
		}
	}	
	fmt.Println(page)
}
func sendCode(data map[string]string){
	redis:=redis.GetRedis("1")
	if redis.Lock("duobao:lock_sendDuobaoCode:"+data["num_id"],10)==false{
		return
	}
	defer redis.Delete("duobao:lock_sendDuobaoCode:"+data["num_id"]);
	if checkOrderData(data) == false{
		return
	}
	//duobaoModel:=models.GetBgbModel("duobao")
	//tx, _ := duobaoModel.GetAdapter().Begin()
	fmt.Println(data)
	//fmt.Println(tx)
}
func checkOrderData(data map[string]string) (bool){
	promiteData := strings.Split(data["promote_id"], "_")
	fmt.Println(promiteData)
	if len(promiteData) == 0 {
		return false
	}
	lib.LogWrite("我擦","duobao");
	return true
}