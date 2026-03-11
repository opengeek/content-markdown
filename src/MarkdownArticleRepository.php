<?php

declare(strict_types=1);

namespace Opengeek\Content\Markdown;

use Opengeek\Content\Article;
use Opengeek\Content\ArticleCollection;
use Opengeek\Content\ArticleRepositoryInterface;

/**
 * Loads Article content from Markdown files with YAML front matter.
 *
 * Each .md file in the configured content path is parsed by mni/front-yaml.
 * Files that cannot be parsed are silently skipped so that one malformed
 * file does not bring down the entire listing.
 *
 * To swap this implementation for a database-backed or API-backed repository,
 * bind a different class to ArticleRepositoryInterface in your DI container.
 *
 * @extends AbstractMarkdownRepository<Article, ArticleCollection>
 */
final class MarkdownArticleRepository extends AbstractMarkdownRepository implements ArticleRepositoryInterface
{
    /**
     * @param array<int, Article> $items
     */
    protected function createCollection(array $items): ArticleCollection
    {
        return new ArticleCollection($items);
    }
}
