<?php

namespace GlobalInstaller\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

/**
 * Global Installer installs packages in global directory.
 */
class GlobalInstaller extends LibraryInstaller
{

    protected $_config;
    protected $_isInUse = false;
    protected $_globalDir = "vendor-global";
    protected $_globalPackages = array();
    protected $_supportedTypes = array('library');

    public function __construct(IOInterface $io, Composer $composer, $type = null)
    {
        if ($composer->getConfig()->has('composer-global-installer')) {
            $this->_isInUse = true;
            $this->_config = $composer->getConfig()->get('composer-global-installer');

            if (isset($this->_config['vendor-global-dir'])) {
                $this->_globalDir = $this->_config['vendor-global-dir'];
            }

            if (isset($this->_config['vendor-global-types'])) {
                // Bad format, use default
                // @todo throw exception
                if (is_array($this->_config['vendor-global-types'])) {
                    $this->_supportedTypes = $this->_config['vendor-global-types'];
                }
            }

            if ($composer->getConfig()->has('vendor-global-packages')) {
                // Bad format, use default
                // @todo throw exception
                if (is_array($composer->getConfig()->get('vendor-global-packages'))) {
                    $this->_globalPackages = $composer->getConfig()->get('vendor-global-packages');
                }
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
        // Do no remove code
    }

    protected function getPackageBasePath(PackageInterface $package)
    {
        if ($this->_isInUse) {
            // If certains packages are specified to be global
            if (!empty($this->_globalPackages) && !in_array($package->getName(), $this->_globalPackages)) {
                return parent::getPackageBasePath($package);
            }

            $this->initializeGlobalDir();
            $this->initializeVendorDir();

            return $this->_globalDir . '/' . $this->getPackagePath($package);
        } else {
            return parent::getPackageBasePath($package);
        }
    }

    protected function getPackagePath(PackageInterface $package)
    {
        $version = $package->getPrettyName();

        if ($package->isDev() && $reference = $package->getSourceReference()) {
            $reference = (strlen($reference) === 40 ? substr($reference, 0, 7) : $reference);
            // If there is / in reference switch to pretty version
            if (strpos($reference, '/') !== FALSE) {
                $version .= '-' . $package->getPrettyVersion();
            } else {
                $version .= '-' . $reference;
            }
        } else {
            $version .= '-' . $package->getPrettyVersion();
        }

        return $version;
    }

    protected function initializeGlobalDir()
    {
        if ($this->_isInUse) {
            $this->filesystem->ensureDirectoryExists($this->_globalDir);
            // @todo throw exception if path is not accessible
            $this->_globalDir = realpath($this->_globalDir);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array($packageType, $this->_supportedTypes);
    }

}
