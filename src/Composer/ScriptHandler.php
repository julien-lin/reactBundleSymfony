<?php

declare(strict_types=1);

namespace ReactBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

class ScriptHandler
{
    /**
     * Installe les dépendances npm après l'installation du bundle
     */
    public static function installAssets(Event $event): void
    {
        $io = $event->getIO();
        $bundlePath = __DIR__ . '/../../..';
        $bundlePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $bundlePath);

        if (!file_exists($bundlePath . DIRECTORY_SEPARATOR . 'package.json')) {
            $io->write('<error>package.json non trouvé dans le bundle</error>');
            return;
        }

        // Si node_modules existe déjà, ne rien faire
        if (is_dir($bundlePath . DIRECTORY_SEPARATOR . 'node_modules')) {
            $io->write('<info>Les dépendances npm sont déjà installées.</info>');
            return;
        }

        $io->write('<info>Installation automatique des dépendances npm pour ReactBundle...</info>');

        // Trouver npm avec plusieurs tentatives
        $npmPath = self::findNpm();

        if (!$npmPath) {
            $io->write('<warning>npm n\'a pas pu être trouvé automatiquement.</warning>');
            $io->write('<comment>Vous pouvez installer les dépendances manuellement avec:</comment>');
            $io->write('<comment>  cd ' . $bundlePath . ' && npm install</comment>');
            $io->write('');
            $io->write('<comment>Ou utiliser la commande Symfony: php bin/console react:build</comment>');
            $io->write('<comment>qui proposera de les installer automatiquement.</comment>');
            return;
        }

        $io->write('<info>npm trouvé: ' . $npmPath . '</info>');

        // Préparer la commande avec support nvm si nécessaire
        $installCommand = self::prepareInstallCommand($npmPath);
        $process = new Process($installCommand, $bundlePath);
        $process->setTimeout(600); // Augmenter le timeout pour les installations lentes

        // Si npm est dans nvm, définir les variables d'environnement AVANT la commande
        if (strpos($npmPath, '.nvm') !== false) {
            $nvmDir = dirname(dirname($npmPath));
            $nodePath = dirname($npmPath);
            $env = $_ENV; // Récupérer l'environnement actuel
            $env['PATH'] = $nodePath . ':' . ($env['PATH'] ?? getenv('PATH'));
            $env['NVM_DIR'] = $nvmDir;
            $process->setEnv($env);
        }

        // ✅ P3-RETRY-01: Implémenter une retry logic avec backoff exponentiel
        $maxRetries = 3;
        $retryDelay = 2; // secondes

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $io->write('<info>Tentative d\'installation npm... (tentative ' . $attempt . '/' . $maxRetries . ')</info>');

                $process->mustRun(function ($type, $buffer) use ($io) {
                    // Afficher seulement les messages importants pour ne pas polluer la sortie
                    if ($type === Process::ERR || strpos($buffer, 'error') !== false || strpos($buffer, 'Error') !== false) {
                        $io->write($buffer, false);
                    } elseif (strpos($buffer, 'added') !== false || strpos($buffer, 'up to date') !== false) {
                        $io->write($buffer, false);
                    }
                });
                $io->write('<info>✓ Dépendances npm installées avec succès !</info>');
                return; // Success
            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    $io->write('<warning>Tentative ' . $attempt . ' échouée: ' . $e->getMessage() . '</warning>');
                    $io->write('<comment>Attente de ' . $retryDelay . 's avant la prochaine tentative...</comment>');
                    sleep($retryDelay);
                    $retryDelay *= 2; // Backoff exponentiel (2s, 4s, 8s)

                    // Créer une nouvelle instance du process pour la prochaine tentative
                    $process = new Process($installCommand, $bundlePath);
                    $process->setTimeout(600);
                    if (strpos($npmPath, '.nvm') !== false) {
                        $nvmDir = dirname(dirname($npmPath));
                        $nodePath = dirname($npmPath);
                        $env = $_ENV;
                        $env['PATH'] = $nodePath . ':' . ($env['PATH'] ?? getenv('PATH'));
                        $env['NVM_DIR'] = $nvmDir;
                        $process->setEnv($env);
                    }
                } else {
                    // Dernière tentative échouée
                    $io->write('<error>Erreur lors de l\'installation npm après ' . $maxRetries . ' tentatives: ' . $e->getMessage() . '</error>');
                    $io->write('<comment>Vous pouvez installer manuellement avec: cd ' . $bundlePath . ' && npm install</comment>');
                    $io->write('<comment>Ou utiliser: php bin/console react:build</comment>');
                }
            }
        }
    }

    /**
     * Trouve le chemin vers npm
     */
    private static function findNpm(): ?string
    {
        // Chemins communs pour npm
        $possiblePaths = [
            'npm', // Dans le PATH
            '/usr/bin/npm',
            '/usr/local/bin/npm',
            '/opt/homebrew/bin/npm',
            '/usr/local/node/bin/npm',
            '/opt/node/bin/npm',
        ];

        // Chercher npm via which/whereis
        $whichProcess = new Process(['which', 'npm']);
        $whichProcess->run();
        if ($whichProcess->isSuccessful()) {
            $foundPath = trim($whichProcess->getOutput());
            if ($foundPath && file_exists($foundPath) && is_executable($foundPath)) {
                return $foundPath;
            }
        }

        // Chercher via whereis (Linux)
        $whereisProcess = new Process(['whereis', '-b', 'npm']);
        $whereisProcess->run();
        if ($whereisProcess->isSuccessful()) {
            $output = trim($whereisProcess->getOutput());
            if (preg_match('/npm:\s*(.+)/', $output, $matches)) {
                $paths = explode(' ', trim($matches[1]));
                foreach ($paths as $path) {
                    if (file_exists($path) && is_executable($path)) {
                        return $path;
                    }
                }
            }
        }

        // Vérifier les chemins possibles
        foreach ($possiblePaths as $path) {
            if ($path !== 'npm' && file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Chercher dans les emplacements nvm courants
        $home = getenv('HOME') ?: getenv('USERPROFILE');
        if ($home) {
            $nvmPaths = [
                $home . '/.nvm/versions/node/*/bin/npm',
                $home . '/.nvm/current/bin/npm',
            ];
            foreach ($nvmPaths as $pattern) {
                $glob = glob($pattern);
                if (!empty($glob) && file_exists($glob[0]) && is_executable($glob[0])) {
                    return $glob[0];
                }
            }
        }

        return null;
    }

    /**
     * Prépare la commande d'installation npm avec support nvm si nécessaire
     *
     * @param string $npmPath Le chemin vers npm (validé et sûr)
     * @return array La commande à exécuter
     */
    private static function prepareInstallCommand(string $npmPath): array
    {
        // Si npm est dans nvm, utiliser bash pour charger l'environnement
        if (strpos($npmPath, '.nvm') !== false) {
            $nvmDir = dirname(dirname($npmPath));
            $nodePath = dirname($npmPath);

            // SÉCURITÉ: Utiliser escapeshellarg() pour éviter les injections de commandes
            // Chaque argument est correctement échappé
            return [
                'bash',
                '-c',
                sprintf(
                    'export PATH=%s:$PATH && export NVM_DIR=%s && %s install',
                    escapeshellarg($nodePath),
                    escapeshellarg($nvmDir),
                    escapeshellarg($npmPath)
                )
            ];
        }

        // SÉCURITÉ: Retourner le tableau directement - Process() gère les tableaux en toute sécurité
        return [$npmPath, 'install'];
    }

    /**
     * Génère automatiquement un vite.config.js optimisé dans le projet
     * ✅ P0-IMPROVEMENT: Génération automatique de vite.config.js
     * ✅ P1-IMPROVEMENT: Génération optionnelle de tsconfig.json
     */
    public static function generateViteConfig(Event $event): void
    {
        $io = $event->getIO();
        
        // Trouver le projet root (remonter depuis vendor/ ou src/)
        $bundlePath = __DIR__ . '/../../..';
        $bundlePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $bundlePath);
        
        $vendorSeparator = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        if (strpos($bundlePath, $vendorSeparator) !== false) {
            $projectRoot = dirname($bundlePath, 3);
        } else {
            $projectRoot = dirname($bundlePath, 2);
        }
        
        $viteConfigPath = $projectRoot . DIRECTORY_SEPARATOR . 'vite.config.js';
        
        // Ne pas écraser un vite.config.js existant
        if (file_exists($viteConfigPath)) {
            $io->write('<info>vite.config.js existe déjà, pas de génération automatique.</info>');
        } else {
            // Lire le template
            $templatePath = __DIR__ . '/../../Resources/templates/vite.config.js';
            if (file_exists($templatePath)) {
                $template = file_get_contents($templatePath);
                
                // Écrire le fichier
                if (file_put_contents($viteConfigPath, $template) !== false) {
                    $io->write('<info>✓ vite.config.js généré automatiquement dans la racine du projet</info>');
                    $io->write('<comment>Vous pouvez le personnaliser selon vos besoins.</comment>');
                }
            }
        }
        
        // ✅ P1-IMPROVEMENT: Générer aussi tsconfig.json si demandé ou si TypeScript est détecté
        $tsConfigPath = $projectRoot . DIRECTORY_SEPARATOR . 'tsconfig.json';
        if (!file_exists($tsConfigPath)) {
            $tsConfigTemplatePath = __DIR__ . '/../../Resources/templates/tsconfig.json';
            if (file_exists($tsConfigTemplatePath)) {
                $tsConfigTemplate = file_get_contents($tsConfigTemplatePath);
                
                // Vérifier si TypeScript est installé
                $packageJsonPath = $projectRoot . DIRECTORY_SEPARATOR . 'package.json';
                $hasTypeScript = false;
                if (file_exists($packageJsonPath)) {
                    $packageJson = json_decode(file_get_contents($packageJsonPath), true);
                    $hasTypeScript = isset($packageJson['devDependencies']['typescript']) ||
                                   isset($packageJson['dependencies']['typescript']);
                }
                
                if ($hasTypeScript || $io->askConfirmation('<question>Voulez-vous générer tsconfig.json pour TypeScript ? (y/n)</question> ', false)) {
                    if (file_put_contents($tsConfigPath, $tsConfigTemplate) !== false) {
                        // Générer aussi tsconfig.node.json
                        $tsConfigNodePath = $projectRoot . DIRECTORY_SEPARATOR . 'tsconfig.node.json';
                        $tsConfigNodeTemplatePath = __DIR__ . '/../../Resources/templates/tsconfig.node.json';
                        if (file_exists($tsConfigNodeTemplatePath)) {
                            $tsConfigNodeTemplate = file_get_contents($tsConfigNodeTemplatePath);
                            file_put_contents($tsConfigNodePath, $tsConfigNodeTemplate);
                        }
                        
                        $io->write('<info>✓ tsconfig.json généré automatiquement</info>');
                        $io->write('<comment>Vous pouvez maintenant utiliser TypeScript (.tsx) pour vos composants React.</comment>');
                    }
                }
            }
        }
    }
}
