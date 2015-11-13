<?php

/*
 * This file is part of the Liip/ThemeBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\ThemeBundle;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Liip\ThemeBundle\Locator\ThemeLocator;
use Psr\Log\LoggerInterface;

/**
 * Theme installer service.
 *
 * This is a service so we can inject it as reference to different parts of the
 * application. Can be used from command-line using assets:themes-install or to
 * install assets after uploading a new theme in the app.
 *
 * @author adrienrn
 */
class Installer
{
    /**
     * Locator service for themes.
     *
     * @var \Liip\ThemeBundle\Locator\ThemeLocator
     */
    protected $themeLocator;

    /**
     * Symfony filesystem service.
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Logger.
     *  Useful since Symfony 2.4 to show output when launching from console.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(ThemeLocator $themeLocator, Filesystem $filesystem, LoggerInterface $logger = null)
    {
        $this->themeLocator = $themeLocator;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Install assets for given $theme in $basePath.
     *
     * @param string $theme    Name of the theme
     * @param string $basePath Path to the target directory, defaults to 'web/themes'
     * @param bool   $symlink  Whether make a symlink or hard copy
     */
    public function installAssets($theme, $basePath = null, $symlink = true)
    {
        if (is_null($basePath)) {
            $basePath = 'web'.DIRECTORY_SEPARATOR.'themes';
        }

        if (file_exists($basePath) && !is_writable($basePath)) {
            throw new \InvalidArgumentException(
                "'basePath' is not writable"
            );
        }

        if (!file_exists($basePath)) {
            // Create base target directory if needed.
            $this->filesystem->mkdir($basePath, 0777);
        }

        // Search in bundles first.
        $pathInfos = $this->themeLocator->locateThemeInBundles($theme);
        if (!empty($pathInfos)) {
            // Found theme in bundle.
            $originDir = $pathInfos['path'];

            // Prepare the directory for this bundle.
            $themesAssetsBundleDir = $this->getBundleThemesAssetsPath($basePath, $pathInfos['bundle']->getName());
            if (!is_dir($themesAssetsBundleDir)) {
                $this->filesystem->mkdir($themesAssetsBundleDir, 0777);
            }

            $targetDir = $themesAssetsBundleDir.DIRECTORY_SEPARATOR.$theme;

            $this->logger->notice(sprintf('Found theme <comment>%s</comment> in bundle <comment>%s</comment> installing in <comment>%s</comment>', $theme, $pathInfos['bundle']->getName(), $targetDir));
        } else {
            // Search in app/
            $path = $this->themeLocator->locateThemeInApp($theme);
            if ($path) {
                $originDir = $path;
                $targetDir = $basePath.DIRECTORY_SEPARATOR.$theme;

                $this->logger->notice(sprintf('Found theme <comment>%s</comment> in <comment>%s</comment> installing in <comment>%s</comment>', $theme, $originDir, $targetDir));
            }
        }

        if (isset($originDir) && isset($targetDir)) {
            // Only link / mirror the public folder.
            $originDir = realpath($originDir).DIRECTORY_SEPARATOR.'public';

            if (!is_dir($originDir)) {
                $this->logger->warning(sprintf('No assets to install for theme %s. <comment>Skipping.</comment>', $theme));
                return false;
            }

            if ($symlink) {
                // Symlink.
                $this->filesystem->symlink($originDir, $targetDir, true);
            } else {
                // Hard copy.
                $this->hardCopy($originDir, $targetDir);
            }

            return true;
        } else {
            $this->logger->warning(sprintf('Theme <comment>%s</comment> not found. <comment>Skipping.</comment>', $theme));
        }
    }

    /**
     * Get the path for assets of themes from bundles.
     *
     * @param string $basePath
     * @param string $bundleName
     *
     * @return string
     */
    public function getBundleThemesAssetsPath($basePath, $bundleName)
    {
        return $basePath.DIRECTORY_SEPARATOR.preg_replace('/bundle$/', '', strtolower($bundleName));
    }

    /**
     * Mirrors the content of $originDir in $targetDir
     *     Inspired by symfony assets:install hardCopy.
     *
     * @param string $originDir
     * @param string $targetDir
     */
    private function hardCopy($originDir, $targetDir)
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));
    }
}
