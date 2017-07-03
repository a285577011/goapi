package db

import (
	"fmt"
	"os"
	"time"
)

//日志记录
func Log(v ...interface{}) {
	systemTime := time.Now().Format("2006-01-02 15:04:05")

	fmt.Print(systemTime, " Log: ")
	fmt.Println(v...)
}

//调试信息
func Debug(v ...interface{}) {
	systemTime := time.Now().Format("2006-01-02 15:04:05")

	fmt.Print(systemTime, " Debug: ")
	fmt.Println(v...)
}

//检查错误
func CheckError(err error) {
	if err != nil {
		systemTime := time.Now().Format("2006-01-02 15:04:05")

		fmt.Fprintf(os.Stderr, "%s Fatal error: %s", systemTime, err.Error())
		os.Exit(1)
	}
}
