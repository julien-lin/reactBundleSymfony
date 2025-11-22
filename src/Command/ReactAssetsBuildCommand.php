<?php

namespace ReactBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'react:build',
    description: 'Build les assets React avec Vite'
)]
class ReactAssetsBuildCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Mode watch pour le développement')
            ->addOption('dev', 'd', InputOption::VALUE_NONE, 'Mode développement avec HMR')
            ->setHelp('Cette commande build les assets React avec Vite. Utilisez --watch pour le développement ou --dev pour le mode développement avec HMR.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $watch = $input->getOption('watch');
        $dev = $input->getOption('dev');

        // Chemin vers le bundle (depuis vendor/ ou src/)
        $bundlePath = $this->getBundlePath();

        // Vérifier que node_modules existe, sinon proposer de l'installer
        if (!is_dir($bundlePath . '/node_modules')) {
            $io->warning('Les dépendances npm ne sont pas installées.');
            if ($io->confirm('Voulez-vous les installer maintenant ?', true)) {
                $npmPath = $this->findNpm();
                if (!$npmPath) {
                    $io->error('npm n\'a pas pu être trouvé. Veuillez installer Node.js et npm, ou installer les dépendances manuellement avec: cd ' . $bundlePath . ' && npm install');
                    return Command::FAILURE;
                }

                $io->info('Installation des dépendances npm...');
                $installProcess = new Process([$npmPath, 'install'], $bundlePath);
                $installProcess->setTimeout(300);

                try {
                    $installProcess->mustRun(function ($type, $buffer) use ($output) {
                        $output->write($buffer);
                    });
                    $io->success('Dépendances npm installées avec succès !');
                } catch (\Exception $e) {
                    $io->error('Erreur lors de l\'installation npm: ' . $e->getMessage());
                    $io->note('Vous pouvez installer manuellement avec: cd ' . $bundlePath . ' && npm install');
                    return Command::FAILURE;
                }
            } else {
                $io->note('Vous pouvez installer les dépendances manuellement avec: cd ' . $bundlePath . ' && npm install');
                return Command::FAILURE;
            }
        }

        // Trouver npm
        $npmPath = $this->findNpm();
        if (!$npmPath) {
            $io->error('npm n\'a pas pu être trouvé. Veuillez installer Node.js et npm.');
            $io->note('Chemins vérifiés: /usr/bin/npm, /usr/local/bin/npm, /opt/homebrew/bin/npm');
            return Command::FAILURE;
        }

        if ($dev) {
            $io->info('Démarrage du serveur Vite en mode développement avec HMR...');
            $command = [$npmPath, 'run', 'dev'];
        } elseif ($watch) {
            $io->info('Build des assets React en mode watch...');
            $command = [$npmPath, 'run', 'build:watch'];
        } else {
            $io->info('Build des assets React pour la production...');
            $command = [$npmPath, 'run', 'build'];
        }

        $process = new Process($command);
        $process->setTimeout(null);
        $process->setWorkingDirectory($bundlePath);

        $io->note('Exécution de: ' . implode(' ', $command) . ' dans ' . $bundlePath);

        try {
            $process->mustRun(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            if (!$watch && !$dev) {
                $io->success('Build terminé avec succès !');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du build: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Trouve le chemin vers npm
     */
    private function findNpm(): ?string
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

    private function getBundlePath(): string
    {
        // Utiliser la réflexion pour trouver le chemin réel du bundle
        $reflection = new \ReflectionClass(\ReactBundle\ReactBundle::class);
        $bundlePath = dirname($reflection->getFileName(), 2);
        
        // Si le bundle est dans vendor/, vérifier que c'est bien le bon chemin
        if (strpos($bundlePath, '/vendor/') !== false) {
            // Vérifier que package.json existe pour confirmer que c'est le bon chemin
            if (file_exists($bundlePath . '/package.json')) {
                return $bundlePath;
            }
        }
        
        // Sinon, on est dans le développement local (src/ReactBundle)
        // Vérifier que package.json existe
        if (file_exists($bundlePath . '/package.json')) {
            return $bundlePath;
        }
        
        // Fallback : remonter depuis le répertoire actuel
        return dirname(__DIR__, 2);
    }
}

