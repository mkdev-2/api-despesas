<?php

namespace tests\unit\fixtures;

use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = 'app\modules\usuarios\models\User';
    public $dataFile = '@tests/unit/fixtures/data/user.php';
}
