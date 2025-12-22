<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Composer;

use PHPUnit\Framework\TestCase;
use ReactBundle\Composer\ScriptHandler;
use Composer\Script\Event;
use Composer\IO\NullIO;

/**
 * Tests pour la génération automatique de vite.config.js
 * ✅ P0-2: Tests pour la génération automatique de vite.config.js
 */
class ScriptHandlerViteConfigTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        // Créer un répertoire temporaire pour les tests
        $this->tempDir = sys_get_temp_dir() . '/react_bundle_vite_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        
        // Créer la structure de répertoires
        mkdir($this->tempDir . '/vendor/julien-lin/react-bundle-symfony/Resources/templates', 0777, true);
        
        $this->originalCwd = getcwd();
        chdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        $this->deleteDirectory($this->tempDir);
    }

    public function testGenerateViteConfigMethodExists(): void
    {
        $this->assertTrue(method_exists(ScriptHandler::class, 'generateViteConfig'));
    }

    public function testGenerateViteConfigIsStatic(): void
    {
        $reflection = new \ReflectionMethod(ScriptHandler::class, 'generateViteConfig');
        $this->assertTrue($reflection->isStatic());
    }

    public function testGenerateViteConfigIsPublic(): void
    {
        $reflection = new \ReflectionMethod(ScriptHandler::class, 'generateViteConfig');
        $this->assertTrue($reflection->isPublic());
    }

    public function testGenerateViteConfigCreatesFile(): void
    {
        // Créer le template
        $templatePath = $this->tempDir . '/vendor/julien-lin/react-bundle-symfony/Resources/templates/vite.config.js';
        file_put_contents($templatePath, '// Template vite.config.js');

        // Créer un Event mock
        $io = new NullIO();
        $event = $this->createMock(Event::class);
        $event->method('getIO')->willReturn($io);

        // Appeler la méthode
        ScriptHandler::generateViteConfig($event);

        // Vérifier que le fichier a été créé
        $viteConfigPath = $this->tempDir . '/vite.config.js';
        $this->assertFileExists($viteConfigPath);
    }

    public function testGenerateViteConfigDoesNotOverwriteExistingFile(): void
    {
        // Créer un vite.config.js existant
        $existingContent = '// Existing vite.config.js';
        $viteConfigPath = $this->tempDir . '/vite.config.js';
        file_put_contents($viteConfigPath, $existingContent);

        // Créer le template
        $templatePath = $this->tempDir . '/vendor/julien-lin/react-bundle-symfony/Resources/templates/vite.config.js';
        file_put_contents($templatePath, '// New template');

        // Créer un Event mock
        $io = new NullIO();
        $event = $this->createMock(Event::class);
        $event->method('getIO')->willReturn($io);

        // Appeler la méthode
        ScriptHandler::generateViteConfig($event);

        // Vérifier que le fichier existant n'a pas été écrasé
        $this->assertEquals($existingContent, file_get_contents($viteConfigPath));
    }

    public function testGenerateViteConfigUsesTemplateContent(): void
    {
        // Créer le template avec un contenu spécifique
        $templateContent = '// Generated vite.config.js from template';
        $templatePath = $this->tempDir . '/vendor/julien-lin/react-bundle-symfony/Resources/templates/vite.config.js';
        file_put_contents($templatePath, $templateContent);

        // Créer un Event mock
        $io = new NullIO();
        $event = $this->createMock(Event::class);
        $event->method('getIO')->willReturn($io);

        // Appeler la méthode
        ScriptHandler::generateViteConfig($event);

        // Vérifier que le contenu du template a été copié
        $viteConfigPath = $this->tempDir . '/vite.config.js';
        $this->assertFileExists($viteConfigPath);
        $this->assertEquals($templateContent, file_get_contents($viteConfigPath));
    }

    public function testGenerateViteConfigHandlesMissingTemplate(): void
    {
        // Ne pas créer le template

        // Créer un Event mock
        $io = new NullIO();
        $event = $this->createMock(Event::class);
        $event->method('getIO')->willReturn($io);

        // Appeler la méthode (ne devrait pas planter)
        ScriptHandler::generateViteConfig($event);

        // Vérifier que le fichier n'a pas été créé
        $viteConfigPath = $this->tempDir . '/vite.config.js';
        $this->assertFileDoesNotExist($viteConfigPath);
    }

    public function testGenerateViteConfigWorksFromVendorDirectory(): void
    {
        // Simuler une installation depuis vendor/
        $templatePath = $this->tempDir . '/vendor/julien-lin/react-bundle-symfony/Resources/templates/vite.config.js';
        file_put_contents($templatePath, '// Template from vendor');

        // Créer un Event mock
        $io = new NullIO();
        $event = $this->createMock(Event::class);
        $event->method('getIO')->willReturn($io);

        // Appeler la méthode
        ScriptHandler::generateViteConfig($event);

        // Vérifier que le fichier a été créé à la racine du projet
        $viteConfigPath = $this->tempDir . '/vite.config.js';
        $this->assertFileExists($viteConfigPath);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

