<?php

namespace Wm\ComposerDistributionHelper;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Composer distribution helper plugin
 *
 * @author oprokidnev
 */
class Plugin implements PluginInterface, \Composer\Plugin\Capable
{
    /**
     *
     * @var Composer\Composer
     */
    static $composer;
    
    /**
     *
     * @var Composer\IO\IOInterface
     */
    static $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        self::$io       = $io;
        self::$composer = $composer;
    }

    public function getCapabilities()
    {
        return [ 
            'Composer\Plugin\Capability\CommandProvider' => CommandProvider::class,
        ];
    }


}
