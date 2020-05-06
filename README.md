影视系统

### 接口
测试
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

#### 3. 影视筛选

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

#### 4. 播放页

Method: **GET**

URL: **/vod/play**

Query: **id=3932&sid=1&nid=1**

>   id: 视频ID
>   sid: 当前播放组序号
>   nid: 当前集数序号

Response:

```json
{
    "code":1,
    "message":"",
    "data":{
      "obj": "视频及播放数据"
    }
}
```

#### 5. 搜索

Method: **GET**

URL: **/vod/search**

Query: **wd=火影忍者&by=time**

>   wd: 关键词
>   by: time(默认)，score，hits

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

#### 6. 评论列表

Method: **GET**

URL: **/comment/ajax**

Query: **rid=5190&mid=1&page=1**

>   rid: 视频ID
>   mid: 模块ID （['vod'=>1,'art'=>2,'topic'=>3,'comment'=>4,'gbook'=>5,'user'=>6,'label'=>7,'actor'=>8,'role'=>9,'plot'=>10,'website'=>11]）

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

#### 7. 发评论

Method: **POST**

URL: **/comment/saveData**


>   comment_pid: 评论父ID
>   comment_content: 内容
>   verify: 验证码
>   comment_mid: 模块ID
>   comment_rid: 视频ID

Response:

```json
{
  "code":1,
  "message":""
}
```

#### 8. 获取验证码

Method: **GET**

URL: **/verify**

Query: **r=0.216236162612**

>   r: 随机数


Response: 图片

#### 9. 专题列表

Method: **GET**

URL: **/topic**

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

#### 10. 专题详情

Method: **GET**

URL: **/topic/detail**

Query: **id=1**

>   rid: 专题ID


Response:

```json
{
    "code":1,
    "message":"",
    "data":{
      "obj": "专题数据"
    }
}
```

