package act

import (
	"app/api/controller"
	"app/api/form"
)

type Index struct {
	controller.BaseController
}

func (this *Index) IndexAction() {
	validator := &form.Validator{
		RequestData: form.ParseParams(this.GetRequest().GetQuerys()),
		Rule: map[string]interface{}{
			"test": map[string]string{
				"rule":     "required|int|min:1|max:",
				"errormsg": "test 范围错误",
			},
			"test2": map[string]string{
				"rule": "string|min:10|max:1000",
				//"errormsg": "test2 范围错误",
			},
			"test3": map[string]string{
				"rule": "float|min:10|max:1000",
				//"errormsg": "test2 范围错误",
			},
		},
	}
	if !validator.Validate() {
		//this.PrintErrorMessage(1000, "我错了")
		this.PrintErrorMessage(1000, validator.ErrorMsg[0])
	}
	//this.PrintErrorMessage(1000, "我错了")
}
