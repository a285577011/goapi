#!/bin/bash

programDir="/mnt/hgfs/www/golang/src"

$programDir/tool/compile.sh

#$programDir/tool/testdeploy/rm.sh

cd $programDir/yxapi/

tar -jcf yx-go-api-linux64.tar.bz yx-go-api runapi.sh

scp $programDir/yxapi/yx-go-api-linux64.tar.bz root@43.243.129.55:/home/wwwroot/default

$programDir/tool/test.sh
