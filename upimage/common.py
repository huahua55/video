#!/usr/bin/env python3
import json
import os
from log import *
import platform
import datetime
import shutil
import subprocess
import stat


# 获取系统
def get_platform():
    return platform.system().lower()


# 列表中的空格
def list_trim(list1):
    return [x.strip() for x in list1 if x.strip() != '']


# 替换不要要的数据并且去除空
def replace_trim(str, it_var):
    new_str = it_var.replace(str, '')
    new_str = new_str.strip()
    return new_str


# 带分割的包的数量
def get_chunk():
    return 50


# 判断类型
def typeof(variate):
    type = None
    if isinstance(variate, int):
        type = "int"
    elif isinstance(variate, str):
        type = "str"
    elif isinstance(variate, float):
        type = "float"
    elif isinstance(variate, list):
        type = "list"
    elif isinstance(variate, tuple):
        type = "tuple"
    elif isinstance(variate, dict):
        type = "dict"
    elif isinstance(variate, set):
        type = "set"
    return type


# 打印数据
def p(data, t=0):
    print(data)
    if t == 0:
        exit()


# 生成 视频信息 -i info
def you_get_i_url_info(strs, type='iqiyi'):
    if strs.find('[ DEFAULT ] _________________________________') >= 0:
        strs = strs.split("[ DEFAULT ] _________________________________")[1]
    get_platform_str = get_platform()
    if get_platform_str == "windows":
        if len(strs.split("\r\n\r\n")) > 1:
            ts_list = strs.split("\r\n\r\n")
        else:
            ts_list = strs.split("\r\n")
    else:
        ts_list = strs.split("\n\n")
    new_ts_list = {}
    ts_key_list = {
        'format': '- format:',
        'container': 'container:',
        'video_profile': 'video-profile:',
        'm3u8_url': 'm3u8_url:',
        'download_with': '# download-with:',
    }
    if type == 'youku':
        ts_key_list['size'] = 'size:'
    for index in range(len(ts_list)):
        new_ts_index_list = {}
        it_var = ts_list[index]
        if get_platform_str == "windows":
            if len(it_var.split("\r\n")) > 1:
                ts_it_var = it_var.split("\r\n")
            else:
                ts_it_var = it_var.split("\n")
        else:
            ts_it_var = it_var.split("\n")
        for ts_it_var_index in range(len(ts_it_var)):
            ts_it_var_str = ts_it_var[ts_it_var_index]
            for ts_val, ts_key in ts_key_list.items():
                if ts_it_var_str.find(ts_key) >= 0:
                    new_ts_index_list[ts_val] = replace_trim(ts_key, ts_it_var_str)
        if len(new_ts_index_list) > 0:
            new_ts_list[new_ts_index_list['format']] = new_ts_index_list
    return new_ts_list


# 打印json
def pJson(data):
    p(json.dumps(data, ensure_ascii=False, indent=1))


# 获取code
def getCode():
    code_path = os.getcwd() + '/code.txt'
    code_path = code_path.replace("\\", '/')
    f = open(code_path, 'r')
    return f.read()


# 添加log
def myLog(txt, logPath=None, logName='mylog'):
    if logPath is None:
        logPath = os.getcwd() + '/runtime/log/' + time.strftime("%Y/%m/%d", time.localtime())
    # 文件夹 创建
    video_mkdir(logPath)
    log = Log(__name__, logPath=logPath, logName=logName).getlog()
    log.info(txt)


# 创建文件夹
def video_mkdir(path):
    # 去除首位空格
    path = path.strip()
    # 去除尾部 \ 符号
    path = path.rstrip("\\")
    # 判断路径是否存在
    # 存在     True
    # 不存在   False
    isExists = os.path.exists(path)
    # 判断结果
    if not isExists:
        # 如果不存在则创建目录
        # 创建目录操作函数
        os.makedirs(path)
        return True
    else:
        # 如果目录存在则不创建，并提示目录已存在
        return False


# 获取pid
def get_pid(type_id):
    type_list = {1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 1, 7: 1, 8: 1, 9: 1, 10: 1, 11: 1, 12: 1, 13: 2, 14: 2, 15: 2,
                 16: 2, 17: 5, 18: 5, 19: 4, 20: 4, 21: 4, 22: 4, 23: 23, 24: 2, 25: 3, 26: 3, 27: 3, 28: 3}
    return type_list[type_id]


# 是否存在文件或者文件夹
def is_mkdir(path, type=1):
    # 去除首位空格
    path = path.strip()
    # 去除尾部 \ 符号
    path = path.rstrip("\\")
    # 判断路径是否存在
    if type == 1:
        isExists = os.path.exists(path)
    else:
        isExists = os.path.isdir(path)
    # 判断结果 不存在 false
    if not isExists:
        return False
    else:
        return True


# 是否存在文件
def is_file(path):
    # 去除首位空格
    path = path.strip()
    # 去除尾部 \ 符号
    path = path.rstrip("\\")
    # 判断路径是否存在
    isExists = os.path.isfile(path)
    # 判断结果 不存在 false
    if not isExists:
        return False
    else:
        return True


# 取出单个列表
def array_column_one(result, key):
    return list(map(lambda x: x[key], result))


# 去出列表
def array_column(result, column=None, index_key=None):
    new_result = {}
    for result_index in range(len(result)):
        result_str = result[result_index]
        if column is None and index_key is not None:
            new_result[result_str[index_key]] = result_str
        elif column is not None and index_key is not None:
            new_result[result_str[index_key]] = result_str[column]
        else:
            new_result[result_index] = result_str[column]
    return new_result


# 将数组元素分割比例
def array_chunk(arr, size):
    s = []
    for i in range(0, int(len(arr)) + 1, size):
        c = arr[i:i + size]
        if c:
            s.append(c)
    return s


# 获取文件夹文件
def file_name(file_dir, new_vod_url_str=''):
    L = []
    for root, dirs, files in os.walk(file_dir):
        for file in files:
            if os.path.splitext(file)[1] == '.ts' or os.path.splitext(file)[1] == '.m3u8':
                if new_vod_url_str != '':
                    L.append(os.path.join(new_vod_url_str, file))
                else:
                    L.append(os.path.join(root, file))
    return L


# 获取文件夹文件
def file_name_list(file_dir, new_vod_url_str='', type=None):
    L = []
    for root, dirs, files in os.walk(file_dir):
        for file in files:
            if type is not None:
                if os.path.splitext(file)[1] != "" and str(os.path.splitext(file)[0])[0] != ".":
                    if new_vod_url_str != '':
                        L.append(os.path.join(new_vod_url_str, file))
                    else:
                        L.append(os.path.join(root, file))
            else:
                if new_vod_url_str != '':
                    L.append(os.path.join(new_vod_url_str, file))
                else:
                    L.append(os.path.join(root, file))
    return L


# 获取视频数据
def get_vod_video(result):
    new_video_install = {}
    new_video_install['type_pid'] = result['type_id_1']
    new_video_install['type_id'] = result['type_id']
    new_video_install['vod_name'] = result['vod_name']
    new_video_install['vod_sub'] = result['vod_sub']
    new_video_install['vod_en'] = result['vod_en']
    new_video_install['vod_tag'] = result['vod_en']
    tag = result["vod_tag"]
    if len(tag) < 2:
        tag = result["vod_class"]
    new_video_install["vod_tag"] = tag
    new_video_install["vod_pic"] = result["vod_pic"]
    new_video_install["vod_pic_thumb"] = result["vod_pic_thumb"]
    new_video_install["vod_pic_slide"] = result["vod_pic_slide"]
    new_video_install["vod_actor"] = result["vod_actor"]
    new_video_install["vod_director"] = result["vod_director"]
    new_video_install["vod_writer"] = result["vod_writer"]
    new_video_install["vod_behind"] = result["vod_behind"]
    if len(result["vod_content"]) > len(result["vod_blurb"]):
        new_video_install["vod_blurb"] = result["vod_content"]
    else:
        new_video_install["vod_blurb"] = result["vod_blurb"]
    new_video_install["vod_remarks"] = result["vod_remarks"]
    new_video_install["vod_pubdate"] = result["vod_pubdate"]
    new_video_install["vod_total"] = result["vod_total"]
    new_video_install["vod_serial"] = result["vod_serial"]
    new_video_install["vod_tv"] = result["vod_tv"]
    new_video_install["vod_weekday"] = result["vod_weekday"]
    new_video_install["vod_area"] = result["vod_area"]
    new_video_install["vod_lang"] = result["vod_lang"]
    new_video_install["vod_year"] = result["vod_year"]
    new_video_install["vod_version"] = result["vod_version"]
    new_video_install["vod_state"] = result["vod_state"]
    new_video_install["vod_duration"] = result["vod_duration"]
    new_video_install["vod_isend"] = result["vod_isend"]
    new_video_install["vod_douban_id"] = result["vod_douban_id"]
    new_video_install["vod_douban_score"] = result["vod_douban_score"]
    new_video_install["vod_time"] = int(time.time())
    new_video_install["vod_time_add"] = int(time.time())
    new_video_install["vod_id"] = result["vod_id"]
    return new_video_install


# 插入
def os_write(file_txt, txt):
    file_ob = open(file_txt, "w")
    file_ob.write(txt)
    file_ob.close()


# 获取视频集数据
def get_vod_collection(install_video_id, result, like_collection, vod_url, like_path):
    install_collection_data = {}
    install_collection_data['video_id'] = install_video_id
    install_collection_data["code"] = getCode()
    install_collection_data['title'] = '第' + str(like_collection) + '集'
    install_collection_data['collection'] = int(like_collection)
    install_collection_data['vod_url'] = vod_url
    install_collection_data['duration'] = get_duration(like_path)
    install_collection_data['resolution'] = ''
    install_collection_data['bitrate'] = ''
    install_collection_data['time_up'] = int(time.time())
    install_collection_data['size'] = str(get_file_size(like_path))
    install_collection_data['name'] = result['vod_name']
    install_collection_data['director'] = result['vod_director']
    return install_collection_data


# 文件获取时长
def get_duration(path):
    duration1 = float(0)
    try:
        get_time = float(0)
        new_m3u8_file = f"{path}/index.m3u8"
        print(new_m3u8_file)
        with open(new_m3u8_file, 'r', encoding='UTF-8') as file_obj:
            line_str = file_obj.read()
            for line_index in line_str.split('\n'):
                line_index_str = replace_trim("\n", line_index)
                if "EXTINF" in line_index_str:
                    str1 = line_index_str.split(":")[1][:-1]
                    if str1.endswith(','):
                        time = float(str1[:-1])
                    else:
                        time = float(str1)
                    get_time = get_time + time
        duration1 = str(datetime.timedelta(seconds=get_time))
        if '.' in duration1:
            duration1 = duration1.split('.')[0]
    except Exception as e:
        pass
    return duration1


# 获取文件大小
def get_file_size(filePath, size=0):
    for root, dirs, files in os.walk(filePath):
        for f in files:
            size += os.path.getsize(os.path.join(root, f))
    return size


def get_mg_header(referer_url=None, cookie=None):
    mg_header = {
        "Referer": referer_url,
        "User-Agent": "Mozilla/5.0 (windows nt 10.0; win64; x64) applewebkit/537.36 (khtml, like gecko) chrome/71.0.3578.98 safari/537.36",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Charset": "UTF-8,*;q=0.5",
        "Cookie": cookie,
        "Accept-Encoding": "gzip,deflate,sdch",
        "Accept-Language": "en-US,en;q=0.8",
    }
    if cookie is None:
        del mg_header['Cookie']
    if referer_url is None:
        del mg_header['Referer']
    return mg_header


# 删除文件夹
def misc_init(filePath):
    if is_mkdir(filePath):
        if os.path.exists(filePath):
            for fileList in os.walk(filePath):
                for name in fileList[2]:
                    os.chmod(os.path.join(fileList[0], name), stat.S_IWRITE)
                    os.remove(os.path.join(fileList[0], name))
            shutil.rmtree(filePath, True)
            return True
        else:
            return False


# 是否测试字符串和数字
def is_number(s):
    try:
        float(s)
        return True
    except ValueError:
        pass
    try:
        import unicodedata
        unicodedata.numeric(s)
        return True
    except (TypeError, ValueError):
        pass
    return False


# log
def common_print_log(str, logName='myLog', logPath=None):
    print(str)
    myLog(str, logPath=logPath, logName=logName)


# 将秒数转换为时间
def getTime(seconds):
    timeArray = time.localtime(seconds)
    otherStyleTime = time.strftime("%Y-%m-%d %H:%M:%S", timeArray)
    return otherStyleTime


# 将时间转换为秒数
def composeTime(time1):
    time2 = datetime.datetime.strptime(time1, "%Y-%m-%d %H:%M:%S")
    time3 = time.mktime(time2.timetuple())
    time4 = int(time3)
    return time4
