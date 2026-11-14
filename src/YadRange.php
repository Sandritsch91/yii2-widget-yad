<?php

namespace sandritsch91\yii2\yad;

use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\InputWidget;

/**
 * YadRange renders two linked pickers for a start and an end, using yad's `Yad.range()`: the end cannot be
 * before the start, the start cannot be after the end (including the time in [[MODE_DATETIME]]), and both
 * calendars highlight the range.
 *
 * ```php
 * echo $form->field($model, 'start')->widget(YadRange::class, [
 *     'attributeTo' => 'end',
 *     'clientOptions' => ['minDate' => 0, 'numberOfMonths' => 2],
 * ]);
 *
 * echo YadRange::widget(['name' => 'from', 'nameTo' => 'to', 'mode' => YadRange::MODE_DATETIME]);
 * ```
 *
 * Every property of [[Yad]] applies to both pickers. [[options]], [[name]], [[value]] and [[attribute]] belong
 * to the start, [[optionsTo]], [[nameTo]], [[valueTo]] and [[attributeTo]] to the end.
 */
class YadRange extends Yad
{
    /**
     * @var string|null the model attribute of the end. Required with [[model]].
     */
    public $attributeTo;
    /**
     * @var string|null the input name of the end. Required without [[model]].
     */
    public $nameTo;
    /**
     * @var mixed the value of the end, without [[model]].
     */
    public $valueTo;
    /**
     * @var array HTML attributes of the end input. The `class` of [[options]] is used unless set here.
     * The `id` defaults to the attribute's input id or `<id>-to`.
     */
    public $optionsTo = [];
    /**
     * @var string how the two pickers are put together, e.g.
     * `'<div class="input-group">{from}<span class="input-group-text">–</span>{to}</div>'` for Bootstrap.
     */
    public $template = '{from} – {to}';

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if ($this->hasModel() && $this->attributeTo === null) {
            throw new InvalidConfigException("Either 'name' and 'nameTo', or 'model', 'attribute' and 'attributeTo' must be set.");
        }
        if (!$this->hasModel() && $this->nameTo === null) {
            throw new InvalidConfigException("Either 'name' and 'nameTo', or 'model', 'attribute' and 'attributeTo' must be set.");
        }

        if (!isset($this->optionsTo['class']) && isset($this->options['class'])) {
            $this->optionsTo['class'] = $this->options['class'];
        }
        if (!isset($this->optionsTo['id'])) {
            $this->optionsTo['id'] = $this->hasModel()
                ? Html::getInputId($this->model, $this->attributeTo)
                : $this->options['id'] . '-to';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $shared = $this->getSharedConfig();
        $containerTo = $this->containerOptions;
        unset($containerTo['id']);

        $from = Yad::widget(array_merge($shared, $this->hasModel()
            ? ['model' => $this->model, 'attribute' => $this->attribute]
            : ['name' => $this->name, 'value' => $this->value], [
            'options' => $this->options,
            'containerOptions' => $this->containerOptions,
        ]));
        $to = Yad::widget(array_merge($shared, $this->hasModel()
            ? ['model' => $this->model, 'attribute' => $this->attributeTo]
            : ['name' => $this->nameTo, 'value' => $this->valueTo], [
            'options' => $this->optionsTo,
            'containerOptions' => $containerTo,
        ]));

        $this->registerRangeScript(
            $this->getTargetId($this->options['id'], $this->containerOptions),
            $this->getTargetId($this->optionsTo['id'], $containerTo)
        );

        return strtr($this->template, ['{from}' => $from, '{to}' => $to]);
    }

    /**
     * @return array the [[Yad]] properties both pickers share
     */
    protected function getSharedConfig()
    {
        $properties = array_diff(
            array_keys(get_class_vars(Yad::class)),
            array_keys(get_class_vars(InputWidget::class)),
            ['containerOptions']
        );
        $config = ['view' => $this->getView()];
        foreach ($properties as $property) {
            $config[$property] = $this->$property;
        }

        return $config;
    }

    /**
     * @param string $inputId
     * @param array $containerOptions
     * @return string the id of the element yad is attached to
     */
    protected function getTargetId($inputId, $containerOptions)
    {
        if (!$this->inline) {
            return $inputId;
        }

        return isset($containerOptions['id']) ? $containerOptions['id'] : "$inputId-yad";
    }

    /**
     * Links the two pickers. It runs after the pickers' own scripts, which are registered before.
     *
     * @param string $fromId
     * @param string $toId
     */
    protected function registerRangeScript($fromId, $toId)
    {
        $js = <<<JS
(function () {
    var Yad = window.yad.Yad;
    var from = document.getElementById(%s);
    var to = document.getElementById(%s);
    if (!from || !to || !Yad.getInstance(from) || !Yad.getInstance(to)) { return; }
    // Yad.range() clears the selection of an inline end calendar: keep the end from the server
    var end = Yad.getInstance(to).getDate();
    var range = Yad.range(from, to);
    if (end && !range.to.getDate()) { range.to.setDate(end); }
})();
JS;
        $this->getView()->registerJs(sprintf($js, Json::htmlEncode($fromId), Json::htmlEncode($toId)), View::POS_END);
    }
}
