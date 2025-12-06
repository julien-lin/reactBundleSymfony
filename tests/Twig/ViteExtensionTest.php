<?php

namespace ReactBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use ReactBundle\Twig\ViteExtension;
use Twig\Extension\AbstractExtension;

class ViteExtensionTest extends TestCase
{
    private ViteExtension $viteExtension;

    protected function setUp(): void
    {
        $this->viteExtension = new ViteExtension(false, 'http://localhost:3000', 'build');
    }

    public function testViteExtensionIsAbstractExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->viteExtension);
    }

    public function testViteExtensionConstructorWithDefaults(): void
    {
        $extension = new ViteExtension();
        $this->assertInstanceOf(ViteExtension::class, $extension);
    }

    public function testViteExtensionConstructorWithCustomValues(): void
    {
        $extension = new ViteExtension(true, 'http://vite.local:5173', 'dist');
        $this->assertInstanceOf(ViteExtension::class, $extension);
    }

    public function testViteExtensionGetFunctions(): void
    {
        $functions = $this->viteExtension->getFunctions();
        $this->assertIsArray($functions);
        $this->assertNotEmpty($functions);
        $this->assertGreaterThanOrEqual(2, count($functions));
    }

    public function testViteExtensionFunctionNames(): void
    {
        $functions = $this->viteExtension->getFunctions();
        $functionNames = array_map(fn($func) => $func->getName(), $functions);

        $this->assertContains('vite_entry_script_tags', $functionNames);
        $this->assertContains('vite_entry_link_tags', $functionNames);
    }

    public function testViteExtensionFunctionsCallable(): void
    {
        $functions = $this->viteExtension->getFunctions();

        foreach ($functions as $function) {
            $this->assertTrue(method_exists($this->viteExtension, $function->getCallable()[1]));
        }
    }

    public function testRenderViteScriptTagsMethod(): void
    {
        $this->assertTrue(method_exists($this->viteExtension, 'renderViteScriptTags'));
    }

    public function testRenderViteLinkTagsMethod(): void
    {
        $this->assertTrue(method_exists($this->viteExtension, 'renderViteLinkTags'));
    }
}
