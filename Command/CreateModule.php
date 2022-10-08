<?php

namespace Module\Maker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'module:create',
    description: 'This command create new empty module',
    aliases: ['md:c'],
    hidden: false
)]
class CreateModule extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('moduleName', InputArgument::REQUIRED, 'The module name of the module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $input->getArgument('moduleName');

        $dir = __DIR__.'/../../';
        $this->createDir($dir, $moduleName);
        $this->createDir($dir.'/'.$moduleName, 'Controller');
        $io = new SymfonyStyle($input, $output);

        $nameEntity = $io->ask('Enter name entity', 'EntityBase');
        $nameRepository = $nameEntity . 'Repository';

        $nameController = $io->ask('Enter name controller', 'IndexController');

        $this->createDir($dir.'/'.$moduleName, 'Entity');
        $this->copyTemplateToModule(
            $moduleName, 'Entity', $nameEntity, 'Entity',
            ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}', '{%repositoryName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameEntity, $nameRepository]
        );


        $this->createDir($dir.'/'.$moduleName, 'Repository');
        $this->copyTemplateToModule(
            $moduleName, 'Repository', $nameRepository, 'Repository',
            ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}', '{%repositoryName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameEntity, $nameRepository]
        );
        $this->copyTemplateToModule(
            $moduleName, 'Controller', $nameController, 'Controller',
            ['{%uModuleName%}', '{%lModuleName%}', '{%controllerName%}', '{%repositoryName%}','{%entityName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameController, $nameRepository, $nameEntity]
        );

        $this->createDir($dir.'/'.$moduleName, 'Service');
        $this->addDoctrineConfigure($moduleName);

        $output->writeln('Module created: '.$moduleName);

        return Command::SUCCESS;
    }

    private function copyTemplateToModule(
        string $moduleName,
        string $templateName,
        string $fileName,
        string $path,
        array $search,
        array $replace
    ): void {
        $controller = file_get_contents(__DIR__.'/templates/'.$templateName.'.tmp');
        $content = str_replace($search, $replace, $controller);
        $this->filesystem->appendToFile(__DIR__.'/../../'.$moduleName.'/'.$path.'/'.$fileName.'.php', $content);
    }

    private function addDoctrineConfigure(string $moduleName): void
    {
        $configDoctrine = __DIR__.'/../../../config/packages/doctrine.yaml';
        $value = Yaml::parseFile($configDoctrine);
        $value['doctrine']['dbal']['connections'][lcfirst($moduleName)]['url'] = "%env(resolve:DATABASE_URL)%";
        $value['doctrine']['orm']['entity_managers'][lcfirst($moduleName)] = [
            "connection" => lcfirst($moduleName),
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

    private function createDir(string $dirPath, string $dirName): void
    {
        try {
            $this->filesystem->mkdir(
                Path::normalize($dirPath.'/'.$dirName),
            );
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }
    }
}