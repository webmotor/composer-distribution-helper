<?php

namespace Wm\ComposerDistributionHelper\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clean Vcs info command
 *
 * @author oprokidnev
 */
class CleanVcs extends \Composer\Command\BaseCommand
{
    
    protected function configure()
    {
        $this->setName('distribution:clean-vcs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clean vcs data...');
        var_dump(\Wm\ComposerDistributionHelper\Plugin::$composer);
    }
}
