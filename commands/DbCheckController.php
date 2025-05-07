<?php

namespace app\commands;

use yii\console\Controller;
use Yii;

class DbCheckController extends Controller
{
    public function actionIndex()
    {
        echo "Database DSN: " . Yii::$app->db->dsn . "\n";
        echo "Database Username: " . Yii::$app->db->username . "\n";
    }
}
