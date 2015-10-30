<?php

namespace Liip\ThemeBundle\Factory;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory as BaseAssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Liip\ThemeBundle\Factory\AssetFactoryWorker;

class AssetFactory extends BaseAssetFactory
{
    /**
     * Container.
     * @var ContainerInterface
     */
    protected $container;

    protected $fileLocator;

    public function __construct(
        KernelInterface $kernel,
        ContainerInterface $container,
        ParameterBagInterface $parameterBag,
        $baseDir,
        $debug = false
    )
    {
        // Init parent::
        parent::__construct($kernel, $container, $parameterBag, $baseDir, $debug);

        // Inject FileLocator.
        //$this->fileLocator = $container->get("liip_theme.templating_locator");
        $this->container = $container;
    }

    protected function parseInput($input, array $options = array())
    {
        $input = $this->resolveValue($input);
        return parent::parseInput($input, $options);
    }

    /**
     * [resolveValue description]
     * @param  [type] $input
     * @return [type]        [description]
     */
    protected function resolveValue($input)
    {
        $matches = array();
        if (preg_match('/%%|%([^%\s]+)%\/(.+)$/', $input, $matches)) {
            //print_r([$matches, array_map(function($v) { return array("function" => $v["function"], "file" => $v["file"]); }, debug_backtrace())]) . "\n";
            if($matches[1] === "theme_dir") {
                // Resolve path.
                return $this->resolvePath($matches[2]);
            }
        }

        // Return as is.
        return $input;
    }

    protected function resolvePath($relativePath)
    {
        // Get the LiipTheme FileLocator.
        $fileLocator = $this->container->get("liip_theme.assetic_locator");

        // Search a bundle resource.
        $relativePath = $fileLocator->locate($relativePath);

        // Return the resolved absolute path.
        return $relativePath;
    }
}