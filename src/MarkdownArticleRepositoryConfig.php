<?php

declare(strict_types=1);

namespace Opengeek\Content\Markdown;

/**
 * Configuration value object for MarkdownArticleRepository.
 */
final readonly class MarkdownArticleRepositoryConfig
{
    /**
     * @param string $contentPath Absolute path to the directory containing .md files
     * @param bool   $recursive   Whether to recurse into subdirectories (default: true)
     */
    public function __construct(
        public readonly string $contentPath,
        public readonly bool $recursive = true,
    ) {
    }
}
