<?php

declare(strict_types=1);

namespace Opengeek\Content\Markdown\Tests;

use Opengeek\Content\ArticleCollection;
use Opengeek\Content\Article;
use Opengeek\Content\Markdown\MarkdownArticleMapper;
use Opengeek\Content\Markdown\MarkdownArticleRepository;
use Opengeek\Content\Markdown\MarkdownArticleRepositoryConfig;
use Opengeek\Content\Exception\ContentNotFoundException;
use PHPUnit\Framework\TestCase;

final class MarkdownArticleRepositoryTest extends TestCase
{
    private string $fixturesPath;
    private MarkdownArticleRepository $repository;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/fixtures/articles';
        $this->repository = new MarkdownArticleRepository(
            config: new MarkdownArticleRepositoryConfig(
                contentPath: $this->fixturesPath,
                recursive: true,
            ),
            mapper: new MarkdownArticleMapper(),
        );
    }

    public function testFindAllReturnsCollection(): void
    {
        $result = $this->repository->findAll();

        self::assertInstanceOf(ArticleCollection::class, $result);
    }

    public function testFindAllSkipsMalformedFiles(): void
    {
        // fixtures contain one malformed file (missing slug/publishDate)
        // all parseable files are returned; the malformed one is silently skipped
        $result = $this->repository->findAll();

        $slugs = array_map(
            static fn(Article $dto) => $dto->slug,
            iterator_to_array($result)
        );

        self::assertNotContains('', $slugs);
        // We have hello-world, future-article, and older-article (3 valid fixtures)
        self::assertCount(3, $result);
    }

    public function testFindPublishedExcludesFutureArticles(): void
    {
        $published = $this->repository->findPublished();

        $slugs = array_map(
            static fn(Article $dto) => $dto->slug,
            iterator_to_array($published)
        );

        self::assertNotContains('2024/01/future-article', $slugs);
    }

    public function testFindPublishedReturnsSortedNewestFirst(): void
    {
        $published = $this->repository->findPublished();
        $items = iterator_to_array($published);

        self::assertGreaterThan(1, count($items), 'Need at least two published fixtures to test ordering');

        for ($i = 0; $i < count($items) - 1; $i++) {
            self::assertGreaterThanOrEqual(
                $items[$i + 1]->getPublishDateTime()->getTimestamp(),
                $items[$i]->getPublishDateTime()->getTimestamp(),
                'Items are not in descending publish-date order'
            );
        }
    }

    public function testFindBySlugReturnsMatchingArticle(): void
    {
        $dto = $this->repository->findBySlug('2024/01/hello-world');

        self::assertInstanceOf(Article::class, $dto);
        self::assertSame('2024/01/hello-world', $dto->slug);
        self::assertSame('Hello World', $dto->title);
    }

    public function testFindBySlugThrowsForUnknownSlug(): void
    {
        $this->expectException(ContentNotFoundException::class);
        $this->expectExceptionMessage('"does-not-exist"');

        $this->repository->findBySlug('does-not-exist');
    }

    public function testFindAllWithRecursiveFalseOnlyReadsTopLevel(): void
    {
        // With recursive = false, the top-level fixtures/articles directory
        // has no .md files directly (they are under 2024/01/); result is empty.
        $repo = new MarkdownArticleRepository(
            config: new MarkdownArticleRepositoryConfig(
                contentPath: $this->fixturesPath,
                recursive: false,
            ),
            mapper: new MarkdownArticleMapper(),
        );

        $result = $repo->findAll();

        self::assertCount(0, $result);
    }
}
