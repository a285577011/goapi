package lib

import (
	"net/http"
	"net/url"
	"strings"
)

//请求对象
type Request struct {
	Module     string
	Controller string
	Action     string
	R          *http.Request
	Params     url.Values
}

//获取GET参数
func (this *Request) GetQuery(key string, defaultValue ...string) string {
	this.R.ParseForm()
	value := this.R.Form.Get(key)
	if len(value) == 0 && len(defaultValue) > 0 {
		return defaultValue[0]
	}
	return value
}

//获取所有GET参数
func (this *Request) GetQuerys() url.Values {
	queryForm, err := url.ParseQuery(this.R.URL.RawQuery)
	if err == nil {
		return queryForm
	}
	return make(url.Values)
}

//获取post参数
func (this *Request) GetPost(key string, defaultValue ...string) string {
	if this.R.Method != "POST" {
		return ""
	}

	this.R.ParseMultipartForm(32 << 20)
	values := this.R.MultipartForm.Value[key]
	if len(values) > 0 && len(values[0]) > 0 {
		return values[0]
	}

	if len(defaultValue) > 0 {
		return defaultValue[0]
	}
	return ""
}

//获取所有post参数
func (this *Request) GetPosts() url.Values {
	//todo 关于Content-Type: multipart/form-data;还需处理
	this.R.ParseForm()
	return this.R.PostForm
}

//获取所有url里的参数
func (this *Request) GetParam(key string, defaultValue ...string) string {
	this.parseParamsForm()

	values := this.Params[key]
	if len(values) > 0 && len(values[0]) > 0 {
		return values[0]
	}

	if len(defaultValue) > 0 {
		return defaultValue[0]
	}
	return ""
}

//获取所有url里所有的参数
func (this *Request) GetParams() url.Values {
	this.parseParamsForm()
	return this.Params
}

//分析url中形如/id/1/name/age/这样的参数
func (this *Request) parseParamsForm() {
	requestUri := this.R.RequestURI
	uriSplits := strings.Split(requestUri, "/")[4:]

	this.Params = make(url.Values)

	length := len(uriSplits) - 1
	for i := 0; i < length; i = i + 2 {
		this.Params[uriSplits[i]] = []string{uriSplits[i+1]}
	}
}

//获取cookie参数
func (this *Request) GetCookie(key string, defaultValue ...string) string {
	//todo 这里还要测试cookie的有效性
	cookie, err := this.R.Cookie(key)
	if err != nil {
		if len(defaultValue) > 0 {
			return defaultValue[0]
		}
		return ""
	}

	return cookie.Value
}

//获取header参数
func (this *Request) GetHeader(key string, defaultValue ...string) string {
	headerValue := this.R.Header.Get(key)
	if len(headerValue) > 0 {
		return headerValue
	}

	if len(defaultValue) > 0 {
		return defaultValue[0]
	}
	return ""
}

//获取客户端ip
func (this *Request) GetClientIp() string {
	//Debug(this.R.RemoteAddr)
	ip := strings.Split(this.R.RemoteAddr, ":")[0]
	ip = strings.TrimLeft(ip, "[")
	if len(ip) == 0 {
		ip = "127.0.0.1"
	}
	return ip
}

func init() {
	//Debug("init goyaf request")
}
