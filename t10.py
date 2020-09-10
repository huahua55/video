# encoding=utf-8

from PIL import Image
import os
import sys


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
        return infile, o_size
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
            rade = 1.8
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


# path = '/data/www/video'
path = os.getcwd()
# print(path)
# exit()
up_path = str(sys.argv[1:][0])
if up_path.find('upload') != -1:
    dir_pic_url_path = path + '/' + str(up_path)
    imgSize = os.path.getsize(dir_pic_url_path)
    last_kb = 1024 * 100  # 最终kb 100k
    if int(imgSize) >= int(last_kb):
        print('大于100K ' + str(imgSize))
        compress_image_size = compress_image(dir_pic_url_path, mb=95, type=2)
    else:
        print('小于100K ' + str(imgSize))
