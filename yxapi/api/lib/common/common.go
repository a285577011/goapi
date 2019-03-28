package common

import (
	"encoding/json"
	"strconv"
)

// 根据页码，页码大小计算offset
func CountOffse(page string, pageSize string) int {
	if page == "" {
		page = "1"
	}
	if pageSize == "" {
		pageSize = "10"
	}
	pageInt, _ := strconv.Atoi(page)
	pageSizeInt, _ := strconv.Atoi(pageSize)
	offset := (pageInt - 1) * pageSizeInt
	return offset
}
func Stripslashes(str string) string {
	dstRune := []rune{}
	strRune := []rune(str)
	strLenth := len(strRune)
	for i := 0; i < strLenth; i++ {
		if strRune[i] == []rune{'\\'}[0] {
			i++
		}
		dstRune = append(dstRune, strRune[i])
	}
	return string(dstRune)
}
func IsJson(str string) bool {
	var js json.RawMessage
	return json.Unmarshal([]byte(str), &js) == nil
}

/**
格式化map[string]string->map[string]interface{}
*/
func FormatmssTomst(data map[string]string) map[string]interface{} {
	netData := map[string]interface{}{}
	for kcc, vcc := range data {
		netData[kcc] = vcc
	}
	return netData
}
