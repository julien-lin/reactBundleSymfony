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
                $installCommand = $this->prepareInstallCommand($npmPath);
                $installProcess = new Process($installCommand, $bundlePath);
                $installProcess->setTimeout(600);
                
                // Si npm est dans nvm, définir les variables d'environnement
                if (strpos($npmPath, '.nvm') !== false) {
                    $nvmDir = dirname(dirname($npmPath));
                    $nodePath = dirname($npmPath);
                    $installProcess->setEnv([
                        'PATH' => $nodePath . ':' . getenv('PATH'),
                        'NVM_DIR' => $nvmDir,
                    ]);
                }

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

        // Si npm est dans nvm, charger l'environnement nvm
        $command = $this->prepareNpmCommand($npmPath, $watch, $dev);

        $process = new Process($command);
        $process->setTimeout(null);
        $process->setWorkingDirectory($bundlePath);
        
        // Si npm est dans nvm, définir les variables d'environnement nécessaires
        if (strpos($npmPath, '.nvm') !== false) {
            $nvmDir = dirname(dirname($npmPath));
            $nodePath = dirname($npmPath);
            $process->setEnv([
                'PATH' => $nodePath . ':' . getenv('PATH'),
                'NVM_DIR' => $nvmDir,
            ]);
        }

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

    /**
     * Prépare la commande npm avec support nvm si nécessaire
     */
    private function prepareNpmCommand(string $npmPath, bool $watch, bool $dev): array
    {
        // Si npm est dans nvm, utiliser bash pour charger l'environnement
        if (strpos($npmPath, '.nvm') !== false) {
            $nvmDir = dirname(dirname($npmPath));
            $nodePath = dirname($npmPath);
            
            if ($dev) {
                return [
                    'bash', '-c',
                    "export PATH=\"$nodePath:\$PATH\" && export NVM_DIR=\"$nvmDir\" && $npmPath run dev"
                ];
            } elseif ($watch) {
                return [
                    'bash', '-c',
                    "export PATH=\"$nodePath:\$PATH\" && export NVM_DIR=\"$nvmDir\" && $npmPath run build:watch"
                ];
            } else {
                return [
                    'bash', '-c',
                    "export PATH=\"$nodePath:\$PATH\" && export NVM_DIR=\"$nvmDir\" && $npmPath run build"
                ];
            }
        }

        // Sinon, utiliser npm directement
        if ($dev) {
            return [$npmPath, 'run', 'dev'];
        } elseif ($watch) {
            return [$npmPath, 'run', 'build:watch'];
        } else {
            return [$npmPath, 'run', 'build'];
        }
    }

    /**
     * Prépare la commande d'installation npm avec support nvm si nécessaire
     */
    private function prepareInstallCommand(string $npmPath): array
    {
        // Si npm est dans nvm, utiliser bash pour charger l'environnement
        if (strpos($npmPath, '.nvm') !== false) {
            $nvmDir = dirname(dirname($npmPath));
            $nodePath = dirname($npmPath);
            return [
                'bash', '-c',
                "export PATH=\"$nodePath:\$PATH\" && export NVM_DIR=\"$nvmDir\" && $npmPath install"
            ];
        }

        return [$npmPath, 'install'];
    }
}

