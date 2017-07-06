package controller

import (
	"app/api/router"
	"encoding/json"
	"net/http"
	"reflect"
	"strconv"
)

//控制器对象
type BaseController struct {
	Response *router.Response
	Request  *router.Request
}

func (this *BaseController) SetRequest(request *router.Request) {
	this.Request = request
}

func (this *BaseController) GetRequest() *router.Request {
	return this.Request
}

func (this *BaseController) SetResponse(response *router.Response) {
	this.Response = response
}

func (this *BaseController) GetResponse() *router.Response {
	return this.Response
}

func (this *BaseController) NotFound() {
	http.NotFound(this.GetResponse().W, this.GetRequest().R)
}

//输出成功信息
func (this *BaseController) PrintSuccessMessage(data interface{}) {
	//转换bool，int为字符串，统一输出
	switch reflect.TypeOf(data).Kind() {
	case reflect.Bool:
		if reflect.ValueOf(data).Bool() {
			data = "1"
		} else {
			data = "0"
		}
	case reflect.Int64:
		data = strconv.FormatInt(reflect.ValueOf(data).Int(), 10)
	case reflect.Float64:
		data = strconv.FormatFloat(reflect.ValueOf(data).Float(), 'f', -1, 64)
	case reflect.Slice:
		//如果是slice类型并且length为0，json编码后会为null，这里做一个转换，使其输出空数组
		if reflect.ValueOf(data).Len() == 0 {
			data = make([]string, 0)
		}
	}

	result := map[string]interface{}{
		"code": "0",
		"msg":  "",
		"data": data,
	}

	jsonResult, _ := json.Marshal(result)

	isJsonp := this.GetRequest().GetQuery("is_jsonp")
	if len(isJsonp) == 0 {
		this.GetResponse().SetHeader("Content-Type", "application/json; charset=utf-8")
		this.GetResponse().AppendBody(string(jsonResult))
	} else {
		callback := this.GetRequest().GetQuery("callback")
		this.GetResponse().AppendBody(callback + "(" + string(jsonResult) + ")")
	}
}

//输出错误信息
func (this *BaseController) PrintErrorMessage(code int, msg string) {
	result := make(map[string]interface{})
	result["code"] = code
	result["msg"] = msg
	result["data"] = ""

	jsonResult, _ := json.Marshal(result)

	isJsonp := this.GetRequest().GetQuery("is_jsonp")
	if len(isJsonp) == 0 {
		this.GetResponse().SetHeader("Content-Type", "application/json; charset=utf-8")
		this.GetResponse().AppendBody(string(jsonResult))
	} else {
		callback := this.GetRequest().GetQuery("callback")
		this.GetResponse().AppendBody(callback + "(" + string(jsonResult) + ")")
	}
}

//输出错误信息
func (this *BaseController) PrintJson(data interface{}) {

	jsonResult, _ := json.Marshal(data)

	isJsonp := this.GetRequest().GetQuery("is_jsonp")
	if len(isJsonp) == 0 {
		this.GetResponse().SetHeader("Content-Type", "application/json; charset=utf-8")
		this.GetResponse().AppendBody(string(jsonResult))
	} else {
		callback := this.GetRequest().GetQuery("callback")
		this.GetResponse().AppendBody(callback + "(" + string(jsonResult) + ")")
	}
}
func init() {
}
