<?php

declare(strict_types=1);

namespace ReactBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'react:dev:check',
    description: 'Vérifie que le serveur Vite est accessible en mode développement'
)]
class ReactDevCheckCommand extends Command
{
    private string $viteServer;
    private bool $isDev;
    private ?LoggerInterface $logger;

    public function __construct(
        string $viteServer = 'http://localhost:3000',
        bool $isDev = false,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct();
        $this->viteServer = $viteServer;
        $this->isDev = $isDev;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si on est en mode dev
        if (!$this->isDev) {
            $io->info('Mode développement non activé. Cette commande est uniquement utile en mode dev.');
            $io->note('Pour activer le mode dev, assurez-vous que APP_ENV=dev et APP_DEBUG=1 dans votre .env');
            return Command::SUCCESS;
        }

        $io->title('Vérification du serveur Vite');

        // Vérifier que l'URL est valide
        if (!filter_var($this->viteServer, FILTER_VALIDATE_URL)) {
            $io->error(sprintf('URL Vite invalide : %s', $this->viteServer));
            return Command::FAILURE;
        }

        $io->section('Informations');
        $io->listing([
            sprintf('URL du serveur Vite : <info>%s</info>', $this->viteServer),
            sprintf('Mode développement : <info>%s</info>', $this->isDev ? 'Oui' : 'Non'),
        ]);

        // Tester la connexion au serveur Vite
        $io->section('Test de connexion');
        $io->text('Tentative de connexion au serveur Vite...');

        $isAccessible = $this->checkViteServer();

        if ($isAccessible) {
            $io->success('✅ Le serveur Vite est accessible et fonctionne correctement !');
            $io->text([
                '',
                'Le HMR (Hot Module Replacement) devrait fonctionner correctement.',
                'Vous pouvez modifier vos composants React et voir les changements en temps réel.',
            ]);

            if ($this->logger) {
                $this->logger->info('Vite server check passed', [
                    'vite_server' => $this->viteServer,
                    'is_dev' => $this->isDev,
                ]);
            }

            return Command::SUCCESS;
        }

        // Le serveur n'est pas accessible
        $io->error('❌ Le serveur Vite n\'est pas accessible');
        $io->warning([
            'Le serveur Vite ne répond pas à l\'adresse : ' . $this->viteServer,
            '',
            'Cela signifie que le HMR ne fonctionnera pas.',
            'Les composants React seront chargés depuis le manifest.json (build de production).',
        ]);

        $io->section('Solutions');
        $io->listing([
            'Démarrer le serveur Vite avec :',
            '  <info>php bin/console react:build --dev</info>',
            '',
            'Ou directement avec npm :',
            '  <info>npm run dev</info>',
            '',
            'Vérifier que le port 3000 n\'est pas utilisé par un autre processus :',
            '  <info>lsof -i :3000</info> (Linux/Mac)',
            '  <info>netstat -ano | findstr :3000</info> (Windows)',
            '',
            'Si vous utilisez un autre port, mettez à jour la configuration :',
            '  <info>config/packages/react.yaml</info>',
            '  <info>vite_server: \'http://localhost:5173\'</info>',
        ]);

        if ($this->logger) {
            $this->logger->warning('Vite server check failed', [
                'vite_server' => $this->viteServer,
                'is_dev' => $this->isDev,
            ]);
        }

        return Command::FAILURE;
    }

    /**
     * Vérifie si le serveur Vite est accessible
     *
     * @return bool True si le serveur est accessible, false sinon
     */
    private function checkViteServer(): bool
    {
        try {
            $url = rtrim($this->viteServer, '/') . '/@vite/client';
            
            // Utiliser file_get_contents avec un contexte HTTP pour éviter la dépendance à HttpClient
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'method' => 'GET',
                    'header' => "User-Agent: ReactBundle/2.0\r\n",
                    'ignore_errors' => true,
                ],
            ]);

            $result = @file_get_contents($url, false, $context);
            
            if ($result === false) {
                return false;
            }

            // Vérifier le code de statut HTTP depuis les headers
            if (isset($http_response_header)) {
                $statusLine = $http_response_header[0];
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
                    $statusCode = (int) $matches[1];
                    return $statusCode >= 200 && $statusCode < 300;
                }
            }

            // Si on a du contenu, considérer que c'est OK
            return !empty($result);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->debug('Vite server check error', [
                    'error' => $e->getMessage(),
                    'vite_server' => $this->viteServer,
                ]);
            }
            return false;
        }
    }
}

