<?php

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

        // Retrieve the active theme.
        $activeTheme = $this->getContainer()->get('liip_theme.active_theme');
        $availableThemes = $activeTheme->getThemes();

        // if($symlink) {
        //     $output->writeln('Trying to install theme assets as <comment>symbolic links</comment>.');
        // } else {
        //     $output->writeln('Installing theme assets as <comment>hard copies</comment>.');
        // }

        foreach($availableThemes as $theme) {
            $output->writeln(sprintf('Installing assets for <comment>%s</comment>', $theme));

            // Install assets for this theme.
            $this->getContainer()->get('liip_theme.installer')->installAssets($theme, $themesAssetsDir, $input->getOption('symlink'));
        }
    }
}