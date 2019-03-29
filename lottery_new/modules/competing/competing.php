<?php

namespace app\modules\competing;

/**
 * competing module definition class
 */
class competing extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\competing\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        \Yii::$app->db->enableSlaves = false;
        parent::init();

        // custom initialization code goes here
    }
}
