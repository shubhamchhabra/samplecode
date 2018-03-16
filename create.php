<?php

use yii\helpers\Html;

$this->title = Yii::t('backend', 'Create Holiday');
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Holidays'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="holiday-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
