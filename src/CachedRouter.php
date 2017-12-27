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
     * The routes cache file.
     * @var string
     */
    protected $cacheFile;

    /**
     * Enable routes cache
     * @var bool
     */
    protected $cacheEnable = true;

    /**
     * dump routes cache on matching
     * @var bool
     */
    protected $cacheOnMatching = true;

    /**
     * object constructor.
     * @param array $config
     * @throws \LogicException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (isset($config['cacheFile'])) {
            $this->cacheFile = $config['cacheFile'];
        }

        if (isset($config['cacheEnable'])) {
            $this->setCacheEnable($config['cacheEnable']);
        }

        if (isset($config['cacheOnMatching'])) {
            $this->setCacheOnMatching($config['cacheOnMatching']);
        }

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
        if ($this->cacheOnMatching) {
            $this->dumpRoutesCache();
        }

        return parent::match($path, $method);
    }

    /**
     * dump routes to cache file
     */
    public function dumpCache()
    {
        $this->dumpRoutesCache();
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
        if (!$this->isCacheEnable()) {
            return false;
        }

        $file = $this->cacheFile;

        if (!$file || !file_exists($file)) {
            return false;
        }

        /**
         * @var int $count
         * @var array $staticRoutes
         * @var array $regularRoutes
         * @var array $vagueRoutes
         * @var array $routesData
         *
         */
        // load routes
        include $file;

        $this->cacheLoaded = true;
        $this->routeCounter = $count;
        $this->setStaticRoutes($staticRoutes);
        $this->setRegularRoutes($regularRoutes);
        $this->setVagueRoutes($vagueRoutes);
        $this->setRoutesData($routesData);

        return true;
    }

    /**
     * dump routes to cache file
     * @return bool|int
     */
    protected function dumpRoutesCache()
    {
        if (!$file = $this->cacheFile) {
            return false;
        }

        if ($this->isCacheEnable() && file_exists($file)) {
            return true;
        }

        $date = date('Y-m-d H:i:s');
        $class = static::class;
        $count = $this->count();
        $staticRoutes = var_export($this->getStaticRoutes(), true);
        $regularRoutes = var_export($this->getRegularRoutes(), true);
        $vagueRoutes = var_export($this->getVagueRoutes(), true);
        $routesData = var_export($this->getRoutesData(), true);

        $code = <<<EOF
<?php
/*
 * This is routes cache file of the package `inhere/sroute`.
 * It is auto generate by $class.
 * @date $date
 * @count $count
 * @notice Please don't edit it.
 */

\$count = $count;

// static routes
\$staticRoutes = $staticRoutes;

// regular routes
\$regularRoutes = $regularRoutes;

// vague routes
\$vagueRoutes = $vagueRoutes;

// routes Data
\$routesData = $routesData;
EOF;
        return file_put_contents($file, preg_replace('/=>\s+\n\s+array \(/', '=> array (', $code));
    }

    /**
     * @return bool
     */
    public function isCacheEnable()
    {
        return (bool)$this->cacheEnable;
    }

    /**
     * @param bool $cacheEnable
     */
    public function setCacheEnable($cacheEnable)
    {
        $this->cacheEnable = (bool)$cacheEnable;
    }

    /**
     * @return string
     */
    public function getCacheFile()
    {
        return $this->cacheFile;
    }

    /**
     * @param string $cacheFile
     */
    public function setCacheFile(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;
    }

    /**
     * @return bool
     */
    public function isCacheExists()
    {
        return ($file = $this->cacheFile) && file_exists($file);
    }

    /**
     * @return bool
     */
    public function isCacheLoaded()
    {
        return $this->cacheLoaded;
    }

    /**
     * @param bool $cacheOnMatching
     */
    public function setCacheOnMatching($cacheOnMatching)
    {
        $this->cacheOnMatching = (bool)$cacheOnMatching;
    }

    /**
     * @return bool
     */
    public function isCacheOnMatching(): bool
    {
        return $this->cacheOnMatching;
    }

}
