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

        if ($dev) {
            $io->info('Démarrage du serveur Vite en mode développement avec HMR...');
            $command = ['npm', 'run', 'dev'];
        } elseif ($watch) {
            $io->info('Build des assets React en mode watch...');
            $command = ['npm', 'run', 'build:watch'];
        } else {
            $io->info('Build des assets React pour la production...');
            $command = ['npm', 'run', 'build'];
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

