<?php

namespace Wm\ComposerDistributionHelper\Command;

/**
 * Clean Vcs info command
 *
 * @author oprokidnev
 */
class CleanVcs extends \Composer\Command\BaseCommand
{
    
    protected function configure()
    {
        $this->setName('distribution-helper');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clean vcs data...');
    }
}
