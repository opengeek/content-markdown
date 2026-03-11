<?php

declare(strict_types=1);

namespace Opengeek\Content\Markdown\Tests;

use Opengeek\Content\Article;
use Opengeek\Content\ArticleCollection;
use Opengeek\Content\Contracts\ContentMapperInterface;
use Opengeek\Content\Exception\ContentMappingException;
use Opengeek\Content\Markdown\AbstractMarkdownRepository;
use Opengeek\Content\Markdown\MarkdownArticleRepositoryConfig;
use PHPUnit\Framework\TestCase;

final class AbstractMarkdownRepositoryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/opengeek_content_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    private function removeDir(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDir("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    private function createRepo(ContentMapperInterface $mapper): AbstractMarkdownRepository
    {
        return new class (
            new MarkdownArticleRepositoryConfig($this->tempDir),
            $mapper
        ) extends AbstractMarkdownRepository {
            protected function createCollection(array $items): ArticleCollection
            {
                return new ArticleCollection($items);
            }
        };
    }

    public function testFindBySlugSkipsMalformedFiles(): void
    {
        file_put_contents($this->tempDir . '/malformed.md', "---\ntitle: Malformed\n---\nContent");
        file_put_contents($this->tempDir . '/valid.md', "---\nslug: valid\ntitle: Valid\npublishDate: '2024-01-01'\n---\nContent");

        $mapper = $this->createMock(ContentMapperInterface::class);
        $mapper->expects(self::atLeastOnce())
            ->method('map')
            ->willReturnCallback(function ($doc) {
                $yaml = $doc->getYAML();
                if (!isset($yaml['slug'])) {
                    throw new ContentMappingException('Missing slug');
                }
                return new Article(
                    slug: (string) $yaml['slug'],
                    title: (string) $yaml['title'],
                    publishDate: (string) $yaml['publishDate'],
                    markdownContent: $doc->getContent()
                );
            });

        $repo = $this->createRepo($mapper);
        $result = $repo->findBySlug('valid');

        self::assertSame('valid', $result->slug);
    }

    public function testParseFileThrowsWhenFileUnreadable(): void
    {
        $file = $this->tempDir . '/unreadable.md';
        file_put_contents($file, 'content');
        chmod($file, 0000);

        $mapper = $this->createMock(ContentMapperInterface::class);
        $repo = $this->createRepo($mapper);

        // We need to call parseFile which is protected.
        $reflection = new \ReflectionClass($repo);
        $method = $reflection->getMethod('parseFile');
        $method->setAccessible(true);

        $this->expectException(ContentMappingException::class);
        $this->expectExceptionMessage('Could not read file');

        try {
            $method->invoke($repo, new \SplFileInfo($file));
        } finally {
            chmod($file, 0666);
        }
    }
}
