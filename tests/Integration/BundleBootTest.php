<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use ReactBundle\DependencyInjection\ReactExtension;

class BundleBootTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    /**
     * @test
     */
    public function testBundleExtensionCanBeRegistered(): void
    {
        $extension = new ReactExtension();
        $this->container->registerExtension($extension);

        $this->assertTrue($this->container->hasExtension('react'));
    }

    /**
     * @test
     */
    public function testServicesAreLoadedCorrectly(): void
    {
        $extension = new ReactExtension();
        // Fournir une URL valide dans la config
        $extension->load([['vite_server' => 'http://localhost:3000']], $this->container);

        // Services doivent être enregistrés
        $this->assertTrue($this->container->has('ReactBundle\\Service\\ReactRenderer'));
        $this->assertTrue($this->container->has('ReactBundle\\Twig\\ReactExtension'));
        $this->assertTrue($this->container->has('ReactBundle\\Twig\\ViteExtension'));
    }

    /**
     * @test
     */
    public function testParametersAreSet(): void
    {
        $extension = new ReactExtension();
        // Fournir une URL valide dans la config
        $extension->load([['vite_server' => 'http://localhost:3000']], $this->container);

        $this->assertEquals('build', $this->container->getParameter('react.build_dir'));
        $this->assertEquals('assets', $this->container->getParameter('react.assets_dir'));
    }

    /**
     * @test
     */
    public function testViteServerParameterIsSet(): void
    {
        $extension = new ReactExtension();
        // Fournir une URL valide dans la config
        $extension->load([['vite_server' => 'http://localhost:3000']], $this->container);

        // Vérifier que VITE_SERVER_URL est validé et utilisé
        $viteServer = $this->container->getParameter('react.vite_server');
        $this->assertIsString($viteServer);
        $this->assertTrue(filter_var($viteServer, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * @test
     */
    public function testCustomConfigurationIsLoaded(): void
    {
        $config = [
            [
                'build_dir' => 'custom-build',
                'assets_dir' => 'custom-assets',
                'vite_server' => 'http://localhost:5173',
            ]
        ];

        $extension = new ReactExtension();
        $extension->load($config, $this->container);

        $this->assertEquals('custom-build', $this->container->getParameter('react.build_dir'));
        $this->assertEquals('custom-assets', $this->container->getParameter('react.assets_dir'));
        $this->assertEquals('http://localhost:5173', $this->container->getParameter('react.vite_server'));
    }
}
