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
 * - handler 将不支持设置为 \Closure 和 Object (无法缓存对象)
 * - 路由选项的 选项值 同样不允许 \Closure 和 Object
 *
 * @package Inhere\Route
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
        $this->config = array_merge($this->config,[
            'cacheFile' => '',
            'cacheEnable' => true,
        ]);

        parent::__construct($config);

        // read route caches from cache file
        $this->loadRoutesCache();
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
     * {@inheritdoc}
     */
    public function match($path, $method)
    {
        // dump routes to cache file
        $this->dumpRoutesCache();

        return parent::match($path, $method);
    }

//////////////////////////////////////////////////////////////////////
/// helper methods
//////////////////////////////////////////////////////////////////////

    /**
     * load route caches from the cache file
     * @return bool
     */
    public function loadRoutesCache()
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }

        $file = $this->config['cacheFile'];

        if (!$file || !file_exists($file)) {
            return false;
        }

        // load routes
        $map = include $file;

        $this->setStaticRoutes($map['staticRoutes']);
        $this->setRegularRoutes($map['regularRoutes']);
        $this->setVagueRoutes($map['vagueRoutes']);
        $this->cacheLoaded =  true;

        return true;
    }

    /**
     * dump routes to cache file
     * @param string $file
     * @return bool|int
     */
    public function dumpRoutesCache()
    {
        if (!$file = $this->config['cacheFile']) {
            return false;
        }

        if ($this->isCacheEnabled() && file_exists($file)) {
            return true;
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
     * @return bool
     */
    public function isCacheEnabled()
    {
        return (bool)$this->getConfig('cacheEnable');
    }

    /**
     * @return bool
     */
    public function isCacheExists()
    {
        return ($file = $this->config['cacheFile']) && file_exists($file);
    }

    /**
     * @return bool
     */
    public function isCacheLoaded()
    {
        return $this->cacheLoaded;
    }
}
