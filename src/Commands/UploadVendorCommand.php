<?php

namespace ZnTool\Phar\Commands;

use ZnCore\Base\Container\Libs\Container;
use ZnCore\Base\Container\Helpers\ContainerHelper;
use ZnLib\Console\Symfony4\Widgets\LogWidget;
use ZnLib\Console\Symfony4\Widgets\TextWidget;
use ZnCore\Base\Arr\Helpers\ArrayHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class UploadVendorCommand extends Command
{

    protected static $defaultName = 'phar:upload:vendor';

    private function ensurePhar(string $pharFile, InputInterface $input, OutputInterface $output)
    {
        $logWidget = new LogWidget($output);
        $logWidget->setPretty(true);
        $pharGzFile = $pharFile . '.gz';

        if (file_exists($pharGzFile)) {
            $question = new ConfirmationQuestion('Update phar? (y|N): ', false);
            $helper = $this->getHelper('question');
            $isUpdate = $helper->ask($input, $output, $question);
            if ( ! $isUpdate) {
                return;
            }
        }
        $packVendorCommand = ContainerHelper::getContainer()->get(PackVendorCommand::class);
        $packVendorCommand->execute($input, $output);
        $logWidget->start('Compress');
        $content = file_get_contents($pharFile);
        file_put_contents($pharGzFile, gzencode($content));
        $logWidget->finishSuccess();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$helper = new QuestionHelper;

        $output->writeln('<fg=white># Upload vendor to phar</>');

        $basePath = $_ENV['VENDOR_FTP_DIRECTORY'];
        $pharFile = __DIR__ . '/../../../../../vendor.phar';
        $pharGzFile = $pharFile . '.gz';

        $this->ensurePhar($pharFile, $input, $output);

        $logWidget = new LogWidget($output);
        $logWidget->setPretty(true);

        $logWidget->start('Connect');
        $conn_id = ftp_connect($_ENV['VENDOR_FTP_SERVER']);
        $logWidget->finishSuccess();

        $logWidget->start('Login');
        $login_result = ftp_login($conn_id, $_ENV['VENDOR_FTP_USERNAME'], $_ENV['VENDOR_FTP_PASSWORD']);
        $logWidget->finishSuccess();

        $logWidget->start('Get version list');
        $versionList = ftp_nlist($conn_id, $basePath);
        $logWidget->finishSuccess();

        ArrayHelper::removeByValue('.', $versionList);
        ArrayHelper::removeByValue('..', $versionList);
        natsort($versionList);
        array_splice($versionList, 0, -3);
        $textWidget = new TextWidget($output);
        $textWidget->writelnList($versionList);

        $logWidget->start('Check last version');
        $lastSize = ftp_size($conn_id, $basePath . '/' . ArrayHelper::last($versionList) . '/vendor.phar.gz');
        if ($lastSize == filesize($pharGzFile)) {
            $logWidget->finishSuccess('Already published!');
            return 0;
        }
        $logWidget->finishSuccess();

        $helper = $this->getHelper('question');
        $question = new Question('Enter new version: ');
        $version = $helper->ask($input, $output, $question);
        $version = trim($version, 'v');

        if ( ! preg_match('#\d+\.\d+\.\d+#', $version)) {
            $output->writeln('<fg=red>bad version!</>');
            return 1;
        }

        if (in_array($version, $versionList)) {
            $output->writeln('<fg=red>exists version!</>');
            return 1;
        }

        if (version_compare($version, ArrayHelper::last($versionList), '<=')) {
            $output->writeln('<fg=red>letter than last version!</>');
            return 1;
        }

        $remoteDirectory = $basePath . '/' . $version;
        $remoteFile = $remoteDirectory . '/' . basename($pharGzFile);

        $logWidget->start('Make directory');
        ftp_mkdir($conn_id, $remoteDirectory);
        $logWidget->finishSuccess();

        $logWidget->start('Uploading');
        if (ftp_put($conn_id, $remoteFile, $pharGzFile, FTP_BINARY)) {
            $logWidget->finishSuccess();
        } else {
            $logWidget->finishFail();
            $logWidget->start('Remove directory');
            ftp_delete($remoteDirectory);
            $logWidget->finishSuccess();
        }

        $logWidget->start('Close connect');
        ftp_close($conn_id);
        $logWidget->finishSuccess();

        return 0;
    }

}
