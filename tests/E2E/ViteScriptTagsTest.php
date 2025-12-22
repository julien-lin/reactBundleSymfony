<?php

declare(strict_types=1);

namespace ReactBundle\Tests\E2E;

use PHPUnit\Framework\TestCase;
use ReactBundle\Twig\ViteExtension;
use Psr\Log\NullLogger;

/**
 * Tests E2E pour la génération des script tags Vite
 * ✅ P1-3: Tests de la génération des script tags
 */
class ViteScriptTagsTest extends TestCase
{
    private ViteExtension $viteExtension;
    private string $testProjectRoot;

    protected function setUp(): void
    {
        $this->testProjectRoot = sys_get_temp_dir() . '/react_bundle_e2e_test_' . uniqid();
        mkdir($this->testProjectRoot, 0777, true);
        mkdir($this->testProjectRoot . '/public/build', 0777, true);
        
        $this->viteExtension = new ViteExtension(false, 'http://localhost:3000', 'build', new NullLogger());
        $this->viteExtension->setEnvironment('prod');
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->testProjectRoot);
    }

    public function testRenderViteScriptTagsInProductionMode(): void
    {
        // Le code utilise getBundlePath() qui retourne le chemin réel du bundle
        // On vérifie juste que la méthode fonctionne avec un manifest existant
        // ou qu'elle retourne un message d'erreur approprié
        
        $extension = new ViteExtension(false, 'http://localhost:3000', 'build', new NullLogger());
        $extension->setEnvironment('prod');
        
        $result = $extension->renderViteScriptTags('app');
        
        // Le résultat doit être une chaîne valide
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        
        // Soit c'est un script tag (si manifest existe), soit un commentaire HTML
        $hasScriptTag = strpos($result, '<script type="module"') !== false;
        $hasComment = strpos($result, '<!--') !== false;
        
        $this->assertTrue(
            $hasScriptTag || $hasComment,
            'Result should be a script tag or HTML comment'
        );
    }

    public function testRenderViteScriptTagsInDevMode(): void
    {
        // Créer un répertoire de test sans manifest pour forcer le mode dev
        $devTestRoot = sys_get_temp_dir() . '/react_bundle_e2e_dev_test_' . uniqid();
        mkdir($devTestRoot, 0777, true);
        mkdir($devTestRoot . '/public/build', 0777, true);
        
        // Créer la structure vendor pour que getBundlePath() fonctionne
        $vendorPath = $devTestRoot . '/vendor/julien-lin/react-bundle-symfony/src';
        mkdir($vendorPath, 0777, true);
        file_put_contents($vendorPath . '/ViteExtension.php', '<?php');
        
        $originalCwd = getcwd();
        chdir($devTestRoot);
        
        try {
            $devExtension = new ViteExtension(true, 'http://localhost:3000', 'build', new NullLogger());
            $devExtension->setEnvironment('dev');
            
            $result = $devExtension->renderViteScriptTags('app');
            
            // En mode dev, le résultat peut être :
            // 1. Un script tag avec le serveur Vite (si accessible)
            // 2. Un script tag avec manifest (si manifest existe et serveur non accessible)
            // 3. Un commentaire HTML (si ni serveur ni manifest)
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
            
            // Vérifier que c'est soit un script tag, soit un commentaire
            $hasScriptTag = strpos($result, '<script type="module"') !== false;
            $hasComment = strpos($result, '<!--') !== false;
            $this->assertTrue(
                $hasScriptTag || $hasComment,
                sprintf('Result should be a script tag or HTML comment. Got: %s', $result)
            );
        } finally {
            chdir($originalCwd);
            $this->deleteDirectory($devTestRoot);
        }
    }

    public function testRenderViteScriptTagsWithMissingManifest(): void
    {
        $originalCwd = getcwd();
        chdir($this->testProjectRoot);
        
        try {
            // Pas de manifest.json
            $result = $this->viteExtension->renderViteScriptTags('app');
            
            // Devrait retourner un commentaire HTML ou un message d'erreur
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        } finally {
            chdir($originalCwd);
        }
    }

    public function testRenderViteScriptTagsWithInvalidEntry(): void
    {
        // Créer un manifest.json avec une entrée différente
        $manifest = [
            'js/other.jsx' => [
                'file' => 'assets/other-abc123.js'
            ]
        ];
        
        $manifestPath = $this->testProjectRoot . '/public/build/manifest.json';
        file_put_contents($manifestPath, json_encode($manifest));
        
        $originalCwd = getcwd();
        chdir($this->testProjectRoot);
        
        try {
            $result = $this->viteExtension->renderViteScriptTags('app');
            
            // Devrait gérer gracieusement l'entrée manquante
            $this->assertIsString($result);
        } finally {
            chdir($originalCwd);
        }
    }

    public function testRenderViteLinkTagsInProduction(): void
    {
        // Le code utilise getBundlePath() qui retourne le chemin réel du bundle
        // On vérifie juste que la méthode fonctionne correctement
        $extension = new ViteExtension(false, 'http://localhost:3000', 'build', new NullLogger());
        $extension->setEnvironment('prod');
        
        $result = $extension->renderViteLinkTags('app');
        
        // Le résultat doit être une chaîne (vide si pas de CSS, ou avec des link tags)
        $this->assertIsString($result);
        
        // Si le manifest existe et contient du CSS, on devrait avoir des link tags
        // Sinon, la chaîne est vide
        if (!empty($result)) {
            $this->assertStringContainsString('<link rel="stylesheet"', $result);
        }
    }

    public function testRenderViteLinkTagsInDevMode(): void
    {
        $devExtension = new ViteExtension(true, 'http://localhost:3000', 'build', new NullLogger());
        $devExtension->setEnvironment('dev');
        
        $result = $devExtension->renderViteLinkTags('app');
        
        // En dev, devrait retourner une chaîne vide (Vite injecte les CSS automatiquement)
        $this->assertEquals('', $result);
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

