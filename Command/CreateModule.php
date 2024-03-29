<?php

namespace Module\Maker\Command;

use Module\Maker\ConfigEditor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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
        $version = 'v1';
        $moduleName = $input->getArgument('moduleName');


        $dir = __DIR__.'/../../';
        $fullPath = $dir.'/'.$moduleName.'/'.ucfirst($version);

        $this->createDir($dir, $moduleName);
        $this->createDir($fullPath, 'Controller');
        $io = new SymfonyStyle($input, $output);

        $nameEntity = $io->ask('Enter name entity', $moduleName);
        $nameRepository = $nameEntity.'Repository';

        $this->createDir($fullPath, 'Entity');
        $this->copyTemplateToModule(
            $moduleName, 'Entity.php', $nameEntity.'.php', ucfirst($version).'/Entity',
            ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}', '{%repositoryName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameEntity, $nameRepository]
        );

        $this->createDir($fullPath, 'Repository');
        $this->copyTemplateToModule(
            $moduleName, 'Repository.php', $nameRepository.'.php', ucfirst($version).'/Repository',
            ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}', '{%repositoryName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameEntity, $nameRepository]
        );

        $nameService = $nameEntity.'Service';
        $this->createDir($fullPath, 'Service');
        $this->copyTemplateToModule(
            $moduleName, 'Service.php', $nameService.'.php', ucfirst($version).'/Service',
            ['{%uModuleName%}', '{%lModuleName%}', '{%entityName%}', '{%repositoryName%}'],
            [ucfirst($moduleName), lcfirst($moduleName), $nameEntity, $nameRepository]
        );

        $this->copyTemplateToModule(
            $moduleName, 'README.md', 'README.md', '/',
            ['{%lModuleName%}'],
            [lcfirst($moduleName)]
        );

        $controllers = [
            'DeleteController.php',
            'GetController.php',
            'ListController.php',
            'PostController.php',
            'PutController.php',
        ];
        foreach ($controllers as $controller) {
            $this->copyTemplateToModule(
                $moduleName, $controller, $controller, ucfirst($version).'/Controller',
                [
                    '{%uModuleName%}',
                    '{%lModuleName%}',
                    '{%repositoryName%}',
                    '{%entityName%}',
                    '{%controllerType%}',
                ],
                [ucfirst($moduleName), lcfirst($moduleName), $nameRepository, $nameEntity]
            );
        }

        $this->createDir($fullPath, 'Service');
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
        $this->filesystem->appendToFile(__DIR__.'/../../'.$moduleName.'/'.$path.'/'.$fileName, $content);
    }

    private function addDoctrineConfigure(string $moduleName): void
    {
        $config = new ConfigEditor('config/packages/doctrine.yaml');
        $value = $config->parse();
        $value['doctrine']['dbal']['connections'][lcfirst($moduleName)]['url'] = "%env(resolve:DATABASE_URL)%";
        $value['doctrine']['orm']['entity_managers'][lcfirst($moduleName)] = [
            "connection" => lcfirst($moduleName),
            "mappings" => [
                ucfirst($moduleName) => [
                    "is_bundle" => false,
                    "type" => "attribute",
                    "dir" => '%kernel.project_dir%/module/'.ucfirst($moduleName).'/V1/Entity',
                    "prefix" => 'Module\\'.ucfirst($moduleName).'\V1\Entity',
                    "alias" => ucfirst($moduleName)."Module",
                ],
            ],
        ];

        $config->save($value);
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