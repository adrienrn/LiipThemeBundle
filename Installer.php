<?php

namespace Liip\ThemeBundle;

use Liip\ThemeBundle\Locator\ThemeLocator;
use Symfony\Component\Filesystem\Filesystem;

class Installer
{
    /**
     * Locator service for themes.
     * @var \Liip\ThemeBundle\Locator\ThemeLocator
     */
    protected $themeLocator;

    /**
     * Symfony filesystem service.
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $filesystem;

    public function __construct(ThemeLocator $themeLocator, Filesystem $filesystem)
    {
        $this->themeLocator = $themeLocator;
        $this->filesystem = $filesystem;
    }

    /**
     * Install assets for given $theme in $basePath
     *
     * @param  string  $theme    [description]
     * @param  string  $basePath Path to the target directory, defaults to 'web/themes'
     * @param  boolean $symlink  Whether make a symlink or hard copy
     */
    public function installAssets($theme, $basePath = 'web/themes', $symlink=true)
    {
        if(file_exists($basePath)) {
            // Cleanup existing basePath folder, if needed.
            $this->filesystem->remove($basePath);
        }
        $this->filesystem->mkdir($basePath, 0777);

        // Search in bundles first.
        $bundle = $this->themeLocator->locateThemeInBundles($theme);
        if(!empty($bundle)) {
            // Found theme in bundle.
            $originDir = $bundle["path"];

            // Prepare the directory for this bundle.
            $themesAssetsBundleDir = $this->getBundleThemesAssetsPath($basePath, $bundle["bundle"]->getName());
            if(!is_dir($themesAssetsBundleDir)) {
                $this->filesystem->mkdir($themesAssetsBundleDir, 0777);
            }

            $targetDir = $themesAssetsBundleDir . DIRECTORY_SEPARATOR . $theme;

            // $output->writeln(sprintf('Found theme <comment>%s</comment> in <comment>%s</comment> installing in <comment>%s</comment> ', $theme, $bundle["bundle"]->getName(), $targetDir));
        } else {
            // Search in app/
            $path = $this->themeLocator->locateThemeInApp($theme);
            if($path) {
                $originDir = $path;
                $targetDir = $appThemesDir . DIRECTORY_SEPARATOR . $theme;
            }

            // $output->writeln(sprintf('Found theme <comment>%s</comment> in <comment>%s</comment> installing in <comment>%s</comment> ', $theme, $originDir, $targetDir));
        }

        // Only link / mirror the public folder.
        $originDir = realpath($originDir) . DIRECTORY_SEPARATOR . "public";

        if($originDir && $targetDir) {
            if($symlink) {
                // Symlink.
                $this->filesystem->symlink($originDir, $targetDir, true);
            } else {
                // Hard copy.
                $this->hardCopy($originDir, $targetDir);
            }
        }
    }

    /**
     * Get the path for assets of themes from bundles.
     * @param  string $basePath
     * @param  string $bundleName
     * @return string
     */
    public function getBundleThemesAssetsPath($basePath, $bundleName)
    {
        return $basePath . DIRECTORY_SEPARATOR . preg_replace('/bundle$/', "", strtolower($bundleName));
    }

    /**
     * Mirrors the content of $originDir in $targetDir
     *     Inspired by symfony assets:install hardCopy.
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