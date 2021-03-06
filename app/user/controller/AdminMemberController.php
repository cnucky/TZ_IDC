<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use app\tools\controller\AjaxController;
use app\user\model\PortalCategoryPostModel;
use app\user\model\PortalPostModel;
use app\user\model\UserExtensionModel;
use app\user\model\UserFavoriteModel;
use app\user\model\UserModel;
use cmf\controller\AdminBaseController;
use think\Db;

class AdminMemberController extends AdminBaseController
{

	/**
	 * 管理员 获取用户数据
	 *
	 * @author 张俊
	 * @return \think\response\Json
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 *
	 * 接口地址：user/Admin_Member/getMemberData
	 * 参数：
	 *     user_id
	 * 返回参数
	 *      id用户ID
	 *      avatar用户头像
	 *      user_nickname用户昵称
	 *      user_name用户账号
	 *      microblog用户微博
	 *      user_email用户邮箱
	 *      user_truename真实姓名
	 *      mobile用户手机号码
	 *      qq用户QQ号码
	 */
	public function getMemberData()
	{

		//实例化ajax工具
		$ajaxTools = new AjaxController();

		//实例化模型
		$userExtensionModel = new UserExtensionModel();
		$userModel          = new UserModel();

		//从session 中获取用户ID
		$userId = $this->request->param('user_id');

		//根据用户ID 查询用户信息
		$userData          = $userModel->get($userId);
		$userExtensionData = $userExtensionModel->getUserExtension($userId);

		//拼装数据
		$data = [

			'id'            => $userId,
			'avatar'        => $userData['avatar'],
			'user_nickname' => $userData['user_nickname'],
			'user_name'     => $userData['user_login'],
			'user_email'    => $userData['user_email'],
			'mobile'        => $userData['mobile'],
			'microblog'     => $userExtensionData['weibo'],
			'user_truename' => $userExtensionData['user_truename'],
			'qq'            => $userExtensionData['qq'],
			'weChat'        => $userExtensionData['wechat']
		];

		//返回数据
		$info = $ajaxTools->ajaxEcho($data, '成功获取用户信息', 1);
		return $info;

	}


	/**
	 * 编辑用户信息
	 *
	 * @author 张俊
	 * @return \think\response\Json
	 *
	 * 接口地址：user/Member/setMemberData
	 * 参数：
	 *      user_nickname用户昵称
	 *      microblog用户微博
	 *      user_email用户邮箱
	 *      user_truename用户名称（真实姓名）
	 *      weChat用户微信
	 *      mobile用户手机号码
	 *
	 */
	public function setMemberData()
	{
		//实例化ajax工具
		$ajaxTools = new AjaxController();

		//实例化模型
		$userModel          = new UserModel();
		$userExtensionModel = new UserExtensionModel();

		//获取用户id
		$userId = $this->request->param('user_id');

		//将需要修改的数据拼装成数组
		$userData          = [
			'user_email'    => $this->request->post('user_email'),
			'mobile'        => $this->request->post('mobile'),
			'user_nickname' => $this->request->post('user_nickname'),
		];
		$userExtensionData = [
			'user_truename' => $this->request->post('user_truename'),
			'weibo'         => $this->request->post('microblog'),
			'wechat'        => $this->request->post('weChat'),
			//						'qq'            => 1231123,
		];

//		//启动事务处理
//		Db::startTrans();

		//修改数据库中的数据
		$userExtensionRes = $userExtensionModel->setUserExtension($userId, $userExtensionData);
		$userUserRes      = $userModel->setUser($userId, $userData);

//		if ($userExtensionRes && $userUserRes) {
//
//			//提交事务
//			Db::commit();
//		} else {
//
//			//事务回滚
//			Db::rollback();
//		}

		$info = $ajaxTools->ajaxEcho(null, '已修改', 1);
		return $info;

	}


	/**
	 * 后台管理 获取用户发布的文章
	 *
	 * @author 张俊
	 * @return \think\response\Json
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 *
	 * 接口地址：user/Admin_Member/getArticle
	 * 参数：无
	 * 请求类型：GET
	 * 返回参数：
	 *         Array类型 [
	 *              {aid,title,status,comment_count,link}
	 *              {aid,title,status,comment_count,link}
	 *          ]
	 *          aid是文章ID
	 *          title：是文章标题
	 *          status：是文章状态
	 *          comment_count：文章评论数量
	 *          link：文章链接  (cmf_url('portal/Article/index',['id'=>1]))
	 *
	 */
	public function getArticle()
	{
		//实例化ajax工具
		$ajaxTools = new AjaxController();

		//获取用户ID
		$userId = $this->request->param('user_id');

		//实例化模型
		$portalPostModel         = new PortalPostModel();
		$portalCategoryPostModel = new PortalCategoryPostModel();

		//根据用户id  获取用户的文章信息
		$result = $portalPostModel->getUserArticle($userId);

		//判断用户有文章数据
		if (!empty($result[0])) {

			//重新拼装成新数组
			foreach ($result as $value => $item) {
				$data[$value]['aid']           = $item['id'];
				$data[$value]['title']         = $item['post_title'];
				$data[$value]['status']        = $item['post_status'];
				$data[$value]['comment_count'] = $item['comment_count'];
				$data[$value]['description']   = $item['post_excerpt'];
				$data[$value]['link']          = cmf_url('portal/Article/index', [
					'id'  => $item['id'],
					'cid' => $portalCategoryPostModel->getCategoryId($item['id']),
				]);
			}

			$info = $ajaxTools->ajaxEcho($data, '获取用户文章信息', 1);
			return $info;
		} else {

			$info = $ajaxTools->ajaxEcho(null, '无文章信息', 0);
			return $info;
		}

	}


	/**
	 * 后台管理 获取用户收藏的文章
	 *
	 * @author 张俊
	 * @return \think\response\Json
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 *
	 * 接口地址：user/Admin_Member/getCollection
	 * 参数：无
	 * 请求类型：GET
	 * 返回参数：
	 *          Array类型 [
	 *              {title,date,link}
	 *          ]
	 *          title：是文章标题
	 *          date:文章收藏时间
	 *          link：文章链接
	 */
	public function getCollection()
	{

		//实例化ajax工具
		$ajaxTools = new AjaxController();

		//实例化模型
		$userFavoriteModel      = new UserFavoriteModel();
		$potalCategoryPostModel = new PortalCategoryPostModel();

		//获取前台登录的id
		$userId = $this->request->param('user_id');

		//获取收藏文章信息
		$postData = $userFavoriteModel->getUserFavorite($userId);

		//判断用户有无收藏列表
		if (!empty($postData[0])) {

			//重新拼装成新数组
			foreach ($postData as $value => $item) {
				$data[$value]['id']    = $item['object_id'];
				$data[$value]['title'] = $item['title'];
				$data[$value]['date']  = $item['create_time'];
				$data[$value]['link']  = cmf_url('portal/Article/index', [
					'id'  => $item['object_id'],
					'cid' => $potalCategoryPostModel->getCategoryId($item['object_id']),
				]);
			}

			$info = $ajaxTools->ajaxEcho($data, '获取用户文章信息', 1);
			return $info;
		} else {

			$info = $ajaxTools->ajaxEcho(null, '无文章信息', 0);
			return $info;
		}

	}


	/**
	 * 删除用户收藏的文章
	 *
	 * @author 张俊
	 * @return \think\response\Json
	 *
	 * 接口地址：user/Member/delCollection
	 * 参数：
	 *     id
	 * 返回参数
	 *       无（状态设为1成功即可）
	 */
	public function delCollection()
	{
		//实例化ajax工具
		$ajaxTools = new AjaxController();

		//实例化模型
		$userFavoriteModel = new UserFavoriteModel();

		//获取需要删除的文章ID
		$delPostId = $this->request->param('id');

		//获取需要删除的文章的用户ID
		$userId = $this->request->param('user_id');

		//执行删操作
		$delResult = $userFavoriteModel->delUserFavorite($delPostId, $userId);

		//判断是否删除成功
		if ($delResult) {
			$info = $ajaxTools->ajaxEcho(null, '删除成功', 1);
		} else {
			$info = $ajaxTools->ajaxEcho(null, '删除失败', 0);
		}

		return $info;

	}


	/**
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 *
	 * 文章删除
	 * @adminMenu(
	 *     'name'   => '文章删除',
	 *     'parent' => 'index',
	 *     'display'=> false,
	 *     'hasView'=> false,
	 *     'order'  => 10000,
	 *     'icon'   => '',
	 *     'remark' => '文章删除',
	 *     'param'  => ''
	 * )
	 * 接口改为：
	 *        用户删除： user/Member/delete
	 *        管理员删除：user/Admin_Member/delete
	 *        统一返回：
	 *                状态（state）为，1 成功，0 失败
	 */
	public function delete()
	{
		$param           = $this->request->param();
		$portalPostModel = new PortalPostModel();

		if (isset($param['id'])) {
			$id           = $this->request->param('id', 0, 'intval');
			$result       = $portalPostModel->where(['id' => $id])->find();
			$data         = [
				'object_id'   => $result['id'],
				'create_time' => time(),
				'table_name'  => 'portal_post',
				'name'        => $result['post_title']
			];
			$resultPortal = $portalPostModel
				->where(['id' => $id])
				->update(['delete_time' => time()]);
			if ($resultPortal) {
				Db::name('portal_category_post')->where(['post_id' => $id])->update(['status' => 0]);
				Db::name('portal_tag_post')->where(['post_id' => $id])->update(['status' => 0]);

				Db::name('recycleBin')->insert($data);
			}
			$portalPostModel->delSlide($id);
//			$this->success("删除成功！", '');
			return idckx_ajax_echo(null, '删除成功', 1);
		}

		if (isset($param['ids'])) {
			$ids     = $this->request->param('ids/a');
			$recycle = $portalPostModel->where(['id' => ['in', $ids]])->select();
			$result  = $portalPostModel->where(['id' => ['in', $ids]])->update(['delete_time' => time()]);
			if ($result) {
				foreach ($recycle as $value) {
					$data = [
						'object_id'   => $value['id'],
						'create_time' => time(),
						'table_name'  => 'portal_post',
						'name'        => $value['post_title']
					];
					Db::name('recycleBin')->insert($data);
				}
				$portalPostModel->delSlide($id);
//				$this->success("删除成功！", '');
				return idckx_ajax_echo(null, '删除成功', 1);
			}
		}
		return idckx_ajax_echo(null, '删除失败', 0);
	}


}