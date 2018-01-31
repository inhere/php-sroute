# benchmark test 

没有改进前的压测结果

## Worst-case matching
This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 12 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Symfony Dumped - last route (1000 routes) | 994 | 0.0000389371 | +0.0000000000 | baseline
Symfony Dumped - unknown route (1000 routes) | 985 | 0.0000407054 | +0.0000017684 | 5% slower
FastRoute - unknown route (1000 routes) | 961 | 0.0001322015 | +0.0000932644 | 240% slower
FastRoute - last route (1000 routes) | 999 | 0.0001686443 | +0.0001297072 | 333% slower
Pux PHP - unknown route (1000 routes) | 971 | 0.0012761564 | +0.0012372194 | 3177% slower
Pux PHP - last route (1000 routes) | 999 | 0.0014034189 | +0.0013644818 | 3504% slower
Symfony - unknown route (1000 routes) | 981 | 0.0036823390 | +0.0036434019 | 9357% slower
Symfony - last route (1000 routes) | 999 | 0.0037877016 | +0.0037487645 | 9628% slower
SRouter - last route (1000 routes) | 999 | 0.0039463984 | +0.0039074613 | 10035% slower
SRoute - last route (1000 routes) | 999 | 0.0039622600 | +0.0039233229 | 10076% slower
SRoute - unknown route (1000 routes) | 999 | 0.0078473841 | +0.0078084470 | 20054% slower
SRouter - unknown route (1000 routes) | 999 | 0.0079089903 | +0.0078700533 | 20212% slower


## First route matching
This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 6 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route | 991 | 0.0000130298 | +0.0000000000 | baseline
FastRoute - first route | 999 | 0.0000136595 | +0.0000006298 | 5% slower
Symfony Dumped - first route | 986 | 0.0000327519 | +0.0000197221 | 151% slower
Symfony - first route | 998 | 0.0000625880 | +0.0000495582 | 380% slower
SRouter - first route | 976 | 0.0037495811 | +0.0037365514 | 28677% slower
SRoute - first route | 999 | 0.0038005320 | +0.0037875022 | 29068% slower
