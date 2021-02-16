<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\RpcMultiplex;

class Constant
{
    const PROTOCOL_DEFAULT = 'multiplex.default';

    const REQUEST_ID = 'request_id';

    const PATH = 'path';

    const DATA = 'data';

    const ERROR = 'error';

    const CODE = 'code';

    const HOST = 'host';

    const PORT = 'port';

    const CHANNEL_ID = 'multiplex.channel_id';

    const DEFAULT_SETTINGS = [
        'open_length_check' => true,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
    ];
}
