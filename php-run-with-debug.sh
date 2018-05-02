#!/bin/sh

# profiler_enable - profiler功能的开关，默认值0，如果设为1，则每次请求都会生成一个性能报告文件。
# export XDEBUG_CONFIG="profiler_enable=1"

# xdebug.profiler_enable_trigger 默认值也是0，如果设为1 则当我们的请求中包含 XDEBUG_PROFILE 参数时才会生成性能报告文件
export XDEBUG_CONFIG="profiler_enable_trigger=1"

php $@
