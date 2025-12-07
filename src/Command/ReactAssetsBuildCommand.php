<?php

declare(strict_types=1);

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
            ->addOption('prod', 'p', InputOption::VALUE_NONE, 'Mode production sans HMR')
            ->setHelp('Cette commande build les assets React avec Vite. Utilisez --watch pour le développement, --dev pour HMR, ou --prod pour la production.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $watch = $input->getOption('watch');
        $dev = $input->getOption('dev');

        // Détecter si le projet a son propre vite.config.js
        $bundlePath = $this->getBundlePath();
        $projectRoot = $this->getProjectRoot($bundlePath);
        $projectViteConfig = $projectRoot . DIRECTORY_SEPARATOR . 'vite.config.js';
        $projectPackageJson = $projectRoot . DIRECTORY_SEPARATOR . 'package.json';

        // Si le projet a son propre vite.config.js, utiliser celui-ci
        if (file_exists($projectViteConfig) && file_exists($projectPackageJson)) {
            $io->info('Configuration Vite du projet détectée, utilisation de celle-ci.');
            $workingPath = $projectRoot;
        } else {
            $workingPath = $bundlePath;
        }

        // Vérifier que node_modules existe, sinon proposer de l'installer
        if (!is_dir($workingPath . DIRECTORY_SEPARATOR . 'node_modules')) {
            $io->warning('Les dépendances npm ne sont pas installées.');
            if ($io->confirm('Voulez-vous les installer maintenant ?', true)) {
                $npmPath = $this->findNpm();
                if (!$npmPath) {
                    $io->error('npm n\'a pas pu être trouvé. Veuillez installer Node.js et npm, ou installer les dépendances manuellement avec: cd ' . $workingPath . ' && npm install');
                    return Command::FAILURE;
                }

                $io->info('Installation des dépendances npm...');
                $installCommand = $this->prepareInstallCommand($npmPath);
                $installProcess = new Process($installCommand, $workingPath);
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
                    $io->note('Vous pouvez installer manuellement avec: cd ' . $workingPath . ' && npm install');
                    return Command::FAILURE;
                }
            } else {
                $io->note('Vous pouvez installer les dépendances manuellement avec: cd ' . $workingPath . ' && npm install');
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

        // Vérifier la version de Node.js
        $this->checkNodeVersion($io, $npmPath);

        // ✅ P3-CMD-01: Gérer l'option --prod
        $prod = $input->getOption('prod');
        if ($prod && $dev) {
            $io->warning('Options --prod et --dev ne peuvent pas être utilisées ensemble. Utilisation de --prod.');
            $dev = false;
        }

        // Si npm est dans nvm, charger l'environnement nvm
        $command = $this->prepareNpmCommand($npmPath, $watch, $dev, $prod);

        $process = new Process($command);
        $process->setTimeout(null);
        $process->setWorkingDirectory($workingPath);

        // Si npm est dans nvm, définir les variables d'environnement nécessaires
        if (strpos($npmPath, '.nvm') !== false) {
            $nvmDir = dirname(dirname($npmPath));
            $nodePath = dirname($npmPath);
            $process->setEnv([
                'PATH' => $nodePath . ':' . getenv('PATH'),
                'NVM_DIR' => $nvmDir,
            ]);
        }

        $io->note('Exécution de: ' . implode(' ', $command) . ' dans ' . $workingPath);

        try {
            $process->mustRun(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            if (!$watch && !$dev) {
                // ✅ P3-CMD-02: Afficher le chemin du manifest en prod
                $manifestPath = $workingPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'manifest.json';
                if (file_exists($manifestPath)) {
                    $io->success('Build terminé avec succès !');
                    $io->section('Manifest généré:');
                    $io->writeln('  <info>' . $manifestPath . '</info>');
                } else {
                    $io->success('Build terminé avec succès !');
                }
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

        // Normaliser les séparateurs de chemin pour Windows
        $bundlePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $bundlePath);

        // Si le bundle est dans vendor/, vérifier que c'est bien le bon chemin
        $vendorSeparator = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        if (strpos($bundlePath, $vendorSeparator) !== false) {
            // Vérifier que package.json existe pour confirmer que c'est le bon chemin
            if (file_exists($bundlePath . DIRECTORY_SEPARATOR . 'package.json')) {
                return $bundlePath;
            }
        }

        // Sinon, on est dans le développement local (src/ReactBundle)
        // Vérifier que package.json existe
        if (file_exists($bundlePath . DIRECTORY_SEPARATOR . 'package.json')) {
            return $bundlePath;
        }

        // Fallback : remonter depuis le répertoire actuel
        return dirname(__DIR__, 2);
    }

    /**
     * ✅ P3-CMD-01: Prépare la commande npm avec support nvm et l'option --prod
     */
    private function prepareNpmCommand(string $npmPath, bool $watch, bool $dev, bool $prod = false): array
    {
        // Si npm est dans nvm, utiliser bash pour charger l'environnement
        if (strpos($npmPath, '.nvm') !== false) {
            $nvmDir = dirname(dirname($npmPath));
            $nodePath = dirname($npmPath);

            if ($dev) {
                return [
                    'bash',
                    '-c',
                    "export PATH=\"$nodePath:\$PATH\" && export NVM_DIR=\"$nvmDir\" && $npmPath run dev"
                ];
            } elseif ($watch) {
                return [
                    'bash',
                    '-c',
                    "export PATH=\"$nodePath:\$PATH\" && export NVM_DIR=\"$nvmDir\" && $npmPath run build:watch"
                ];
            } elseif ($prod) {
                return [
                    'bash',
                    '-c',
                    "export PATH=\"$nodePath:\$PATH\" && export NVM_DIR=\"$nvmDir\" && $npmPath run build"
                ];
            } else {
                return [
                    'bash',
                    '-c',
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
            // Both --prod and default build are the same (build without HMR)
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
                'bash',
                '-c',
                "export PATH=\"$nodePath:\$PATH\" && export NVM_DIR=\"$nvmDir\" && $npmPath install"
            ];
        }

        return [$npmPath, 'install'];
    }

    /**
     * Vérifie la version de Node.js et affiche un avertissement si nécessaire
     */
    private function checkNodeVersion(SymfonyStyle $io, string $npmPath): void
    {
        try {
            // Trouver node (généralement dans le même répertoire que npm)
            $nodePath = dirname($npmPath) . DIRECTORY_SEPARATOR . 'node';
            if (!file_exists($nodePath) || !is_executable($nodePath)) {
                // Essayer 'node' dans le PATH
                $nodeProcess = new Process(['node', '--version']);
                $nodeProcess->run();
                if (!$nodeProcess->isSuccessful()) {
                    return; // Ne pas bloquer si on ne peut pas vérifier
                }
                $version = trim($nodeProcess->getOutput());
            } else {
                $nodeProcess = new Process([$nodePath, '--version']);
                $nodeProcess->run();
                if (!$nodeProcess->isSuccessful()) {
                    return;
                }
                $version = trim($nodeProcess->getOutput());
            }

            // Extraire le numéro de version (ex: v18.17.0 -> 18.17.0)
            $version = ltrim($version, 'v');
            $majorVersion = (int) explode('.', $version)[0];

            if ($majorVersion < 18) {
                $io->warning(sprintf(
                    'Node.js version %s détectée. Version recommandée: >= 18.0.0. Certaines fonctionnalités peuvent ne pas fonctionner correctement.',
                    $version
                ));
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de vérification de version
        }
    }

    /**
     * Calcule le chemin racine du projet Symfony
     */
    private function getProjectRoot(string $bundlePath): string
    {
        // Normaliser les séparateurs de chemin
        $bundlePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $bundlePath);

        // Si dans vendor/, remonter de 3 niveaux pour atteindre la racine du projet
        $vendorSeparator = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        if (strpos($bundlePath, $vendorSeparator) !== false) {
            return dirname($bundlePath, 3);
        }
        // Sinon, on est dans src/ReactBundle, remonter de 2 niveaux
        return dirname($bundlePath, 2);
    }
}
