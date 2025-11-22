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

        // Vérifier si npm est disponible
        $npmCheck = new Process(['npm', '--version']);
        $npmCheck->run();

        if (!$npmCheck->isSuccessful()) {
            $io->write('<warning>npm n\'est pas disponible. Veuillez installer les dépendances manuellement avec: cd ' . $bundlePath . ' && npm install</warning>');
            return;
        }

        // Installer les dépendances npm
        $process = new Process(['npm', 'install'], $bundlePath);
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
}

