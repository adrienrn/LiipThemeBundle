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

/**
 * Command that installs themes assets into the web/ folder.
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
            ->setHelp(<<<EOT
The <info>%command.name%</info> command installs themes assets into a given
directory (e.g. the <comment>web</comment> directory).

  <info>php %command.full_name% web</info>

A "themes" directory will be created inside the target directory and the
"themes/<options=bold>%theme_name%</>/public" directory of each theme will be copied into it.

To create a symlink to each bundle instead of copying its assets, use the
<info>--symlink</info> option (will fall back to hard copies when symbolic links aren't possible:

  <info>php %command.full_name% web --symlink</info>

EOT
            )
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
        $themesAssetsDir = $targetArg.DIRECTORY_SEPARATOR.'themes';
        if (file_exists($themesAssetsDir)) {
            $this->getContainer()->get('filesystem')->remove($themesAssetsDir);
        }

        // Retrieve the active theme.
        $activeTheme = $this->getContainer()->get('liip_theme.active_theme');
        $availableThemes = $activeTheme->getThemes();
        $installedThemes = array();

        // Logging install mode.
        if ($input->getOption('symlink')) {
            $output->writeLn(sprintf('Trying to install theme assets as <comment>symbolic links</comment> in <info>%s</info>.', $themesAssetsDir));
        } else {
            $output->writeLn(sprintf('Installing theme assets as <comment>hard copies</comment> in <info>%s</info>.', $themesAssetsDir));
        }

        // Logging list of discovered themes.
        $output->writeLn(sprintf('Found following theme(s) to install: <comment>%s</comment>.', join(', ', $availableThemes)));
        foreach ($availableThemes as $theme) {
            // Install assets for this theme.
            $installed = $this->getContainer()->get('liip_theme.installer')->installAssets($theme, $themesAssetsDir, $input->getOption('symlink'));
            if ($installed === true) {
                array_push($installedThemes, $theme);
            }
        }

        $output->writeLn(sprintf('<info>Successfully installed assets for %d theme(s).</info>', count($installedThemes)));
    }
}
