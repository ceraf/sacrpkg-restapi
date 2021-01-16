<?php

namespace sacrpkg\RestapiBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class TestCommand extends Command
{
    protected static $defaultName = 'sacrpkg:restapi:test';
    
    protected function configure()
    {
    }

    public static function getCommanName(): string
    {
        return self::$defaultName;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Test command',
            '============',
            '',
        ]);
 
        echo "OK \n";
 
 
        return 0;
    }


}