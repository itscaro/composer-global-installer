<?php

namespace GlobalInstaller\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class GlobalInstallerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = new GlobalInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }
}