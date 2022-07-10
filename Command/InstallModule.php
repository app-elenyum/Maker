<?php

namespace Module\Maker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class InstallModule extends Command
{
    protected static $defaultName = 'module:install';
    protected static $defaultDescription = 'This command install module from https://github.com/elenyum-ru';

    protected function configure()
    {
        $this
            ->addArgument('moduleName', InputArgument::REQUIRED, 'The module name of the module')
            ->addOption('token', 't', InputOption::VALUE_OPTIONAL, 'Github token');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleName = ucfirst($input->getArgument('moduleName'));

        $token = $_ENV['GITHUB_TOKEN'] ?? $input->getOption('token');
        $command = "git clone https://{$token}@github.com/elenyum-ru/{$moduleName}.git ". __DIR__ ."/../../{$moduleName}";
        shell_exec($command);
        $this->addDoctrineConfigure($moduleName);

        $output->writeln('Module installed: '.$moduleName);

        return Command::SUCCESS;
    }

    private function addDoctrineConfigure(string $moduleName): void
    {
        $configDoctrine = __DIR__.'/../../../config/packages/doctrine.yaml';
        $value = Yaml::parseFile($configDoctrine);
        $value['doctrine']['dbal']['connections'][lcfirst($moduleName)]['url'] = "%env(resolve:DATABASE_URL)%";
        $value['doctrine']['orm']['entity_managers'][lcfirst($moduleName)] = [
            "connection" => "users",
            "mappings" => [
                ucfirst($moduleName) => [
                    "is_bundle" => false,
                    "type" => "attribute",
                    "dir" => '%kernel.project_dir%/module/'.ucfirst($moduleName).'/Entity',
                    "prefix" => 'Module\\'.ucfirst($moduleName).'\Entity',
                    "alias" => ucfirst($moduleName)."Module",
                ],
            ],
        ];

        file_put_contents($configDoctrine, Yaml::dump($value, 10));
    }
}