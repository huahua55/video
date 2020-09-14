# !/usr/bin/python
# -*- coding:utf-8 -*-

import logging
import time


class Log(object):

    def __init__(self, logger=None, logPath=None, logName='mylog', log_cate='search'):
        '''
            指定保存日志的文件路径，日志级别，以及调用文件
            将日志存入到指定的文件中
        '''
        # 创建一个logger
        self.logger = logging.getLogger(logName)
        if not self.logger.handlers:
            self.logger.setLevel(logging.DEBUG)
            # 创建一个handler，用于写入日志文件
            self.log_time = time.strftime("%Y_%m_%d")
            self.log_path = logPath
            self.log_name = self.log_path + "/" + logName + '.log'
            fh = logging.FileHandler(self.log_name, 'a', encoding='utf-8')  # 追加模式  这个是python2的
            # fh = logging.FileHandler(self.log_name, 'a', encoding='utf-8')  # 这个是python3的
            fh.setLevel(logging.INFO)

            # 再创建一个handler，用于输出到控制台
            ch = logging.StreamHandler()
            ch.setLevel(logging.INFO)

            # 定义handler的输出格式
            formatter = logging.Formatter(
                '[%(asctime)s] %(filename)s->%(funcName)s line:%(lineno)d [%(levelname)s]%(message)s')
            # logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
            fh.setFormatter(formatter)
            ch.setFormatter(formatter)

            # 给logger添加handler
            self.logger.addHandler(fh)
            # self.logger.addHandler(ch)

            #  添加下面一句，在记录日志之后移除句柄
            # self.logger.removeHandler(ch)
            # self.logger.removeHandler(fh)
            # 关闭打开的文件
            fh.close()
            ch.close()

    def getlog(self):
        return self.logger
