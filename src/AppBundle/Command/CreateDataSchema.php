<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateDataSchema extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('feedsreader:create-data-schema')
            ->setDescription('Setup database for feedsreader.')
            ->setHelp('This command create database and tables for feedsreader');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('doctrine:database:create');

        $arguments = array(
            'command' => 'doctrine:database:create',
        );

        $commandInput = new ArrayInput($arguments);
        $command->run($commandInput, $output);

        $command = $this->getApplication()->find('doctrine:schema:update');

        $arguments = array(
            'command' => 'doctrine:schema:update',
            '--force' => true,
        );

        $commandInput = new ArrayInput($arguments);
        $command->run($commandInput, $output);
    }
}
