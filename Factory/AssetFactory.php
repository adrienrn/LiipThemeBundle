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

    public function __construct(
        KernelInterface $kernel,
        ContainerInterface $container,
        ParameterBagInterface $parameterBag,
        $baseDir,
        $debug = false
    )
    {
        parent::__construct($kernel, $container, $parameterBag, $baseDir, $debug);
        $this->container = $container;
    }

    protected function parseInput($input, array $options = array())
    {
        //print_r(["2", $input, $this->container->getParameter("liip_theme.path_patterns")]) . "\n";
        return parent::parseInput($input, $options);
    }
}