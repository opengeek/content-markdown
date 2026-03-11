<?php

declare(strict_types=1);

namespace Opengeek\Content\Markdown;

use Iterator;
use Mni\FrontYAML\Document;
use Mni\FrontYAML\Parser;
use Opengeek\Content\Article;
use Opengeek\Content\ArticleCollection;
use Opengeek\Content\Contracts\ContentMapperInterface;
use Opengeek\Content\Contracts\ContentRepositoryInterface;
use Opengeek\Content\Exception\ContentMappingException;
use Opengeek\Content\Exception\ContentNotFoundException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Base implementation for file-based content loading from Markdown files.
 *
 * @template TDto
 * @template TCollection
 * @implements ContentRepositoryInterface<TDto, TCollection>
 */
abstract class AbstractMarkdownRepository implements ContentRepositoryInterface
{
    protected readonly Parser $parser;

    /**
     * @param ContentMapperInterface<Document, TDto> $mapper
     */
    public function __construct(
        protected readonly MarkdownArticleRepositoryConfig $config,
        protected readonly ContentMapperInterface $mapper,
        ?Parser $parser = null,
    ) {
        $this->parser = $parser ?? new Parser();
    }

    /**
     * @return TCollection
     */
    public function findAll(): ArticleCollection
    {
        $items = [];

        foreach ($this->findMarkdownFiles() as $file) {
            try {
                $items[] = $this->parseFile($file);
            } catch (ContentMappingException) {
                // Skip unparseable or incomplete files.
                continue;
            }
        }

        return $this->createCollection($items);
    }

    /**
     * @return TCollection
     */
    public function findPublished(?\DateTimeImmutable $now = null): ArticleCollection
    {
        return $this->findAll()
            ->filterPublished($now ?? new \DateTimeImmutable())
            ->sortByPublishDateDescending();
    }

    /**
     * @return TDto
     */
    public function findBySlug(string $slug): Article
    {
        foreach ($this->findMarkdownFiles() as $file) {
            try {
                $dto = $this->parseFile($file);
            } catch (ContentMappingException) {
                continue;
            }

            // Assumes TDto has a public slug property or we might need a more generic way
            // For now, mirroring the existing Article-specific logic but keeping it somewhat generic.
            if (is_object($dto) && isset($dto->slug) && $dto->slug === $slug) {
                return $dto;
            }
        }

        throw ContentNotFoundException::forSlug($slug);
    }

    /**
     * @param array<int, TDto> $items
     * @return TCollection
     */
    abstract protected function createCollection(array $items): mixed;

    /** @return Iterator<SplFileInfo> */
    protected function findMarkdownFiles(): Iterator
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
     * @return TDto
     */
    protected function parseFile(SplFileInfo $file): mixed
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
                'Failed to map content from "%s": %s',
                $file->getPathname(),
                $e->getMessage()
            ), previous: $e);
        }
    }
}
