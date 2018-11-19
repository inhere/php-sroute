# benchmark

压测时间: 2017-07-16
改进路由收集和匹配逻辑后的压测结果

自动生成了1000条路由，每条有9个参数位，分别测试1000次的 

- 第一条路由匹配
- 最后一条路由匹配
- 不会匹配到的路由

## Worst-case matching

This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 14 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.

Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Router - unknown route (1000 routes) | 988 | 0.0000120063 | +0.0000000000 | baseline
Router - last route (1000 routes) | 988 | 0.0000122867 | +0.0000002804 | 2% slower
SRouter - unknown route (1000 routes) | 983 | 0.0000123633 | +0.0000003570 | 3% slower
SRouter - last route (1000 routes) | 998 | 0.0000142205 | +0.0000022142 | 18% slower
Symfony Dumped - last route (1000 routes) | 990 | 0.0000468579 | +0.0000348516 | 290% slower
Symfony Dumped - unknown route (1000 routes) | 995 | 0.0000490268 | +0.0000370205 | 308% slower
FastRoute - unknown route (1000 routes) | 968 | 0.0001358227 | +0.0001238164 | 1031% slower
FastRoute(cached) - last route (1000 routes) | 999 | 0.0001397746 | +0.0001277683 | 1064% slower
FastRoute(cached) - unknown route (1000 routes) | 960 | 0.0001424064 | +0.0001304001 | 1086% slower
FastRoute - last route (1000 routes) | 999 | 0.0001659009 | +0.0001538946 | 1282% slower
Pux PHP - unknown route (1000 routes) | 964 | 0.0013507533 | +0.0013387470 | 11150% slower
Pux PHP - last route (1000 routes) | 999 | 0.0014749475 | +0.0014629412 | 12185% slower
Symfony - unknown route (1000 routes) | 979 | 0.0038350259 | +0.0038230196 | 31842% slower
Symfony - last route (1000 routes) | 999 | 0.0040060059 | +0.0039939995 | 33266% slower


## First route matching

This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 7 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route(1000) | 993 | 0.0000105502 | +0.0000000000 | baseline
Router - first route(1000) | 984 | 0.0000118334 | +0.0000012832 | 12% slower
SRouter - first route(1000) | 982 | 0.0000118473 | +0.0000012971 | 12% slower
FastRoute(cached) - first route(1000) | 999 | 0.0000143361 | +0.0000037859 | 36% slower
FastRoute - first route(1000) | 999 | 0.0000143980 | +0.0000038477 | 36% slower
Symfony Dumped - first route | 993 | 0.0000350874 | +0.0000245372 | 233% slower
Symfony - first route | 999 | 0.0000630564 | +0.0000525061 | 498% slower
