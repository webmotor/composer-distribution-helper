<?php
namespace Wm\ComposerDistributionHelper\Listener;

/**
 * CleanVcsAfterInstall
 *
 * @author oprokidnev
 */
class CleanVcsAfterInstall
{

    const SETTING_FILE = 'distribution-helper.json';

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

    public function trigger(\Composer\Script\Event $scriptEvent)
    {
        $config = $this->composer->getConfig();

        $homeDir  = $config->get('home');
        $filename = $homeDir . '/' . self::SETTING_FILE;

        if (!file_exists($filename)) {
            $optionExists = false;
            $localConfig  = [
                'ask_before_install'               => true,
                'clean_creadentials_after_install' => true,
            ];
            file_put_contents($filename, json_encode($localConfig));
        } else {
            $localConfig = json_decode(file_get_contents($filename), true);
        }

        if ($localConfig['ask_before_install']) {
            $localConfig['clean_creadentials_after_install'] = $this->io->askConfirmation('Should we clean repository credentials [Y/n]?', true);
            $localConfig['ask_before_install']               = false;
        }
        
        if($localConfig['clean_creadentials_after_install']){
            $cleaner = new \Wm\ComposerDistributionHelper\Cleaner\VcsPasswords($this->composer, $this->io);
            $cleaner->clean();
        }

        file_put_contents($filename, json_encode($localConfig));
    }
}
