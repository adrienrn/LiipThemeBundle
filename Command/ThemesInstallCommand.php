<?php

/*
 * This file is part of the Liip/ThemeBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\ThemeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

/**
 * Command that installs themes assets into the web/ folder
 *
 * @author adrienrn
 */
class ThemesInstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('assets:themes-install')
            ->setDefinition(array(
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web'),
            ))
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it')
            ->setDescription('Installs themes assets into the web directory')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When the target directory does not exist or symlink cannot be used
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Target directory.
        $targetArg = rtrim($input->getArgument('target'), '/');
        if (!is_dir($targetArg)) {
            throw new \InvalidArgumentException(sprintf('The target directory "%s" does not exist.', $input->getArgument('target')));
        }

        // Target themes assets directory.
        $themesAssetsDir = $targetArg . DIRECTORY_SEPARATOR . "themes";
        if(file_exists($themesAssetsDir)) {
            $this->getContainer()->get('filesystem')->remove($themesAssetsDir);
        }

        // Retrieve the active theme.
        $activeTheme = $this->getContainer()->get('liip_theme.active_theme');
        $availableThemes = $activeTheme->getThemes();
        $installedThemes = [];

        // Logging install mode.
        if($input->getOption('symlink')) {
            $output->writeLn(sprintf('Trying to install theme assets as <comment>symbolic links</comment> in <info>%s</info>.', $themesAssetsDir));
        } else {
            $output->writeLn(sprintf('Installing theme assets as <comment>hard copies</comment> in <info>%s</info>.', $themesAssetsDir));
        }

        // Logging list of discovered themes.
        $output->writeLn(sprintf("Found following theme(s) to install: <comment>%s</comment>.", join(', ', $availableThemes)));
        foreach($availableThemes as $theme) {
            // Install assets for this theme.
            $installed = $this->getContainer()->get('liip_theme.installer')->installAssets($theme, $themesAssetsDir, $input->getOption('symlink'));
            if($installed === true) {
                array_push($installedThemes, $theme);
            }
        }

        $output->writeLn(sprintf("<info>Successfully installed assets for %d theme(s).</info>", count($installedThemes)));
    }
}