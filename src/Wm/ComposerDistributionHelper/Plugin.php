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

    protected $io;
    protected $composer;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io       = $io;
        $this->composer = $composer;
    }

    public function getCapabilities()
    {
        return [ 
            'Composer\Plugin\Capability\CommandProvider' => 'My\Composer\CommandProvider',
        ];
    }

}
