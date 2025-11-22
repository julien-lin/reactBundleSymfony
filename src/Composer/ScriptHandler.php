<?php

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

        if (!file_exists($bundlePath . '/package.json')) {
            $io->write('<error>package.json non trouvé dans le bundle</error>');
            return;
        }

        $io->write('<info>Installation des dépendances npm pour ReactBundle...</info>');

        // Trouver npm
        $npmPath = self::findNpm();
        if (!$npmPath) {
            $io->write('<warning>npm n\'est pas disponible. Veuillez installer les dépendances manuellement avec: cd ' . $bundlePath . ' && npm install</warning>');
            return;
        }

        // Installer les dépendances npm
        $process = new Process([$npmPath, 'install'], $bundlePath);
        $process->setTimeout(300);

        try {
            $process->mustRun(function ($type, $buffer) use ($io) {
                $io->write($buffer);
            });
            $io->write('<info>Dépendances npm installées avec succès !</info>');
        } catch (\Exception $e) {
            $io->write('<error>Erreur lors de l\'installation npm: ' . $e->getMessage() . '</error>');
            $io->write('<comment>Vous pouvez installer manuellement avec: cd ' . $bundlePath . ' && npm install</comment>');
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

        foreach ($possiblePaths as $path) {
            // Si c'est juste "npm", essayer de l'exécuter directement
            if ($path === 'npm') {
                $process = new Process(['which', 'npm']);
                $process->run();
                if ($process->isSuccessful()) {
                    $foundPath = trim($process->getOutput());
                    if ($foundPath && file_exists($foundPath)) {
                        return $foundPath;
                    }
                }
            } elseif (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }
}

