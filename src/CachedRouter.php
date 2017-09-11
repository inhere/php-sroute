<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

/**
 * Class CachedRouter - this is object version.
 *
 * - 支持缓存路由信息到文件
 * - handler 将不支持设置为 \Closure 和 Object。
 * - 路由选项的 选项 value 同样不允许 \Closure 和 Object
 *
 * @package Inhere\Route
 * @todo  un-completed
 */
class CachedRouter extends ORouter
{
    /** @var bool  */
    private $cacheLoaded = false;

    /**
     * object constructor.
     * @param array $config
     * @throws \LogicException
     */
    public function __construct(array $config = [])
    {
        $config = array_merge([
            'cacheFile' => '',
            'cacheEnable' => true,
        ], $config);

        parent::__construct($config);

        // read route caches from cache file
        if (($file = $this->getConfig('cacheFile')) && file_exists($file)) {
            $this->cacheLoaded = $this->loadRoutesCache($file);
        }
    }

    private $indexId = 0;

    /**
     * generate index id
     * @return int
     */
    protected function generateId()
    {
        return ++$this->indexId;
    }

//////////////////////////////////////////////////////////////////////
/// route collection
//////////////////////////////////////////////////////////////////////

    /**
     * {@inheritdoc}
     */
    public function map($method, $route, $handler, array $opts = [])
    {
        // file cache exists check.
        if ($this->cacheLoaded) {
            return $this;
        }

        return parent::map($method, $route, $handler, $opts);
    }

    /**
     * @param $method
     * @param $handler
     * @throws \InvalidArgumentException
     */
    public static function validateArguments($method, $handler)
    {
        $supStr = implode('|', self::SUPPORTED_METHODS);

        if (false === strpos('|' . $supStr . '|', '|' . $method . '|')) {
            throw new \InvalidArgumentException("The method [$method] is not supported, Allow: $supStr");
        }

        if (!$handler || (!is_string($handler) && !is_array($handler))) {
            throw new \InvalidArgumentException('The route handler is not empty and type only allow: string,array');
        }
    }

//////////////////////////////////////////////////////////////////////
/// route match
//////////////////////////////////////////////////////////////////////

    /**
     * find the matched route info for the given request uri path
     * @param string $method
     * @param string $path
     * @return mixed
     */
    public function match($path, $method)
    {
        // dump routes to cache file
        if (
            ($file = $this->getConfig('cacheFile')) &&
            (!file_exists($file) || !$this->cacheEnabled())
        ) {
            $this->dumpRoutesCache($file);
        }

        return parent::match($path, $method);
    }

//////////////////////////////////////////////////////////////////////
/// helper methods
//////////////////////////////////////////////////////////////////////

    /**
     * @return bool
     */
    public function cacheEnabled()
    {
        return (bool)$this->getConfig('cacheEnable');
    }

    /**
     * @return bool
     */
    public function cacheExists()
    {
        return ($file = $this->getConfig('cacheFile')) && file_exists($file);
    }

    /**
     * @param string $file
     * @return bool|int
     */
    public function dumpRoutesCache($file)
    {
        if (!$file) {
            return false;
        }

        $date = date('Y-m-d H:i:s');
        $staticRoutes = var_export($this->getStaticRoutes(), true);
        $regularRoutes = var_export($this->getRegularRoutes(), true);
        $vagueRoutes = var_export($this->getVagueRoutes(), true);

        $code = <<<EOF
<?php
/*
 * This inhere/sroute routes cache file. is auto generate by Inhere\Route\ORouter.
 * @date $date
 */
return [
    'staticRoutes' => $staticRoutes,
    'regularRoutes' => $regularRoutes,
    'vagueRoutes' => $vagueRoutes,
];
EOF;

        return file_put_contents($file, $code);
    }

    /**
     * @param string $file
     * @return bool
     */
    public function loadRoutesCache($file)
    {
        if (!$this->cacheEnabled()) {
            return false;
        }

        $map = include $file;

        $this->setStaticRoutes($map['staticRoutes']);
        $this->setRegularRoutes($map['regularRoutes']);
        $this->setVagueRoutes($map['vagueRoutes']);

        return true;
    }

    /**
     * @return bool
     */
    public function isCacheLoaded()
    {
        return $this->cacheLoaded;
    }
}
