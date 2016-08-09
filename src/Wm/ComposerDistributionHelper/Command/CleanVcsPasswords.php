<?php

namespace Wm\ComposerDistributionHelper\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clean Vcs passwords command
 *
 * @author oprokidnev
 */
class CleanVcsPasswords extends \Composer\Command\BaseCommand
{

    protected $lineDelimiter = "\n";

    protected function configure()
    {
        $this->setName('distribution:clean-vcs-passwords');
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
        $output->writeln('You are gonna remove stored passwords in this packages catalogs:');

        $paths = [];
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            try {
                if ($package instanceof \Composer\Package\CompletePackage) {
                    $downloader = $this->getComposer()->getDownloadManager()->getDownloaderForInstalledPackage($package);
                    if ($downloader instanceof \Composer\Downloader\GitDownloader) {
                        $path    = $composer->getInstallationManager()->getInstallPath($package) . '/.git/config';
                        if (!file_exists($path)) {
                            continue;
                        }
                        $paths[] = $path;
                        $output->writeln(sprintf('%s', $path));
                    }
                }
            } catch (\Exception $ex) {
                var_dump($ex->getMessage());
            }
        }

        if ($this->getIO()->ask('Commit deletion (y/n)?') === 'y') {
            foreach ($paths as $path) {
                echo (sprintf('Cleaning passwords in "%s"', $path)) . PHP_EOL;
                $this->cleanGitConfig($path);
            }
            $this->cleanCache($composer);
        }
    }

    /**
     * Check if url exists and clean auth data from it
     * @param string $string
     * @return string
     */
    protected function findAuthDataAndClean(&$string)
    {
        $matches = [];
        preg_match('/^(\s)*url(\s)*=(\s)*(.*)$/i', $string, $matches);
        if (count($matches) !== 5) {
            return $string;
        }
        if (!($parsedUrl = parse_url(trim($matches[4])))) {
            return $string;
        }
        if (!(isset($parsedUrl['user']) || isset($parsedUrl['pass']))) {
            return $string;
        }
        if (isset($parsedUrl['user'])) {
            unset($parsedUrl['user']);
        }
        if (isset($parsedUrl['pass'])) {
            unset($parsedUrl['pass']);
        }
        $cleanUrl = (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '') .
            (isset($parsedUrl['host'])
                ? $parsedUrl['host'] . (isset($parsedUrl['path']) ? $parsedUrl['path'] . (isset($parsedUrl['query']) ? $parsedUrl['query'] : '') : '')
                : '');
        if (!$cleanUrl) {
            return $string;
        }
        return $matches[1] . 'url' . $matches[2] . '=' . $matches[3] . $cleanUrl;
    }

    /**
     * Clean passwords from git config file
     * @param string $path
     */
    protected function cleanGitConfig($path)
    {
        if (!file_exists($path)) {
            return;
        }
        if (!is_writable($path)) {
            echo sprintf('File "%s" is NOT writable') . PHP_EOL;
            return;
        }
        $gitConfigContent = file_get_contents($path);
        $gitConfigContentCleanedArray = [];
        foreach (explode($this->lineDelimiter, $gitConfigContent) as &$lineContent) {
            $gitConfigContentCleanedArray[] = $this->findAuthDataAndClean($lineContent);
        }
        file_put_contents($path, implode($this->lineDelimiter, $gitConfigContentCleanedArray));
    }

    /**
     * Clean catalog recursively
     * @param string $path
     */
    protected function cleanCatalog($path)
    {
        if (is_dir($path)) {
            foreach (glob($path . DIRECTORY_SEPARATOR . '*') as $subPath) {
                $this->cleanCatalog($subPath);
            }
            if (!rmdir($path)) {
                echo sprintf('Can not remove catalog: "%s"', $path) . PHP_EOL;
            }
        } else {
            if (!unlink($path)) {
                echo sprintf('Can not delete file "%s"', $path) . PHP_EOL;
            }
        }
    }

    /**
     * @param string $url
     * @return boolean
     */
    protected function isUrlIncludePassword(&$url)
    {
        if (!($parsedUrl = parse_url($url))) {
            return false;
        }
        if (isset($parsedUrl['user']) || isset($parsedUrl['pass'])) {
            return true;
        }
        return false;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function urlToPath($url)
    {
        return str_replace([':', '/', '@'], '-', $url);
    }

    /**
     * Clean cache catalogs
     * @param \Composer\Composer $composer
     */
    protected function cleanCache(\Composer\Composer $composer)
    {
        echo 'Cleaning passwords in cache' . PHP_EOL;
        $repositories = $composer->getRepositoryManager()->getRepositories();
        if (!count($repositories)) {
            return;
        }
        $composerConfig = $composer->getConfig();
        $packagesUrls = [];
        foreach ($repositories as &$repository) { // getting urls
            $repositoryConfig = $repository->getRepoconfig();
            if (isset($repositoryConfig['url'])) {
                $packagesUrls[$repositoryConfig['url']] = $this->urlToPath($repositoryConfig['url']);
            }
            if (count($repository->getProviderNames()) !== 0) {
                continue;
            }
            foreach ($repository->getPackages() as $package) {
                $sourceUrl = $package->getSourceUrl();
                $packagesUrls[$sourceUrl] = $this->urlToPath($sourceUrl);
            }
        }
        $packagesUrlsFlipped = array_flip($packagesUrls);

        foreach ([$composerConfig->get('cache-vcs-dir'), $composerConfig->get('cache-repo-dir')] as $catalog) {
            foreach (glob($catalog . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $subcatalog) {
                if ($this->isUrlIncludePassword($packagesUrlsFlipped[basename($subcatalog)])) {
                    echo sprintf('Deleting unnecessary catalog "%s"', $subcatalog) . PHP_EOL;
                    $this->cleanCatalog($subcatalog);
                    continue;
                }
                $configFile = $subcatalog . DIRECTORY_SEPARATOR . 'config';
                if (!file_exists($configFile)) {
                    continue;
                }
                echo sprintf('Cleaning passwords in "%s"', $configFile) . PHP_EOL;
                $this->cleanGitConfig($configFile);
            }
        }
    }

}
