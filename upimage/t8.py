# encoding=utf-8

import requests
from io import BytesIO
from PIL import Image
from common import *
from mysql import *
import math
import urllib.request
import hashlib

mysql_config = {'host': 'rm-j6co0332808hbxpcwpo.mysql.rds.aliyuncs.com', 'port': '3306', 'user': 'uservide',
                'passwd': 'CHjduTY793CKLp', 'dbname': 'video',
                'charset': 'utf8mb4'}
# mysql_config = {'host': '192.168.1.216', 'port': '3306', 'user': 'zongbo', 'passwd': 'zongbo@2020', 'dbname': 'video',
#                 'charset': 'utf8mb4'}
db1 = Mysql(mysql_config)


def get_size(file):
    # 获取文件大小:KB
    size = os.path.getsize(file)
    return size / 1024


def get_outfile(infile, outfile):
    if outfile:
        return outfile
    dir, suffix = os.path.splitext(infile)
    outfile = '{}{}'.format(dir, suffix)
    return outfile


def compress_image(infile, outfile='', mb=150, step=10, quality=80, type=1):
    """不改变图片尺寸压缩到指定大小
    :param infile: 压缩源文件
    :param outfile: 压缩文件保存地址
    :param mb: 压缩目标，KB
    :param step: 每次调整的压缩比率
    :param quality: 初始压缩比率
    :return: 压缩文件地址，压缩文件大小
    """
    imgSize = os.path.getsize(infile)
    o_size = get_size(infile)
    if o_size <= mb:
        return infile
    outfile = get_outfile(infile, outfile)
    while o_size > mb:
        im = Image.open(infile)
        w, h = im.size
        rade = 4
        if int(imgSize) >= int(1024 * 1024):  # 大于一兆
            rade = 4.5
        elif int(imgSize) >= int(1024 * 600):  # 大于600
            rade = 3
        elif int(imgSize) >= int(1024 * 300):  # 大于300
            rade = 2
        elif int(imgSize) >= int(1024 * 100):  # 大于100
            rade = 1.5
        dImg = im.resize((int(w / rade), int(h / rade)), Image.ANTIALIAS)
        # outfile = outfile.replace('min', str(int(w / 4)) + 'x' + str(int(h / 4)))
        if infile.find('.jpg') != -1 or infile.find('.jepg') != -1:
            dImg = dImg.convert('RGB')
            dImg.save(outfile, quality=quality, subsampling=0, dpi=(300.0, 300.0))
        else:
            dImg.save(outfile, quality=quality)
        if quality - step < 0:
            break
        quality -= step
        o_size = get_size(outfile)
    if type == 1:
        return outfile
    else:
        return outfile, get_size(outfile)


def resize_image(infile, outfile='', x_s=1376):
    """修改图片尺寸
    :param infile: 图片源文件
    :param outfile: 重设尺寸文件保存地址
    :param x_s: 设置的宽度
    :return:
    """
    im = Image.open(infile)
    x, y = im.size
    y_s = int(y * x_s / x)
    out = im.resize((x_s, y_s), Image.ANTIALIAS)
    outfile = get_outfile(infile, outfile)
    out.save(outfile)


def StrOfSize(size):
    '''
    递归实现，精确为最大单位值 + 小数点后三位
    '''

    def strofsize(integer, remainder, level):
        if integer >= 1024:
            remainder = integer % 1024
            integer //= 1024
            level += 1
            return strofsize(integer, remainder, level)
        else:
            return integer, round(remainder / 1024, 2), level

    units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB']
    integer, remainder, level = strofsize(size, 0, 0)
    if level + 1 > len(units):
        level = -1
    return '{} {}'.format(integer + remainder, units[level])


#  查询总sql数量
def sql_count(sql):
    data = db1.fetchone(sql)
    return int(data['total_count'])


# 总数量
total_count = sql_count("select count(id) as total_count from video where  id > 23705")
# 总页码数
total_page = math.ceil(total_count / 20)
# 循环分页取值
page = 1
t_k_error = []  # 图片为空
b_c_error = []  # 图片链接失效
x_y3k_error = []  # 图片小于3k
common_print_log("总条数:" + str(total_count) + "总页数:" + str(total_page), logName='imgLog')
while total_page > 0:
    common_print_log("当前页码" + str(page), logName='imgLog')
    if page == 1:
        page_limit = 0
    else:
        page_limit = page * 50
    video_sql = "select `id`, `type_pid`, `type_id`, `vod_name`, `vod_sub`, `vod_en`, `vod_tag`, `vod_pic`, `vod_pic_thumb`, `vod_pic_slide`, `vod_actor`, `vod_director`, `vod_writer`, `vod_behind`, `vod_pubdate`, `vod_total`, `vod_serial`, `vod_tv`, `vod_weekday`, `vod_area`, `vod_lang`, `vod_year`, `vod_version`, `e_id`, `vod_state`, `vod_duration`, `vod_isend`, `vod_douban_id`, `vod_douban_score`, `vod_time`, `vod_time_add`, `is_from`, `is_examine`, `vod_status`, `vod_id`, `is_selected` from video  where  id > 23705 limit  " + str(
        page_limit) + ",50"
    result = db1.fetchall(video_sql)
    if result is not None:
        for index_key, index_val in enumerate(result):
            vod_pic = index_val['vod_pic']
            if vod_pic == '':
                t_k_error.append(index_val['id'])
                continue
            is_http_url = True
            if vod_pic.find('http') == -1:
                is_http_url = False
                vod_pic = 'https://vpc.alsmzw.cn/' + vod_pic
            response = requests.get(vod_pic, stream=True, headers={"Accept-Encoding": "identity"})
            if 'Content-Length' not in response.headers.keys():
                b_c_error.append(index_val['id'])
                common_print_log("！！！url不存在：id: " + str(index_val['id']) + " ;名字: " + str(
                    index_val['vod_name']) + " ;url: " + vod_pic + " ", logName='imgLog')
                continue
            imgSize = response.headers['Content-Length']
            if int(imgSize) <= int(1024 * 3):
                x_y3k_error.append(index_val['id'])
                common_print_log("文件小于3kb 过滤：id: " + str(index_val['id']) + " ;名字: " + str(
                    index_val['vod_name']) + " ;url: " + vod_pic, logName='imgLog')
                continue
            imgSizeData = StrOfSize(int(imgSize))
            tmpIm = BytesIO(response.content)
            im = Image.open(tmpIm)
            w = im.size[0]
            h = im.size[1]
            last_kb = 1024 * 100  # 最终kb 100k
            if int(imgSize) <= int(last_kb):
                common_print_log("小于100k 过滤：id: " + str(index_val['id']) + " ;名字: " + str(
                    index_val['vod_name']) + " ;url: " + vod_pic + " ,原始文件大小: " + str(
                    imgSize) + " ,大小: " + imgSizeData + "" + ", 宽度：%s" % (str(w)) + " ,高度：%s" % (str(h)),
                                 logName='imgLog')
                continue
            else:
                common_print_log("--大于100k 处理 id: " + str(index_val['id']) + " ;名字: " + str(
                    index_val['vod_name']) + " ;url: " + vod_pic + " ,原始文件大小: " + str(
                    imgSize) + " ,转换为kb等大小: " + imgSizeData + "" + ", 宽度：%s" % (
                                     str(w)) + " ,高度：%s" % (str(h)), logName='imgLog')
                # path = os.getcwd()
                path = '/data/www/video'
                if is_http_url:
                    t = 'vod/' + datetime.datetime.now().strftime(
                        '%Y%m%d-%H') + '/' + hashlib.md5().hexdigest() + '.jpg'
                    dir_pic_url_path = str(path + '/' + str(t)).replace("\\", '/')
                else:
                    dir_pic_url_path = str(path + '/' + str(index_val['vod_pic'])).replace("\\", '/')
                # p(dir_pic_url_path)
                file_name = dir_pic_url_path.split('/').pop()  # 弹出文件名字
                file_dir = dir_pic_url_path.replace(file_name, '')  # 获取文件夹
                video_mkdir(file_dir)  # 创建文件夹
                urllib.request.urlretrieve(vod_pic, dir_pic_url_path)  # 下载图片
                compress_image_size = compress_image(dir_pic_url_path, mb=95, type=2)
                # 新文件路径  大小
                common_print_log("----大于100k 处理 id: " + str(index_val['id']) + " ;新文件路径: " + str(
                    compress_image_size[0]) + " ;大小: " + str(round(compress_image_size[1], 2)) + ' kb',
                                 logName='imgLog')
                svideo_data = {
                    'vod_pic': "upload/" + compress_image_size[0].split('upload/')[1]
                }
                common_print_log('https://vpc.alsmzw.cn/' + svideo_data['vod_pic'], logName='imgLogs')
                common_print_log(svideo_data, logName='imgLog')
                db1.update("video", svideo_data, "id = %s" % str(index_val['id']))
                db1.update("vod", svideo_data, "vod_id = %s" % str(index_val['vod_id']))
    page = page + 1
    total_page = total_page - 1
common_print_log("图片为空:" + str(len(t_k_error)) + '条;ID:' + str(t_k_error), logName='imgLog')
common_print_log("图片链接失效:" + str(len(b_c_error)) + '条;ID:' + str(b_c_error), logName='imgLog')
common_print_log("图片小于3k:" + str(len(x_y3k_error)) + '条;ID:' + str(x_y3k_error), logName='imgLog')
