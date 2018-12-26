<?php
namespace Imi\Hprose\Route\Annotation\Parser;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Parser\BaseParser;
use Imi\Util\Traits\TServerAnnotationParser;
use Imi\Hprose\Route\Annotation\HproseController;

/**
 * 控制器注解处理器
 */
class HproseControllerParser extends BaseParser
{
    use TServerAnnotationParser;

    protected $controllerAnnotationClass = HproseController::class;

    /**
     * 处理方法
     * @param \Imi\Bean\Annotation\Base $annotation 注解类
     * @param string $className 类名
     * @param string $target 注解目标类型（类/属性/方法）
     * @param string $targetName 注解目标名称
     * @return void
     */
    public function parse(\Imi\Bean\Annotation\Base $annotation, string $className, string $target, string $targetName)
    {
    }

}