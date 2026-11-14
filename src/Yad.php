<?php

namespace sandritsch91\yii2\yad;

use IntlDateFormatter;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FormatConverter;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\InputWidget;

/**
 * Yad renders a date, datetime or time picker based on [yad](https://github.com/Sandritsch91/yad).
 *
 * ```php
 * echo $form->field($model, 'birthday')->widget(Yad::class);
 *
 * echo Yad::widget([
 *     'name' => 'meeting',
 *     'mode' => Yad::MODE_DATETIME,
 *     'submitFormat' => true, // submit "yyyy-MM-dd HH:mm:ss", show the localized format
 *     'clientOptions' => ['minDate' => 0, 'stepMinute' => 15],
 * ]);
 * ```
 *
 * The display format follows the application's formatter ([[dateFormat]], [[timeFormat]]), so the value the
 * picker writes is the value Yii formats and parses on the server. Texts, first day of the week and the
 * like come from the yad locale matching [[language]].
 */
class Yad extends InputWidget
{
    const MODE_DATE = 'date';
    const MODE_DATETIME = 'datetime';
    const MODE_TIME = 'time';

    /**
     * Locales shipped by yad as `dist/i18n/<key>.umd.js`. English is built in and needs no file.
     */
    const LOCALES = [
        'af', 'ar', 'az', 'be', 'bg', 'bn', 'bs', 'ca', 'cs', 'cy', 'da', 'de', 'de-AT', 'de-CH', 'el',
        'en-AU', 'en-CA', 'en-GB', 'en-IE', 'en-IN', 'en-NZ', 'en-ZA', 'eo', 'es', 'es-AR', 'es-MX', 'et',
        'eu', 'fa', 'fi', 'fil', 'fo', 'fr', 'fr-BE', 'fr-CA', 'fr-CH', 'ga', 'gl', 'he', 'hi', 'hr', 'hu',
        'hy', 'id', 'is', 'it', 'it-CH', 'ja', 'ka', 'kk', 'ko', 'lt', 'lv', 'mk', 'ms', 'nb', 'nl', 'nl-BE',
        'nn', 'pl', 'pt', 'pt-BR', 'ro', 'ru', 'sk', 'sl', 'sq', 'sr', 'sr-Latn', 'sv', 'sw', 'ta', 'th',
        'tr', 'uk', 'ur', 'uz', 'vi', 'zh-CN', 'zh-HK', 'zh-TW',
    ];

    /**
     * Locale keys of yad that have no file of their own.
     */
    const LOCALE_ALIASES = [
        'no' => 'nb',
        'zh' => 'zh-CN',
        'pt-PT' => 'pt',
        'sr-Cyrl' => 'sr',
    ];

    /**
     * @var string what to pick: [[MODE_DATE]], [[MODE_DATETIME]] or [[MODE_TIME]].
     */
    public $mode = self::MODE_DATE;
    /**
     * @var bool whether to render an always visible calendar instead of a popup. The value is kept in a
     * hidden input; its `id` is the widget's input id, the calendar gets `<id>-yad`.
     */
    public $inline = false;
    /**
     * @var string|null the language whose yad locale (texts, first day of the week, ...) is used, e.g.
     * `de-CH`. It falls back to the language without region (`de`), then English.
     * Defaults to the application language.
     */
    public $language;
    /**
     * @var string|null the date format as an ICU pattern (`dd.MM.yyyy`), a `php:` format (`php:d.m.Y`) or
     * `short`, `medium`, `long`, `full`. Defaults to the formatter's `dateFormat`.
     */
    public $dateFormat;
    /**
     * @var string|null the time format, written like [[dateFormat]]. Defaults to the formatter's `timeFormat`.
     */
    public $timeFormat;
    /**
     * @var string the text between date and time in [[MODE_DATETIME]].
     */
    public $separator = ' ';
    /**
     * @var bool|array submit a machine-readable value instead of the displayed one.
     *
     * - `false` (default): the visible input is submitted, in the display format.
     * - `true`: a hidden input is submitted with `yyyy-MM-dd`, `HH:mm:ss` or `yyyy-MM-dd HH:mm:ss`; the
     *   visible input has no `name`.
     * - an array to choose the formats (ICU or `php:`), e.g.
     *   `['date' => 'php:Y-m-d', 'time' => 'HH:mm', 'separator' => 'T']`.
     */
    public $submitFormat = false;
    /**
     * @var string|null colour mode: `light`, `dark` or `auto` (follows the operating system).
     * `null` keeps yad's default (light). Has no effect with [[bootstrapTheme]].
     */
    public $theme;
    /**
     * @var bool whether to load yad's Bootstrap 5 theme. Bootstrap's stylesheet must be registered before.
     */
    public $bootstrapTheme = false;
    /**
     * @var array options for yad, see https://github.com/Sandritsch91/yad/blob/main/docs/options.md.
     * Use [[\yii\web\JsExpression]] for callbacks such as `onSelect` or `beforeShowDay`.
     * Set the formats through [[dateFormat]] and [[timeFormat]], not here, so the server formats the
     * value the same way.
     */
    public $clientOptions = [];
    /**
     * @var array DOM event handlers for the input, e.g. `['change' => 'function (e) { ... }']`.
     * yad dispatches `input` and `change` when the user changes the value of a popup picker.
     * An inline calendar has no visible input: use the `onSelect` option instead.
     */
    public $clientEvents = [];
    /**
     * @var array HTML attributes of the calendar container in [[inline]] mode.
     */
    public $containerOptions = [];

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (!in_array($this->mode, [self::MODE_DATE, self::MODE_DATETIME, self::MODE_TIME], true)) {
            throw new InvalidConfigException("Invalid mode \"$this->mode\".");
        }
        if ($this->language === null) {
            $this->language = Yii::$app->language;
        }
        $formatter = Yii::$app->formatter;
        if ($this->dateFormat === null) {
            $this->dateFormat = $formatter->dateFormat;
        }
        if ($this->timeFormat === null) {
            $this->timeFormat = $formatter->timeFormat;
        }
        if (!$this->inline && !array_key_exists('autocomplete', $this->options)) {
            $this->options['autocomplete'] = 'off';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $id = $this->options['id'];
        $dateIcu = $this->resolveIcuPattern($this->dateFormat, 'date');
        $timeIcu = $this->resolveIcuPattern($this->timeFormat, 'time');
        $value = $this->hasModel() ? Html::getAttributeValue($this->model, $this->attribute) : $this->value;
        $displayValue = $this->formatValue($value, $dateIcu, $timeIcu, $this->separator);

        $clientOptions = [
            'dateFormat' => FormatConverter::convertDateIcuToJui($dateIcu, 'date', $this->language),
            'timeFormat' => static::convertTimeIcuToYad($timeIcu),
            'separator' => $this->separator,
        ];
        if ($this->mode === self::MODE_DATETIME) {
            $clientOptions['showTime'] = true;
        } elseif ($this->mode === self::MODE_TIME) {
            $clientOptions['timeOnly'] = true;
        }
        if ($this->theme !== null) {
            $clientOptions['theme'] = $this->theme;
        }

        $submit = $this->getSubmitFormats();
        $submitValue = $submit === null
            ? $displayValue
            : $this->formatValue($value, $submit['date'], $submit['time'], $submit['separator']);
        $hiddenId = $this->inline ? $id : "$id-value";

        if ($this->inline || $submit !== null) {
            $clientOptions['altField'] = '#' . $hiddenId;
            if ($submit !== null) {
                $clientOptions['altFormat'] = FormatConverter::convertDateIcuToJui($submit['date'], 'date', $this->language);
                $clientOptions['altTimeFormat'] = static::convertTimeIcuToYad($submit['time']);
                $clientOptions['altSeparator'] = $submit['separator'];
            }
        }
        $clientOptions = ArrayHelper::merge($clientOptions, $this->clientOptions);

        if ($this->inline) {
            $containerOptions = $this->containerOptions;
            if (!isset($containerOptions['id'])) {
                $containerOptions['id'] = "$id-yad";
            }
            $html = $this->renderHidden($hiddenId, $submitValue) . Html::tag('div', '', $containerOptions);
            $targetId = $containerOptions['id'];
        } elseif ($submit !== null) {
            $html = Html::textInput(null, $displayValue, $this->options) . $this->renderHidden($hiddenId, $submitValue);
            $targetId = $id;
        } else {
            $options = $this->options;
            $options['value'] = $displayValue;
            $html = $this->hasModel()
                ? Html::activeTextInput($this->model, $this->attribute, $options)
                : Html::textInput($this->name, $displayValue, $options);
            $targetId = $id;
        }
        $this->registerClientScript($targetId, $clientOptions, $this->getDateParts($value));

        return $html;
    }

    /**
     * Returns the yad locale key for a language, or `null` for English (built in).
     *
     * @param string $language e.g. `de-CH`, `pt_br` or `zh-Hant-TW`
     * @return string|null
     */
    public static function resolveLocale($language)
    {
        $keys = static::resolveLocales($language);

        return $keys ? $keys[0] : null;
    }

    /**
     * Returns the yad locale keys for a language, most specific first: `de-CH` gives `['de-CH', 'de']`.
     *
     * @param string $language
     * @return string[] empty for English (built in) and unknown languages
     */
    public static function resolveLocales($language)
    {
        $parts = preg_split('/[-_]/', (string)$language);
        $parts[0] = strtolower($parts[0]);
        foreach ($parts as $i => $part) {
            if ($i > 0) {
                $parts[$i] = strlen($part) === 4 ? ucfirst(strtolower($part)) : strtoupper($part);
            }
        }

        $candidates = [implode('-', $parts)];
        if (count($parts) > 2) {
            $candidates[] = $parts[0] . '-' . end($parts); // zh-Hant-TW -> zh-TW
        }
        for ($n = count($parts) - 1; $n > 0; $n--) {
            $candidates[] = implode('-', array_slice($parts, 0, $n));
        }
        $keys = [];
        foreach ($candidates as $key) {
            if (isset(self::LOCALE_ALIASES[$key])) {
                $key = self::LOCALE_ALIASES[$key];
            }
            if (in_array($key, self::LOCALES, true) && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Converts an ICU time pattern into a yad (jQuery UI Timepicker) time format.
     *
     * @param string $pattern e.g. `HH:mm:ss` or `h:mm a`
     * @return string e.g. `HH:mm:ss` or `h:mm TT`
     */
    public static function convertTimeIcuToYad($pattern)
    {
        $result = '';
        $length = strlen($pattern);
        for ($i = 0; $i < $length;) {
            $char = $pattern[$i];
            if ($char === "'") {
                if ($i + 1 < $length && $pattern[$i + 1] === "'") {
                    $result .= "''";
                    $i += 2;
                    continue;
                }
                $end = strpos($pattern, "'", $i + 1);
                if ($end === false) {
                    $end = $length;
                }
                $result .= substr($pattern, $i, $end - $i) . "'";
                $i = $end + 1;
                continue;
            }
            if (!ctype_alpha($char)) {
                $result .= $char;
                $i++;
                continue;
            }
            $count = strspn($pattern, $char, $i);
            $i += $count;
            switch ($char) {
                case 'H':
                case 'k':
                    $result .= $count > 1 ? 'HH' : 'H';
                    break;
                case 'h':
                case 'K':
                    $result .= $count > 1 ? 'hh' : 'h';
                    break;
                case 'm':
                    $result .= $count > 1 ? 'mm' : 'm';
                    break;
                case 's':
                    $result .= $count > 1 ? 'ss' : 's';
                    break;
                case 'S':
                    $result .= 'l';
                    break;
                case 'a':
                case 'b':
                case 'B':
                    $result .= 'TT';
                    break;
                default:
                    // time zones and date fields are not supported by yad's time format
                    break;
            }
        }

        return trim(preg_replace('/\s{2,}/', ' ', $result));
    }

    /**
     * Turns `php:` formats and the names `short`, `medium`, `long` and `full` into an ICU pattern.
     *
     * @param string $format
     * @param string $type `date` or `time`
     * @return string
     */
    protected function resolveIcuPattern($format, $type)
    {
        if (strncmp($format, 'php:', 4) === 0) {
            return FormatConverter::convertDatePhpToIcu(substr($format, 4));
        }
        $styles = ['short' => 3, 'medium' => 2, 'long' => 1, 'full' => 0];
        if (!isset($styles[$format])) {
            return $format;
        }
        if (extension_loaded('intl')) {
            $formatter = $type === 'date'
                ? new IntlDateFormatter($this->language, $styles[$format], IntlDateFormatter::NONE)
                : new IntlDateFormatter($this->language, IntlDateFormatter::NONE, $styles[$format]);

            return $formatter->getPattern();
        }

        return FormatConverter::convertDatePhpToIcu(FormatConverter::convertDateIcuToPhp($format, $type, $this->language));
    }

    /**
     * Formats a value for the input according to [[mode]]. Values that cannot be parsed are returned as is.
     *
     * @param mixed $value a timestamp, a DateTime or a string the formatter understands
     * @param string $dateIcu
     * @param string $timeIcu
     * @param string $separator
     * @return string
     */
    protected function formatValue($value, $dateIcu, $timeIcu, $separator)
    {
        if ($value === null || $value === '') {
            return '';
        }
        $formatter = Yii::$app->formatter;
        try {
            switch ($this->mode) {
                case self::MODE_DATETIME:
                    return $formatter->asDatetime($value, $dateIcu) . $separator . $formatter->asDatetime($value, $timeIcu);
                case self::MODE_TIME:
                    return $formatter->asTime($value, $timeIcu);
                default:
                    return $formatter->asDate($value, $dateIcu);
            }
        } catch (\Exception $e) {
            return (string)$value;
        }
    }

    /**
     * Returns the value as numbers, so the picker starts with the right date and time even when the server's
     * and the browser's month names differ (e.g. without ext-intl).
     *
     * @param mixed $value
     * @return int[]|null year, month (1-12), day, hour, minute, second; `null` if empty or not parsable
     */
    protected function getDateParts($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $formatter = Yii::$app->formatter;
        try {
            switch ($this->mode) {
                case self::MODE_DATETIME:
                    $text = $formatter->asDatetime($value, 'yyyy-M-d-H-m-s');
                    break;
                case self::MODE_TIME:
                    $text = $formatter->asTime($value, 'yyyy-M-d-H-m-s');
                    break;
                default:
                    $text = $formatter->asDate($value, 'yyyy-M-d') . '-0-0-0';
            }
        } catch (\Exception $e) {
            return null;
        }

        return preg_match('/^\d+(-\d+){5}$/', $text) ? array_map('intval', explode('-', $text)) : null;
    }

    /**
     * @return array|null `date`, `time` (ICU patterns) and `separator`, or `null` when [[submitFormat]] is off
     */
    protected function getSubmitFormats()
    {
        if ($this->submitFormat === false || $this->submitFormat === null) {
            return null;
        }
        $formats = array_merge(
            ['date' => 'yyyy-MM-dd', 'time' => 'HH:mm:ss', 'separator' => ' '],
            is_array($this->submitFormat) ? $this->submitFormat : []
        );
        $formats['date'] = $this->resolveIcuPattern($formats['date'], 'date');
        $formats['time'] = $this->resolveIcuPattern($formats['time'], 'time');

        return $formats;
    }

    /**
     * Renders the hidden input that carries the submitted value.
     *
     * @param string $id
     * @param string $value
     * @return string
     */
    protected function renderHidden($id, $value)
    {
        $options = ['id' => $id, 'value' => $value];

        return $this->hasModel()
            ? Html::activeHiddenInput($this->model, $this->attribute, $options)
            : Html::hiddenInput($this->name, $value, $options);
    }

    /**
     * Registers the assets and the script that creates the picker.
     *
     * @param string $targetId id of the input (popup) or the container (inline)
     * @param array $clientOptions
     * @param int[]|null $dateParts the initial value, see [[getDateParts()]]
     */
    protected function registerClientScript($targetId, $clientOptions, $dateParts = null)
    {
        $view = $this->getView();
        $bundle = YadAsset::register($view);
        if ($this->bootstrapTheme) {
            YadBootstrap5Asset::register($view);
        }

        $locales = static::resolveLocales($this->language);
        if ($locales) {
            $bundle->addLocale($locales[0]);
        }
        // the most specific registered locale wins: a compressed bundle may hold "de" but not "de-CH"
        $lookup = [];
        foreach ($locales ?: [''] as $key) {
            $lookup[] = 'Yad.regional[' . Json::htmlEncode($key) . ']';
        }
        $lookup[] = '{}';

        $js = [
            'var el = document.getElementById(' . Json::htmlEncode($targetId) . ');',
            'if (!el) { return; }',
            'var Yad = window.yad.Yad;',
            'var locale = ' . implode(' || ', $lookup) . ';',
            'var dp = new Yad(el, Object.assign({}, locale, ' . Json::htmlEncode($clientOptions) . '));',
        ];
        if ($dateParts !== null) {
            list($y, $m, $d, $h, $i, $s) = $dateParts;
            $m--;
            $js[] = "dp.setDate(new Date($y, $m, $d, $h, $i, $s));";
        }
        if (!$this->inline) {
            foreach ($this->clientEvents as $event => $handler) {
                $js[] = 'el.addEventListener(' . Json::htmlEncode($event) . ', ' . $handler . ');';
            }
        }

        // POS_END: runs after the asset files and the markup, without requiring jQuery like POS_READY does
        $view->registerJs("(function () {\n    " . implode("\n    ", $js) . "\n})();", View::POS_END);
    }
}
