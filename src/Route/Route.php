<?php
namespace Imi\Hprose\Route;

use Imi\Util\Text;
use Imi\ServerManage;
use Imi\Bean\Annotation\Bean;
use Imi\Rpc\Route\IRoute;
use Imi\Server\Route\RouteCallable;
use Imi\Rpc\Route\Annotation\Contract\IRpcController;
use Imi\Rpc\Route\Annotation\Contract\IRpcRoute;
use Imi\Hprose\Route\Annotation\HproseRoute;

/**
 * @Bean("HproseRoute")
 */
class Route implements IRoute
{
    /**
     * 路由解析处理
     * @param mixed $data
     * @return array
     */
    public function parse($data)
    {
        // 由 hprose 对象内部处理
        return null;
    }

    /**
     * 增加路由规则，直接使用注解方式
     * 
     * @param \Imi\Rpc\Route\Annotation\Contract\IRpcController $controllerAnnotation
     * @param \Imi\Rpc\Route\Annotation\Contract\IRpcRoute $routeAnnotation
     * @param mixed $callable
     * @param array $options
     * @return void
     */
    public function addRuleAnnotation(IRpcController $controllerAnnotation, IRpcRoute $routeAnnotation, $callable, $options = [])
    {
        // callable
        $callable = $this->parseCallable($callable);
        $isObject = is_array($callable) && isset($callable[0]) && $callable[0] instanceof IRpcController;
        if($isObject)
        {
            // 复制一份控制器对象
            $callable[0] = clone $callable[0];
        }

        $serverName = $options['serverName'];
        $hproseServer = ServerManage::getServer($serverName)->getHproseService();

        // alias
        if(Text::isEmpty($controllerAnnotation->prefix))
        {
            $alias = $routeAnnotation->name;
        }
        else
        {
            $alias = $controllerAnnotation->prefix . $routeAnnotation->name;
        }

        // funcOptions
        $funcOptions = [
            'mode'          =>  $routeAnnotation->mode,
            'simple'        =>  $routeAnnotation->simple,
            'oneway'        =>  $routeAnnotation->oneway,
            'async'         =>  $routeAnnotation->async,
            'passContext'   =>  $routeAnnotation->passContext,
        ];

        $hproseServer->addFunction($callable, $alias, $funcOptions);
    }

    /**
     * 获取缺省的路由注解
     *
     * @param string $className
     * @param string $methodName
     * @param \Imi\Rpc\Route\Annotation\Contract\IRpcController $controllerAnnotation
     * @param array $options
     * @return Imi\Rpc\Route\Annotation\Contract\IRpcRoute
     */
    public function getDefaultRouteAnnotation($className, $methodName, IRpcController $controllerAnnotation, $options = [])
    {
        return new HproseRoute([
            'name'      =>  $methodName,
        ]);
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
