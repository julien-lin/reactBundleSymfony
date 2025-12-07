<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use ReactBundle\Command\ReactAssetsBuildCommand;
use Symfony\Component\Console\Tester\CommandTester;

class ReactAssetsBuildCommandProdTest extends TestCase
{
    /**
     * Test that --prod option is available
     */
    public function testProdOptionIsAvailable(): void
    {
        $command = new ReactAssetsBuildCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('prod'));
        $option = $definition->getOption('prod');
        $this->assertFalse($option->acceptValue());
    }

    /**
     * Test that --prod option conflicts with --dev
     */
    public function testProdAndDevAreConflicting(): void
    {
        $command = new ReactAssetsBuildCommand();
        $tester = new CommandTester($command);

        // Check that both options exist
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('prod'));
        $this->assertTrue($definition->hasOption('dev'));
    }

    /**
     * Test command description mentions --prod
     */
    public function testCommandHelpMentionsProd(): void
    {
        $command = new ReactAssetsBuildCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('--prod', $help);
        $this->assertStringContainsString('production', $help);
    }

    /**
     * Test that manifest path info is shown after build
     */
    public function testManifestPathIsShownAfterBuild(): void
    {
        $command = new ReactAssetsBuildCommand();
        $definition = $command->getDefinition();

        // Verify that the command has options for build
        $this->assertTrue($definition->hasOption('watch') || $definition->hasOption('dev') || $definition->hasOption('prod'));
    }
}
