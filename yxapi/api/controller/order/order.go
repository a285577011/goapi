package order

import (
	"yxapi/api/controller"
	//"yxapi/api/lib/db"
	//"yxapi/api/models"
)

type Order struct {
	controller.BaseController
}

/**
 */
func (this *Order) IndexAction() {
	this.PrintJson("hellow yx")
}

/**
打印机充值
*/
func (this *Order) PrinterRechargeAction() {
	//data := models.RechargeModel.FetchAll(db.Select{})
	this.PrintJson(controller.UserNo)
}
