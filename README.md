# Silverstripe Meta Editor

The Silverstripe Meta Editor interface allows easy editing of Meta Titles and Meta Descriptions
for pages within a customised ModelAdmin interface.

Values are updated directly via Ajax, and provide handy warnings about value length and duplicates.

This module is a complete rebuild of [axllent/silverstripe-seo-editor](https://github.com/axllent/silverstripe-seo-editor)
for **Silverstripe 4**.

![Silverstripe Meta Editor](images/Screenshot.png "Silverstripe Meta Editor")


## Features

- Tree-like navigation, browse down into sub-pages
- Ajax updates, character counter
- Input validation (too long or short, duplicates)
- Data cleaning - excess whitespace removed
- Search, including selecting all pages with warnings
- Set non-editable pages (eg: RedirectPage)
- Set hidden pages (eg: ErrorPage)
- Works transparently with [Fluent](https://github.com/tractorcow-farm/silverstripe-fluent) (optional)


## Requirements

- Silverstripe ^4


## Configuration

Please refer to the [Configuration docs](docs/en/Configuration.md).


## Installation

```
composer require axllent/silverstripe-meta-editor
```
