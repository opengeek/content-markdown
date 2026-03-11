# opengeek/content-markdown

![PHP ^8.3](https://img.shields.io/badge/PHP-%5E8.3-blue)

Markdown file repository implementation for `opengeek/content`.

## Installation

```bash
composer require opengeek/content-markdown
```

## Features

- **Read-only**: Designed for read-only access to filesystem-based content.
- **Front Matter**: Supports YAML front matter via `mnapoli/front-yaml`.
- **Flexible Discovery**: Uses `symfony/finder` to locate content files.

## Usage

```php
use Opengeek\Content\Markdown\MarkdownArticleRepository;
use Opengeek\Content\Markdown\MarkdownArticleRepositoryConfig;
use Opengeek\Content\Markdown\MarkdownArticleMapper;

$config = new MarkdownArticleRepositoryConfig(
    contentPath: __DIR__ . '/content/articles',
    recursive: true
);

$repository = new MarkdownArticleRepository(
    $config,
    new MarkdownArticleMapper()
);

$articles = $repository->findAll();
```
