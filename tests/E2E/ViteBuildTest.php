<?php

declare(strict_types=1);

namespace ReactBundle\Tests\E2E;

use PHPUnit\Framework\TestCase;
use ReactBundle\Service\BundlePathResolver;
use Symfony\Component\Process\Process;

/**
 * Tests E2E pour le build Vite
 * ✅ P1-3: Tests E2E avec Vite
 */
class ViteBuildTest extends TestCase
{
    private string $projectRoot;
    private string $bundlePath;

    protected function setUp(): void
    {
        $this->bundlePath = dirname(__DIR__, 2);
        $this->projectRoot = dirname($this->bundlePath, 2);
    }

    public function testViteConfigExists(): void
    {
        $viteConfigPath = $this->bundlePath . DIRECTORY_SEPARATOR . 'vite.config.js';
        $this->assertFileExists($viteConfigPath, 'vite.config.js should exist in bundle');
    }

    public function testPackageJsonExists(): void
    {
        $packageJsonPath = $this->bundlePath . DIRECTORY_SEPARATOR . 'package.json';
        $this->assertFileExists($packageJsonPath, 'package.json should exist in bundle');
    }

    public function testNodeModulesExists(): void
    {
        $nodeModulesPath = $this->bundlePath . DIRECTORY_SEPARATOR . 'node_modules';
        
        // Ne pas échouer si node_modules n'existe pas (peut être dans .gitignore)
        if (!is_dir($nodeModulesPath)) {
            $this->markTestSkipped('node_modules not found. Run npm install first.');
        }
        
        $this->assertDirectoryExists($nodeModulesPath, 'node_modules should exist');
    }

    public function testViteIsInstalled(): void
    {
        $vitePath = $this->bundlePath . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'vite';
        
        if (!is_dir($vitePath)) {
            $this->markTestSkipped('Vite not installed. Run npm install first.');
        }
        
        $this->assertDirectoryExists($vitePath, 'Vite should be installed');
    }

    public function testReactIsInstalled(): void
    {
        $reactPath = $this->bundlePath . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'react';
        
        if (!is_dir($reactPath)) {
            $this->markTestSkipped('React not installed. Run npm install first.');
        }
        
        $this->assertDirectoryExists($reactPath, 'React should be installed');
    }

    public function testViteConfigIsValid(): void
    {
        $viteConfigPath = $this->bundlePath . DIRECTORY_SEPARATOR . 'vite.config.js';
        
        if (!file_exists($viteConfigPath)) {
            $this->markTestSkipped('vite.config.js not found');
        }
        
        $content = file_get_contents($viteConfigPath);
        
        // Vérifier que c'est du JavaScript valide (au moins qu'il contient defineConfig)
        $this->assertStringContainsString('defineConfig', $content, 'vite.config.js should use defineConfig');
        $this->assertStringContainsString('react', $content, 'vite.config.js should configure React plugin');
    }

    public function testPackageJsonHasRequiredScripts(): void
    {
        $packageJsonPath = $this->bundlePath . DIRECTORY_SEPARATOR . 'package.json';
        
        if (!file_exists($packageJsonPath)) {
            $this->markTestSkipped('package.json not found');
        }
        
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        
        $this->assertIsArray($packageJson, 'package.json should be valid JSON');
        $this->assertArrayHasKey('scripts', $packageJson, 'package.json should have scripts section');
        
        $scripts = $packageJson['scripts'];
        $this->assertArrayHasKey('dev', $scripts, 'package.json should have dev script');
        $this->assertArrayHasKey('build', $scripts, 'package.json should have build script');
    }

    public function testPackageJsonHasRequiredDependencies(): void
    {
        $packageJsonPath = $this->bundlePath . DIRECTORY_SEPARATOR . 'package.json';
        
        if (!file_exists($packageJsonPath)) {
            $this->markTestSkipped('package.json not found');
        }
        
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        
        $this->assertIsArray($packageJson, 'package.json should be valid JSON');
        
        if (isset($packageJson['dependencies'])) {
            $this->assertArrayHasKey('react', $packageJson['dependencies'], 'React should be in dependencies');
            $this->assertArrayHasKey('react-dom', $packageJson['dependencies'], 'react-dom should be in dependencies');
        }
        
        if (isset($packageJson['devDependencies'])) {
            $this->assertArrayHasKey('vite', $packageJson['devDependencies'], 'Vite should be in devDependencies');
            $this->assertArrayHasKey('@vitejs/plugin-react', $packageJson['devDependencies'], '@vitejs/plugin-react should be in devDependencies');
        }
    }
}

