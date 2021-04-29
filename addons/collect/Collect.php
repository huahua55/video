<?php
namespace addons\collect;
use think\Addons;
/**
 * 官方采集插件
 */
class Collect extends Addons {
	/**
	 * 插件安装方法
	 * @return bool
	 */
	public function install() {
		return true;
	}

	/**
	 * 插件卸载方法
	 * @return bool
	 */
	public function uninstall() {
		return true;
	}

}
