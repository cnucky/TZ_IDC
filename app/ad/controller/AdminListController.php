<?php

namespace app\ad\controller;

use app\ad\model\AdminListModel;
use app\ad\model;
use cmf\controller\AdminBaseController;
use think\Loader;
use think\Request;
use think\Validate;

/**
 * Class ListController
 *
 * @author 张俊
 * @package app\ad\controller
 */
class AdminListController extends AdminBaseController
{

    /**
     * 广告列表  获取获列表
     *
     * @author 张俊
     *
     */
    public function getList(Request $request)
    {

        //获取分页数据
        $page  = $request->param('page');
        $limit = $request->param('limit');

        //计算分页偏移量
        $offset = ($page - 1) * $limit;

        //实例化  广告模型
        $getListModel = new AdminListModel();

        //从广告列表模型中取出 广告数据
        $dataList = $getListModel->getList($offset, $limit);

        //将获取的广告列表数据重组成新数组
        $i = 0;
        foreach ($dataList['list'] as $k => $v) {
            $data[$k] = $v;
            switch ($data[$k]['ad_state']) {
                case 0:
                    $data[$k]['ad_state'] = "关闭";
                    break;
                case 1:
                    $data[$k]['ad_state'] = "开启";
                    break;
                default:
                    break;
            }
            $i++;
        }

        //将数据 与 状态码 和消息重组成数组并转成json 数据返回到视图层的AJAX中
        $res['code']  = 0;
        $res['msg']   = '';
        $res['count'] = $dataList['count'];
        $res['data']  = $data;
        echo json_encode($res);


//        dump($dataList);
    }

    /**
     * 广告列表  修改状态
     *
     * @author 张俊
     * @param Request $request
     *
     */
    public function alterStatu(Request $request)
    {

        //从请求信息中获得  数据
        $adId   = $request->param('adId');
        $status = $request->param('status');

        $alterStateModel = new AdminListModel();
        $info            = $alterStateModel->alterState($adId, $status);
        if ($info) {
            $res['code'] = 1;
//            $res['msg']  = '成功';
        } else {
            $res['code'] = 0;
//            $res['msg']  = '失败!';
        }

        echo json_encode($res);
    }

}