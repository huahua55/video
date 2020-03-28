影视系统

### 接口

通用返回结构
```json
{
  "system": "系统配置json",
  "user": "用户信息json",
  "nav": "主导航json",
  "param": "GET参数json"
}
```

#### 1. 首页

Method: **GET**

URL: **/vod**

Query: 

Response:

```json
{
  "code":1,
  "message":"",
  "data":{
    "banner": "轮播配置json",
    "art": "公告json",
    "vod_recommend": "热播推荐json"
  }
}
```

#### 2. 电影、电视剧、综艺、动漫 分类首页

Method: **GET**

URL: **/vod/type**

Query: **id=1**

>   id: 分类ID

Response:

```json
{
  "code":1,
    "message":"",
    "data":{
      "banner": "轮播配置json",
      "vod_recommend": "热播推荐json"
    }
}
```

#### 2. 影视筛选

Method: **GET**

URL: **/vod/show**

Query: **id=1&class=喜剧&area=大陆&year=2020**

>   id: 分类ID（1：电影）
>   class: 类型
>   area: 地区
>   year: 时间

Response:

```json
{
  "code":1,
    "message":"",
    "data":{
      "list": "数据列表"
    }
}
```