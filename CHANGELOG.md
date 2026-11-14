# Changelog

## Unreleased

- Require PHP 8.2 and Yii 2.0.49 or later. Properties, parameters and return values are typed: subclasses that
  override methods (e.g. `init()`, `run()`, `formatValue()`) must declare the same types.
- Require yad 1.2.
- `YadRange` widget: a start and an end picker linked with yad's `Yad.range()`, so the end cannot be before the
  start and both calendars highlight the range.

## 1.0.0 (2026-11-14)

- Initial release: `Yad` input widget for yad 1.1 with date, datetime and time modes, inline calendar,
  automatic locale loading, optional machine-readable submit value and the Bootstrap 5 theme.
