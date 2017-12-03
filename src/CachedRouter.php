<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

/**
 * Class CachedRouter - this is object version and support cache routes.
 *
 * - 支持缓存路由信息到文件
 * - handler 将不支持设置为 \Closure (无法缓存 \Closure)
 * - 路由选项的 选项值 同样不允许 \Closure
 *
 * @package Inhere\Route
 */
class CachedRouter extends ORouter
{
    /** @var bool */
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
     * {@inheritdoc}
     */
    public function match($path, $method = self::GET)
    {
        // dump routes to cache file
        $this->dumpRoutesCache();

        return parent::match($path, $method);
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

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
        $class = static::class;
        $count = $this->count();
        $staticRoutes = var_export($this->getStaticRoutes(), true);
        $regularRoutes = var_export($this->getRegularRoutes(), true);
        $vagueRoutes = var_export($this->getVagueRoutes(), true);

        $code = <<<EOF
<?php
/*
 * This `inhere/sroute` routes cache file.
 * It is auto generate by $class.
 * @date $date
 * @count $count
 * @notice Please don't edit it.
 */
return array (
// static routes
'staticRoutes' => $staticRoutes,
// regular routes
'regularRoutes' => $regularRoutes,
// vague routes
'vagueRoutes' => $vagueRoutes,
);
EOF;
        return file_put_contents($file, preg_replace('/=>\s+\n\s+array \(/', '=> array (', $code));
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
