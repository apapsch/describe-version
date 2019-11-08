<?php
/* Copyright (C) 2019 Ellen Papsch <aljosha.papsch@vinexus.eu> */

namespace Apapsch\DescribeVersion;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use limenet\GitVersion\Directory as GitDirectory;
use limenet\GitVersion\Formatters\SemverFormatter;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{

    private const OUTPUT_BASE_NAME = 'version.php';

    /**
     * @var IOInterface
     */
    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'createVersionFile',
            'post-update-cmd' => 'createVersionFile',
        ];
    }

    public function createVersionFile(): void
    {
        $outputDirectory = getcwd();
        $outputFile = $outputDirectory . DIRECTORY_SEPARATOR . self::OUTPUT_BASE_NAME;
        try {
            $gitDirectory = new GitDirectory($outputDirectory);
            $version = $gitDirectory->get(new SemverFormatter());

            $this->writeToFile($version, $outputFile);
            $this->io->write(sprintf('Version described as %s', $version));
        }
        catch (\Exception $exception) {
            $this->io->writeError(
                sprintf('Could not determine Git version in %s: %s', $outputDirectory, $exception->getMessage())
            );
        }
    }

    /**
     * @param string $version
     * @param string $outputFile
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function writeToFile(string $version, string $outputFile)
    {
        $twig = new TwigEnvironment(
            new FilesystemLoader(__DIR__ . '/../data')
        );
        $contents = $twig->render(self::OUTPUT_BASE_NAME . '.twig', [
            'version' => $version,
        ]);
        $this->writeSafely($outputFile, $contents);
    }

    private function writeSafely(string $outputFile, string $contents)
    {
        $ex = null;
        set_error_handler(static function () use ($outputFile, &$ex) {
            $ex = new \RuntimeException(sprintf(
                'Unable to write to %s: %s',
                $outputFile,
                func_get_args()[1]
            ));
        });

        file_put_contents($outputFile, $contents);
        restore_error_handler();

        if ($ex) {
            /** @var $ex \RuntimeException */
            throw $ex;
        }
    }
}