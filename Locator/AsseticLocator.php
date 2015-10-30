<?php

namespace Liip\ThemeBundle\Locator;

use Symfony\Component\HttpKernel\KernelInterface;
use Liip\ThemeBundle\Locator\FileLocator;
use Liip\ThemeBundle\ActiveTheme;

class AsseticLocator extends FileLocator
{
    /**
     * Constructor.
     *
     * @param FileLocatorInterface $locator     A FileLocatorInterface instance
     * @param string               $cacheDir    The cache path
     * @param ActiveTheme          $activeTheme
     */
    public function __construct(
        KernelInterface $kernel,
        ActiveTheme $activeTheme,
        $path = null,
        array $paths = array(),
        array $pathPatterns = array()
    )
    {
        parent::__construct($kernel, $activeTheme, $path, $paths, $pathPatterns);
    }

    public function getLocator()
    {
        return $this->locator;
    }

    public function locateBundleResource($name, $dir = null, $first = true)
    {
        if (false !== strpos($name, '..')) {
            throw new \RuntimeException(sprintf('File name "%s" contains invalid characters (..).', $name));
        }

        // Explode path components.
        list($bundleName, $path) = explode('/', $name, 2);
        $bundleName = substr($name, 1);

        if (0 !== strpos($path, 'Resources')) {
            throw new \RuntimeException('Template files have to be in Resources.');
        }

        $resourceBundle = null;
        $bundles = $this->kernel->getBundles();
        $files = array();

        $parameters = array(
            '%app_path%' => $this->path,
            '%dir%' => $dir,
            '%override_path%' => substr($path, strlen('Resources/')),
            '%current_theme%' => $this->lastTheme,
            '%current_device%' => $this->activeTheme->getDeviceType(),
            '%template%' => substr($path, strlen('Resources/views/')),
        );

        foreach ($bundles as $bundle) {
            $parameters = array_merge($parameters, array(
                '%bundle_path%' => $bundle->getPath(),
                '%bundle_name%' => $bundle->getName(),
            ));

            $checkPaths = $this->getPathsForBundleResource($parameters);

            foreach ($checkPaths as $checkPath) {
                if (file_exists($checkPath)) {
                    if (null !== $resourceBundle) {
                        throw new \RuntimeException(sprintf('"%s" resource is hidden by a resource from the "%s" derived bundle. Create a "%s" file to override the bundle resource.',
                            $path,
                            $resourceBundle,
                            $checkPath
                        ));
                    }

                    if ($first) {
                        return $checkPath;
                    }
                    $files[] = $checkPath;
                }
            }

            $file = $bundle->getPath().'/'.$path;
            if (file_exists($file)) {
                if ($first) {
                    return $file;
                }
                $files[] = $file;
                $resourceBundle = $bundle->getName();
            }
        }

        if (count($files) > 0) {
            return $first ? $files[0] : $files;
        }

        throw new \InvalidArgumentException(sprintf('Unable to find file "%s".', $name));
    }

    public function locate($asset, $currentPath = null, $first = true)
    {
        try {
            return $this->locateBundleResource("@%bundle_name%/Resources/views/" . $asset, $currentPath);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(sprintf('Unable to find template "%s" in "%s".', $asset, $e->getMessage()), 0, $e);
        }
    }
}
