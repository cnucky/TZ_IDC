<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------


use think\Route;


//首页路由
//Route::rule([
//    '/'  =>  'web/Index/Index'
//],'','GET');

//Route::rule('portal/article/index','portal/article/index','GET|POST');


if (file_exists(CMF_ROOT . "data/conf/route.php")) {
	$runtimeRoutes = include CMF_ROOT . "data/conf/route.php";
} else {
	$runtimeRoutes = [];
}

return $runtimeRoutes;