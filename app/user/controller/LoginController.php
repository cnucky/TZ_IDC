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

use app\user\model\UserExtensionModel;
use think\Loader;
use think\Session;
use think\Validate;
use cmf\controller\HomeBaseController;
use app\user\model\UserModel;
use app\tools\controller\AjaxController;
use app\user\model\UserTokenModel;

class LoginController extends HomeBaseController
{


	/**
	 * 判断前台用户是否已登录
	 *
	 * @author 张俊
	 * @return \think\response\Json
	 *
	 */
	public function isLogin()
	{
		//实例化
		$ajaxTools = new AjaxController();


		//判断session中是否存在用户信息
		if (Session("user")) {
			if (Session('user.expire_time') > time()) {
				//拼装返回数据
				$data = [
					'id'  => Session("user.id"),
					'img' => Session("user.avatar"),
				];
				$info = $ajaxTools->ajaxEcho($data, '已登录', 1);
				return $info;
			} else {
				$info = $ajaxTools->ajaxEcho(null, '登录过期', 5000);
				return $info;
			}
		} else {
			$info = $ajaxTools->ajaxEcho(null, '未登录', 5000);
			return $info;
		}
	}


	/**
	 * 退出登录
	 * @author 张俊
	 * @return \think\response\Redirect
	 *
	 * 成功退出登录返回 状态码5000
	 */
	public function outLogin()
	{
		//实例化
		$ajaxTools = new AjaxController();

		//清空session中的信息
		Session('user', null);
		Session('__token__', null);
		$data = [
			'url' => '/',
		];

		//返回信息
		$info = $ajaxTools->ajaxEcho($data, '已退出登录', 1);
		return $info;

	}


	/**
	 * 登录操作
	 *
	 * @author 张俊
	 * @return \think\response\Json
	 *
	 * 登录框
	 * 接口名称：user/login/dologin
	 * 参数：    username：用户名
	 *           password：密码
	 *           Autologon：是否能自动登录（把token时间设置长点，默认是1小时的）
	 * 返回参数：
	 *        id：用户id
	 *        avatar：头像
	 *        token：当前登录token
	 *        （必须用我封装的那个函数返回）
	 */
	public function doLogin()
	{
		//实例化
		$ajaxTools = new AjaxController();
		$userModel = new UserModel();

		//判断是否接收到参数
		if ($this->request->isPost()) {

			//重新整合数组
			$user = [
				'user_login' => $this->request->post('username'),
				'user_pass'  => $this->request->post('password'),
			];
			$log  = $userModel->doName($user);

			switch ($log) {
				case 0:
					$userTokenModel = new UserTokenModel();

					//写入登录信息
					cmf_user_action('login');

					//生成token 并保存token值
					$token = $this->request->token('__token__', Session('user.id'));

					//根据自动登录 修改过期时间 (7天自动登录  604800)
					if ($this->request->post('Autologon') == 1) {
						$res = $userTokenModel->addUserTokenData(Session('user.id'), $token, 604800);
						Session('user.expire_time', time() + 604800);
					} else {
						$res = $userTokenModel->addUserTokenData(Session('user.id'), $token, 3600);
						Session('user.expire_time', time() + 3600);
					}

					//拼装返回的数据数组
					$resData = [
						"id"     => Session('user.id'),
						"avatar" => Session('user.avatar'),
						"token"  => $token,
					];

					//清理过期Token
					$userTokenModel->clearExpireToken();

					$info = $ajaxTools->ajaxEcho($resData, '登录成功', 1);
					return $info;
					break;
				case 1:
					$info = $ajaxTools->ajaxEcho(null, '登录密码错误', 0);
					return $info;
					break;
				case 2:
					$info = $ajaxTools->ajaxEcho(null, '账户不存在', 0);
					return $info;
					break;
				case 3:
					$info = $ajaxTools->ajaxEcho(null, '账号禁止登录', 0);
					return $info;
					break;
				default :
					$info = $ajaxTools->ajaxEcho(null, '未受理请求', 0);
					return $info;
					break;
			}
		} else {
			$info = $ajaxTools->ajaxEcho(null, '错误请求', 0);
			return $info;
		}

	}


	/**
	 * 登录
	 */
	public function index()
	{
		$redirect = $this->request->post("redirect");
		if (empty($redirect)) {
			$redirect = $this->request->server('HTTP_REFERER');
		} else {
			$redirect = base64_decode($redirect);
		}
		session('login_http_referer', $redirect);
		if (cmf_is_user_login()) { //已经登录时直接跳到首页
			return redirect($this->request->root() . '/');
		} else {
			return $this->fetch(":login");
		}
	}

	/**
	 * 登录验证提交
	 */
	public function doLoginCmf()
	{
		if ($this->request->isPost()) {
			$validate = new Validate([
				'captcha'  => 'require',
				'username' => 'require',
				'password' => 'require|min:6|max:32',
			]);
			$validate->message([
				'username.require' => '用户名不能为空',
				'password.require' => '密码不能为空',
				'password.max'     => '密码不能超过32个字符',
				'password.min'     => '密码不能小于6个字符',
				'captcha.require'  => '验证码不能为空',
			]);

			$data = $this->request->post();
			if (!$validate->check($data)) {
				$this->error($validate->getError());
			}

			if (!cmf_captcha_check($data['captcha'])) {
				$this->error('验证码错误');
			}

			$userModel         = new UserModel();
			$user['user_pass'] = $data['password'];
			if (Validate::is($data['username'], 'email')) {
				$user['user_email'] = $data['username'];
				$log                = $userModel->doEmail($user);
			} else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['username'])) {
				$user['mobile'] = $data['username'];
				$log            = $userModel->doMobile($user);
			} else {
				$user['user_login'] = $data['username'];
				$log                = $userModel->doName($user);
			}
			$session_login_http_referer = session('login_http_referer');
			$redirect                   = empty($session_login_http_referer) ? $this->request->root() : $session_login_http_referer;
			switch ($log) {
				case 0:
					cmf_user_action('login');
					$this->success('登录成功', $redirect);
					break;
				case 1:
					$this->error('登录密码错误');
					break;
				case 2:
					$this->error('账户不存在');
					break;
				case 3:
					$this->error('账号被禁止访问系统');
					break;
				default :
					$this->error('未受理的请求');
			}
		} else {
			$this->error("请求错误");
		}
	}

	/**
	 * 找回密码
	 */
	public function findPassword()
	{
		return $this->fetch('/find_password');
	}

	/**
	 * 用户密码重置
	 */
	public function passwordReset()
	{

		if ($this->request->isPost()) {
			$validate = new Validate([
				'captcha'           => 'require',
				'verification_code' => 'require',
				'password'          => 'require|min:6|max:32',
			]);
			$validate->message([
				'verification_code.require' => '验证码不能为空',
				'password.require'          => '密码不能为空',
				'password.max'              => '密码不能超过32个字符',
				'password.min'              => '密码不能小于6个字符',
				'captcha.require'           => '验证码不能为空',
			]);

			$data = $this->request->post();
			if (!$validate->check($data)) {
				$this->error($validate->getError());
			}

			if (!cmf_captcha_check($data['captcha'])) {
				$this->error('验证码错误');
			}
			$errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
			if (!empty($errMsg)) {
				$this->error($errMsg);
			}

			$userModel = new UserModel();
			if ($validate::is($data['username'], 'email')) {

				$log = $userModel->emailPasswordReset($data['username'], $data['password']);

			} else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['username'])) {
				$user['mobile'] = $data['username'];
				$log            = $userModel->mobilePasswordReset($data['username'], $data['password']);
			} else {
				$log = 2;
			}
			switch ($log) {
				case 0:
					$this->success('密码重置成功', $this->request->root());
					break;
				case 1:
					$this->error("您的账户尚未注册");
					break;
				case 2:
					$this->error("您输入的账号格式错误");
					break;
				default :
					$this->error('未受理的请求');
			}

		} else {
			$this->error("请求错误");
		}
	}


	/**
	 * TODO
	 *参数：
	 *     extData: 第三方返回的对象
	 *     type: 第三方类型weibo（微博）、qq（qq）、wx微信
	 *返回参数
	 *    state为1就是登录成功,state为0就是登录失败
	 *    如果要处理这些数据尽量把它处理成和普通登录一样
	 *
	 */
	public function extLogin()
	{


	}


	/**
	 * 第三方账号登录
	 *
	 * 参数  :
	 *           $type   平台类型:  wechat:微信    weibo:微博    qq:qq
	 *          $open_id   开放平台获取到的唯一id
	 *          $token  token值
	 * 返回参数:
	 *       状态码: 登录成功1    未绑定2 没有token5001    token过期5002
	 *
	 * @return \think\response\Json
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function doLoginByOpenAccount($openId = null, $type = null, $token = null)
	{

		//实例化模型
		$userModel = new UserModel();

		//若有页面传参则执行页面函数
		if (empty($openId)) {
			//获取参数
			$openId = $this->request->param('open_id');
			$type   = $this->request->param('type');
			$token  = $this->request->param('token');
		}


		//查询token
		$dbToken = idckx_token_get($token);

		//判断有无绑定第三方帐号
		if ($bindingId = idckx_verify_binding($type, $dbToken['user_id'])) {
			//有绑定

			//验证数据库中否存在前端发送过来的token
			if ($dbToken) {
				//存在token

				//验证token是否过期
				if ($dbToken['expire_time'] > time()) {
					//未过期

					$loginLog = $userModel->doLoginByOpenAccount($bindingId);

					switch ($loginLog) {
						case 0:
							//登录成功
							// 将token加入到  session中
							session('__token__', $dbToken['token']);
							Session('user.expire_time', $dbToken['expire_time']);
							return idckx_ajax_echo(null, '登录成功', 1);
							break;
						case 1:

							//帐号被拉黑
							return idckx_ajax_echo(null, '帐号已被拉黑', 0);
						case 2:
							//登录失败,请重试
							return idckx_ajax_echo(null, '登录失败 请重试', 0);

							break;
						default:
							//未知错误

					}


				} else {
					//已过期

					//清理此条过期token
					idckx_token_del(1, $dbToken['token']);

					return idckx_ajax_echo(null, 'token已过期', 5002);
				}


			} else {
				//不存在token
				return idckx_ajax_echo(null, '不存在token', 5001);
			}

		} else {
			//无绑定
			return idckx_ajax_echo(null, '无绑定本站帐号', 2);

		}


	}


	/**
	 * 第三方绑定  登录
	 *
	 *
	 * @Author ZhangJun
	 * 参数 :
	 *   open_id:   第三方帐号的openId
	 *    token :   第三方登录的token值
	 *      type: 第三方平台   wechat微信
	 *
	 *
	 *
	 * 状态码:
	 *  登录成功:   绑定成功 1  绑定失败2
	 *   token  过期5001  不存在5002
	 */
	public function doBindingAndLogin()
	{
		//实例化
		$userModel = new UserModel();

		//接收参数
		$openId = $this->request->param('open_id');
		$token  = $this->request->param('token');
		$type   = $this->request->param('bing_type');
		$user   = [
			'user_login' => $this->request->param('username'),
			'user_pass'  => $this->request->param('password'),
		];

		$token = trim($token);

		//查询token是否有效
		if (idckx_token_valid($token)) {
			//有效

			$log = $userModel->doName($user);

			switch ($log) {
				case 0:
					//实例化模型
					$userTokenModel     = new UserTokenModel();
					$userExtensionModel = new UserExtensionModel();

					//绑定第三方帐号
					$userExtensionModel->bindUserOpenAccount(Session('user.id'), $type, $openId);

					//查询token
					$dbToken = idckx_token_get($token);

					//写入过期时间
					Session('user.expire_time', $dbToken['expire_time']);

					//写入登录信息
					cmf_user_action('login');


					//拼装返回的数据数组
					$resData = [
						"id"     => Session('user.id'),
						"avatar" => Session('user.avatar'),
						"token"  => $token,
					];


					return idckx_ajax_echo($resData, '绑定并成功', 1);
					break;
				case 1:
					return idckx_ajax_echo(null, '登录密码错误', 0);
					break;
				case 2:
					return idckx_ajax_echo(null, '账户不存在', 0);
					break;
				case 3:
					return idckx_ajax_echo(null, '账号禁止登录', 0);
					break;
				default :
					return idckx_ajax_echo(null, '未受理请求', 0);
					break;
			}

		} else {
			//无效或或过期


			return idckx_ajax_echo(null, 'token无效或过期', 5001);
		}


	}


	/**
	 * 解除绑定
	 */
	public function removeBinding()
	{
		//判断用户是否登录
		if (cmf_is_user_login()) {
			//已登录

			//实例化模型
			$userExtensionModel = new UserExtensionModel();

			//获取参数
			$type = $this->request->param('type');

			//模型执行解绑
			$res = $userExtensionModel->removeBinding($type);

			return $res ? idckx_ajax_echo(null, '解绑成功', 1) : idckx_ajax_echo(null, '解绑失败', 0);
		} else {

			//未登录
			return idckx_ajax_echo(null, '未登录', null);
		}

	}


	/**
	 *
	 *
	 * 测试控制器
	 */
	public function test()
	{

//		dump(cmf_get_current_user_id());
//
//		dump(idckx_token_valid('2.00aSZU6GSmUCFBb825497896bwpGbB'));
//		dump(Session('user.id'));
////		dump(Session::get());


	}


}