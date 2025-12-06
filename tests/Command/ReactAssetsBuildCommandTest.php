<?php

namespace ReactBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use ReactBundle\Command\ReactAssetsBuildCommand;
use Symfony\Component\Console\Command\Command;

class ReactAssetsBuildCommandTest extends TestCase
{
    private ReactAssetsBuildCommand $command;

    protected function setUp(): void
    {
        $this->command = new ReactAssetsBuildCommand();
    }

    public function testCommandExtends(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testCommandName(): void
    {
        $this->assertEquals('react:build', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $description = $this->command->getDescription();
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('assets', strtolower($description));
        $this->assertStringContainsString('react', strtolower($description));
    }

    public function testCommandHasWatchOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('watch'));
    }

    public function testCommandHasDevOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('dev'));
    }

    public function testCommandHasHelp(): void
    {
        $help = $this->command->getHelp();
        $this->assertNotEmpty($help);
    }

    public function testCommandConfigure(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertNotNull($definition);
    }

    public function testCommandIsExecutable(): void
    {
        $this->assertTrue(method_exists($this->command, 'execute'));
    }
}
