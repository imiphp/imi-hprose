<?php
namespace Imi\Hprose\Route;

use Imi\ServerManage;
use Imi\Bean\Annotation\Bean;
use Imi\Util\ObjectArrayHelper;
use Imi\Server\Route\RouteCallable;
use Imi\Hprose\Controller\HproseController;
use Imi\Hprose\Route\Annotation\HproseRoute as HproseRouteAnnotation;
use Imi\Util\Text;

/**
 * @Bean("HproseRoute")
 */
class HproseRoute implements IRoute
{
    /**
     * 路由解析处理
     * @param mixed $data
     * @return array
     */
    public function parse($data)
    {
        return null;
    }

    /**
     * 增加路由规则，直接使用注解方式
     * @param Imi\Hprose\Route\Annotation\HproseRoute $annotation
     * @param mixed $callable
     * @param array $options
     * @return void
     */
    public function addRuleAnnotation(HproseRouteAnnotation $annotation, $callable, $options = [])
    {
        $serverName = $options['serverName'];
        $controllerAnnotation = $options['controller'];
        $methodName = $options['methodName'];
        $hproseServer = ServerManage::getServer($serverName)->getHproseService();

        // callable
        $callable = $this->parseCallable($callable);
        $isObject = is_array($callable) && isset($callable[0]) && $callable[0] instanceof HproseController;
        if($isObject)
        {
            // 复制一份控制器对象
            $callable[0] = clone $callable[0];
        }

        // alias
        if(Text::isEmpty($controllerAnnotation->prefix))
        {
            $alias = $annotation->name;
        }
        else
        {
            $alias = $controllerAnnotation->prefix . $annotation->name;
        }

        // funcOptions
        $funcOptions = [
            'mode'          =>  $annotation->mode,
            'simple'        =>  $annotation->simple,
            'oneway'        =>  $annotation->oneway,
            'async'         =>  $annotation->async,
            'passContext'   =>  $annotation->passContext,
        ];

        $hproseServer->addFunction($callable, $alias, $funcOptions);
    }

    /**
     * 处理回调
     * @param array $params
     * @param mixed $callable
     * @return callable
     */
    private function parseCallable($callable)
    {
        if($callable instanceof RouteCallable)
        {
            return $callable->getCallable();
        }
        else
        {
            return $callable;
        }
    }
}