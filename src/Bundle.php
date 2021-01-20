<?php

namespace ZnTool\Phar;

use ZnCore\Base\Libs\App\Base\BaseBundle;

class Bundle extends BaseBundle
{

    public function console(): array
    {
        return [
            'ZnTool\Phar\Commands',
        ];
    }
}
