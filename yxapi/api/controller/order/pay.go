package order

import (
	"yxapi/api/controller"
	//"yxapi/api/lib/db"
	//"yxapi/api/models"
	//"fmt"
	//"strconv"
	"yxapi/api/form"
	//"yxapi/api/lib/db"
	"yxapi/api/logic"
	//"yxapi/api/models"
	"fmt"
	"github.com/bitly/go-simplejson"
	"yxapi/api/lib/httplib"
)

type Pay struct {
	controller.BaseController
}

/**
打印机充值支付
*/
func (this *Pay) PayRechargeAction() {
	if !this.CheckIslogin() {
		this.PrintErrorMessage(402, "请登录")
		return
		//controller.UserNo = "hyy"
	}
	validator := &form.Validator{
		//RequestData: form.ParseParams(this.Request.GetQuerys()),
		RequestData: this.Request.GetJPosts(),
		Rule: map[string]interface{}{ //required,int,string,flaot,range,phone,
			"orderSn": map[string]string{
				"rule":     "required|string",
				"errormsg": "订单编号,范围错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	pdata := validator.RequestData
	orderLogic := &logic.OrderLogic{}
	returnData := orderLogic.PayRechage(pdata["orderSn"])
	this.PrintJson(returnData)
}
func (this *Pay) TestAction() {
	str := httplib.Post("http://m.yixuanchina.cn/api/temple/index/banner")
	str.Param("type", "1")
	jsonstr, _ := str.String()
	js, _ := simplejson.NewJson([]byte(jsonstr))
	resData, _ := js.Get("result").Get("banner").Array()
	for _, v := range resData {
		if each_map, ok := v.(map[string]interface{}); ok {

			fmt.Println(each_map["img"])
		}
	}
	//fmt.Println(js.Get("code").Int())
	//f//mt.Println(js.Get("msg").String())
	//fmt.Println(js.Get("result").Map())
}
