<?php

// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Home\Controller;

use Think\ManageBaseController;

/**
 * 公众号管理
 */
class AppsController extends ManageBaseController {
	protected $addon, $model;
	function __construct() {
		$this->need_appinfo = false;
		parent::__construct ();
	}
	public function _initialize() {
		parent::_initialize ();
		
		$this->assign ( 'check_all', false );
		$this->assign ( 'search_url', U ( 'lists' ) );
		
		define ( 'ADDON_PUBLIC_PATH', '' );
		
		$this->model = M ( 'model' )->getByName ( 'apps' );
		$this->assign ( 'model', $this->model );
		// dump ( $this->model );
	}
	protected function _display() {
		$this->view->display ( 'Addons:' . ACTION_NAME );
	}
	function help() {
		if (empty ( $_GET ['public_id'] )) {
			$this->error ( '110009:公众号参数非法' );
		}
		$this->display ( 'Index/help' );
	}
	/**
	 * 显示指定模型列表数据
	 */
	public function lists() {
		// 获取模型信息
		$model = $this->model;
		
		// 搜索条件
		$mp_ids = M ( 'apps_link' )->where ( "uid='{$this->mid}'" )->getFields ( 'mp_id' );
		$map ['id'] = 0;
		if (! empty ( $mp_ids )) {
			$map ['id'] = $map3 ['mp_id'] = array (
					'in',
					$mp_ids 
			);
			
			$list = M ( 'apps_link' )->where ( $map3 )->group ( 'mp_id' )->field ( 'mp_id,count(1) as num' )->select ();
			foreach ( $list as $vo ) {
				$countArr [$vo ['mp_id']] = $vo ['num'];
			}
		}
		
		// 读取模型数据列表
		$name = parse_name ( get_table_name ( $model ['id'] ), true );
		$data = M ( $name )->field ( true )->where ( $map )->order ( 'id desc' )->select ();
		
		$dataTable = D ( 'Common/Model' )->getFileInfo ( $model );
		$data = $this->parseData ( $data, $dataTable->fields, $dataTable->list_grid, $dataTable->config );
		
		foreach ( $data as $d ) {
			$d ['count'] = $countArr [$d ['id']];
			$d ['is_creator'] = $d ['uid'] == $this->mid ? 1 : 0;
			$listArr [$d ['app_type']] [] = $d;
		}
		
		$list_data ['list_data'] = $listArr;
		$this->assign ( $list_data );
		
		$this->display ();
	}
	public function del() {
		$model = $this->model;
		
		$id = I ( 'id' );
		
		if (empty ( $id )) {
			$this->error ( '110010:请选择要操作的数据!' );
		}
		
		$Model = M ( get_table_name ( $model ['id'] ) );
		$map ['id'] = $id;
		$uid = $Model->where ( $map )->getField ( 'uid' );
		
		if ($uid == $this->mid) {
			
			if ($Model->where ( $map )->delete ()) {
				$map_link ['mp_id'] = $id;
				M ( 'apps_link' )->where ( $map_link )->delete ();
				
				if (C ( 'PUBLIC_BIND' )) { // TODO 通知微信解除绑定
				}
				
				$this->success ( '删除成功' );
			} else {
				$this->error ( '110011:删除失败！' );
			}
		} else {
			$map_link ['mp_id'] = $id;
			$map_link ['uid'] = $this->mid;
			M ( 'apps_link' )->where ( $map_link )->delete ();
			
			$this->success ( '删除成功' );
		}
	}
	public function edit($model = null, $id = 0) {
		$id || $id = I ( 'id' );
		redirect ( U ( 'add', [ 
				'id' => $id 
		] ) );
	}
	public function add($model = null) {
		if (IS_POST) {
			foreach ( $_POST as &$v ) {
				$v = trim ( $v );
			}
			
			$map ['uid'] = $this->mid;
			if (M ( 'manager' )->where ( $map )->find ()) {
				M ( 'manager' )->where ( $map )->save ( $_POST );
			} else {
				$_POST ['uid'] = $this->mid;
				M ( 'manager' )->add ( $_POST );
			}
			
			$data ['is_init'] = 1;
			$res = D ( 'Common/User' )->updateInfo ( $this->mid, $data );
			
			$is_open = C ( 'PUBLIC_BIND' ) && $this->mid == 46283;
			
			$url = U ( 'lists' );
			if ($res) {
				$this->success ( '保存基本信息成功！', $url );
			} elseif ($res === 0) {
				$this->success ( ' ', $url );
			} else {
				$this->error ( '110012:保存失败' );
			}
		} else {
			$manager = ( array ) M ( 'manager' )->find ( $this->mid );
			$data = D ( 'Common/User' )->find ( $this->mid );
			
			$data = array_merge ( $data, $manager );
			
			$this->assign ( 'info', $data );
			
			$this->display ();
		}
	}
	function step_0() {
		if (C ( 'PUBLIC_BIND' ) && is_install ( 'PublicBind' )) {
			$res = D ( 'Addons://PublicBind/PublicBind' )->bind ();
			if (! $res ['status']) {
				$this->error ( '110013:' . $res ['msg'] );
				exit ();
			}
			redirect ( $res ['jumpURL'] );
		}
		
		$map ['id'] = $id = I ( 'id' );
		$data = D ( 'Common/Apps' )->where ( $map )->find ();
		if (! empty ( $data ) && $data ['uid'] != $this->mid) {
			$this->error ( '110014:非法操作' );
		}
		
		$this->assign ( 'id', $id );
		
		$model = $this->model;
		if (IS_POST) {
			foreach ( $_POST as &$v ) {
				$v = trim ( $v );
			}
			if (empty ( $_POST ['public_name'] )) {
				$this->error ( '110015:公众号名称不能为空' );
			}
			if (empty ( $_POST ['public_id'] )) {
				$this->error ( '110016:原始ID不能为空' );
			}
			
			$_POST ['token'] = $_POST ['public_id'];
			$_POST ['group_id'] = intval ( C ( 'DEFAULT_APPS_GROUP_ID' ) );
			$_POST ['uid'] = $this->mid;
			$_POST ['app_type'] = 0;
			
			$map2 ['uid'] = $this->mid;
			M ( 'manager' )->where ( $map2 )->setField ( 'has_public', 1 );
			
			$Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
			// 获取模型的字段信息
			$Model = $this->checkAttr ( $Model, $model ['id'] );
			if (empty ( $id )) {
				if ($Model->create () && $id = $Model->add ()) {
					// 增加公众号与用户的关联关系
					$data ['uid'] = $this->mid;
					$data ['mp_id'] = $id;
					$data ['is_creator'] = 1;
					M ( 'apps_link' )->add ( $data );
					// 更新缓存
					D ( 'Common/Apps' )->clear ( $id );
					D ( 'Common/User' )->clear ( $this->mid );
					
					$url = U ( 'step_1?id=' . $id );
					
					$this->success ( '添加基本信息成功！', $url );
				} else {
					$this->error ( '110017:' . $Model->getError () );
				}
			} else {
				$_POST ['id'] = $id;
				$url = U ( 'step_1?id=' . $id );
				$Model->create () && $res = $Model->save ();
				// 更新缓存
				D ( 'Common/Apps' )->clear ( $id );
				D ( 'Common/User' )->clear ( $this->mid );
				
				if ($res) {
					$this->success ( '保存基本信息成功！', $url );
				} elseif ($res === 0) {
					$this->success ( ' ', $url );
				} else {
					$this->error ( '110018:' . $Model->getError () );
				}
			}
		} else {
			$data ['type'] = intval ( $data ['type'] );
			$this->assign ( 'info', $data );
			
			$this->display ();
		}
	}
	function step_1() {
		$id = I ( 'id' );
		$this->assign ( 'id', $id );
		
		$this->display ();
	}
	function step_2() {
		$model = $this->model;
		$id = I ( 'get.id' );
		$this->assign ( 'id', $id );
		
		$data = M ( get_table_name ( $model ['id'] ) )->find ( $id );
		if (empty ( $data ) || $data ['uid'] != $this->mid) {
			$this->error ( '110019:非法操作' );
		}
		
		$user = D ( 'Common/User' )->find ( $this->mid );
		$is_audit = $user ['is_audit'];
		$this->assign ( 'is_audit', $is_audit );
		if (IS_POST) {
			$_POST ['id'] = $id;
			
			foreach ( $_POST as &$v ) {
				$v = trim ( $v );
			}
			
			$Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
			// 获取模型的字段信息
			$Model = $this->checkAttr ( $Model, $model ['id'] );
			
			if ($Model->create () && false !== $Model->save ()) {
				D ( 'Common/Apps' )->clear ( $data ['id'] );
				D ( 'Common/Apps' )->clear ( $id );
				
				if ($is_audit == 0 && ! C ( 'REG_AUDIT' )) {
					$this->success ( '提交成功！', U ( 'waitAudit', [ 
							'id' => $id 
					] ) );
				} else {
					$this->success ( '保存成功！', U ( 'Home/Apps/lists' ) );
				}
			} else {
				$this->error ( '110020:' . $Model->getError () );
			}
		} else {
			$data || $this->error ( '110021:数据不存在！' );
			
			$this->assign ( 'info', $data );
			
			$this->display ();
		}
	}
	function check_url() {
		$info = parse_url ( SITE_URL );
		if (! APP_DEBUG) {
			if ($info ['scheme'] == 'http') {
				$this->error ( '110500:小程序需要在https环境下配置' );
			}
			if ($info ['host'] == 'localhost' || $info ['host'] == '127.0.0.1') {
				$this->error ( '110501:小程序需要有域名的环境下配置' );
			}
		}
		$this->assign ( 'host', $info ['host'] );
	}
	function step_miniapp_0() {
		$this->check_url ();
		
		$map ['id'] = $id = I ( 'id' );
		$map ['app_type'] = 1;
		$data = D ( 'Common/Apps' )->where ( $map )->find ();
		if (! empty ( $data ) && $data ['uid'] != $this->mid) {
			$this->error ( '110022:非法操作' );
		}
		
		$this->assign ( 'id', $id );
		
		$model = $this->model;
		if (IS_POST) {
			foreach ( $_POST as &$v ) {
				$v = trim ( $v );
			}
			
			$_POST ['token'] = $_POST ['public_id'];
			$_POST ['group_id'] = intval ( C ( 'DEFAULT_APPS_GROUP_ID' ) );
			$_POST ['uid'] = $this->mid;
			$_POST ['app_type'] = 1;
			
			$map2 ['uid'] = $this->mid;
			M ( 'manager' )->where ( $map2 )->setField ( 'has_public', 1 );
			
			$Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
			// 获取模型的字段信息
			$Model = $this->checkAttr ( $Model, $model ['id'] );
			if (empty ( $id )) {
				if ($Model->create () && $id = $Model->add ()) {
					// 增加公众号与用户的关联关系
					$data ['uid'] = $this->mid;
					$data ['mp_id'] = $id;
					$data ['is_creator'] = 1;
					M ( 'apps_link' )->add ( $data );
					// 更新缓存
					D ( 'Common/Apps' )->clear ( $id );
					D ( 'Common/User' )->clear ( $this->mid );
					
					$url = U ( 'step_miniapp_1?id=' . $id );
					
					$this->success ( '添加基本信息成功！', $url );
				} else {
					$this->error ( '110023:' . $Model->getError () );
				}
			} else {
				$_POST ['id'] = $id;
				$url = U ( 'step_miniapp_1?id=' . $id );
				$Model->create () && $res = $Model->save ();
				// 更新缓存
				D ( 'Common/Apps' )->clear ( $id );
				D ( 'Common/User' )->clear ( $this->mid );
				
				if ($res) {
					$this->success ( '保存基本信息成功！', $url );
				} elseif ($res === 0) {
					$this->success ( ' ', $url );
				} else {
					$this->error ( '110024:' . $Model->getError () );
				}
			}
		} else {
			$data ['type'] = intval ( $data ['type'] );
			$this->assign ( 'info', $data );
			
			$this->display ();
		}
	}
	function step_miniapp_1() {
		$this->check_url ();
		
		$id = I ( 'id' );
		$this->assign ( 'id', $id );
		
		$baseUrl = SITE_URL . '/index.php?s=/w' . $id . '/';
		$this->assign ( 'baseUrl', $baseUrl );
		
		$this->display ();
	}
	function step_miniapp_2() {
		$model = $this->model;
		$id = I ( 'get.id' );
		$this->assign ( 'id', $id );
		
		$data = M ( get_table_name ( $model ['id'] ) )->find ( $id );
		if (empty ( $data ) || $data ['uid'] != $this->mid) {
			$this->error ( '110025:非法操作' );
		}
		
		$user = D ( 'Common/User' )->find ( $this->mid );
		$is_audit = $user ['is_audit'];
		$this->assign ( 'is_audit', $is_audit );
		if (IS_POST) {
			$_POST ['id'] = $id;
			
			foreach ( $_POST as &$v ) {
				$v = trim ( $v );
			}
			
			$Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
			// 获取模型的字段信息
			$Model = $this->checkAttr ( $Model, $model ['id'] );
			
			if ($Model->create () && false !== $Model->save ()) {
				D ( 'Common/Apps' )->clear ( $data ['id'] );
				
				if ($is_audit == 0 && ! C ( 'REG_AUDIT' )) {
					$this->success ( '提交成功！', U ( 'waitAudit', [ 
							'id' => $id 
					] ) );
				} else {
					$this->success ( '保存成功！', U ( 'Home/Apps/lists' ) );
				}
			} else {
				$this->error ( '110026:' . $Model->getError () );
			}
		} else {
			$data || $this->error ( '110027:数据不存在！' );
			
			$this->assign ( 'info', $data );
			
			$this->display ();
		}
	}
	// 微信支付配置
	function payment_set() {
		$id = I ( 'id' );
		$data = D ( 'Common/Apps' )->getInfo ( $id );
		if (! empty ( $data ) && $data ['uid'] != $this->mid) {
			$this->error ( '110022:非法操作' );
		}
		
		$this->assign ( 'id', $id );
		
		if (IS_POST) {
			foreach ( $_POST as &$v ) {
				$v = trim ( $v );
			}
			$save ['appid'] = I ( 'appid' );
			if (empty ( $save ['appid'] )) {
				$this->error ( '110101:小程序APPID不能为空' );
			}
			$save ['mch_id'] = I ( 'mch_id' );
			if (empty ( $save ['mch_id'] )) {
				$this->error ( '110102:微信支付商户号不能为空' );
			}
			$save ['partner_key'] = I ( 'partner_key' );
			if (empty ( $save ['partner_key'] )) {
				$this->error ( '110103:API密钥不能为空' );
			}
			
			if (! empty ( $data ['appid'] ) && $save ['appid'] != $data ['appid']) {
				$this->error ( '110104:appid与当前账号的appid不一致' );
			}
			
			$save ['cert_pem'] = I ( 'cert_pem' );
			$save ['key_pem'] = I ( 'key_pem' );
			
			D ( 'Common/Apps' )->updateInfo ( $id, $save );
			
			// 更新缓存
			D ( 'Common/User' )->clear ( $this->mid );
			
			$this->success ( '保存成功！', U ( 'lists' ) );
		} else {
			$data ['type'] = intval ( $data ['type'] );
			$this->assign ( 'info', $data );
			
			$this->display ();
		}
	}
	protected function checkAttr($Model, $model_id) {
		$fields = get_model_attribute ( $model_id );
		$validate = $auto = array ();
		foreach ( $fields as $key => $attr ) {
			if ($attr ['is_must']) { // 必填字段
				$validate [] = array (
						$attr ['name'],
						'require',
						$attr ['title'] . '必须!' 
				);
			}
			// 自动验证规则
			if (! empty ( $attr ['validate_rule'] ) || $attr ['validate_type'] == 'unique') {
				$validate [] = array (
						$attr ['name'],
						$attr ['validate_rule'],
						$attr ['error_info'] ? $attr ['error_info'] : $attr ['title'] . '验证错误',
						0,
						$attr ['validate_type'],
						$attr ['validate_time'] 
				);
			}
			// 自动完成规则
			if (! empty ( $attr ['auto_rule'] )) {
				$auto [] = array (
						$attr ['name'],
						$attr ['auto_rule'],
						$attr ['auto_time'],
						$attr ['auto_type'] 
				);
			} elseif ('checkbox' == $attr ['type'] || 'dynamic_checkbox' == $attr ['type']) { // 多选型
				$auto [] = array (
						$attr ['name'],
						'arr2str',
						3,
						'function' 
				);
			} elseif ('datetime' == $attr ['type']) { // 日期型
				$auto [] = array (
						$attr ['name'],
						'strtotime',
						3,
						'function' 
				);
			}
		}
		return $Model->validate ( $validate )->auto ( $auto );
	}
	// 等待审核页面
	function waitAudit() {
		$data = D ( 'Common/User' )->find ( $this->mid );
		$is_audit = $data ['is_audit'];
		if ($is_audit == 0 && ! C ( 'REG_AUDIT' )) {
			$this->display ();
		} else {
			redirect ( U ( 'home/index/index' ) );
		}
	}
	// 自动检测
	function check_res() {
		$map ['id'] = I ( 'id', 0, 'intval' );
		$info = M ( 'apps' )->where ( $map )->find ();
		$type = $info ['type'];
		$arr = array (
				'0' => '普通订阅号',
				'1' => '微信认证订阅号',
				'2' => '普通服务号',
				'3' => '微信认证服务号' 
		);
		$this->assign ( 'public_type', $arr [$type] );
		$this->assign ( 'info', $info );
		
		$map2 ['token'] = $info ['token'];
		M ( 'apps_check' )->where ( $map2 )->delete ();
		
		// 获取微信权限节点
		$list = M ( 'apps_auth' )->select ();
		foreach ( $list as &$vo ) {
			$vo ['type'] = $vo ['type_' . $type];
		}
		$this->assign ( 'list_data', $list );
		// dump ( $list );
		
		$this->display ();
	}
	/*************************小程序代码管理************************/
	/*
	 * 修改服务器域名
	 * 需要先将域名登记到第三方平台的小程序服务器域名中，才可以调用接口进行配置。
	 */
	function modify_domain()
    {
        $publicid = I('id', 0, 'intval');
        $publicInfo = D('Common/Apps')->getInfo($publicid);
        dump($publicInfo);
        $url = 'https://api.weixin.qq.com/wxa/modify_domain?access_token=' . get_access_token($publicInfo['token']);
        dump($url);
        //add添加, delete删除, set覆盖, get获取。当参数是get时不需要填四个域名字段。
        $param['action']='get';
        $param['requestdomain']=array(REMOTE_BASE_URL,REMOTE_BASE_URL);
        $req = str_replace('https','wss',REMOTE_BASE_URL);
        $param['wsrequestdomain']=array($req,$req);
        $param['uploaddomain']=array(REMOTE_BASE_URL,REMOTE_BASE_URL);
        $param['downloaddomain']=array(REMOTE_BASE_URL,REMOTE_BASE_URL);
        dump(json_encode($param));
        $res = post_data($url, $param);
        dump($res);
    }
    /*
     * 预览
     */
    function preview_code(){
        $publicid = I('id', 0, 'intval');
        $this->assign('id',$publicid);
        $publicInfo = D('Common/Apps')->getInfo($publicid);
        $audArr = wp_explode($publicInfo['mini_auditid'], '_');
        if (!empty($audArr[1])){
            $param ['scene'] = uniqid ( 'Preview_' );
            $result =getwxacode ( 'B', $param, $publicInfo['token'] );
            $fileUrl=$result['url'];
            $test=0;
        }else {
            //未发布，显示体验二维码
            $fileDir = SITE_PATH .'/Uploads/WxaCode/' . $publicInfo['token'];
            if(!is_dir ( $fileDir )){
                mkdirs ( $fileDir );
            }
            $filePath = $fileDir . '/' . md5 ( $publicInfo['token'] ) . '.jpg';
            $fileUrl = SITE_URL . '/Uploads/WxaCode/' . $publicInfo['token'].'/'.md5 ( $publicInfo['token'] ) . '.jpg';
            if (!file_exists ( $filePath )) {
                $url='https://api.weixin.qq.com/wxa/get_qrcode?access_token='.get_access_token($publicInfo['token']);
                $img = get_data($url);
                $res = file_put_contents ( $filePath, $img );
            }
            $test=1;
        }
       
        $this->assign('qr_code',$fileUrl);
        $this->assign('is_test',$test);
        $this->display();
    }
    /*
     * 绑定体验者
     */
    function bind_tester(){
        $wechatid=I('wechat','');
        if (empty($wechatid)){
            $this->error('体验者微信号不能为空','',true);
        }
        $publicid = I('id', 0, 'intval');
        $publicInfo = D('Common/Apps')->getInfo($publicid);
        $url='https://api.weixin.qq.com/wxa/bind_tester?access_token='.get_access_token($publicInfo['token']);
        $param['wechatid']=$wechatid;
        $res = post_data($url,$param);
        if($res['errcode']==0 &&  strtolower($res['errmsg']) == 'ok'){
            $this->success('绑定成功','',true);
        }else{
            $err['-1']='系统繁忙';
            $err['85001']='微信号不存在或微信号设置为不可搜索';
            $err['85002']='小程序绑定的体验者数量达到上限';
            $err['85003']='微信号绑定的小程序体验者达到上限';
            $err['85004']='微信号已经绑定';
            $this->error('绑定失败：' . $err[$res['errcode']],'',true);
        }
    }
    /*
     * 代码上传，审核过程
     * 为授权的小程序帐号上传小程序代码,并提交审核
     */
    function audit_page(){
        $publicid = I('id', 0, 'intval');
        $this->assign('id',$publicid);
        $publicInfo = D('Common/Apps')->getInfo($publicid);
        $jump_url='';
        if (empty($publicInfo['mini_auditid'])){
            $hasAudit=0;
            $jump_url=U('Home/Apps/audit_commit');
        }else {
            $audArr = wp_explode($publicInfo['mini_auditid'], '_');
            $publicInfo['mini_auditid']=$audArr[0];
            $faBu=empty($audArr[1])?0:1;
            $hasAudit=1;
            if ($faBu){
                $hasAudit=2;
            }else {
                //查询审核结果
                $key='get_auditstatus_'.$publicInfo['mini_auditid'];
                $result = S($key);
                if (empty($result)){
                    $rUrl='https://api.weixin.qq.com/wxa/get_auditstatus?access_token='.get_access_token($publicInfo['token']);
                    $param['auditid']=$publicInfo['mini_auditid'];
                    $result = post_data($rUrl, $param);
                    S($key,$result,1800);
                }
                //审核状态，其中0为审核成功，1为审核失败，2为审核中
                if ($result['status']==0){
                    $msg='您的小程序审核成功啦，赶快点下面按钮发布上线吧！！！';
                    //发布
                    $jump_url=U('Home/Apps/mini_release');
                }elseif ($result['status']==1) {
                    $jump_url=U('Home/Apps/audit_commit');
                    $msg='审核失败  '.$result['reason'];
                    //将审核编号设置为空，重新审核
//                     $save['mini_auditid'] = '';
//                     D('Common/Apps')->updateInfo($publicid, $save);
                }else{
                    $msg='小程序正在审核中，请耐心等待。。。。。。';
                }
                $this->assign('show_msg',$msg);
                $this->assign('audit_status',$result['status']);
            }
        }
        
//         $hasAudit=0;
//         $jump_url=U('Home/Apps/audit_commit');
        
        $this->assign('has_audit',$hasAudit);
        $this->assign('jump_url',$jump_url);
        $this->display();
    }

    /*
     * 发布审核通过的小程序
     */
    function mini_release()
    {
        $publicid = I('id', 0, 'intval');
        $publicInfo = D('Common/Apps')->getInfo($publicid);
        $url = 'https://api.weixin.qq.com/wxa/release?access_token=' . get_access_token($publicInfo['token']);
        $res = post_data($url, '{}');
        addWeixinLog($res, 'mini_release_' . $publicid);
        if ($res['errcode'] == 0 && strtolower($res['errmsg']) == 'ok') {
            $save['mini_auditid'] = $publicInfo['mini_auditid'] . '_1';
            D('Common/Apps')->updateInfo($publicid, $save);
            $this->success('发布成功', U('audit_page', array(
                'id' => $publicid
            )));
        } else {
            $err['-1']='系统繁忙';
            $err['85019']='没有审核版本';
            $err['85020']='审核状态未满足发布';
            $this->error('发布失败：' . $err[$res['errcode']]);
        }
    }

    /*
     * 代码上传，审核过程
     * 为授权的小程序帐号上传小程序代码,并提交审核
     */
    function audit_commit()
    {
        $publicid = I('id', 0, 'intval');
        $publicInfo = D('Common/Apps')->getInfo($publicid);
        $access_token = get_access_token($publicInfo['token']);
        /*
         * //模板库模板
         * $apiUrl = REMOTE_BASE_URL . '/index.php?s=/w0/Api/Center/getTemplateList.html';
         * $resdata = get_data($apiUrl);
         * $resdata = json_decode($resdata, true);
         * dump($resdata);
         */
        // 提交代码
        $extArr['extEnable'] = true;
        $extArr['extAppid'] = $publicInfo['appid'];
        $extArr['ext']['appurl'] = REMOTE_BASE_URL . '/news/index.php?s=/w' . $publicInfo['id'] . '/';
        $extArr['window']['navigationBarTitleText'] = $publicInfo['public_name'];
        $strExt = json_encode($extArr);
        $cUrl = 'https://api.weixin.qq.com/wxa/commit?access_token=' . $access_token;
        $cParam['template_id'] = 1;
        $cParam['ext_json'] = $strExt;
        $cParam['user_version'] = 'NEWS1.0';
        $cParam['user_desc'] = '新闻小程序';
        $strParam = json_encode($cParam);
        $cRes = post_data($cUrl, $strParam);
        
        $log['commit_code'] = $cRes;
        // addWeixinLog($cRes,'commit_code_'.$publicid);
        
        // 获取授权小程序帐号的可选类目
        $cateUrl = 'https://api.weixin.qq.com/wxa/get_category?access_token=' . $access_token;
        $cates = get_data($cateUrl);
        $cates = json_decode($cates, true);
        if (empty($cates['category_list'])){
            $this->error('您的小程序还没有类别，请到小程序后台添加类别');
        }
        // addWeixinLog($cates,'categote_'.$publicid);
        $log['get_category'] = $cates;
        
        // 获取小程序的第三方提交代码的页面配置
        $pageUrl = 'https://api.weixin.qq.com/wxa/get_page?access_token=' . $access_token;
        $pages = get_data($pageUrl);
        $pages = json_decode($pages, true);
        
        $log['get_page'] = $pages;
        
        // 审核
        foreach ($pages['page_list'] as $k => $v) {
            if ($k >= 5) {
                break;
            }
            $item['address'] = $v;
            // 小程序的标签，多个标签用空格分隔，标签不能多于10个，标签长度不超过20
            $item['tag'] = '新闻 资讯 文娱';
            if (!isset($cates['category_list'][$k])){
                $cc=0;
            }else {
                $cc=$k;
            }
            if (isset($cates['category_list'][$cc]['first_class'])) {
                $item['first_class'] = $cates['category_list'][$cc]['first_class'];
                $item['first_id'] = $cates['category_list'][$cc]['first_id'];
            }
            if (isset($cates['category_list'][$cc]['second_class'])) {
                $item['second_class'] = $cates['category_list'][$cc]['second_class'];
                $item['second_id'] = $cates['category_list'][$cc]['second_id'];
            }
            if (isset($cates['category_list'][$cc]['third_class'])) {
                $item['third_class'] = $cates['category_list'][$cc]['third_class'];
                $item['third_id'] = $cates['category_list'][$cc]['third_id'];
            }
            $item['title'] = '';
            $isIndex = strpos($v, "index");
            if ($isIndex) {
                $item['title'] = '首页';
            }
            $isDetail = strpos($v, "detail");
            if ($isDetail) {
                $item['title'] = '详情页';
            }
            $items[] = $item;
            unset($item);
        }
        $sParam['item_list'] = $items;
        
        $log['item_list'] = $sParam;
        $sUrl = 'https://api.weixin.qq.com/wxa/submit_audit?access_token=' . $access_token;
        $res = post_data($sUrl, $sParam);
        $log['submit_audit'] = $res;
        addWeixinLog($log, 'audit_commit_' . $publicid);
        if ($res['errcode'] == 0 && $res['auditid']) {
            $save['mini_auditid'] = $res['auditid'];
            D('Common/Apps')->updateInfo($publicid, $save);
            $this->success('提交审核成功', U('audit_page', array(
                'id' => $publicid
            )));
        } else {
            $err['-1'] = '系统繁忙';
            $err['86000'] = '不是由第三方代小程序进行调用';
            $err['86001'] = '不存在第三方的已经提交的代码';
            $err['85006'] = '标签格式错误';
            $err['85007'] = '页面路径错误';
            $err['85008'] = '类目填写错误';
            $err['85009'] = '已经有正在审核的版本';
            $err['85010'] = 'item_list有项目为空';
            $err['85011'] = '标题填写错误';
            $err['85023'] = '审核列表填写的项目数不在1-5以内';
            $err['86002'] = '小程序还未设置昵称、头像、简介。请先设置完后再重新提交。';
            $msg = isset($err[$res['errcode']]) ? $err[$res['errcode']] : '审核失败';
            $this->error($msg);
        }
    }

    /*
     * 修改小程序线上代码的可见状态
     */
    function see_code()
    {
        $publicid = I('id', 0, 'intval');
        $publicInfo = D('Common/Apps')->getInfo($publicid);
        $access_token = get_access_token($publicInfo['token']);
        $status = I('status', 'open');
        $url = 'https://api.weixin.qq.com/wxa/change_visitstatus?access_token=' . $access_token;
        $param['action'] = $status;
        $res = post_data($url, $param);
        dump($res);
        $err['-1'] = '系统繁忙';
        $err['85021']='状态不可变';
        $err['85022']='action非法';
        dump($err[$res['errcode']]);
    }
}