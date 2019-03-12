package core

import (
	"encoding/json"
	"fmt"
	"net/http"
	"reflect"
	"strings"
	"yxapi/api/lib"
	"yxapi/api/router"
	//"log"
)

//默认路由
type Mux struct{}

func (p *Mux) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	//记录请求
	//Log("access: " + r.RemoteAddr + " " + r.Method + " " + r.RequestURI)
	uriSplits := strings.Split(r.RequestURI, "/")
	if len(uriSplits) < 4 {
		http.NotFound(w, r)
		return
	}
	if strings.Index(uriSplits[3], "?") != -1 {
		uriSplits3 := strings.Split(uriSplits[3], "?")
		uriSplits[3] = uriSplits3[0]
	}
	is404 := true
	var finalController reflect.Value
	Routes := router.GetRouters() //初始化路由
	for path, controller := range Routes {
		if strings.Index(r.RequestURI, path) == 0 {
			finalController = reflect.New(reflect.TypeOf(controller))
			is404 = false
			break
		}
	}

	if is404 {
		http.NotFound(w, r)
		return
	}

	request := &router.Request{
		Module:     uriSplits[1],
		Controller: uriSplits[2],
		Action:     uriSplits[3],
		R:          r,
	}
	params := make([]reflect.Value, 1)
	params[0] = reflect.ValueOf(request)
	finalController.MethodByName("SetRequest").Call(params)

	response := &router.Response{
		W: w,
	}
	responseParams := make([]reflect.Value, 1)
	responseParams[0] = reflect.ValueOf(response)
	finalController.MethodByName("SetResponse").Call(responseParams)
	action := finalController.MethodByName(strings.Title(uriSplits[3]) + "Action")
	if action.IsValid() {
		//检测是否有设置panic处理控制器
		//if panicHandleController != nil {
		//newPHC := reflect.New(reflect.ValueOf(panicHandleController).Type())
		if false {
			defer func() {
				if r := recover(); r != nil {
					lib.LogWrite(r, "panic")
					sysError(w)
					return
					//newPHC.MethodByName("SetRequest").Call(params)
					//newPHC.MethodByName("SetResponse").Call(responseParams)

					//recoverParams := make([]reflect.Value, 1)
					//recoverParams[0] = reflect.ValueOf(r)
					//newPHC.MethodByName("ErrorAction").Call(recoverParams)
					//response.Response()
				}
			}()
		}

		//检测是否有Init方法
		init := finalController.MethodByName("Init")
		if init.IsValid() {
			if init.Call(nil)[0].Bool() == false {
				return
			}
		}

		action.Call(nil)
		response.Response()
		return
	}

	http.NotFound(w, r)
	return
}

//系统错误
func sysError(w http.ResponseWriter) {
	result := make(map[string]interface{})
	result["code"] = 1000
	result["msg"] = "系统错误,请稍后再试"
	result["data"] = ""

	jsonResult, _ := json.Marshal(result)
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	fmt.Fprintln(w, string(jsonResult))
}
