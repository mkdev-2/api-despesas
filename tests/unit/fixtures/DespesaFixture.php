<?php

namespace tests\unit\fixtures;

use yii\test\ActiveFixture;

class DespesaFixture extends ActiveFixture
{
    public $modelClass = 'app\modules\financeiro\models\Despesa';
    public $dataFile = '@tests/unit/fixtures/data/despesa.php';
    
    public $depends = [
        'tests\unit\fixtures\UserFixture',
    ];
} 