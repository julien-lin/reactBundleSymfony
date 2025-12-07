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
}
