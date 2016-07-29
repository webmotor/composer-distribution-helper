<?php

namespace Wm\ComposerDistributionHelper;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * CommandProvider
 *
 * @author oprokidnev
 */
class CommandProvider implements CommandProviderCapability
{

    public function getCommands()
    {
        return [
            new Command\CleanVcs(),
        ];
    }

}
