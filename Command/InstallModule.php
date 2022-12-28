<?php

namespace Module\Maker\Command;

use Module\Maker\ConfigEditor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'module:install',
    description: 'This command install module from https://github.com/elenyum-ru',
    aliases: ['md:i'],
    hidden: false
)]
class InstallModule extends Command
{
    protected function configure()
    {
        $this
            ->addArgument('moduleName', InputArgument::REQUIRED, 'The module name of the module')
            ->addOption('token', 't', InputOption::VALUE_OPTIONAL, 'Github token');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = ucfirst($input->getArgument('moduleName'));

        $token = $_ENV['GITHUB_TOKEN'] ?? $input->getOption('token');
        $modulePath = __DIR__."/../../{$moduleName}";
        $command = "git clone https://{$token}@github.com/elenyum-ru/{$moduleName}.git ".$modulePath;

        shell_exec($command);
        $this->addDoctrineConfigure($moduleName);

        $output->writeln('Module installed: '.$moduleName);

        return Command::SUCCESS;
    }

    private function addDoctrineConfigure(string $moduleName): void
    {
        $config = new ConfigEditor('config/packages/doctrine.yaml');
        $value = $config->parse();
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

        $config->save($value);
    }
}