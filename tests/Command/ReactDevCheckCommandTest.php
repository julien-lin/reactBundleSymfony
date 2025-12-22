<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use ReactBundle\Command\ReactDevCheckCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ReactDevCheckCommandTest extends TestCase
{
    private ReactDevCheckCommand $command;

    protected function setUp(): void
    {
        $this->command = new ReactDevCheckCommand('http://localhost:3000', false);
    }

    public function testCommandExtendsCommand(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testCommandName(): void
    {
        $this->assertEquals('react:dev:check', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $description = $this->command->getDescription();
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('vite', strtolower($description));
        $this->assertStringContainsString('développement', strtolower($description));
    }

    public function testCommandHasExecuteMethod(): void
    {
        $this->assertTrue(method_exists($this->command, 'execute'));
    }

    public function testCommandWithDevModeDisabled(): void
    {
        $command = new ReactDevCheckCommand('http://localhost:3000', false);
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);
        $outputContent = $output->fetch();

        // En mode non-dev, la commande devrait retourner SUCCESS
        $this->assertEquals(Command::SUCCESS, $result);
        $this->assertStringContainsString('Mode développement non activé', $outputContent);
    }

    public function testCommandWithDevModeEnabled(): void
    {
        $command = new ReactDevCheckCommand('http://localhost:3000', true);
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);
        $outputContent = $output->fetch();

        // La commande devrait s'exécuter (peut échouer si le serveur n'est pas accessible)
        $this->assertIsInt($result);
        $this->assertStringContainsString('Vérification du serveur Vite', $outputContent);
    }

    public function testCommandWithInvalidUrl(): void
    {
        $command = new ReactDevCheckCommand('invalid-url', true);
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);
        $outputContent = $output->fetch();

        // Devrait échouer avec une URL invalide
        $this->assertEquals(Command::FAILURE, $result);
        $this->assertStringContainsString('URL Vite invalide', $outputContent);
    }

    public function testCommandWithCustomViteServer(): void
    {
        $command = new ReactDevCheckCommand('http://localhost:5173', true);
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);
        $outputContent = $output->fetch();

        // Devrait afficher l'URL personnalisée
        $this->assertStringContainsString('http://localhost:5173', $outputContent);
    }

    public function testCommandShowsSolutionsWhenServerNotAccessible(): void
    {
        // Utiliser une URL qui n'existe probablement pas
        $command = new ReactDevCheckCommand('http://localhost:9999', true);
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);
        $outputContent = $output->fetch();

        // Devrait afficher des solutions
        $this->assertStringContainsString('Solutions', $outputContent);
        $this->assertStringContainsString('react:build --dev', $outputContent);
        $this->assertStringContainsString('npm run dev', $outputContent);
    }

    public function testCommandConstructorWithLogger(): void
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $command = new ReactDevCheckCommand('http://localhost:3000', true, $logger);

        $this->assertInstanceOf(ReactDevCheckCommand::class, $command);
    }

    public function testCommandConstructorWithDefaults(): void
    {
        $command = new ReactDevCheckCommand();
        $this->assertInstanceOf(ReactDevCheckCommand::class, $command);
    }
}

