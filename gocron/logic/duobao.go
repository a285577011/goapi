package logic

import (
	"fmt"
	"gocron/models"
	"gocron/lib/db"
	"time"
)

func sendCode(){
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
	res:=orderModel.FetchRow(db.Select{Count:10,Where:where});
	if len(res)==0 {
		return
	}

	fmt.Println(res)
}