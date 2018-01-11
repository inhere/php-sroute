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
    protected $cacheOnMatching = false;

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

    /**
     * talk to me routes collect completed.
     */
    public function completed()
    {
        // parent::completed();
        $this->dumpRoutesCache();
    }

    /**
     * {@inheritdoc}
     */
    public function map($method, string $route, $handler, array $opts = []): AbstractRouter
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
    public function match(string $path, string $method = self::GET): array
    {
        // dump routes to cache file
        if ($this->cacheOnMatching) {
            $this->dumpRoutesCache();
        }

        return parent::match($path, $method);
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * load route caches from the cache file
     * @return bool
     */
    public function loadRoutesCache(): bool
    {
        if (!$this->isCacheEnable()) {
            return false;
        }

        $file = $this->cacheFile;

        if (!$file || !file_exists($file)) {
            return false;
        }

        // load routes
        $map = include $file;

        $this->setStaticRoutes($map['staticRoutes']);
        $this->setRegularRoutes($map['regularRoutes']);
        $this->setVagueRoutes($map['vagueRoutes']);
        $this->cacheLoaded = true;

        return true;
    }

    /**
     * dump routes to cache file
     * @return int
     */
    protected function dumpRoutesCache(): int
    {
        if (!$file = $this->cacheFile) {
            return 0;
        }

        if ($this->isCacheEnable() && file_exists($file)) {
            return 1;
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
 * This is routes cache file of the package `inhere/sroute`.
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
    public function isCacheEnable(): bool
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
    public function isCacheExists(): bool
    {
        return ($file = $this->cacheFile) && file_exists($file);
    }

    /**
     * @return bool
     */
    public function isCacheLoaded(): bool
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
