<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReactBundle\DependencyInjection\ReactExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UrlValidationTest extends TestCase
{
    /**
     * @test
     */
    #[DataProvider('validUrlsProvider')]
    public function testValidUrlsAreAccepted(string $url): void
    {
        $extension = new ReactExtension();
        $container = new ContainerBuilder();

        $config = [
            [
                'vite_server' => $url,
            ]
        ];

        // Ne doit pas lever d'exception
        try {
            $extension->load($config, $container);
            $this->assertTrue(true);
        } catch (\InvalidArgumentException $e) {
            $this->fail('Valid URL was rejected: ' . $url . ' - Error: ' . $e->getMessage());
        }
    }

    public static function validUrlsProvider(): array
    {
        return [
            'http localhost' => ['http://localhost:3000'],
            'https production' => ['https://assets.example.com'],
            'http with path' => ['http://localhost:3000/build'],
            'https with port' => ['https://localhost:5173'],
            'http localhost no port' => ['http://localhost'],
            'https without port' => ['https://example.com'],
            'http with query' => ['http://localhost:3000?test=1'],
        ];
    }

    /**
     * @test
     */
    #[DataProvider('invalidUrlsProvider')]
    public function testInvalidUrlsAreRejected(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $extension = new ReactExtension();
        $container = new ContainerBuilder();

        $config = [
            [
                'vite_server' => $url,
            ]
        ];

        $extension->load($config, $container);
    }

    public static function invalidUrlsProvider(): array
    {
        return [
            'ftp scheme' => ['ftp://example.com'],
            'file scheme' => ['file:///etc/passwd'],
            'javascript scheme' => ['javascript:alert(1)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'not a url' => ['not-a-url'],
            'only path' => ['/path/to/resource'],
            'telnet' => ['telnet://localhost:23'],
            'empty string' => [''],
        ];
    }

    /**
     * @test
     */
    public function testHttpOnlyScheme(): void
    {
        // HTTPS doit être accepté
        $extension = new ReactExtension();
        $container = new ContainerBuilder();
        $config = [['vite_server' => 'https://example.com']];
        $extension->load($config, $container);
        $this->assertTrue(true);

        // HTTP doit être accepté
        $extension = new ReactExtension();
        $container = new ContainerBuilder();
        $config = [['vite_server' => 'http://example.com']];
        $extension->load($config, $container);
        $this->assertTrue(true);

        // Autres schemes doivent être rejetés
        $this->expectException(\InvalidArgumentException::class);
        $extension = new ReactExtension();
        $container = new ContainerBuilder();
        $config = [['vite_server' => 'gopher://example.com']];
        $extension->load($config, $container);
    }
}
