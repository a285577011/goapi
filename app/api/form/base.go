package form

import (
	//"fmt"
	"strconv"
	"strings"
)

//表单基类
type Validator struct {
	RequestData map[string]string
	Rule        map[string]interface{}
	ErrorMsg    []string
	RuleType    *RuleType
}

//验证规则类型类
type RuleType struct {
	Required bool
	Int      bool
	String   bool
	Float    bool
	Range    map[string]int
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
func (this *Validator) Validate() bool {
	if len(this.Rule) == 0 { //无请求数据或无验证规则
		return true
	}
	var rules []string
	Error := false
	this.initRuleType()
	this.RuleType.Range = make(map[string]int)
	//ruleType.Range = make(map[string]int)
	for k, v := range this.Rule {
		switch v.(type) {
		case map[string]string:
			this.resetRuleType()
			v := v.(map[string]string)
			if _, ok := v["rule"]; !ok {
				continue
			}
			rules = strings.Split(v["rule"], "|")
			this.setRuleType(rules)
			if this.RuleType.Required == true {
				errorMsg := "fields is required"
				_, ok := v["errormsg"]
				if ok {
					errorMsg = v["errormsg"]
				}
				if _, ok := this.RequestData[k]; !ok || len(this.RequestData[k]) == 0 { //必须验证
					Error = true
					this.ErrorMsg = append(this.ErrorMsg, errorMsg)
					continue
				}
			}
			if this.RuleType.Required == false {
				if _, ok := this.RequestData[k]; !ok { //非必须验证
					continue
				}
			}
			if this.RuleType.Int {
				vc, err := strconv.Atoi(this.RequestData[k])
				errorMsg := "fields is no int"
				_, ok := v["errormsg"]
				if ok {
					errorMsg = v["errormsg"]
				}
				if err != nil {
					Error = true
					this.ErrorMsg = append(this.ErrorMsg, errorMsg)
				} else if !this.ValidateInt(vc) {
					this.ErrorMsg = append(this.ErrorMsg, errorMsg)
					Error = true
				}
			}
			if this.RuleType.String {
				errorMsg := "fields is no string"
				_, ok := v["errormsg"]
				if ok {
					errorMsg = v["errormsg"]
				}
				if !this.ValidateString(this.RequestData[k]) {
					this.ErrorMsg = append(this.ErrorMsg, errorMsg)
					Error = true
				}
			}
			if this.RuleType.Float {
				vc, err := strconv.ParseFloat(this.RequestData[k], 64)
				errorMsg := "fields is no float"
				_, ok := v["errormsg"]
				if ok {
					errorMsg = v["errormsg"]
				}
				if err != nil {
					Error = true
					this.ErrorMsg = append(this.ErrorMsg, errorMsg)
				} else if !this.ValidateFloat(vc) {
					this.ErrorMsg = append(this.ErrorMsg, errorMsg)
					Error = true
				}
			}
			break

		}
	}
	return Error == false
}
func (this *Validator) initRuleType() {
	this.RuleType = &RuleType{
		Required: false,
		Int:      false,
		String:   false,
		Float:    false,
	}
}
func (this *Validator) resetRuleType() {
	this.RuleType.Required = false
	this.RuleType.Int = false
	this.RuleType.String = false
	this.RuleType.Float = false
	this.RuleType.Range = make(map[string]int)
}
func (this *Validator) setRuleType(rules []string) {
	if len(rules) > 0 {
		for _, vc := range rules {
			switch {
			case vc == "int":
				this.RuleType.Int = true
				break
			case vc == "string":
				this.RuleType.String = true
				break
			case vc == "required":
				this.RuleType.Required = true
				break
			case vc == "float":
				this.RuleType.Float = true
				break
			case strings.Index(vc, "min:") == 0:
				minData := strings.Split(vc, ":")
				if len(minData) != 2 {
					continue
				}
				min, err := strconv.Atoi(minData[1])
				if err != nil {
					continue
				}
				this.RuleType.Range["min"] = min
				break
			case strings.Index(vc, "max:") == 0:
				maxData := strings.Split(vc, ":")
				if len(maxData) != 2 {
					continue
				}
				max, err := strconv.Atoi(maxData[1])
				if err != nil {
					continue
				}
				this.RuleType.Range["max"] = max
				break
			}
		}
	}
}
func (this *Validator) ValidateInt(value int) bool {
	res := true
	if _, ok := this.RuleType.Range["min"]; ok {
		if value < this.RuleType.Range["min"] {
			res = false
		}
	}
	if _, ok := this.RuleType.Range["max"]; ok {
		if value > this.RuleType.Range["max"] {
			res = false
		}
	}
	return res
}
func (this *Validator) ValidateString(value string) bool {
	res := true
	length := len([]rune(value))
	if _, ok := this.RuleType.Range["min"]; ok {
		if length < this.RuleType.Range["min"] {
			res = false
		}
	}
	if _, ok := this.RuleType.Range["max"]; ok {
		if length > this.RuleType.Range["max"] {
			res = false
		}
	}
	return res
}
func (this *Validator) ValidateFloat(value float64) bool {
	res := true
	if _, ok := this.RuleType.Range["min"]; ok {
		min := float64(this.RuleType.Range["min"])
		if value < min {
			res = false
		}
	}
	if _, ok := this.RuleType.Range["max"]; ok {
		max := float64(this.RuleType.Range["max"])
		if value > max {
			res = false
		}
	}
	return res
}
