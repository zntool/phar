<?php


use Illuminate\Container\Container;
use Symfony\Component\Console\Application;
use ZnLib\Console\Symfony4\Helpers\CommandHelper;

/**
 * @var Application $application
 * @var Container $container
 */

CommandHelper::registerFromNamespaceList([
    'ZnTool\Phar\Commands',
], $container);
