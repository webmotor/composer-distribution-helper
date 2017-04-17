<?php
namespace Wm\ComposerDistributionHelper\Cleaner;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  VcsPasswords
 *
 * @author oprokidnev
 * @author lexxur
 */
class VcsPasswords
{

    const LINE_DELIMITER = PHP_EOL;

    /**
     *
     * @var \Composer\Composer
     */
    protected $composer;

    /**
     *
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    public function __construct(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    public function clean()
    {
        $composer = $this->composer;

        // init repos
        $platformOverrides = array();
        if ($composer) {
            $platformOverrides = $composer->getConfig()->get('platform') ?: array();
        }

        $platformRepo  = new \Composer\Repository\PlatformRepository(array(), $platformOverrides);
        $localRepo     = $composer->getRepositoryManager()->getLocalRepository();
        $installedRepo = new \Composer\Repository\CompositeRepository(array($localRepo, $platformRepo));

        $this->io->write('<info>composer-distribution-helper:</info> cleaning git credentials...');
        $paths = [];
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            try {
                if ($package instanceof \Composer\Package\CompletePackage) {
                    $downloader = $this->composer->getDownloadManager()->getDownloaderForInstalledPackage($package);
                    if ($downloader instanceof \Composer\Downloader\GitDownloader) {
                        $path = $composer->getInstallationManager()->getInstallPath($package) . '/.git/config';
                        if (!file_exists($path)) {
                            continue;
                        }
                        $paths[] = $path;
                    }
                }
            } catch (\Exception $ex) {
                var_dump($ex->getMessage());
            }
        }
        
        if ($this->io->isVerbose()) {
            $this->io->write('<info>composer-distribution-helper:</info> clean passwords from: ');
        }
        foreach ($paths as $path) {
            if ($this->io->isVerbose()) {
                $this->io->write(sprintf(' - "vendor%s"', \mb_substr($path, \mb_strlen(\realpath($this->composer->getConfig()->get('vendor-dir'))) )) ) . PHP_EOL;
            }
            $this->cleanGitConfig($path);
            
            if(\is_dir($logsDir = dirname($path).'/logs')){
                $this->cleanCatalog($logsDir);
            }
        }
        $this->cleanCache($composer);
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
            (isset($parsedUrl['host']) ? $parsedUrl['host'] . (isset($parsedUrl['path']) ? $parsedUrl['path'] . (isset($parsedUrl['query']) ? $parsedUrl['query'] : '') : '') : '');
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
            $this->io->writeError(sprintf('File "%s" is NOT writable'));
            return;
        }
        $gitConfigContent             = file_get_contents($path);
        $gitConfigContentCleanedArray = [];
        foreach (explode(self::LINE_DELIMITER, $gitConfigContent) as &$lineContent) {
            $gitConfigContentCleanedArray[] = $this->findAuthDataAndClean($lineContent);
        }
        file_put_contents($path, implode(self::LINE_DELIMITER, $gitConfigContentCleanedArray));
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
                $this->io->writeError(sprintf('Can not remove catalog: "%s"', $path));
            }
        } else {
            if (!unlink($path)) {
                $this->io->writeError( sprintf('Can not delete file "%s"', $path) );
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
        $this->io->write('<info>composer-distribution-helper:</info> cleaning passwords in cache...');
        $repositories = $composer->getRepositoryManager()->getRepositories();
        if (!count($repositories)) {
            return;
        }
        $composerConfig = $composer->getConfig();
        $packagesUrls   = [];
        foreach ($repositories as &$repository) { // getting urls
            $repositoryConfig = $repository->getRepoconfig();
            if (isset($repositoryConfig['url'])) {
                $packagesUrls[$repositoryConfig['url']] = $this->urlToPath($repositoryConfig['url']);
            }
            if (!$repository->count()) {
                continue;
            }
            foreach ($repository->getPackages() as $package) {
                $sourceUrl                = $package->getSourceUrl();
                $packagesUrls[$sourceUrl] = $this->urlToPath($sourceUrl);
            }
        }
        $packagesUrlsFlipped = array_flip($packagesUrls);

        if ($this->io->isVerbose()) {
            $this->io->write('<info>composer-distribution-helper:</info> clean passwords from cache folder: ');
        }

        foreach ([$composerConfig->get('cache-vcs-dir'), $composerConfig->get('cache-repo-dir')] as $catalog) {
            foreach (glob($catalog . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $subcatalog) {
                if ($this->isUrlIncludePassword($packagesUrlsFlipped[basename($subcatalog)])) {
                    if ($this->io->isVerbose()) {
                        $this->io->write( sprintf('<info>composer-distribution-helper:</info>  deleting unnecessary catalog "%s"', $subcatalog) );
                    }
                    $this->cleanCatalog($subcatalog);
                    continue;
                }
                $configFile = $subcatalog . DIRECTORY_SEPARATOR . 'config';
                if (!file_exists($configFile)) {
                    continue;
                }
                if ($this->io->isVerbose()) {
                    $this->io->write( sprintf(' - "%s"', $configFile) );
                }
                $this->cleanGitConfig($configFile);
                
                if(\is_dir($logsDir = $catalog.'/logs')){
                    $this->cleanCatalog($logsDir);
                }
            }
        }
    }
}
