<?php

namespace ZnTool\Phar\Domain\Libs;

use ArrayIterator;
use Phar;
use ZnCore\FileSystem\Helpers\FileStorageHelper;

class Packager
{

    private function getStubCode(): string
    {
        return file_get_contents(__DIR__ . '/stub.php');
    }

    private function createPhar(string $sourcePath, string $outPath, ArrayIterator $arrayIterator = null, string $stubCode = null): Phar
    {
        $outPath = rtrim(rtrim($outPath, DIRECTORY_SEPARATOR), '/');
        $outPath = str_replace(DIRECTORY_SEPARATOR, '/', $outPath);

        FileStorageHelper::remove($outPath);
        FileStorageHelper::remove($outPath . '.gz');

        $phar = new Phar($outPath, \FilesystemIterator::CURRENT_AS_FILEINFO, basename($outPath));
        $phar->startBuffering();
        //$arrayIterator = $arrayIterator ?? new ArrayIterator($this->getFiles($sourcePath, []));
        $phar->buildFromIterator($arrayIterator, $sourcePath);
        $this->fixBaseDir($phar);
        $stubCode = $stubCode ?? $this->getStubCode();
        $phar->setStub($stubCode);
        $phar->stopBuffering();
        //$phar->buildFromDirectory($sourcePath);
        return $phar;
    }

    /*public function exportApp($sourcePath, $outPath, array $excludes = [])
    {
        //$outPath = $sourcePath . '/app.phar';
        $fileList = $this->getFiles($sourcePath, $excludes);
        $arrayIterator = new ArrayIterator($fileList);
        $phar = $this->createPhar($sourcePath, $outPath, $arrayIterator);
    }

    public function exportVendor($sourcePath, $outPath, array $excludes = [])
    {
        //$outPath = $sourcePath . '/vendor.phar';
        $fileList = $this->getFiles($sourcePath, $excludes);
        $arrayIterator = new ArrayIterator($fileList);
        $phar = $this->createPhar($sourcePath, $outPath, $arrayIterator);
    }*/

    public function pack($sourcePath, $outPath, array $excludes = [])
    {
        //$outPath = $sourcePath . '/vendor.phar';
        $fileList = $this->getFiles($sourcePath, $excludes);
        $arrayIterator = new ArrayIterator($fileList);
        $phar = $this->createPhar($sourcePath, $outPath, $arrayIterator);
    }

    private function updateBaseDir(Phar $phar, string $file)
    {
        $content = \file_get_contents($phar[$file]->getPathname());
        if (false !== $content) {
            $phar[$file] = \preg_replace(
                '/\$baseDir\s*=\s*dirname\(\$vendorDir\);/m',
                '$baseDir=\\\\Phar::running(true).\'/.mount\';',
                $content
            );
        }
    }

    private function fixBaseDir(\Phar $phar)
    {
        if (isset($phar['composer/autoload_classmap.php'])) {
            $this->updateBaseDir($phar, 'composer/autoload_classmap.php');
        }
        if (isset($phar['composer/autoload_files.php'])) {
            $this->updateBaseDir($phar, 'composer/autoload_files.php');
        }
        if (isset($phar['composer/autoload_namespaces.php'])) {
            $this->updateBaseDir($phar, 'composer/autoload_namespaces.php');
        }
        if (isset($phar['composer/autoload_psr4.php'])) {
            $this->updateBaseDir($phar, 'composer/autoload_psr4.php');
        }
        if (isset($phar['composer/autoload_static.php'])) {
            $content = file_get_contents($phar['composer/autoload_static.php']->getPathname());
            if (false !== $content) {
                /**
                 * @var string $autoloadStaticContent
                 */
                $autoloadStaticContent = preg_replace(
                    '/__DIR__\s*\.\s*\'\/..\/..\'\s*\.\s*/m',
                    'PHAR_RUNNING . ',
                    $content,
                    -1,
                    $replaced
                );
                if ($replaced > 0) {
                    $autoloadStaticContent = str_replace(
                        'namespace Composer\Autoload;',
                        'namespace Composer\Autoload;' . PHP_EOL . PHP_EOL . "define('PHAR_RUNNING',\\Phar::running(true).'/.mount');",
                        $autoloadStaticContent
                    );
                }
                $phar['composer/autoload_static.php'] = $autoloadStaticContent;
            }

        }
    }

    private function getFiles(string $directoryPath, array $exlcudes = [])
    {
        $directoryIterator = new \RecursiveDirectoryIterator($directoryPath);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $files = [];
        foreach ($iterator as $info) {
            /** @var $info \SplFileInfo */
            if ($info->isDir()) {
                continue;
            }
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $info->getRealPath());
            $path = str_replace($directoryPath, '', $path);
            if ($this->matchExclude($path, $exlcudes)) {
                continue;
            }
            $files[] = $info;
        }
        return $files;
    }

    private function matchExclude($path, $exlcudes = [])
    {
        foreach ($exlcudes as $rule) {
            if (stripos($rule, 'regex') !== false) {
                $rule = str_replace('regex:', '', $rule);
                if (preg_match($rule, $path)) {
                    return true;
                }
            }
            if (stripos($path, $rule) !== false) {
                return true;
            }
        }
        return false;
    }

    /*public function getRelativePath($from, $to)
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
        $from = str_replace('\\', '/', $from);
        $to   = str_replace('\\', '/', $to);

        $from     = explode('/', $from);
        $to       = explode('/', $to);
        $relPath  = $to;

        foreach($from as $depth => $dir) {
            // find first non-matching dir
            if($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
    }*/

}
