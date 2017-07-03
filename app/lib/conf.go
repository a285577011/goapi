package lib

import (
	"github.com/achun/tom-toml"
	"fmt"
	"io/ioutil"
	"strings"
)
var Config map[string]toml.Toml

func InitConfig(DEBUG string) {
	var dir string
	Config = make(map[string]toml.Toml)
	switch DEBUG {
	case "0":
		dir="conf/product/";
	default:
		dir="conf/dev/";
	}
	files:=getFilelist(dir);
	files2:=getFilelist("conf/common/");
	files=append(files,files2...)
	for _, v := range files {
		conf, err := toml.LoadFile(v)
		if err != nil {
		fmt.Println(err)
		return
		}
		path := strings.Split(v, ".")
		name := strings.Split(path[0], "/")
		Config[name[len(name)-1]]=conf
	}
}


//根据环境参数获取配置
func GetConfig(fileName string) toml.Toml {
	config, ok := Config[fileName]
	if !ok {
		return Config["db"]
	}
	return config;
}
func getFilelist(path string) []string{
		var paths []string
        files, _ := ioutil.ReadDir(path) //specify the current dir
        for _,file := range files{
                if file.IsDir(){
                        continue;
                }else{
                	paths=append(paths,path+file.Name());
                }
        }
        return paths;
}