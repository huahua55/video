# !/usr/bin/python
# -*- coding:utf-8 -*-


from common import *
import requests
from bs4 import BeautifulSoup
import sys
from mysql import *
import Levenshtein
import difflib
import math

# 内网地址
logPathFile = '/data/www/video/' + '/runtime/log/' + time.strftime("%Y/%m/%d", time.localtime())

mysql_config = {'host': '45.120.216.57', 'port': '6306', 'user': 'uservide', 'passwd': 'C@HjduTY793CKLp', 'dbname': 'video',
                'charset': 'utf8mb4'}


# mysql_config = {'host': '127.0.0.1', 'port': '3306', 'user': 'root', 'passwd': 'root', 'dbname': 'tcengvod',
#                 'charset': 'utf8mb4'}
# logPathFile = os.getcwd() + '/runtime/log/' + time.strftime("%Y/%m/%d", time.localtime())
db1 = Mysql(mysql_config)





# 查找数据 修改集数 未完成

# 字符串相似度
def get_difflib_rate(str1, str2):
    return difflib.SequenceMatcher(None, str1, str2).quick_ratio()


# 字符串相似度
def get_Levenshtein_rate(str1, str2):
    return Levenshtein.ratio(str1, str2)


# where 条件
def common_where(vod_can, where, where_name='vod_actor', type=' and ', wherei=0):
    vod_can_arr = list_trim(str(vod_can).strip().split(','))
    if vod_can_arr:
        vod_can_where = '( '
        for i_vod_can in range(len(vod_can_arr)):
            str_vod_can = str(vod_can_arr[i_vod_can]).strip()
            str_vod_can = str(str_vod_can).replace("\"", '')
            if i_vod_can == 0:
                vod_can_where += ' ' + where_name + ' like "%' + str_vod_can + '%"  '
            else:
                vod_can_where += 'or  ' + where_name + ' like "%' + str_vod_can + '%"  '
        vod_can_where += ' )'
        if wherei == 1:
            where += vod_can_where
        else:
            where += type + vod_can_where
    return where


# 分页 页码
def paginate(page, size=20):
    """
    数据库 分页 和 翻页 功能函数
    @param page: int or str 页面页数
    @param size: int or str 分页大小
    @return: dict
    {
        'limit': 20,   所取数据行数
        'offset': 0,   跳过的行数
        'before': 0,   前一页页码
        'current': 1,  当前页页码
        'next': 2      后一页页码
    }
    """
    if not isinstance(page, int):
        try:
            page = int(page)
        except TypeError:
            page = 1

    if not isinstance(size, int):
        try:
            size = int(size)
        except TypeError:
            size = 20

    if page > 0:
        page -= 1

    data = {
        "limit": size,
        "offset": page * size,
        "before": page,
        "current": page + 1,
        "next": page + 2
    }
    return data


#  查询总sql数量
def sql_count(sql):
    data = db1.fetchone(sql)
    return int(data['total_count'])


d_id_list = []
error_id_list = []
succ_id_list = []

# 总数量
total_count = sql_count("select count(id) as total_count from video where tx_vod_id = 0")
# 总页码数
# total_page = math.ceil(total_count / 20)
total_page = 1
# 循环分页取值
page = 1
common_print_log("总条数:" + str(total_count) + "总页数:" + str(total_page), logName='up_Video_TxVideo',
                 logPath=logPathFile)
while total_page > 0:
    common_print_log("当前页码" + str(page), logName='up_Video_TxVideo',
                     logPath=logPathFile)
    if page == 1:
        page_limit = 0
    else:
        page_limit = page * 20
#     video_sql = "select `id`, `type_pid`, `type_id`, `vod_name`, `vod_sub`, `vod_en`, `vod_tag`, `vod_pic`, `vod_pic_thumb`, `vod_pic_slide`, `vod_actor`, `vod_director`, `vod_writer`, `vod_behind`, `vod_blurb`, `vod_remarks`, `vod_pubdate`, `vod_total`, `vod_serial`, `vod_tv`, `vod_weekday`, `vod_area`, `vod_lang`, `vod_year`, `vod_version`, `e_id`, `vod_state`, `vod_duration`, `vod_isend`, `vod_douban_id`, `vod_douban_score`, `vod_time`, `vod_time_add`, `is_from`, `is_examine`, `vod_status`, `vod_id`, `is_selected` from video where tx_vod_id = 0 limit  " + str(
#         page_limit) + ",20"
    video_sql = "select `id`, `type_pid`, `type_id`, `vod_name`, `vod_sub`, `vod_en`, `vod_tag`, `vod_pic`, `vod_pic_thumb`, `vod_pic_slide`, `vod_actor`, `vod_director`, `vod_writer`, `vod_behind`, `vod_blurb`, `vod_remarks`, `vod_pubdate`, `vod_total`, `vod_serial`, `vod_tv`, `vod_weekday`, `vod_area`, `vod_lang`, `vod_year`, `vod_version`, `e_id`, `vod_state`, `vod_duration`, `vod_isend`, `vod_douban_id`, `vod_douban_score`, `vod_time`, `vod_time_add`, `is_from`, `is_examine`, `vod_status`, `vod_id`, `is_selected` from video where tx_vod_id = 0"
    result = db1.fetchall(video_sql)
    # 数据存在
    if result is not None:
        for index_key, index_val in enumerate(result):
            # common_print_log("当前video视频表数据:" + str(index_val))
            common_print_log(
                "  当前video视频表数据:id:" + str(index_val['id']) + ';type_pid:' + str(
                    index_val['type_pid']) + ';type_id:' + str(index_val['type_id']) + ';名称:' + index_val[
                    'vod_name'] + ';演员:' + index_val['vod_actor'] + ';导演:' + index_val['vod_director'] + ';内容:' +
                index_val['vod_blurb'], logName='up_Video_TxVideo',
                logPath=logPathFile)
            vod_name = str(index_val['vod_name']).strip()  # 名称
            type_pid = index_val['type_pid']  # 名称
            where = 'vod_name =  "' + vod_name + '" and type_pid =  "' + str(type_pid) + '"  '
            whereNo = ""
            wherei = 0
            tx_vod_sql = ""
            try:
                # 主演列表
                vod_actor = index_val['vod_actor']
                if vod_actor:
                    wherei = wherei + 1
                    whereNo = common_where(vod_actor, whereNo, where_name='vod_actor', type=' or ', wherei=wherei)
                # 导演
                vod_director = index_val['vod_director']
                if vod_director:
                    wherei = wherei + 1
                    whereNo = common_where(vod_director, whereNo, where_name='vod_director', type=' or ', wherei=wherei)
                # sql
                if whereNo != "":
                    if str(whereNo[0:3]).strip() == 'or':
                        whereNo = whereNo[3:]
                    tx_vod_sql = "select * from tx_vod where " + where + " and (" + whereNo + ") "
                else:
                    tx_vod_sql = "select * from tx_vod where " + where + ""
                result_tx_vod = db1.fetchall(tx_vod_sql)
                if len(result_tx_vod) == 0:
                    common_print_log(
                        "    --tx_vod未找到数据 过滤：" + str(index_val['id']) + '；名称：' + str(
                            index_val['vod_name']) + ";类型：" + str(
                            type_pid) + "；sql : " + str(tx_vod_sql), logName='up_Video_TxVideo',
                        logPath=logPathFile)
                    continue
                else:
                    if len(result_tx_vod) > 1:
                        # p(result_tx_vod)
                        common_print_log("    --tx_vod未找到数据 过滤：" + str(index_val['id']) + '；名称：' + str(
                            index_val['vod_name']) + ";类型：" + str(type_pid) + "；sql : " + str(
                            tx_vod_sql) + ";tx_vod查找结果条数大于1 暂时取第一个 ：" + str(len(result_tx_vod)),
                                         logName='up_Video_TxVideo',
                                         logPath=logPathFile)
                        common_print_log(result_tx_vod, logName='up_Video_TxVideo',
                                         logPath=logPathFile)
                        d_id_list.append(index_val['id'])
                        tx_vod_array = result_tx_vod[0]
                        # continue
                    else:
                        tx_vod_array = result_tx_vod[0]
                        common_print_log("查找tx_vod视频表数据:" + str(tx_vod_array), logName='up_Video_TxVideo',
                                         logPath=logPathFile)
                        common_print_log(
                            "  查找tx_vod视频表数据:id:" + str(tx_vod_array['id']) + ';type_pid:' + str(
                                tx_vod_array['type_pid']) + ';type_id:' + str(tx_vod_array[
                                                                                  'type_id']) + ';名称:' + str(
                                tx_vod_array['vod_name']) + ';演员:' + tx_vod_array[
                                'vod_actor'] + ';导演:' +
                            tx_vod_array['vod_director'] + ';内容:' + tx_vod_array['vod_blurb'],
                            logName='up_Video_TxVideo',
                            logPath=logPathFile)
                    if len(tx_vod_array['vod_blurb']) > 1:
                        round_vod_blurb = round(
                            get_Levenshtein_rate(index_val['vod_blurb'], tx_vod_array['vod_blurb']) * 100, 2)
                        # 简介相似度
                        common_print_log('简介相似度:' + str(round_vod_blurb), logName='up_Video_TxVideo',
                                         logPath=logPathFile)
                    up_data = {}
                    # 判断 tx_vod 是否存在此数据
                    up_list_key = [
                        'vod_sub',
                        'vod_en',
                        'vod_tag',
                        # 'vod_pic',
                        'vod_pic_thumb',
                        'vod_pic_slide',
                        'vod_actor',
                        'vod_director',
                        'vod_writer',
                        'vod_behind',
                        'vod_blurb',
                        'vod_remarks',
                        'vod_pubdate',
                        'vod_total',
                        'vod_serial',
                        'vod_tv',
                        'vod_weekday',
                        'vod_area',
                        'vod_lang',
                        'vod_year',
                        'vod_version',
                        'vod_state',
                        'vod_duration',
                        'vod_isend',
                        'vod_is_from',
                        # 'vod_is_advance', #是否 超前点播
                        # 'vod_is_pay_mark', #是否 vip
                    ]
                    # 获取 修改 up_data 数据
                    for up_list_key_key, up_list_key_val in enumerate(up_list_key):
                        if len(str(tx_vod_array[up_list_key_val])) >= 1 and str(
                                tx_vod_array[up_list_key_val]) != ' ':
                            if up_list_key_val == 'vod_is_from':
                                up_data['is_from'] = str(tx_vod_array[up_list_key_val])
                            else:
                                up_data[up_list_key_val] = str(tx_vod_array[up_list_key_val])
                    if len(up_data) > 1:
                        up_data['tx_vod_id'] = tx_vod_array['id']  # 获取tx_vod_id标识id
                    res = db1.update("video", up_data, "id = %s" % str(index_val['id']))
                    if res:
                        succ_id_list.append(index_val['id'])
                        common_print_log("up_succ ：" + str(res) + ';id:' + str(index_val['id']),
                                         logName='up_Video_TxVideo',
                                         logPath=logPathFile)
                        common_print_log(result_tx_vod, logName='up_Video_TxVideo',
                                         logPath=logPathFile)
            except Exception as e:
                error_id_list.append(index_val['id'])
                common_print_log(
                    "error ：" + str(e) + ';sql:' + str(tx_vod_sql) + str(e) + ':id;' + str(index_val['id']),
                    logName='up_Video_TxVideo',
                    logPath=logPathFile)
                continue
    page = page + 1
    total_page = total_page - 1
common_print_log("成功更改总数量:" + str(len(succ_id_list)) + '条;ID:' + str(succ_id_list), logName='up_Video_TxVideo',
                 logPath=logPathFile)
common_print_log("事物报错总数量:" + str(len(error_id_list)) + '条;ID:' + str(error_id_list), logName='up_Video_TxVideo',
                 logPath=logPathFile)
common_print_log("影响调试大于1总数量:" + str(len(d_id_list)) + '条;ID:' + str(d_id_list), logName='up_Video_TxVideo',
                 logPath=logPathFile)
