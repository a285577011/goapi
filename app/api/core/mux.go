package core

import (
	"app/api/router"
	//"app/lib"
	"fmt"
	"net/http"
	"reflect"
	"strings"
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

	is404 := true
	var finalController reflect.Value
	Routes := router.GetRouters() //初始化路由
	for path, controller := range Routes {
		if strings.Index(r.RequestURI, path) == 0 {
			finalController = reflect.New(reflect.ValueOf(controller).Type())
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
		defer func() {
			if r := recover(); r != nil {
				fmt.Println(r)
				//lib.LogWrite(r.(string), "panic")
				//newPHC.MethodByName("SetRequest").Call(params)
				//newPHC.MethodByName("SetResponse").Call(responseParams)

				//recoverParams := make([]reflect.Value, 1)
				//recoverParams[0] = reflect.ValueOf(r)
				//newPHC.MethodByName("ErrorAction").Call(recoverParams)
				//response.Response()
			}
		}()
		//}

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
