package order

import (
	"yxapi/api/controller"
	//"yxapi/api/lib/db"
	//"yxapi/api/models"
	//"fmt"
	//"strconv"
	"yxapi/api/form"
	//	"yxapi/api/lib/db"
	"yxapi/api/logic"
	//	"yxapi/api/models"
	//"fmt"
	"fmt"
	"strconv"
	"yxapi/api/lib/common"
	"yxapi/api/lib/db"
	"yxapi/api/models"
)

type Subaccount struct {
	controller.BaseController
}

/**
控制器初始化方法
*/
func (this *Subaccount) Before() bool {

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
获取分账规则列表
*/
func (this *Subaccount) IndexAction() {
	returnD := map[string]interface{}{}
	payNo := models.TempleModel.FetchColumn(db.Select{Where: map[string]interface{}{
		"id": controller.TempleId,
	}}, "pay_user_no")
	payRoute := models.GetActModel("pay_route").FetchAll(db.Select{Where: map[string]interface{}{
		"cust_no": payNo,
	}})
	//newPayRoute := map[int]interface{}{}
	orderType := models.OrderModel.GetOrderType(controller.TempleId)
	orderTypeData := []interface{}{}
	for _, v := range payRoute {
		orderTypeDatamap := map[string]interface{}{}
		for kcc, vcc := range v {
			orderTypeDatamap[kcc] = vcc
		}
		orderTypeDatamap["orderTypeCn"] = orderType[v["order_type"]]
		//payRoute[k]["orderTypeCn"] = orderType[v["order_type"]]
		payRouteDetail := models.GetActModel("pay_route_detail").FetchAll(db.Select{Where: map[string]interface{}{
			"route_id": v["id_"],
			"type_":    "01",
		}})
		for kc, vc := range payRouteDetail {
			person := models.GetActModel("pay_route_person").FetchRow(db.Select{Where: map[string]interface{}{
				"id": vc["p_id"],
			}})
			payRouteDetail[kc]["personName"] = person["name"]
		}
		orderTypeDatamap["payRouteDetail"] = payRouteDetail
		orderTypeData = append(orderTypeData, orderTypeDatamap)
		//	newPayRoute[k] = newPayRoute.(map[string]interface{}{})
		//newPayRoute[k] = v
		//newPayRoute[k]["payRouteDetail"] = payRouteDetail

	}
	returnD["code"] = 600
	returnD["result"] = orderTypeData
	this.PrintJson(returnD)
	return
}

/**
添加分账人员
*/
func (this *Subaccount) AddPersonAction() {
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"name": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "姓名错误",
			},
			"bank_name": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "银行名称错误",
			},
			"bank_account": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "银行卡号错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	pdata := validator.RequestData
	orderLogic := &logic.OrderLogic{}
	//money, _ := strconv.ParseFloat(pdata["money"], 64)
	returnData := orderLogic.AddPerson(pdata)
	this.PrintJson(returnData)
}

/**
获取分账人员列表
*/
func (this *Subaccount) PersonListAction() {
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"status": map[string]string{
				"rule":     "int",
				"errormsg": "状态错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	where := map[string]interface{}{
		"temple_id": controller.TempleId,
	}
	if validator.RequestData["status"] != "" {
		where["status"] = validator.RequestData["status"]
	}
	data := models.GetActModel("pay_route_person").FetchAll(db.Select{Where: where, Order: "c_time DESC"})
	returnData := map[string]interface{}{
		"code":   600,
		"result": data,
	}
	this.PrintJson(returnData)
}

/**
获取分账所有订单类型
*/
func (this *Subaccount) OrderTypeAction() {
	data := make(map[string]interface{})
	orderType := models.OrderModel.GetOrderType(controller.TempleId)
	data["orderType"] = orderType
	data["masSubRate"] = logic.CountMaxSubRate(controller.TempleId)
	returnData := map[string]interface{}{
		"code":   600,
		"result": data,
	}
	//fmt.Println("a")
	this.PrintJson(returnData)
	return

}

/**
添加分账规则

*/
func (this *Subaccount) AddRuleAction() {
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"title": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "规则名称错误",
			},
			"orderType": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "订单类型错误",
			},
			"rules": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "规则类型错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	logic := &logic.OrderLogic{}
	returnData := logic.AddRule(validator.RequestData["title"], validator.RequestData["orderType"], validator.RequestData["rules"])
	this.PrintJson(returnData)
}

/**
修改分账规则

*/
func (this *Subaccount) UpdateRuleAction() {
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"title": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "规则名称错误",
			},
			"orderType": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "订单类型错误",
			},
			"rules": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "规则类型错误",
			},
			"routeId": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "规则主键错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	logic := &logic.OrderLogic{}
	returnData := logic.UpdateRule(validator.RequestData["title"], validator.RequestData["orderType"], validator.RequestData["rules"], validator.RequestData["routeId"])
	this.PrintJson(returnData)
}

/**
d获取规则明细
*/
func (this *Subaccount) RuleDetailAction() {
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"routeId": map[string]string{
				"rule":     "required|int|min:1",
				"errormsg": "规则ID错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	returnData := make(map[string]interface{})
	returnData["code"] = 600
	data := make(map[string]interface{})
	routeData := models.GetActModel("pay_route").FetchRow(db.Select{Where: map[string]interface{}{
		"id_": validator.RequestData["routeId"],
	}})
	orderType := models.OrderModel.GetOrderType(controller.TempleId)
	routeData["orderTypeCn"] = orderType[routeData["order_type"]]
	data["base"] = routeData
	prd := []interface{}{}
	payRouteDetail := models.GetActModel("pay_route_detail").FetchAll(db.Select{Where: map[string]interface{}{
		"route_id": validator.RequestData["routeId"],
		"type_":    "01",
	}})
	for _, vc := range payRouteDetail {
		prdD := common.FormatmssTomst(vc)
		person := models.GetActModel("pay_route_person").FetchRow(db.Select{Where: map[string]interface{}{
			"id": vc["p_id"],
		}})
		prdD["personDetail"] = person
		prd = append(prd, prdD)
	}
	data["detail"] = prd
	data["masSubRate"] = logic.CountMaxSubRate(controller.TempleId)
	returnData["result"] = data
	this.PrintJson(returnData)
}

/**
新增人员规则
*/
func (this *Subaccount) AddPersonRuleAction() {
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"personId": map[string]string{
				"rule":     "required|int|min:1",
				"errormsg": "人员ID错误",
			},
			"rate": map[string]string{
				"rule":     "required|float|max:30",
				"errormsg": "费率错误",
			},
			"routeId": map[string]string{
				"rule":     "required|int|min:1",
				"errormsg": "规则ID错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	//fmt.Println(validator.RequestData["routeId"])
	payRoute := models.GetActModel("pay_route").FetchRow(db.Select{Where: map[string]interface{}{
		"id_": validator.RequestData["routeId"],
	}})
	returnData := make(map[string]interface{})
	if len(payRoute) == 0 {
		returnData["code"] = 1001
		returnData["msg"] = "规则不存在"
		this.PrintJson(returnData)
		return
	}
	routeDetail := models.GetActModel("pay_route_detail").FetchRow(db.Select{Where: map[string]interface{}{
		"p_id":     validator.RequestData["personId"],
		"route_id": validator.RequestData["routeId"],
	}})
	if len(routeDetail) > 0 {
		returnData["code"] = 1002
		returnData["msg"] = "该人员已在这个规则里面"
		this.PrintJson(returnData)
		return
	}
	totalRate := logic.GetTotalRate(validator.RequestData["routeId"])
	userRate, _ := strconv.ParseFloat(validator.RequestData["rate"], 64)
	if totalRate+userRate*100 > 10000 {
		returnData["code"] = "1003"
		returnData["msg"] = "超出比例"
		this.PrintJson(returnData)
		return
	}
	insert := make(map[string]string)
	insert["id_"] = common.UuidRedis("pay_route_detail")
	insert["route_id"] = validator.RequestData["routeId"]
	insert["cust_no"] = validator.RequestData["personId"]
	insert["rate_"] = validator.RequestData["rate"]
	insert["type_"] = "01"
	insert["create_time"] = common.GetTimeDate()
	insert["p_id"] = validator.RequestData["personId"]
	_, err := models.GetActModel("pay_route_detail").Insert(insert)
	if err != nil {
		returnData["code"] = 1004
		returnData["msg"] = "写入明细失败"
		this.PrintJson(returnData)
		return
	}
	returnData["code"] = 600
	returnData["msg"] = "写入成功"
	returnData["result"] = insert
	this.PrintJson(returnData)
	return

}

/**
修改人员费率
*/
func (this *Subaccount) UpdateRateAction() {
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"routeDetaulId": map[string]string{
				"rule":     "required|int|min:1",
				"errormsg": "规则明细ID错误",
			},
			"rate": map[string]string{
				"rule":     "required|float|max:30",
				"errormsg": "费率错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	returnData := make(map[string]interface{})
	routeDetail := models.GetActModel("pay_route_detail").FetchRow(db.Select{Where: map[string]interface{}{
		"id_": validator.RequestData["routeDetaulId"],
	}})
	if len(routeDetail) == 0 {
		returnData["code"] = 1002
		returnData["msg"] = "规则明细不存在"
		this.PrintJson(returnData)
		return
	}
	if routeDetail["type_"] == "00" {
		returnData["code"] = 1005
		returnData["msg"] = "主账号不允许修改"
		this.PrintJson(returnData)
		return
	}
	totalRate := logic.GetTotalRate(routeDetail["route_id"])
	userRate, _ := strconv.ParseFloat(routeDetail["rate_"], 64)
	newRate, _ := strconv.ParseFloat(validator.RequestData["rate"], 64)
	leavRate := newRate*100 - userRate*100
	if totalRate+leavRate > 10000 {
		returnData["code"] = 1003
		returnData["msg"] = "超出比例"
		this.PrintJson(returnData)
		return
	}
	set := map[string]string{}
	set["rate_"] = strconv.FormatFloat(newRate, 'f', -1, 64)
	where := map[string]string{
		"id_": routeDetail["id_"],
	}
	_, err := models.GetActModel("pay_route_detail").Update(set, where)
	if err != nil {
		returnData["code"] = 1004
		returnData["msg"] = err
		this.PrintJson(returnData)
		return
	}
	returnData["code"] = 600
	returnData["msg"] = "修改成功"
	returnData["result"] = ""
	this.PrintJson(returnData)
	return

}

/**
删除规则
*/
func (this *Subaccount) DelRuleAction() {
	returnData := make(map[string]interface{})
	routeId := this.Request.GetJPost("routeId")
	if routeId == "" {
		this.PrintErrorMessage(1002, "参数缺失")
		return
	}
	payNo := models.TempleModel.FetchColumn(db.Select{Where: map[string]interface{}{
		"id": controller.TempleId,
	}}, "pay_user_no")
	res := models.PayRouteModel.Delete(map[string]string{
		"id_":     routeId,
		"cust_no": "'" + payNo + "'",
	})
	if res <= 0 {
		returnData["code"] = 1001
		returnData["msg"] = "删除失败"
		returnData["result"] = ""
		this.PrintJson(returnData)
		return
	}
	models.PayRouteDetailModel.Delete(map[string]string{
		"route_id": routeId,
	})
	returnData["code"] = 600
	returnData["msg"] = "删除成功"
	returnData["result"] = ""
	this.PrintJson(returnData)
}

/**
获取打印机充值优惠
*/
func (this *Subaccount) DiscountListAction() {
	isFirst := 0 //是否首充
	count := models.RechargeModel.FetchColumn(db.Select{Where: map[string]interface{}{
		"user_no":    controller.UserNo,
		"pay_status": "1",
	}, Columns: "count(id)"}, "count(id)")
	if count == "0" {
		isFirst = 1
	}
	where := map[string]interface{}{}
	if isFirst == 1 {
		where["type"] = "1"
	} else {
		where["type"] = "2"
	}
	where["status"] = "1"
	data := map[string]interface{}{}
	data["discountData"] = models.GetActModel("printer_num_discount").FetchAll(db.Select{Where: where})
	data["isfirst"] = isFirst
	returnData := make(map[string]interface{})
	returnData["code"] = 600
	returnData["msg"] = "获取成功"
	returnData["result"] = data
	this.PrintJson(returnData)
}
