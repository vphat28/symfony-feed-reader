<?php

namespace AppBundle\Test\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GrabFeeds extends WebTestCase
{
    public function testGrabFeeds()
    {
        $kernel = static::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('feedsreader:grab-feeds');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'urls' => 'http://www.feedforall.com/sample.xml',
            ]
        );

        $this->assertRegExp('/Added item/', $commandTester->getDisplay());
    }
}
