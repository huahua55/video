<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'app\crontab\command\Cj',//采集当天资源站
    'app\crontab\command\DoubanScore',//采集数据 cms返回反应慢
    'app\crontab\command\CmsDouban',//更新当前豆瓣详情
    'app\crontab\command\CmsVodScore',//合并push到数据表中
    'app\crontab\command\DoubanScoreCopy',//采集数据接口  不稳定
    'app\crontab\command\DoubanScoreJs',//采集数据js  目前在使用
    'app\crontab\command\DoubanTopList',//推荐定时脚本
    'app\crontab\command\VodCode',
    'app\crontab\command\PushData',
    'app\crontab\command\EditVod',//修改视频表数据
    'app\crontab\command\CjUpdateTime',//修改视频表更新时间
    'app\crontab\command\UpVideoVod',//修改视频表更新时间
    'app\crontab\command\RecomVodList',//定时修改 推荐短视频
    'app\crontab\command\CheckVideoCollection',//定时修改 检查视频集是否完整
    'app\crontab\command\VodPicEdit',
    'app\crontab\command\VodPicEditZd',
];
