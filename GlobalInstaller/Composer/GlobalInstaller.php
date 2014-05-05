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
    protected $_globalDir;
    protected $_globalPackages = array();
    protected $_supportedTypes = array('library');

    public function __construct(IOInterface $io, Composer $composer, $type = null)
    {
        if ($composer->getConfig()->has('composer-global-installer')) {
            $this->_config = $composer->getConfig()->get('composer-global-installer');

            if (isset($this->_config['vendor-global-dir'])) {
                $this->_globalDir = $this->_config['vendor-global-dir'];
            } else {
                $this->_globalDir = "vendor-global";
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
        if (isset($this->_globalDir)) {
            // If certains packages are specified to be global
            if (!empty($this->_globalPackages) && !in_array($package->getName(), $this->_globalPackages)) {
                return $this->getPackageBasePath($package);
            }

            $this->initializeGlobalDir();
            $this->initializeVendorDir();

            return $this->_globalDir . '/' . $this->getPackagePath($package);
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
        $this->filesystem->ensureDirectoryExists($this->_globalDir);
        // @todo throw exception if path is not accessible
        $this->_globalDir = realpath($this->_globalDir);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array($packageType, $this->_supportedTypes);
    }

}
