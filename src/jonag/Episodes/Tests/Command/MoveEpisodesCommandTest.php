<?php

namespace jonag\Episodes\Tests\Command;


use jonag\Episodes\Application;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Symfony\Component\Console\Tester\CommandTester;

class MoveEpisodesCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var Application */
    private $application;

    public function setUp()
    {
        $this->application = new Application();

        vfsStream::setup('Episodes');
        vfsStream::create([
            'From' => [],
            'To' => []
        ]);
    }

    public function testIgnoreSamples()
    {
        $container = $this->application->getContainer();

        $container['config'] = [
            'source_directory' => vfsStream::url('Episodes/From'),
            'target_directory' => vfsStream::url('Episodes/To'),
            'ignore_if_nuked' => false,
            'delete_nuked' => false,
            'search_subtitles' => false,
            'prefer_move_over_copy' => false
        ];

        copy(
            __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Fixtures/breakdance.mp4',
            vfsStream::url('Episodes/From/sample-angie.tribeca.s01e07.720p.hdtv.x264-killers.mkv')
        );

        $commandTester = new CommandTester($this->application->find('episodes:move'));
        $commandTester->execute([]);

        $this->assertContains('File sample-angie.tribeca.s01e07.720p.hdtv.x264-killers.mkv ignored because it\'s a sample', $commandTester->getDisplay());
        $this->assertEquals([], vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure()['Episodes']['To'], 'Target directory is empty');
    }

    public function testNormalizeShowName()
    {
        $container = $this->application->getContainer();

        $container['config'] = [
            'source_directory' => vfsStream::url('Episodes/From'),
            'target_directory' => vfsStream::url('Episodes/To'),
            'ignore_if_nuked' => false,
            'delete_nuked' => false,
            'search_subtitles' => false,
            'prefer_move_over_copy' => false
        ];

        copy(
            __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Fixtures/breakdance.mp4',
            vfsStream::url('Episodes/From/angie.tribeca.s01e07.720p.hdtv.x264-killers.mkv')
        );

        $commandTester = new CommandTester($this->application->find('episodes:move'));
        $commandTester->execute([]);

        $this->assertFileExists(vfsStream::url('Episodes/To/Angie Tribeca/Saison 1/angie.tribeca.s01e07.720p.hdtv.x264-killers.mkv'));
    }
}
