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
     * @var \Composer\Composer
     */
    static protected $composer;

    /**
     *
     * @var \Composer\IO\IOInterface
     */
    static protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        self::$io       = $io;
        self::$composer = $composer;
    }

    /**
     * 
     * @return \Composer\Composer
     * @throws \Exception
     */
    public static function getComposer()
    {
        if (self::$composer === null) {
            throw new \Exception('Can\'t access composer instance');
        }
        return self::$composer;
    }

    /**
     * 
     * @return \Composer\IO\IOInterface
     * @throws \Exception
     */
    public static function getIo()
    {
        if (self::$io === null) {
            throw new \Exception('Can\'t access io instance');
        }
        return self::$io;
    }

    public function getCapabilities()
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => CommandProvider::class,
        ];
    }

}
