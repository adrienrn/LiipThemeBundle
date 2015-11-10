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

        // Retrieve the active theme.
        $activeTheme = $this->getContainer()->get('liip_theme.active_theme');
        $availableThemes = $activeTheme->getThemes();

        $filesystem = $this->getContainer()->get('filesystem');

        $themesAssetsDir = $targetArg . DIRECTORY_SEPARATOR . "themes";
        $filesystem->remove($themesAssetsDir);
        $filesystem->mkdir($themesAssetsDir, 0777);

        // Do we hard copy or symlink ?
        $symlink = $input->getOption('symlink');
        if($symlink) {
            $output->writeln('Trying to install theme assets as <comment>symbolic links</comment>.');
        } else {
            $output->writeln('Installing theme assets as <comment>hard copies</comment>.');
        }

        foreach($availableThemes as $theme) {
            $output->writeln(sprintf('Installing assets for <comment>%s</comment>', $theme));

            // Find theme path.
            $themeLocator = $this->getContainer()->get("liip_theme.theme_locator");

            // Search in bundles first.
            $bundle = $themeLocator->locateThemeInBundles($theme);
            if(!empty($bundle)) {
                // Prepare the directory for this bundle.
                $themesAssetsBundleDir = $themesAssetsDir . DIRECTORY_SEPARATOR . strtolower($bundle["bundle"]->getName());
                if(!is_dir($themesAssetsBundleDir)) {
                    $filesystem->mkdir($themesAssetsBundleDir, 0777);
                }

                // Found theme in bundle.
                $originDir = $bundle["path"];
                $targetDir = $themesAssetsBundleDir . DIRECTORY_SEPARATOR . $theme;

                $output->writeln(sprintf('Found theme <comment>%s</comment> in <comment>%s</comment> installing in <comment>%s</comment> ', $theme, $bundle["bundle"]->getName(), $targetDir));
            } else {
                // Search in app/
                $path = $themeLocator->locateThemeInApp($theme);
                if($path) {
                    $originDir = $path;
                    $targetDir = $appThemesDir . DIRECTORY_SEPARATOR . $theme;
                }

                $output->writeln(sprintf('Found theme <comment>%s</comment> in <comment>%s</comment> installing in <comment>%s</comment> ', $theme, $originDir, $targetDir));
            }

            if($originDir && $targetDir) {
                if($symlink) {
                    // Symlink.
                    $filesystem->symlink($originDir, $targetDir, true);
                } else {
                    // Hard copy.
                    $this->hardCopy($originDir, $targetDir);
                }
            }
        }
    }

    /**
     * @param string $originDir
     * @param string $targetDir
     */
    private function hardCopy($originDir, $targetDir)
    {
        $filesystem = $this->getContainer()->get('filesystem');

        $filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));
    }
}