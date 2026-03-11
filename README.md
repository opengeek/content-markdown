# opengeek/content-markdown

![PHP ^8.3](https://img.shields.io/badge/PHP-%5E8.3-blue)

Markdown file repository implementation for `opengeek/content`.

## Installation

```bash
composer require opengeek/content-markdown
```

## Features
- **Generic Base**: `AbstractMarkdownRepository` to easily build additional file-based content types.
- **Mappers**: `ContentMapperInterface` support for decoupling raw Markdown/YAML data from DTOs.
- **Read-only**: Designed for performant, read-only filesystem-based content.
- **Front Matter**: Full YAML front matter support via `mni/front-yaml`.
- **Recursive Discovery**: Built-in support for nested directory structures.

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
