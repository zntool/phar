<?php


use Illuminate\Container\Container;
use Symfony\Component\Console\Application;
use ZnLib\Console\Symfony4\Helpers\CommandHelper;

/**
 * @var Application $application
 * @var Container $container
 */

//$container = Container::getInstance();

// --- Application ---

//$container->bind(Application::class, Application::class, true);

// --- Generator ---

//$container->bind(\ZnTool\Generator\Domain\Interfaces\Services\DomainServiceInterface::class, \ZnTool\Generator\Domain\Services\DomainService::class);
//$container->bind(\ZnTool\Generator\Domain\Interfaces\Services\ModuleServiceInterface::class, \ZnTool\Generator\Domain\Services\ModuleService::class);

CommandHelper::registerFromNamespaceList([
    'ZnTool\Phar\Commands',
], $container);
