<?php
\Phar::interceptFileFuncs();
$pharUrl = \Phar::running(true);
$mountPath = $pharUrl . '/.mount/';
\Phar::mount($mountPath, __DIR__ . '/');
__HALT_COMPILER();