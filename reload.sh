#!/bin/bash
passwd="5523\{fC7\}wC0\\\$sA1%"
/usr/bin/expect <<-EOF
set time 5
spawn ssh -p 22 root@43.243.129.55
expect {
"*yes/no" { send "yes\r"; exp_continue }
"*password:" { send "$passwd\r" }
}
expect "*#"
send "tar -xf /home/wwwroot/default/yx-go-api-linux64.tar.bz -C /home/wwwroot/default\r"
send "chown -R root:root /home/wwwroot/default/yx-go-api\r"
send "chmod a+x /home/wwwroot/default/yx-go-api\r"
send "chmod a+x /home/wwwroot/default/runapi.sh\r"
send "/usr/bin/sh /home/wwwroot/default/runapi.sh restart\r"
send "exit\r"

expect eof
