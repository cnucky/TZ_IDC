<?php
// +----------------------------------------------------------------------
// | IDCKX
// +----------------------------------------------------------------------
// | Author: 张俊
// +----------------------------------------------------------------------
namespace app\tools\controller;

use app\tools\model\PluginReptilePostModel;
use cmf\controller\AdminBaseController;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;

class BaiduSitemapController extends AdminBaseController
{

	/**
	 * 站点地图测试方法
	 */
	public function test()
	{

		//实例化模型
		$pluginReptilePost = new PluginReptilePostModel();

		dump();

	}

	/**
	 * 创建站点地图  滴入生成在public 目录下
	 *
	 * @param int $num
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function buildSitemap($num = 50000)
	{
		//获取域名
		$domain = $this->request->domain();

		//实例化站点地图类
		$sitemapModel = new SitemapController();

		//从数据库中取出需要提交的文章数据
		$portalData = Db::name('portal_post')
			->field('id,published_time')
			->where('post_status', 1)
			->where('delete_time', 0)
			->order('published_time desc')
			->limit($num)
			->select();

		//根据接口规则  重新拼装新数组
		foreach ($portalData as $value => $item) {
			$cid = idckx_get_category_id($item['id']);
			$url = $domain . cmf_url('portal/Article/index', ['id' => $item['id'], 'cid' => $cid]);
			$sitemapModel->AddItem($url, $item['published_time'],0);
		}
		$sitemapModel->SaveToFile('sitemap.xml');

		return idckx_ajax_echo(null, '成功', 1);

	}

}
