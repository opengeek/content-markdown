<?php

declare(strict_types=1);

namespace Opengeek\Content\Markdown;

use Iterator;
use Mni\FrontYAML\Parser;
use Opengeek\Content\Article;
use Opengeek\Content\ArticleCollection;
use Opengeek\Content\ArticleRepositoryInterface;
use Opengeek\Content\Exception\ContentMappingException;
use Opengeek\Content\Exception\ContentNotFoundException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Loads Article content from Markdown files with YAML front matter.
 *
 * Each .md file in the configured content path is parsed by mni/front-yaml.
 * Files that cannot be parsed are silently skipped so that one malformed
 * file does not bring down the entire listing.
 *
 * To swap this implementation for a database-backed or API-backed repository,
 * bind a different class to ArticleRepositoryInterface in your DI container.
 */
final class MarkdownArticleRepository implements ArticleRepositoryInterface
{
    private readonly Parser $parser;

    public function __construct(
        private readonly MarkdownArticleRepositoryConfig $config,
        private readonly MarkdownArticleMapper $mapper,
        ?Parser $parser = null,
    ) {
        $this->parser = $parser ?? new Parser();
    }

    public function findAll(): ArticleCollection
    {
        $articles = [];

        foreach ($this->findMarkdownFiles() as $file) {
            try {
                $articles[] = $this->parseFile($file);
            } catch (ContentMappingException) {
                // Skip unparseable or incomplete files.
                continue;
            }
        }

        return new ArticleCollection($articles);
    }

    public function findPublished(?\DateTimeImmutable $now = null): ArticleCollection
    {
        return $this->findAll()
            ->filterPublished($now ?? new \DateTimeImmutable())
            ->sortByPublishDateDescending();
    }

    public function findBySlug(string $slug): Article
    {
        foreach ($this->findMarkdownFiles() as $file) {
            try {
                $dto = $this->parseFile($file);
            } catch (ContentMappingException) {
                continue;
            }

            if ($dto->slug === $slug) {
                return $dto;
            }
        }

        throw ContentNotFoundException::forSlug($slug);
    }

    /** @return Iterator<SplFileInfo> */
    private function findMarkdownFiles(): Iterator
    {
        $finder = (new Finder())
            ->files()
            ->name('*.md')
            ->in($this->config->contentPath);

        if (!$this->config->recursive) {
            $finder->depth('== 0');
        }

        return $finder->getIterator();
    }

    /**
     * @throws ContentMappingException
     */
    private function parseFile(SplFileInfo $file): Article
    {
        $raw = file_get_contents($file->getPathname());

        if ($raw === false) {
            throw new ContentMappingException(sprintf(
                'Could not read file: %s',
                $file->getPathname()
            ));
        }

        $document = $this->parser->parse($raw, false);

        try {
            return $this->mapper->map($document);
        } catch (ContentMappingException $e) {
            throw new ContentMappingException(sprintf(
                'Failed to map article from "%s": %s',
                $file->getPathname(),
                $e->getMessage()
            ), previous: $e);
        }
    }
}
