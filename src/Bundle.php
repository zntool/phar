<?php

namespace ZnTool\Phar;

use ZnCore\Base\App\Base\BaseBundle;

class Bundle extends BaseBundle
{

    public function console(): array
    {
        return [
            'ZnTool\Phar\Commands',
        ];
    }
}
