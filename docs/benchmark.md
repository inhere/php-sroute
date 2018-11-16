
# 压测

自动生成了1000条路由，每条有9个参数位，分别测试1000次的 

- 第一条路由匹配
- 最后一条路由匹配
- 不存在的路由匹配

详细的测试代码请看仓库 https://github.com/ulue/php-router-benchmark

- 压测日期 **2017.12.3**
- An example route: `/9b37eef21e/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/bda37e9f9b`

压测结果

## Worst-case matching

Test Name | Results | Time(ms) | + Interval | Change
--------- | ------- | ---- | ---------- | ------
inhere/sroute(Router) - unknown route (1000 routes) | 987 | 0.010222 | +0.000000 | baseline
inhere/sroute(SRouter) - unknown route (1000 routes) | 984 | 0.012239 | +0.002017 | 20% slower
inhere/sroute(SRouter) - last route (1000 routes) | 999 | 0.024386 | +0.014820 | 155% slower
inhere/sroute(Router) - last route (1000 routes) | 975 | 0.024554 | +0.014989 | 157% slower
Symfony Cached - last route (1000 routes) | 997 | 0.029091 | +0.019525 | 204% slower
Symfony Cached - unknown route (1000 routes) | 985 | 0.037226 | +0.027661 | 289% slower
FastRoute - unknown route (1000 routes) | 988 | 0.089904 | +0.080338 | 840% slower
FastRoute(cached) - unknown route (1000 routes) | 988 | 0.091358 | +0.081792 | 855% slower
FastRoute(cached) - last route (1000 routes) | 999 | 0.092567 | +0.083001 | 868% slower
FastRoute - last route (1000 routes) | 999 | 0.113668 | +0.104103 | 1088% slower
phroute/phroute - unknown route (1000 routes) | 987 | 0.168871 | +0.159305 | 1665% slower
phroute/phroute - last route (1000 routes) | 999 | 0.169914 | +0.160348 | 1676% slower
Pux PHP - unknown route (1000 routes) | 981 | 0.866280 | +0.856714 | 8956% slower
Pux PHP - last route (1000 routes) | 999 | 0.941322 | +0.931757 | 9741% slower
AltoRouter - unknown route (1000 routes) | 982 | 2.245384 | +2.235819 | 23373% slower
AltoRouter - last route (1000 routes) | 979 | 2.281995 | +2.272429 | 23756% slower
Symfony - unknown route (1000 routes) | 984 | 2.488247 | +2.478681 | 25912% slower
Symfony - last route (1000 routes) | 999 | 2.540170 | +2.530605 | 26455% slower
Macaw - unknown route (1000 routes) | 982 | 2.617635 | +2.608069 | 27265% slower
Macaw - last route (1000 routes) | 999 | 2.700128 | +2.690562 | 28127% slower


## First route matching

Test Name | Results | Time(ms) | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route(1000) | 997 | 0.006587 | +0.000000 | baseline
FastRoute - first route(1000) | 999 | 0.008751 | +0.002165 | 33% slower
phroute/phroute - first route (1000 routes) | 999 | 0.021902 | +0.015315 | 233% slower
Symfony Dumped - first route | 997 | 0.022254 | +0.015667 | 238% slower
Router - first route(1000) | 993 | 0.025026 | +0.018440 | 280% slower
SRouter - first route(1000) | 997 | 0.025553 | +0.018967 | 288% slower
noodlehaus/dispatch - first route (1000 routes) | 989 | 0.030126 | +0.023540 | 357% slower
AltoRouter - first route (1000 routes) | 994 | 0.041488 | +0.034902 | 530% slower
Symfony - first route | 991 | 0.047335 | +0.040748 | 619% slower
FastRoute(cached) - first route(1000) | 999 | 0.092703 | +0.086117 | 1307% slower
Macaw - first route (1000 routes) | 999 | 2.710132 | +2.703545 | 41047% slower
