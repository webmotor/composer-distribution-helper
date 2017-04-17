<?php
namespace Wm\ComposerDistributionHelper\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clean Vcs passwords command
 *
 * @author oprokidnev
 * @author lexxur
 */
class CleanVcsPasswords extends \Composer\Command\BaseCommand
{

    protected $cleaner = null;

    protected function configure()
    {
        $this->setName('distribution:clean-vcs-passwords');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cleaner = new \Wm\ComposerDistributionHelper\Cleaner\VcsPasswords($this->getComposer(), $this->getIO());
        $composer = $this->getComposer();

        $output->writeln('You are gonna remove stored passwords in this packages catalogs:');

        if ($this->getIO()->askConfirmation('Commit deletion (y/N)?', false)) {
            $this->cleaner->clean();
        }
    }

}
