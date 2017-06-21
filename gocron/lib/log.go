package lib

import (
	"fmt"
	"log"
	"os"
	"time"
)

var ErrorLog *log.Logger

//日志记录
func Log(v ...interface{}) {
	systemTime := time.Now().Format("2006-01-02 15:04:05")

	fmt.Print(systemTime, " [GOYAF LOG] ")
	fmt.Println(v...)
}

//调试信息
func Debug(v ...interface{}) {
	systemTime := time.Now().Format("2006-01-02 15:04:05")

	fmt.Print(systemTime, " [GOYAF DEBUG] ")
	fmt.Println(v...)
}

//记录错误信息
func Error(v ...interface{}) {
	ErrorLog.Println(v)
}
func LogWrite(content string,dir string){
	systemTime := time.Now().Format("2006-01-02")
	path := "log/"+dir+"/"
	flag, err := FileExists(path)
    if flag==false {
		err := os.MkdirAll(path, 0777)
    	if err != nil {
        	fmt.Println(path)
        	fmt.Println(path)
        	return
    	}
    }
	logfile, err := os.OpenFile(path+systemTime+".log", os.O_RDWR|os.O_CREATE|os.O_APPEND, 0666)
			if err != nil {
				fmt.Printf("%s\r\n", err.Error()+"1111")
				os.Exit(-1)
			}
			defer logfile.Close()
			logger := log.New(logfile, "[Log]", log.Ldate|log.Ltime|log.Llongfile)
			logger.Println(content)

}
func FileExists(path string) (bool, error) {
    _, err := os.Stat(path)
    if err == nil { return true, nil }
    if os.IsNotExist(err) { return false, nil }
    return true, err
}
func init() {
	ErrorLog = log.New(os.Stderr, "[GOYAF ERROR] ", log.Ldate|log.Ltime|log.Lshortfile)
}