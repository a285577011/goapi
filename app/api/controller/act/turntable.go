package act

import (
	"app/api/controller"
	"app/api/form"
	"app/logic"
)

type Turntable struct {
	controller.BaseController
}

func (this *Turntable) PlayAction() {
	validator := &form.Validator{
		RequestData: form.ParseParams(this.GetRequest().GetPosts()),
		Rule: map[string]interface{}{
			"tel": map[string]string{
				"rule":     "required|phone",
				"errormsg": "tel 格式错误",
			},
			"openId": map[string]string{
				"rule":     "string|min:1",
				"errormsg": "openId 范围错误",
			},
			"eventKey": map[string]string{
				"rule":     "required|string|min:1",
				"errormsg": "eventKey 范围错误",
			},
			"limitType": map[string]string{
				"rule":     "required|int|min:1",
				"errormsg": "limitType 范围错误",
			},
			"maxNum": map[string]string{
				"rule":     "required|int|min:1",
				"errormsg": "maxNum 范围错误",
			},
			"probabilityType": map[string]string{
				"rule":     "required|int|min:1",
				"errormsg": "probabilityType 范围错误",
			},
		},
	}
	if !validator.Validate() {
		this.PrintErrorMessage(1001, validator.ErrorMsg[0])
		return
	}
	logic := &logic.TurnTableLogic{EventKey: validator.RequestData["eventKey"], LimitType: validator.RequestData["limitType"], MaxNum: validator.RequestData["maxNum"], ProbabilityType: validator.RequestData["probabilityType"]}
	res := logic.Play(validator.RequestData["tel"], validator.RequestData["openId"])
	this.PrintJson(res)
}
