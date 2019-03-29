<?php
namespace JPush;
use InvalidArgumentException;

class Client {

    private $appKey;
    private $masterSecret;
    private $retryTimes;
    private $logFile;

    public function __construct($logFile=Config::DEFAULT_LOG_FILE, $retryTimes=Config::DEFAULT_MAX_RETRY_TIMES) {
//         if (!is_string($appKey) || !is_string($masterSecret)) {
//             throw new InvalidArgumentException("Invalid appKey or masterSecret");
//         }
        $this->appKey = '3e62e878bbc96cb9ba6288b4';
        $this->masterSecret = 'e42127d1b7fdcf13368fca2a';
        if (!is_null($retryTimes)) {
            $this->retryTimes = $retryTimes;
        } else {
            $this->retryTimes = 1;
        }
        $this->logFile = $logFile;
    }

    public function push() {
        if(YII_ENV_DEV){
            return 0;
        }
        return new PushPayload($this);
    }
    public function report() { return new ReportPayload($this); }
    public function device() { return new DevicePayload($this); }
    public function schedule() { return new SchedulePayload($this);}

    public function getAuthStr() { return $this->appKey . ":" . $this->masterSecret; }
    public function getRetryTimes() { return $this->retryTimes; }
    public function getLogFile() { return $this->logFile; }

    public function is_group() {
        $str = substr($this->appKey, 0, 6);
        return $str === 'group-';
    }
}
