<?php

namespace sandritsch91\yii2\yad\tests;

use sandritsch91\yii2\yad\YadRange;
use Yii;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;

class YadRangeTest extends TestCase
{
    public function testModel(): void
    {
        $model = new DynamicModel(['start' => '2026-11-20', 'end' => '2026-11-27']);
        $model->addRule(['start', 'end'], 'safe');
        $html = YadRange::widget([
            'model' => $model,
            'attribute' => 'start',
            'attributeTo' => 'end',
            'options' => ['class' => 'form-control'],
            'clientOptions' => ['minDate' => 0],
        ]);

        $this->assertMatchesRegularExpression(
            '#^<input type="text" id="dynamicmodel-start" class="form-control" name="DynamicModel\[start\]" value="20.11.2026" autocomplete="off"> – '
            . '<input type="text" id="dynamicmodel-end" class="form-control" name="DynamicModel\[end\]" value="27.11.2026" autocomplete="off">$#',
            $html
        );

        $scripts = $this->registeredScripts();
        $this->assertCount(3, $scripts);
        $this->assertStringContainsString('document.getElementById("dynamicmodel-start")', $scripts[0]);
        $this->assertStringContainsString('"minDate":0', $scripts[0]);
        $this->assertStringContainsString('document.getElementById("dynamicmodel-end")', $scripts[1]);
        $this->assertStringContainsString('"minDate":0', $scripts[1]);
        // the link comes after both pickers exist
        $this->assertStringContainsString('var from = document.getElementById("dynamicmodel-start");', $scripts[2]);
        $this->assertStringContainsString('var to = document.getElementById("dynamicmodel-end");', $scripts[2]);
        $this->assertStringContainsString('var range = Yad.range(from, to);', $scripts[2]);
    }

    public function testNamesTemplateAndSharedOptions(): void
    {
        $html = YadRange::widget([
            'name' => 'from',
            'nameTo' => 'to',
            'value' => '2026-11-20 08:00:00',
            'valueTo' => '2026-11-20 17:30:00',
            'id' => 'trip',
            'mode' => YadRange::MODE_DATETIME,
            'submitFormat' => true,
            'optionsTo' => ['placeholder' => 'End'],
            'template' => '<div class="input-group">{from}<span class="input-group-text">–</span>{to}</div>',
        ]);

        $this->assertStringStartsWith('<div class="input-group"><input type="text" id="trip" ', $html);
        $this->assertStringContainsString('<input type="hidden" id="trip-value" name="from" value="2026-11-20 08:00:00">', $html);
        $this->assertStringContainsString('<span class="input-group-text">–</span><input type="text" id="trip-to" value="20.11.2026 17:30" placeholder="End"', $html);
        $this->assertStringContainsString('<input type="hidden" id="trip-to-value" name="to" value="2026-11-20 17:30:00">', $html);

        $scripts = $this->registeredScripts();
        $this->assertStringContainsString('"showTime":true', $scripts[0]);
        $this->assertStringContainsString('"showTime":true', $scripts[1]);
        $this->assertStringContainsString('"altField":"#trip-to-value"', $scripts[1]);
        $this->assertStringContainsString('getElementById("trip-to")', $scripts[2]);
    }

    public function testInline(): void
    {
        $html = YadRange::widget([
            'name' => 'from',
            'nameTo' => 'to',
            'valueTo' => '2026-11-27',
            'id' => 'stay',
            'inline' => true,
            'containerOptions' => ['id' => 'stay-calendar', 'class' => 'calendar'],
        ]);

        $this->assertStringContainsString('<div id="stay-calendar" class="calendar"></div>', $html);
        $this->assertStringContainsString('<input type="hidden" id="stay-to" name="to" value="27.11.2026">', $html);
        $this->assertStringContainsString('<div id="stay-to-yad" class="calendar"></div>', $html);

        $link = $this->registeredScripts()[2];
        $this->assertStringContainsString('var from = document.getElementById("stay-calendar");', $link);
        $this->assertStringContainsString('var to = document.getElementById("stay-to-yad");', $link);
        $this->assertStringContainsString('if (end && !range.to.getDate()) { range.to.setDate(end); }', $link);
    }

    public function testEndIsRequired(): void
    {
        $this->expectException(InvalidConfigException::class);
        YadRange::widget(['name' => 'from']);
    }

    public function testEndAttributeIsRequired(): void
    {
        $model = new DynamicModel(['start' => null]);
        $this->expectException(InvalidConfigException::class);
        YadRange::widget(['model' => $model, 'attribute' => 'start']);
    }

    /**
     * @return string[] the scripts registered at the end of the body, in order
     */
    private function registeredScripts(): array
    {
        return array_values(Yii::$app->view->js[\yii\web\View::POS_END]);
    }
}
