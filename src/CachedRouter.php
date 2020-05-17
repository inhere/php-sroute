<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

use LogicException;
use function date;
use function file_exists;
use function file_put_contents;
use function preg_replace;
use function trim;
use function var_export;

/**
 * Class CachedRouter - this is object version and support cache routes.
 *
 * - 支持缓存路由信息到文件，加快后面请求的解析速度
 * - 注：不能将 handler 设置为 \Closure (无法缓存 \Closure)
 * - 路由选项的 选项值 同样不允许 \Closure
 *
 * @package Inhere\Route
 */
final class CachedRouter extends Router
{
    /** @var bool */
    private $cacheLoaded = false;

    // cacheType: array, serialize

    /**
     * The routes cache file.
     * @var string
     */
    protected $cacheFile = '';

    /**
     * Enable routes cache
     * @var bool
     */
    protected $cacheEnable = true;

    /**
     * object constructor.
     *
     * @param array $config
     *
     * @throws LogicException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (isset($config['cacheFile'])) {
            $this->setCacheFile($config['cacheFile']);
        }

        if (isset($config['cacheEnable'])) {
            $this->setCacheEnable($config['cacheEnable']);
        }

        // read route caches from cache file
        $this->loadRoutesCache();
    }

    /**
     * talk to me routes collect completed.
     */
    public function completed(): void
    {
        $this->dumpRoutesCache();
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $method, string $path, $handler, array $binds = [], array $opts = []): Route
    {
        // file cache exists check.
        if ($this->cacheLoaded) {
            return Route::createFromArray();
        }

        return parent::add($method, $path, $handler, $binds, $opts);
    }

    /**
     * {@inheritdoc}
     */
    public function addRoute(Route $route): Route
    {
        // file cache exists check.
        if ($this->cacheLoaded) {
            return $route;
        }

        return parent::addRoute($route);
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
        $map                = require $file;
        $this->routeCounter = 0;
        $staticRoutes       = $regularRoutes = $vagueRoutes = [];

        foreach ($map['staticRoutes'] as $key => $info) {
            $this->routeCounter++;
            $staticRoutes[$key] = Route::createFromArray($info);
        }

        foreach ($map['regularRoutes'] as $key => $routes) {
            foreach ($routes as $info) {
                $this->routeCounter++;
                $regularRoutes[$key][] = Route::createFromArray($info);
            }
        }

        foreach ($map['vagueRoutes'] as $key => $routes) {
            foreach ($routes as $info) {
                $this->routeCounter++;
                $vagueRoutes[$key][] = Route::createFromArray($info);
            }
        }

        $this->staticRoutes  = $staticRoutes;
        $this->regularRoutes = $regularRoutes;
        $this->vagueRoutes   = $vagueRoutes;
        $this->cacheLoaded   = true;

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

        $date  = date('Y-m-d H:i:s');
        $class = static::class;
        $count = $this->count();

        $staticRoutes  = var_export($this->staticRoutes, true);
        $regularRoutes = var_export($this->regularRoutes, true);
        $vagueRoutes   = var_export($this->vagueRoutes, true);

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
);\n
EOF;
        return file_put_contents($file, preg_replace(
            ['/\s+\n\s+Inhere\\\\Route\\\\Route::__set_state\(/', '/\)\),/', '/=>\s+\n\s+array \(/'],
            [' ', '),', '=> array ('],
            $code
        ));
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
    public function setCacheEnable($cacheEnable): void
    {
        $this->cacheEnable = (bool)$cacheEnable;
    }

    /**
     * @return string
     */
    public function getCacheFile(): string
    {
        return $this->cacheFile;
    }

    /**
     * @param string $cacheFile
     */
    public function setCacheFile(string $cacheFile): void
    {
        $this->cacheFile = trim($cacheFile);
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
}
