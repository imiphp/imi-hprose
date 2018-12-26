<?php
namespace Imi\Hprose\Route\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Annotation\Parser;

/**
 * Hprose 动作注解
 * @Annotation
 * @Target("METHOD")
 * @Parser("Imi\Hprose\Route\Annotation\Parser\HproseControllerParser")
 */
class HproseAction extends Base
{
    
}