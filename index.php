<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\HolidaySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('backend', 'Holidays');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="holiday-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('backend', 'Create Holiday'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php \yii\widgets\Pjax::begin(['id' => 'pjax-Holiday-grid-data']); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'id' => 'Holiday-grid-data',
        'filterModel' => $searchModel,
        'rowOptions' => function ($model) {
            return ['class' => ' stateCritical '];
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
//            'id',
            'name',
            [
                'header' => 'Date',
                'attribute' => 'date',
                'value' => function ($data) {
                    return date(Yii::$app->MyComponent->dateFormatDbToFrontend(), strtotime($data->date));
                }

            ],
            [
                'header' => 'Reuse next year',
                'attribute' => 'reuse',
                'value' => function ($data) {
                    if($data->reuse==0){
                        return 'No';
                    }
                    else{
                        return 'Yes';
                    }
                }

            ],

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
    <?php \yii\widgets\Pjax::end(); ?>

    <script>
        var input;
        var submit_form = false;
        var filter_selector = '#Holiday-grid-data-filters input';

        $("body").on('beforeFilter', "#Holiday-grid-data", function (event) {
            return submit_form;
        });

        $("body").on('afterFilter', "#Holiday-grid-data", function (event) {
            submit_form = false;
        });

        $(document)
            .off('keydown.yiiGridView change.yiiGridView', filter_selector)
            .on('keyup', filter_selector, function () {
                id = $(this).attr('id');

                if (submit_form === false) {
                    submit_form = true;
                    timer = setTimeout(function () {
                        $("#Holiday-grid-data").yiiGridView("applyFilter");
                    }, 2000);
                }
            })
            .on('pjax:success', function () {
                $('#' + id).focus();
                tmpStr = $('#' + id).val();
                $('#' + id).val('');
                $('#' + id).val(tmpStr);
            });

    </script>


</div>
