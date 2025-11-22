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
}

