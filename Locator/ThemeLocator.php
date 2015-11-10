<?php

namespace Liip\ThemeBundle\Locator;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 *
 */
class ThemeLocator
{
    protected $appPath;
    protected $kernel;
    protected $pathPatterns;

    function __construct(KernelInterface $kernel, $appPath = null, array $pathPatterns = array())
    {
        $this->kernel = $kernel;
        $this->appPath = $appPath;

        $defaultPathPatterns = array(
            'app_resource' => array(
                '%app_path%/themes/%current_theme%/%template%',
                '%app_path%/views/%template%',
            ),
            'bundle_resource' => array(
                '%bundle_path%/Resources/themes/%current_theme%/%template%',
            ),
            'bundle_resource_dir' => array(
                '%dir%/themes/%current_theme%/%bundle_name%/%template%',
                '%dir%/%bundle_name%/%override_path%',
            ),
        );

        $this->pathPatterns = array_merge_recursive(array_filter($pathPatterns), $defaultPathPatterns);
    }

    public function locate($theme, $dir = null, $first = true)
    {
        // @TODO
    }

    public function locateThemeInBundles($theme, $dir = null, $first = true)
    {
        $parameters = array(
            '%app_path%' => $this->appPath,
            '%dir%' => $dir,
            '%override_path%' => $theme, // ?
            '%current_theme%' => $theme,
            '%current_device%' => "", // ?
            '%template%' => ""
        );

        foreach ($this->kernel->getBundles() as $bundle) {
            $checkPaths = $this->getPathsForBundle(
                array_merge(
                    $parameters,
                    array(
                        '%bundle_path%' => $bundle->getPath(),
                        '%bundle_name%' => $bundle->getName()
                    )
                )
            );

            $found = [];
            foreach ($checkPaths as $checkPath) {
                if (file_exists($checkPath)) {
                    if ($first) {
                        return array(
                            "path" => $checkPath,
                            "bundle" => $bundle
                        );
                    }
                    $found[] = array(
                        "path" => $checkPath,
                        "bundle" => $bundle
                    );
                }
            }
        }

        if (count($found) > 0 && $first) {
            return $found[0];
        }

        return $found;
    }

    public function locateThemeInApp($theme, $dir = null, $first = true)
    {
        $files = array();
        $parameters = array(
            '%app_path%' => $this->appPath,
            '%current_theme%' => $theme,
            '%current_device%' => "", // ?
            '%template%' => "",
        );

        foreach ($this->getPathsForAppResource($parameters) as $checkPath) {
            if (file_exists($checkPath)) {
                if ($first) {
                    return $checkPath;
                }
                $files[] = $checkPath;
            }
        }

        return $files;
    }

    /**
     * Search all themes in paths described in liip_theme config
     *     "Goes on adventures, searching for themes"
     * @return Array<String> list of found themes.
     */
    public function discoverThemes()
    {
        $parameters = array(
            '%app_path%' => $this->appPath,
            '%dir%' => "",
            '%override_path%' => "", // ?
            '%current_theme%' => "",
            '%current_device%' => "", // ?
            '%template%' => ""
        );

        $finder = new Finder();
        $paths = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            $paths = array_merge(
                $paths,
                $this->getPathsForBundle(
                    array_merge(
                        $parameters,
                        array(
                            '%bundle_path%' => $bundle->getPath(),
                            '%bundle_name%' => $bundle->getName()
                        )
                    )
                )
            );
        }

        $parameters = array(
            '%app_path%' => $this->appPath,
            '%current_theme%' => "",
            '%current_device%' => "", // ?
            '%template%' => "",
        );

        $paths = array_merge($paths, $this->getPathsForAppResource($parameters));

        $paths = array_map(
            function($v) {
                return preg_replace('/(^.*\/themes\/)(.*)$/', "$1", $v);
            },
            array_filter(
                $paths,
                function($v) {
                    return file_exists($v) && (preg_match('/^.*\/themes\/.*$/', $v));
                }
            )
        );

        $themes = [];
        foreach ($finder->directories()->in($paths)->depth('== 0') as $file) {
            array_push($themes, $file->getFilename());
        }

        return $themes;
    }

    protected function getPathsForBundle($parameters)
    {
        $pathPatterns = array();
        $paths = array();

        if (!empty($parameters['%dir%'])) {
            $pathPatterns = array_merge($pathPatterns, $this->pathPatterns['bundle_resource_dir']);
        }

        $pathPatterns = array_merge($pathPatterns, $this->pathPatterns['bundle_resource']);

        foreach ($pathPatterns as $pattern) {
            $paths[] = strtr($pattern, $parameters);
        }

        return $paths;
    }

    protected function getPathsForAppResource($parameters)
    {
        $paths = array();

        foreach ($this->pathPatterns['app_resource'] as $pattern) {
            $paths[] = strtr($pattern, $parameters);
        }

        return $paths;
    }
}