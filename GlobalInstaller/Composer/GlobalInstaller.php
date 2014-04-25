<?php

namespace GlobalInstaller\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

/**
 * Global Installer installs packages in a directory with the version number
 */
class GlobalInstaller extends LibraryInstaller
{

    protected $globalDir;
    protected $globalPackages = array();
    protected $supportedType = array('library');

    public function __construct(IOInterface $io, Composer $composer, $type = null)
    {
        if ($composer->getConfig()->has('vendor-global-dir')) {
            $this->globalDir = $composer->getConfig()->get('vendor-global-dir');
        } else {
            $this->globalDir = "vendor-global";
        }

        if ($composer->getConfig()->has('vendor-global')) {
            $this->globalPackages = $composer->getConfig()->get('vendor-global');
            if (!is_array($this->globalPackages)) {
                $this->globalPackages = array();
            }
        }

        parent::__construct($io, $composer, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->initializeGlobalDir();
        parent::install($repo, $package);
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->initializeGlobalDir();
        parent::update($repo, $initial, $target);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$repo->hasPackage($package)) {
            return;
        }

        $this->removeBinaries($package);
        $repo->removePackage($package);
    }

    protected function installCode(PackageInterface $package)
    {
        if (!is_readable($this->getInstallPath($package))) {
            parent::installCode($package);
        }
    }

    protected function updateCode(PackageInterface $initial, PackageInterface $target)
    {
        $this->installCode($target);
    }

    protected function removeCode(PackageInterface $package)
    {

    }

    protected function getPackageBasePath(PackageInterface $package)
    {
        if (isset($this->globalDir)) {
            // If certains packages are specified to be global
            if (!empty($this->globalPackages) && !in_array($package->getName(), $this->globalPackages)) {
                return $this->getPackageBasePath($package);
            }

            $this->initializeGlobalDir();
            $this->initializeVendorDir();

            return $this->globalDir . '/' . $this->getPackagePath($package);
        } else {
            return $this->getPackageBasePath($package);
        }
    }

    protected function getPackagePath(PackageInterface $package)
    {
        /*
        $version = $package->getVersion();
        if ($package->isDev() && $reference = $package->getSourceReference()) {
            $version .= '-' . (strlen($reference) === 40 ? substr($reference, 0, 7) : $reference);
        }
        */

        return $package->getPrettyName() . '-' . $package->getPrettyVersion();
    }

    protected function initializeGlobalDir()
    {
        $this->filesystem->ensureDirectoryExists($this->globalDir);
        $this->globalDir = realpath($this->globalDir);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array($packageType, $this->supportedType);
    }

}
