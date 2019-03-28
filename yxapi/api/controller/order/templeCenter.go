package order

import (
	//"fmt"
	"strconv"
	"yxapi/api/controller"
	"yxapi/api/form"
	"yxapi/api/lib/common"
	"yxapi/api/lib/db"
	"yxapi/api/models"
)

type TempleCenter struct {
	controller.BaseController
}

/**
控制器初始化方法
*/
func (this *TempleCenter) Before() bool {

	//controller.BaseController.Init()
	controller.TempleId = controller.GetTempleId()
	//controller.TempleId = "40"
	if controller.TempleId == "" {
		returnData := map[string]interface{}{
			"code": 6001,
			"msg":  "非寺院主账号",
		}
		this.PrintJson(returnData)
		//panic(map[string]interface{}{
		//"code": "6001",
		//"msg":  "非寺院主账号",
		//	})
		return false
		//controller.UserNo = "hyy"
	}
	return true
}

/**
打印机申请
*/
func (this *TempleCenter) ApplyPrinterAction() {
	applyData := models.GetActModel("temple_printer_apply").FetchRow(db.Select{Where: map[string]interface{}{
		"temple_id": controller.TempleId,
	}})
	if len(applyData) >= 1 {
		this.PrintErrorMessage(1001, "已经申请过")
		return
	}
	insert := map[string]string{}
	insert["temple_id"] = controller.TempleId
	insert["status"] = "0"
	insert["c_time"] = common.GetTimeInt()
	models.GetActModel("temple_printer_apply").Insert(insert)
	this.PrintSuccessMessage(insert)
	return
}

/**
获取打印使用记录
*/
func (this *TempleCenter) PrinterRecordAction() {
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"page": map[string]string{
				"rule":     "int",
				"errormsg": "页面错误",
			},
			"pageSize": map[string]string{
				"rule":     "int",
				"errormsg": "页面大小错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	page := validator.RequestData["page"]
	pageSize := validator.RequestData["pageSize"]
	if pageSize == "" {
		pageSize = "10"
	}
	pageSizeInt, _ := strconv.Atoi(pageSize)
	offser := common.CountOffse(page, pageSize)
	data := map[string]interface{}{}
	data["applyData"] = models.GetActModel("printer_num_record").FetchAll(db.Select{Where: map[string]interface{}{
		"temple_id": controller.TempleId,
	}, Count: pageSizeInt, Offset: offser})
	data["count"] = models.GetActModel("printer_num_record").FetchColumn(db.Select{Where: map[string]interface{}{
		"temple_id": controller.TempleId,
	}, Columns: "count(id)"}, "count(id)")
	this.PrintSuccessMessage(data)
	return
}
