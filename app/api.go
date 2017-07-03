package main

import (
"app/core"
"app/lib"
)
const DEBUG string = "1"
func init() {
	lib.InitConfig(DEBUG);//初始化配置

}
func main() {
      core.HttpApp.Run()
}



