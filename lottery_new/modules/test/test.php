<?php
namespace app\modules\test;

class Test extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\test\controllers';

    public function init()
    {
    	//测试
        parent::init();
        $this->SetContainer([
            'app\modules\test\services\ITestService'=>'app\modules\test\services\TestService',
        	'app\modules\platform\services\IPlatformServices'=>'app\modules\platform\services\PlatformServices',
        		
        ]);
        // \Yii::$container->set('app\modules\frontend\services\IUserService', 'app\modules\frontend\services\UserServiceImpl');
    }
    
    public function behaviors() {
        return
        [
            "OpenApiFilter" => [
                "class" => 'app\modules\core\filters\OpenApiFilter',
                'only' => [
                    'test/l',
                ],
            ]
        ];
    }
    
    
    private function SetContainer($relation)
    {
        foreach ($relation as $key => $value)
        {
            \Yii::$container->set($key,$value);
        }
    }
}