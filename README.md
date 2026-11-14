# yii2-widget-yad

[![Tests](https://github.com/Sandritsch91/yii2-widget-yad/actions/workflows/test.yml/badge.svg)](https://github.com/Sandritsch91/yii2-widget-yad/actions/workflows/test.yml)
[![Packagist](https://img.shields.io/packagist/v/sandritsch91/yii2-widget-yad)](https://packagist.org/packages/sandritsch91/yii2-widget-yad)

A Yii2 input widget for [yad](https://github.com/Sandritsch91/yad) (Yet Another Datepicker): a dependency-free
date, datetime and time picker with a jQuery UI compatible API, 80+ locales, dark mode and a Bootstrap 5 theme.

- Formats follow the application's formatter, so the value the picker writes is the value Yii parses
- The yad locale is picked from the application language (`de-CH` → `de-CH`, `de-DE` → `de`), only that one is loaded
- Optionally submit a machine-readable value (`2026-11-20 15:45:00`) while the user sees a localized one
- Popup or inline calendar
- No jQuery required

## Installation

Requires PHP 8.2 or later and Yii 2.0.49 or later.

```bash
composer require sandritsch91/yii2-widget-yad
```

yad is installed from [asset-packagist](https://asset-packagist.org) (`npm-asset/sandritsch91--yad`). If your
application does not use it yet, add it to your `composer.json`:

```json
"repositories": [
    {"type": "composer", "url": "https://asset-packagist.org"}
]
```

and make sure the `@npm` alias points to `vendor/npm-asset` (the default in the Yii application templates).

## Usage

```php
use sandritsch91\yii2\yad\Yad;

// With a model
echo $form->field($model, 'birthday')->widget(Yad::class);

// Without a model
echo Yad::widget(['name' => 'from', 'value' => '2026-11-20']);
```

### Date, datetime or time

```php
echo $form->field($model, 'starts_at')->widget(Yad::class, [
    'mode' => Yad::MODE_DATETIME,           // MODE_DATE (default), MODE_DATETIME, MODE_TIME
    'clientOptions' => ['stepMinute' => 15],
]);
```

### Submitting a machine-readable value

By default the visible input is submitted, in the display format (as with `yii\jui\DatePicker`). With
`submitFormat` the visible input has no `name`; a hidden input is submitted instead:

```php
echo $form->field($model, 'starts_at')->widget(Yad::class, [
    'mode' => Yad::MODE_DATETIME,
    'submitFormat' => true,     // yyyy-MM-dd, HH:mm:ss or yyyy-MM-dd HH:mm:ss
]);

// Or choose the formats (ICU or php:)
'submitFormat' => ['date' => 'php:Y-m-d', 'time' => 'HH:mm', 'separator' => 'T'],
```

The model value is read in any form the formatter understands (timestamp, `DateTime`, `Y-m-d H:i:s`, ...).

### Inline calendar

```php
echo Yad::widget(['name' => 'day', 'inline' => true]);
```

The value is kept in a hidden input with the widget's `id`; the calendar `<div>` gets `<id>-yad`
(`containerOptions` sets its attributes).

### Date ranges

`YadRange` renders a start and an end picker linked with yad's
[`Yad.range()`](https://github.com/Sandritsch91/yad/blob/main/docs/date-ranges.md): the end cannot be before
the start, the start cannot be after the end (including the time in `MODE_DATETIME`), and both calendars
highlight the range.

```php
use sandritsch91\yii2\yad\YadRange;

echo $form->field($model, 'start')->widget(YadRange::class, [
    'attributeTo' => 'end',
    'submitFormat' => true,
    'clientOptions' => ['minDate' => 0, 'numberOfMonths' => 2],
]);

echo YadRange::widget([
    'name' => 'from',
    'nameTo' => 'to',
    'inline' => true,
    'template' => '<div class="d-flex gap-3">{from}{to}</div>',
]);
```

All `Yad` properties apply to both pickers. `options`, `name`, `value` and `attribute` belong to the start;
`optionsTo`, `nameTo`, `valueTo` and `attributeTo` to the end. `template` (default `'{from} – {to}'`) puts
them together, e.g. `'<div class="input-group">{from}<span class="input-group-text">–</span>{to}</div>'`.

### Options and events

`clientOptions` is passed to yad, see its [options](https://github.com/Sandritsch91/yad/blob/main/docs/options.md).
Use `JsExpression` for callbacks. `clientEvents` attaches DOM event listeners (`input`, `change`) to the input.

```php
use yii\web\JsExpression;

echo Yad::widget([
    'name' => 'appointment',
    'clientOptions' => [
        'minDate' => 0,
        'maxDate' => '+2m',
        'changeMonth' => true,
        'changeYear' => true,
        'showButtonPanel' => true,
        'beforeShowDay' => new JsExpression('yad.Yad.noWeekends'),
        'onSelect' => new JsExpression('function (dateText, inst) { console.log(dateText, inst.getDate()); }'),
    ],
    'clientEvents' => [
        'change' => 'function (e) { console.log(e.target.value); }',
    ],
]);
```

The picker instance is available in JavaScript with `yad.Yad.getInstance('#input-id')`.

### Widget properties

| Property | Default | Description |
|---|---|---|
| `mode` | `Yad::MODE_DATE` | `MODE_DATE`, `MODE_DATETIME` or `MODE_TIME` |
| `inline` | `false` | Always visible calendar instead of a popup |
| `language` | `Yii::$app->language` | Language of the yad locale (texts, first day of the week, ...) |
| `dateFormat` | formatter's `dateFormat` | ICU pattern, `php:` format or `short` / `medium` / `long` / `full` |
| `timeFormat` | formatter's `timeFormat` | Same as `dateFormat`, for the time |
| `separator` | `' '` | Text between date and time |
| `submitFormat` | `false` | See [above](#submitting-a-machine-readable-value) |
| `theme` | `null` (light) | `light`, `dark` or `auto` (follows the operating system) |
| `bootstrapTheme` | `false` | Load yad's Bootstrap 5 theme (Bootstrap must be registered before) |
| `clientOptions` | `[]` | yad options |
| `clientEvents` | `[]` | DOM event handlers for the input |
| `containerOptions` | `[]` | HTML attributes of the inline calendar `<div>` |

Set the formats with `dateFormat` and `timeFormat`, not in `clientOptions`, so the server formats the value
the same way. Formats with month or day names (`medium`, `long`, `MMM`, ...) need the `intl` extension, so
that PHP and yad use the same names; numeric formats work without it.

### Bootstrap 5

```php
echo $form->field($model, 'date')->widget(Yad::class, ['bootstrapTheme' => true]);
```

To use it everywhere, set it once in the container definitions of your config:

```php
'container' => [
    'definitions' => [
        sandritsch91\yii2\yad\Yad::class => ['bootstrapTheme' => true, 'theme' => 'auto'],
    ],
],
```

### Asset compression

Normally the widget loads the locale of the current language on demand. `yii asset/compress` cannot know
these, so list the locales your application uses in the `assetManager` section of the compressor config:

```php
// assets.php, used by: yii asset/compress assets.php config/assets-prod.php
return [
    'bundles' => [
        sandritsch91\yii2\yad\YadAsset::class,
        // ...
    ],
    'assetManager' => [
        'basePath' => '@webroot/assets',
        'baseUrl' => '@web/assets',
        'bundles' => [
            sandritsch91\yii2\yad\YadAsset::class => ['locales' => ['de', 'fr', 'it']],   // English is built in
        ],
    ],
    // targets, jsCompressor, cssCompressor ...
];
```

The locales are then part of the compressed file, and the widget adds no files of its own. Regional
languages fall back to the language: with `de` compressed, an application in `de-CH` uses the German locale.
List `de-CH` as well if you want its Swiss specifics.

## Development

```bash
composer install
vendor/bin/phpunit
php -S localhost:8091 -t demo/web     # demo page
```

## License

MIT
