<?php

declare(strict_types=1);

namespace Opengeek\Content\Markdown;

use Mni\FrontYAML\Document;
use Opengeek\Content\Article;
use Opengeek\Content\Contracts\ContentMapperInterface;
use Opengeek\Content\Exception\ContentMappingException;

/**
 * Maps a FrontYAML Document (parsed from a Markdown file) to an Article.
 *
 * Fields are mapped explicitly rather than through generic property hydration,
 * making required-field validation obvious and the mapping self-documenting.
 *
 * @implements ContentMapperInterface<Document, Article>
 */
final class MarkdownArticleMapper implements ContentMapperInterface
{
    /**
     * @param Document $source A parsed FrontYAML document
     *
     * @throws ContentMappingException if a required YAML field is absent or invalid
     */
    public function map(mixed $source): Article
    {
        if (!$source instanceof Document) {
            throw new ContentMappingException(sprintf(
                '%s expects a %s instance, got %s',
                self::class,
                Document::class,
                get_debug_type($source)
            ));
        }

        $yaml = $source->getYAML() ?? [];

        return new Article(
            slug: $this->requireString($yaml, 'slug'),
            title: $this->requireString($yaml, 'title'),
            publishDate: $this->requireString($yaml, 'publishDate'),
            markdownContent: $source->getContent() ?? '',
            subtitle: $this->optionalString($yaml, 'subtitle'),
            summary: $this->optionalString($yaml, 'summary'),
            image: $this->optionalString($yaml, 'image'),
            categories: $this->optionalStringArray($yaml, 'categories'),
            tags: $this->optionalStringArray($yaml, 'tags'),
        );
    }

    /**
     * @param array<string, mixed> $yaml
     *
     * @throws ContentMappingException
     */
    private function requireString(array $yaml, string $field): string
    {
        $value = $yaml[$field] ?? null;

        if ($value === null || $value === '') {
            throw ContentMappingException::missingField($field, 'markdown document');
        }

        // Symfony YAML parses ISO-8601 date-only values (e.g. 2024-01-01) as
        // Unix timestamp integers. Convert them back to a date string.
        if (is_int($value)) {
            return (new \DateTimeImmutable('@' . $value))->format('Y-m-d');
        }

        if (!is_string($value)) {
            throw ContentMappingException::missingField($field, 'markdown document');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $yaml
     */
    private function optionalString(array $yaml, string $field, string $default = ''): string
    {
        return (isset($yaml[$field]) && is_string($yaml[$field])) ? $yaml[$field] : $default;
    }

    /**
     * @param array<string, mixed> $yaml
     * @return string[]
     */
    private function optionalStringArray(array $yaml, string $field): array
    {
        if (!isset($yaml[$field]) || !is_array($yaml[$field])) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $yaml[$field])));
    }
}
