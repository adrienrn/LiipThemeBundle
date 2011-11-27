<?php

/*
 * This file is part of the Liip/ThemeBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\ThemeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class LiipThemeExtension extends Extension
{
    /**
     * Loads the services based on your application configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        $container->setParameter($this->getAlias().'.themes', $config['themes']);
        $container->setParameter($this->getAlias().'.active_theme', $config['active_theme']);
        $container->setParameter($this->getAlias().'.cache_warming', $config['cache_warming']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (!empty($config['theme_cookie'])) {
            $container->setParameter($this->getAlias().'.theme_cookie', $config['theme_cookie']);
            $loader->load('theme_request_listener.xml');

            if (!empty($config['autodetect_theme'])) {
                $autodetect_theme = $container->hasDefinition($config['autodetect_theme'])
                    ? $config['autodetect_theme'] : 'liip_theme.theme_auto_detect';
                $container->getDefinition($this->getAlias().'.theme_request_listener')->addArgument($container->getDefinition($autodetect_theme));
            }
        }

        $loader->load('templating.xml');
    }
}
