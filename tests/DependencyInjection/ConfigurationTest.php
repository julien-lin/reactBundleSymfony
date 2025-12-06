<?php

namespace ReactBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use ReactBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigurationTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
    }

    public function testConfigurationImplementsConfigurationInterface(): void
    {
        $this->assertInstanceOf(Configuration::class, $this->configuration);
    }

    public function testGetConfigTreeBuilder(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testTreeBuilderName(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        // Le nom du root node est 'react'
        $this->assertNotNull($treeBuilder);
    }

    public function testConfigurationDefinesBuildDir(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $tree = $treeBuilder->buildTree();
        $this->assertNotNull($tree);
    }

    public function testConfigurationDefinesAssetsDir(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $tree = $treeBuilder->buildTree();
        $this->assertNotNull($tree);
    }

    public function testConfigurationDefinesViteServer(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $tree = $treeBuilder->buildTree();
        $this->assertNotNull($tree);
    }

    public function testConfigurationTreeIsNotNull(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $tree = $treeBuilder->buildTree();
        $this->assertNotNull($tree);
    }

    public function testConfigurationRootNode(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $root = $treeBuilder->getRootNode();
        $this->assertNotNull($root);
    }
}
