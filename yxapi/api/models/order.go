package models

import (
	"yxapi/api/lib/db"
)

type Order struct {
	Base
}

var OrderModel *Order
var OrderType map[string]string

func init() {
	OrderModel = &Order{}
	OrderModel.Table = "order"
	OrderType = make(map[string]string)
	OrderType["gongde"] = "功德箱"
	OrderType["rixingyishang"] = "日行一善"
	OrderType["siyuanhuodongbaoming"] = "法会活动"
}
func (this *Order) GetOrderType(templeId string) map[string]string {
	orderType := OrderType
	templePro := GetActModel("temple_project").FetchAll(db.Select{Columns: "code,name", Where: map[string]interface{}{
		"temple_id": templeId,
	}})
	for _, v := range templePro {
		orderType[v["code"]] = v["name"]
	}
	return orderType
}
