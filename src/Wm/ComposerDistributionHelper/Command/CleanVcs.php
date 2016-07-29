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
        $composer = $this->getComposer();

        // init repos
        $platformOverrides = array();
        if ($composer) {
            $platformOverrides = $composer->getConfig()->get('platform') ? : array();
        }

        $platformRepo  = new \Composer\Repository\PlatformRepository(array(), $platformOverrides);
        $localRepo     = $composer->getRepositoryManager()->getLocalRepository();
        $installedRepo = new \Composer\Repository\CompositeRepository(array($localRepo, $platformRepo));
        $output->writeln('You are gonna remove this directory packages:');

        $paths = [];
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            try {
                if ($package instanceof \Composer\Package\CompletePackage) {
                    $downloader = $this->getComposer()->getDownloadManager()->getDownloaderForInstalledPackage($package);
                    if ($downloader instanceof \Composer\Downloader\GitDownloader) {
                        $paths[] = $path    = $composer->getInstallationManager()->getInstallPath($package);
                        $output->writeln(sprintf('%s', $path));
                    }
                }
            } catch (\Exception $ex) {
                var_dump($ex->getMessage());
            }

//            /* @var $repository \Composer\Repository\ComposerRepository */
//            if ($repository instanceof \Composer\Repository\ComposerRepository && !$repository->getProviderNames()) {
//                $this->parseRepository($repository);
//            }
        }
        if ($this->getIO()->ask('Commit deletion (y/n)?') === 'y') {
            foreach ($paths as $path) {
                echo (sprintf('rm -rf %s/.git', $path)).PHP_EOL;
            }
        }
    }

    /**
     * 
     * @param \Composer\Repository\ComposerRepository $repository
     */
    protected function parseRepository($repository)
    {
        foreach ($repository->getPackages() as $package) {
            
        }
    }

}
