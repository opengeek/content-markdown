<?php

declare(strict_types=1);

namespace Opengeek\Content\Markdown\Tests;

use Mni\FrontYAML\Parser;
use Opengeek\Content\Article;
use Opengeek\Content\Markdown\MarkdownArticleMapper;
use Opengeek\Content\Exception\ContentMappingException;
use PHPUnit\Framework\TestCase;

final class MarkdownArticleMapperTest extends TestCase
{
    private MarkdownArticleMapper $mapper;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->mapper = new MarkdownArticleMapper();
        $this->parser = new Parser();
    }

    private function parse(string $raw): \Mni\FrontYAML\Document
    {
        return $this->parser->parse($raw, false);
    }

    public function testMapsFullDocument(): void
    {
        $raw = <<<'MD'
        ---
        slug: 2024/01/hello-world
        title: Hello World
        subtitle: The subtitle
        summary: A short summary.
        publishDate: 2024-01-15 09:00am
        image: /images/test.jpg
        categories:
          - General
          - News
        tags:
          - intro
        ---

        ## Hello World

        Body content here.
        MD;

        $dto = $this->mapper->map($this->parse($raw));

        self::assertInstanceOf(Article::class, $dto);
        self::assertSame('2024/01/hello-world', $dto->slug);
        self::assertSame('Hello World', $dto->title);
        self::assertSame('The subtitle', $dto->subtitle);
        self::assertSame('A short summary.', $dto->summary);
        self::assertSame('2024-01-15 09:00am', $dto->publishDate);
        self::assertSame('/images/test.jpg', $dto->image);
        self::assertSame(['General', 'News'], $dto->categories);
        self::assertSame(['intro'], $dto->tags);
        self::assertStringContainsString('Body content here.', $dto->markdownContent);
    }

    public function testMapsMinimalDocument(): void
    {
        $raw = <<<'MD'
        ---
        slug: test/minimal
        title: Minimal
        publishDate: 2024-01-01
        ---
        MD;

        $dto = $this->mapper->map($this->parse($raw));

        self::assertSame('test/minimal', $dto->slug);
        self::assertSame('Minimal', $dto->title);
        self::assertSame('', $dto->subtitle);
        self::assertSame('', $dto->summary);
        self::assertSame('', $dto->image);
        self::assertSame([], $dto->categories);
        self::assertSame([], $dto->tags);
    }

    public function testThrowsWhenSlugMissing(): void
    {
        $raw = <<<'MD'
        ---
        title: No Slug
        publishDate: 2024-01-01
        ---
        MD;

        $this->expectException(ContentMappingException::class);
        $this->expectExceptionMessage('"slug"');
        $this->mapper->map($this->parse($raw));
    }

    public function testThrowsWhenTitleMissing(): void
    {
        $raw = <<<'MD'
        ---
        slug: test/no-title
        publishDate: 2024-01-01
        ---
        MD;

        $this->expectException(ContentMappingException::class);
        $this->expectExceptionMessage('"title"');
        $this->mapper->map($this->parse($raw));
    }

    public function testThrowsWhenPublishDateMissing(): void
    {
        $raw = <<<'MD'
        ---
        slug: test/no-date
        title: No Date
        ---
        MD;

        $this->expectException(ContentMappingException::class);
        $this->expectExceptionMessage('"publishDate"');
        $this->mapper->map($this->parse($raw));
    }

    public function testThrowsWhenSourceIsNotDocument(): void
    {
        $this->expectException(ContentMappingException::class);
        $this->mapper->map('not a document'); // @phpstan-ignore-line
    }
}
