# todo

- 调整参数路由的解析，由现在的正则分析 -> 使用字符串分析 
- 增加属性 `$routesData` 存储路由中不用于匹配的数据，减轻现有路由数据的复杂度
  - 现有的变量 `$routesData` 改成 `$routesInfo`
