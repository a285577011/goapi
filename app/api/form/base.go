package form

import (
//"strconv"
)

//表单基类
type Base struct {
}

/**格式化表单获取的数据
 */
func ParseParams(params map[string][]string) map[string]string {
	parseParams := make(map[string]string)
	for key, value := range params {
		parseParams[key] = value[0]
	}
	return parseParams
}
