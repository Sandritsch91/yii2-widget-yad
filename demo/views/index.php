<?php

/** @var yii\web\View $this */

use sandritsch91\yii2\yad\Yad;
use sandritsch91\yii2\yad\YadRange;
use yii\base\DynamicModel;
use yii\helpers\Html;
use yii\web\JsExpression;

$model = new DynamicModel([
    'birthday' => '1991-05-17',
    'meeting' => '2026-11-20 15:00:00',
    'from' => '2026-11-20',
    'to' => '2026-11-27',
]);
$model->addRule(['birthday', 'meeting', 'from', 'to'], 'safe');
$model->load(Yii::$app->request->post());

$this->beginPage();
?>
<!doctype html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="utf-8">
    <title>yii2-widget-yad demo</title>
    <?php $this->head() ?>
    <style>
        body { font: 15px system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; }
        .row { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; margin-bottom: .25rem; }
        pre { background: #f3f4f6; padding: .75rem; }
    </style>
</head>
<body>
<?php $this->beginBody() ?>
<h1>yii2-widget-yad</h1>
<p>Language: <?= Html::encode(Yii::$app->language) ?> ·
    <a href="?lang=de-CH">de-CH</a> · <a href="?lang=en-US">en-US</a> · <a href="?lang=fr">fr</a> · <a href="?lang=ja">ja</a></p>

<?php if (Yii::$app->request->isPost): ?>
    <pre id="posted"><?= Html::encode(print_r(Yii::$app->request->post(), true)) ?></pre>
<?php endif; ?>

<?= Html::beginForm('', 'post') ?>
<div class="row">
    <label for="birthday">Date (model)</label>
    <?= Yad::widget(['model' => $model, 'attribute' => 'birthday', 'options' => ['id' => 'birthday'],
        'clientOptions' => ['changeMonth' => true, 'changeYear' => true, 'yearRange' => '1900:c+0']]) ?>
</div>
<div class="row">
    <label for="meeting">Datetime, ISO submitted</label>
    <?= Yad::widget(['model' => $model, 'attribute' => 'meeting', 'mode' => Yad::MODE_DATETIME,
        'submitFormat' => true, 'options' => ['id' => 'meeting'],
        'clientOptions' => ['stepMinute' => 15, 'controlType' => 'select'],
        'clientEvents' => ['change' => 'function (e) { document.getElementById("log").textContent = "change: " + e.target.value; }']]) ?>
    <div id="log"></div>
</div>
<div class="row">
    <label for="at">Time</label>
    <?= Yad::widget(['name' => 'at', 'value' => '08:30', 'mode' => Yad::MODE_TIME, 'id' => 'at', 'theme' => 'dark']) ?>
</div>
<div class="row">
    <label for="inline">Inline, no weekends</label>
    <?= Yad::widget(['name' => 'inline', 'value' => '2026-11-18', 'inline' => true, 'id' => 'inline',
        'clientOptions' => ['beforeShowDay' => new JsExpression('yad.Yad.noWeekends'), 'showWeek' => true]]) ?>
</div>
<div class="row">
    <label for="from">Range, ISO submitted</label>
    <?= YadRange::widget(['model' => $model, 'attribute' => 'from', 'attributeTo' => 'to',
        'submitFormat' => true, 'options' => ['id' => 'from'], 'optionsTo' => ['id' => 'to'],
        'clientOptions' => ['numberOfMonths' => 2]]) ?>
</div>
<div class="row">
    <label>Inline range</label>
    <?= YadRange::widget(['name' => 'stayFrom', 'nameTo' => 'stayTo', 'value' => '2026-11-18', 'valueTo' => '2026-11-24',
        'id' => 'stay', 'inline' => true, 'template' => '<div style="display:flex;gap:1rem">{from}{to}</div>']) ?>
</div>
<button type="submit">Submit</button>
<?= Html::endForm() ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
