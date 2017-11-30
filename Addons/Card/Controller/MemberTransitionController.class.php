<?php

namespace Addons\Card\Controller;

use Addons\Card\Controller\BaseController;

class MemberTransitionController extends BaseController {
	var $model;
	function _initialize() {
		$this->model = $this->getModel ( 'card_member' );
		parent::_initialize ();
	}
	// 通用插件的列表模型
	public function lists() {
		$this->display ();
	}
	
	// 会员充值列表
	function recharge_lists() {
		$this->_navShow ( '充值查询', 'recharge_lists' );
		
		$this->assign ( 'add_button', false );
		$this->assign ( 'del_button', false );
		$this->assign ( 'check_all', false );
		$this->assign ( 'search_url', addons_url ( "Card://MemberTransition/recharge_lists", array (
				'mdm' => $_GET ['mdm'] 
		) ) );
		// $map ['manager_id'] = $this->mid;
		$map ['token'] = get_token ();
		$branch = M ( 'coupon_shop' )->where ( $map )->getFields ( 'id,name' );
		$this->assign ( 'shop', $branch );
		
		$key = I ( 'operator' );
		$this->assign ( 'search', $key );
		if ($key) {
			$where = "username LIKE '%{$key}%' OR phone LIKE '%{$key}%'";
			$map1 ['token'] = get_token ();
			$u_card_id = ( array ) D ( 'card_member' )->where ( $map1 )->where ( $where )->getFields ( 'id' );
			if (! empty ( $u_card_id )) {
				$u_card_id = implode ( ',', $u_card_id );
				$map ['member_id'] = array (
						'exp',
						' in (' . $u_card_id . ') ' 
				);
			} else {
				$map ['operator'] = array (
						'like',
						'%' . $key . '%' 
				);
			}
			unset ( $_REQUEST ['operator'] ); // 去掉对核心common_condition的影响
		}
		
		$shop_id = I ( 'pay_shop', - 1, 'intval' );
		$this->assign ( 'pay_shop', $shop_id );
		if ($shop_id != - 1) {
			$map ['branch_id'] = $shop_id;
		}
		$isRecharge = I ( 'is_recharge' );
		if ($isRecharge) {
			$minVal = I ( 'min_value', 0, 'intval' );
			$maxVal = I ( 'max_value', 0, 'intval' );
			if ($minVal && $maxVal) {
				$minVal < $maxVal && $map ['recharge'] = array (
						'between',
						array (
								$minVal,
								$maxVal 
						) 
				);
				$minVal > $maxVal && $map ['recharge'] = array (
						'between',
						array (
								$maxVal,
								$minVal 
						) 
				);
				$minVal == $maxVal && $map ['recharge'] = $minVal;
			} else if (! empty ( $minVal )) {
				$map ['recharge'] = array (
						'egt',
						$minVal 
				);
			} else if (! empty ( $maxVal ) || $maxVal == 0) {
				$map ['recharge'] = array (
						'elt',
						$maxVal 
				);
			}
		}
		
		$isCTime = I ( 'is_ctime' );
		if ($isCTime) {
			$startVal = I ( 'start_ctime', 0, 'strtotime' );
			$endVal = I ( 'end_ctime', 0, 'strtotime' );
			$endVal = $endVal == 0 ? 0 : $endVal + 86400 - 1;
			if ($startVal && $endVal) {
				$startVal < $endVal && $map ['cTime'] = array (
						'between',
						array (
								$startVal,
								$endVal 
						) 
				);
				$startVal > $endVal && $map ['cTime'] = array (
						'between',
						array (
								$startVal,
								$endVal 
						) 
				);
				$startVal == $endVal && $map ['cTime'] = array (
						'egt',
						$startVal 
				);
			} else if (! empty ( $startVal )) {
				$map ['cTime'] = array (
						'egt',
						$startVal 
				);
			} else if (! empty ( $endVal )) {
				$map ['cTime'] = array (
						'elt',
						$endVal 
				);
			}
		}
		$map ['token'] = get_token ();
		session ( 'common_condition', $map );
		
		$model = $this->getModel ( 'recharge_log' );
		$list_data = $this->_get_model_list ( $model );
		
		$cardMemberDao = M ( 'card_member' );
		foreach ( $list_data ['list_data'] as &$vo ) {
			$cardMember = $cardMemberDao->find ( $vo ['member_id'] );
			$vo ['member_id'] = $cardMember ['number'];
			$vo ['truename'] = $cardMember ['username'];
			$vo ['phone'] = $cardMember ['phone'];
			$vo ['branch_id'] = $vo ['branch_id'] == 0 ? '商店总部' : $branch [$vo ['branch_id']];
		}
		
		// dump($uInfo);
		// dump($list_data);
		$this->assign ( $list_data );
		$this->display ();
	}
	// 会员消费列表
	function buy_lists() {
		$this->_navShow ( '消费查询', 'buy_lists' );
		
		$this->assign ( 'add_button', false );
		$this->assign ( 'del_button', false );
		$this->assign ( 'check_all', false );
		$this->assign ( 'search_url', addons_url ( "Card://MemberTransition/buy_lists", array (
				'mdm' => $_GET ['mdm'] 
		) ) );
		// $map ['manager_id'] = $this->mid;
		$map ['token'] = get_token ();
		$branch = M ( 'coupon_shop' )->where ( $map )->getFields ( 'id,name' );
		$this->assign ( 'shop', $branch );
		
		$search = I ( 'member' );
		$this->assign ( 'search', $search );
		if ($search) {
			$where = "username LIKE '%{$search}%' OR phone LIKE '%{$search}%'";
			$map1 ['token'] = $map2 ['token'] = get_token ();
			$u_card_id = D ( 'card_member' )->where ( $map1 )->where ( $where )->getFields ( 'id' );
			if (! empty ( $u_card_id )) {
				$u_card_id = implode ( ',', $u_card_id );
				$map ['member_id'] = array (
						'exp',
						' in (' . $u_card_id . ') ' 
				);
			} else {
				$map ['id'] = 0;
			}
			unset ( $_REQUEST ['member'] );
		}
		$shop_id = I ( 'pay_shop', - 1, 'intval' );
		$this->assign ( 'pay_shop', $shop_id );
		if ($shop_id != - 1) {
			$map ['branch_id'] = $shop_id;
		}
		$isRecharge = I ( 'is_recharge' );
		if ($isRecharge) {
			$minVal = I ( 'min_value', 0, 'intval' );
			$maxVal = I ( 'max_value', 0, 'intval' );
			if ($minVal && $maxVal) {
				$minVal < $maxVal && $map ['pay'] = array (
						'between',
						array (
								$minVal,
								$maxVal 
						) 
				);
				$minVal > $maxVal && $map ['pay'] = array (
						'between',
						array (
								$maxVal,
								$minVal 
						) 
				);
				$minVal == $maxVal && $map ['pay'] = $minVal;
			} else if (! empty ( $minVal )) {
				$map ['pay'] = array (
						'egt',
						$minVal 
				);
			} else if (! empty ( $maxVal ) || $maxVal == 0) {
				$map ['pay'] = array (
						'elt',
						$maxVal 
				);
			}
		}
		
		$isCTime = I ( 'is_ctime' );
		if ($isCTime) {
			$startVal = I ( 'start_ctime', 0, 'strtotime' );
			$endVal = I ( 'end_ctime', 0, 'strtotime' );
			$endVal = $endVal == 0 ? 0 : $endVal + 86400 - 1;
			if ($startVal && $endVal) {
				$startVal < $endVal && $map ['cTime'] = array (
						'between',
						array (
								$startVal,
								$endVal 
						) 
				);
				$startVal > $endVal && $map ['cTime'] = array (
						'between',
						array (
								$startVal,
								$endVal 
						) 
				);
				$startVal == $endVal && $map ['cTime'] = array (
						'egt',
						$startVal 
				);
			} else if (! empty ( $startVal )) {
				$map ['cTime'] = array (
						'egt',
						$startVal 
				);
			} else if (! empty ( $endVal )) {
				$map ['cTime'] = array (
						'elt',
						$endVal 
				);
			}
		}
		
		$map ['token'] = get_token ();
		$map ['manager_id'] = [ 
				'gt',
				0 
		];
		session ( 'common_condition', $map );
		
		$model = $this->getModel ( 'buy_log' );
		$list_data = $this->_get_model_list ( $model );
		$is_export = I ( 'is_export', 0, 'intval' );
		$dataArr = $ht = [ ];
		if ($is_export) {
			foreach ( $list_data ['list_grids'] as $grid ) {
				$ht [] = $grid ['title'];
			}
			$dataArr [0] = $ht;
			
			$list_data ['list_data'] = M ( 'buy_log' )->where ( $map )->order ( 'id desc' )->limit ( 10000 )->select (); // 最多能导出1万条
		}
		
		$pay_type = [ 
				1 => '会员卡余额消费',
				2 => '现金或POS机消费' 
		];
		if (! empty ( $list_data ['list_data'] )) {
			$map ['manager_id'] = $this->mid;
			$map ['token'] = get_token ();
			$branch = M ( 'coupon_shop' )->where ( $map )->getFields ( 'id,name' );
			
			$cardMemberDao = M ( 'card_member' );
			
			foreach ( $list_data ['list_data'] as $vo ) {
				$snArr [$vo ['sn_id']] = $vo ['sn_id'];
			}
			$map2 ['id'] = array (
					'in',
					$snArr 
			);
			$prizeData = M ( 'sn_code' )->where ( $map2 )->getFields ( 'id,prize_title' );
			
			foreach ( $list_data ['list_data'] as &$vo ) {
				$cardMember = $cardMemberDao->find ( $vo ['member_id'] );
				
				$data [0] = $vo ['member_id'] = $cardMember ['username'];
				$data [1] = $vo ['phone'] = $cardMember ['phone'];
				$data [2] = $is_export ? time_format ( $vo ['cTime'] ) : $vo ['cTime'];
				$data [3] = $vo ['branch_id'] = $vo ['branch_id'] == 0 ? '商店总部' : $branch [$vo ['branch_id']];
				$data [4] = $vo ['pay'];
				$data [5] = $vo ['sn_id'] = floatval ( $prizeData [$vo ['sn_id']] );
				$data [6] = $is_export ? $pay_type [$vo ['pay_type']] : $vo ['pay_type'];
				$data [7] = $vo ['manager_id'] = get_nickname ( $vo ['manager_id'] );
				
				$dataArr [] = $data;
			}
		}
		
		if ($is_export) {
			outExcel ( $dataArr, 'buy_list_' . date ( 'YmdHis' ) );
			exit ();
		}
		
		$get_param = $this->get_param;
		$get_param ['is_export'] = 1;
		$this->assign ( 'export_url', U ( 'buy_lists', $get_param ) );
		
		$this->assign ( $list_data );
		$this->display ();
	}
	
	// 积分查询
	function score_lists() {
		$this->_navShow ( '积分查询', 'score_lists' );
		
		$this->assign ( 'add_button', false );
		$this->assign ( 'del_button', false );
		// $this->assign ( 'search_button', false );
		$this->assign ( 'check_all', false );
		$this->assign ( 'search_url', addons_url ( "Card://MemberTransition/score_lists", array (
				'mdm' => $_GET ['mdm'] 
		) ) );
		$this->assign ( 'search_key', 'username' );
		$this->assign ( 'placeholder', '请输入用户名或手机号' );
		
		$list_data ['list_grids'] = [ 
				'credit_name' => [ 
						'title' => '交易名称',
						'name' => 'credit_name' 
				],
				'username' => [ 
						'title' => '用户名',
						'name' => 'username' 
				],
				'phone' => [ 
						'title' => '手机号码',
						'name' => 'phone' 
				],
				'cTime' => [ 
						'title' => '交易时间',
						'name' => 'cTime' 
				],
				'score' => [ 
						'title' => '积分',
						'name' => 'score' 
				],
				'operator' => [ 
						'title' => '操作员',
						'name' => 'operator' 
				] 
		];
		// 获取交易方式信息
		$creditTitle = M ( 'credit_data' )->group ( 'credit_name' )->getFields ( 'credit_name,credit_title' );
		$this->assign ( 'credit_title', $creditTitle );
		
		// 交易方式查询
		$creditType = I ( 'credit_type' );
		if ($creditType) {
			foreach ( $creditTitle as $key => $cr ) {
				if ($creditType == $key) {
					$map ['credit_name'] = $creditType;
					if ($cr ['name'] == 'addAuto') {
						$map ['score'] = array (
								'egt',
								0 
						);
					} else if ($cr ['name'] == 'delAuto') {
						$map ['score'] = array (
								'elt',
								0 
						);
					}
				}
			}
		}
		// 时间查询
		$isCTime = I ( 'is_ctime' );
		if ($isCTime) {
			$startVal = I ( 'start_ctime', 0, 'strtotime' );
			$endVal = I ( 'end_ctime', 0, 'strtotime' );
			$endVal = $endVal == 0 ? 0 : $endVal + 86400 - 1;
			if ($startVal && $endVal) {
				$startVal < $endVal && $map ['cTime'] = array (
						'between',
						array (
								$startVal,
								$endVal 
						) 
				);
				$startVal > $endVal && $map ['cTime'] = array (
						'between',
						array (
								$startVal,
								$endVal 
						) 
				);
				$startVal == $endVal && $map ['cTime'] = array (
						'egt',
						$startVal 
				);
			} else if (! empty ( $startVal )) {
				$map ['cTime'] = array (
						'egt',
						$startVal 
				);
			} else if (! empty ( $endVal )) {
				$map ['cTime'] = array (
						'elt',
						$endVal 
				);
			}
		}
		// 搜索查询
		$search = I ( 'username' );
		$this->assign ( 'search', $search );
		if (! empty ( $search )) {
			$nickname_follow_ids = D ( 'Common/User' )->searchUser ( $search );
			$map ['uid'] = array (
					'exp',
					' in (' . $nickname_follow_ids . ') ' 
			);
		}
		
		$map ['token'] = get_token ();
		$data = M ( 'credit_data' )->where ( $map )->order ( 'id desc' )->selectPage ();
		$list_data ['list_data'] = $data ['list_data'];
		
		foreach ( $list_data ['list_data'] as &$vo ) {
			$vo ['cTime'] = time_format ( $vo ['cTime'] );
			if ($vo ['credit_name'] == 'card_member_update_score') {
				if ($vo ['score'] > 0) {
					$vo ['credit_name'] = '手动增加';
				} else {
					$vo ['credit_name'] = '手动扣除';
				}
				if ($vo ['uid'] && $vo ['admin_uid']) {
					$updateData = M ( 'update_score_log' )->find ( $vo ['admin_uid'] );
					$vo ['operator'] = $updateData ['operator'];
				}
			} else {
				$vo ['credit_name'] = $creditTitle [$vo ['credit_name']];
				if ($vo ['uid']) {
					$userInfo = get_userinfo ( $vo ['uid'] );
					$vo ['username'] = $userInfo ['nickname'];
					$vo ['phone'] = $userInfo ['mobile'];
				}
			}
			// 判断是否为会员
			if ($vo ['uid']) {
				$userInfo = get_userinfo ( $vo ['uid'] );
				$vo ['username'] = $userInfo ['truename'] ? $userInfo ['truename'] : $userInfo ['nickname'];
				$vo ['phone'] = $userInfo ['mobile'];
			}
			if (empty ( $vo ['credit_name'] )) {
				$vo ['credit_name'] = $vo ['credit_title'];
			}
		}
		
		$this->assign ( $list_data );
		$this->display ();
	}
	private function _navShow($title, $act) {
		$param ['mdm'] = $_GET ['mdm'];
		$res ['title'] = '会员交易';
		$res ['url'] = addons_url ( 'Card://MemberTransition/lists', $param );
		$res ['class'] = '';
		$nav [] = $res;
		
		$res ['title'] = $title;
		$res ['url'] = addons_url ( 'Card://MemberTransition/' . $act, $param );
		$res ['class'] = 'current';
		$nav [] = $res;
		$this->assign ( 'nav', $nav );
	}
	function sncode_lists() {
		$this->_navShow ( '优惠券核销查询', 'sncode_lists' );
		
		$this->assign ( 'add_button', false );
		$this->assign ( 'del_button', false );
		$this->assign ( 'check_all', false );
		$this->assign ( 'search_url', addons_url ( "Card://MemberTransition/sncode_lists", array (
				'mdm' => $_GET ['mdm'] 
		) ) );
		$this->assign ( 'search_key', 'username' );
		$this->assign ( 'placeholder', '请输入用户名或手机号' );
		
		$list_data ['list_grids'] = [ 
				'username' => [ 
						'title' => '用户名',
						'name' => 'username' 
				],
				'phone' => [ 
						'title' => '手机号码',
						'name' => 'phone' 
				],
				'credit_name' => [ 
						'title' => '优惠券',
						'name' => 'credit_name' 
				],
				'use_time' => [ 
						'title' => '使用时间',
						'name' => 'cTime' 
				],
				'operator' => [ 
						'title' => '核销员',
						'name' => 'operator' 
				] 
		];
		// 获取交易方式信息
		$coupon_map ['token'] = get_token ();
		$creditTitle = M ( 'coupon' )->where ( $coupon_map )->getFields ( 'id,title' );
		$this->assign ( 'credit_title', $creditTitle );
		
		// 交易方式查询
		$coupon_id = I ( 'coupon_id' );
		if ($coupon_id > 0) {
			$map ['target_id'] = $coupon_id;
		}
		// 时间查询
		$isCTime = I ( 'is_ctime' );
		if ($isCTime) {
			$startVal = I ( 'start_ctime', 0, 'strtotime' );
			$endVal = I ( 'end_ctime', 0, 'strtotime' );
			$endVal = $endVal == 0 ? 0 : $endVal + 86400 - 1;
			if ($startVal && $endVal) {
				$startVal < $endVal && $map ['use_time'] = array (
						'between',
						array (
								$startVal,
								$endVal 
						) 
				);
				$startVal > $endVal && $map ['use_time'] = array (
						'between',
						array (
								$startVal,
								$endVal 
						) 
				);
				$startVal == $endVal && $map ['use_time'] = array (
						'egt',
						$startVal 
				);
			} else if (! empty ( $startVal )) {
				$map ['use_time'] = array (
						'egt',
						$startVal 
				);
			} else if (! empty ( $endVal )) {
				$map ['use_time'] = array (
						'elt',
						$endVal 
				);
			}
		}
		// 搜索查询
		$search = I ( 'username' );
		$this->assign ( 'search', $search );
		if (! empty ( $search )) {
			$nickname_follow_ids = D ( 'Common/User' )->searchUser ( $search );
			$map ['uid'] = array (
					'exp',
					' in (' . $nickname_follow_ids . ') ' 
			);
		}
		
		$map ['token'] = get_token ();
		$map ['is_use'] = 1;
		
		$is_export = I ( 'is_export', 0, 'intval' );
		$dataArr = [ ];
		if ($is_export) {
			$ht = [ 
					0 => '用户名',
					1 => '手机号码',
					2 => '优惠券',
					3 => '使用时间',
					4 => '核销员' 
			];
			$dataArr [0] = $ht;
			
			$data ['list_data'] = M ( 'sn_code' )->where ( $map )->order ( 'id desc' )->limit ( 10000 )->select (); // 最多能导出1万条
		} else {
			$data = M ( 'sn_code' )->where ( $map )->order ( 'id desc' )->selectPage ();
		}
		
		$list_data ['list_data'] = $data ['list_data'];
		
		foreach ( $list_data ['list_data'] as &$vo ) {
			$userInfo = get_userinfo ( $vo ['uid'] );
			$data = [ ];
			$data [0] = $vo ['username'] = $userInfo ['truename'] ? $userInfo ['truename'] : $userInfo ['nickname'];
			$data [1] = $vo ['phone'] = $userInfo ['mobile'];
			$data [2] = $vo ['credit_name'] = $creditTitle [$vo ['target_id']];
			$data [3] = $vo ['use_time'] = time_format ( $vo ['use_time'] );
			$data [4] = $vo ['operator'] = get_nickname ( $vo ['admin_uid'] );
			
			$dataArr [] = $data;
		}
		
		if ($is_export) {
			outExcel ( $dataArr, 'coupon_use_list_' . date ( 'YmdHis' ) );
			exit ();
		}
		
		$get_param = $this->get_param;
		$get_param ['is_export'] = 1;
		$this->assign ( 'export_url', U ( 'sncode_lists', $get_param ) );
		$this->assign ( $list_data );
		$this->display ();
	}
	// 消费统计
	function buy_tongji() {
		$this->_navShow ( '消费统计', 'buy_tongji' );
		
		$year = I ( 'year' );
		$month = I ( 'month' );
		$is_ajax = I ( 'is_ajax' );
		$map ['token'] = get_token ();
		if ($year && $month && $is_ajax) {
			$start_date = $year . '-' . $month;
			$end_month = $month + 1;
			$end_date = $year . '-' . $end_month;
			$start_date = strtotime ( $start_date );
			$end_date = strtotime ( $end_date );
			$map ['cTime'] = array (
					'between',
					array (
							$start_date,
							$end_date 
					) 
			);
		} else {
			$now_month = time_format ( NOW_TIME, 'Y-m' );
			$map ['cTime'] = array (
					'egt',
					strtotime ( $now_month ) 
			);
		}
		// 本月总消费金额
		$map ['manager_id'] = [ 
				'gt',
				0 
		];
		$totalPay = M ( 'buy_log' )->where ( $map )->field ( "sum(pay) totalPay" )->select ();
		$totalPay = round ( floatval ( $totalPay [0] ['totalPay'] ), 2 );
		$this->assign ( 'total_pay', $totalPay );
		
		$data = M ( 'buy_log' )->where ( $map )->field ( "sum(pay) totalPay,from_unixtime(cTime,'%m-%d') allday" )->group ( "allday" )->select ();
		foreach ( $data as $v ) {
			$allDay [] = $v ['allday'];
			$allPay [] = round ( floatval ( $v ['totalPay'] ), 2 );
		}
		$highcharts ['xAxis'] = $allDay;
		$highcharts ['series'] = $allPay;
		if ($is_ajax) {
			$highcharts ['total_pay'] = $totalPay;
			$this->ajaxReturn ( $highcharts );
		} else {
			$highcharts = json_encode ( $highcharts );
			$this->assign ( 'highcharts', $highcharts );
			$this->display ();
		}
	}
	function getDays($year, $month) {
		if ($month == 2) {
			if ($year % 400 == 0 || $year % 4 == 0 && $year % 100 != 0) {
				$day = 28;
			} else {
				$day = 29;
			}
		} else if ($month == 4 || $month == 6 || $month == 9 || $month == 11) {
			$day = 30;
		} else {
			$day = 31;
		}
		return $day;
	}
	// 积分统计
	// 消费统计
	function score_tongji() {
		$this->_navShow ( '当月用户积分统计', 'score_tongji' );
		
		$year = I ( 'year' );
		$month = I ( 'month' );
		$is_ajax = I ( 'is_ajax' );
		$map1 ['token'] = $map2 ['token'] = get_token ();
		
		if ($year && $month && $is_ajax) {
			$month = intval ( $month );
			$day = $this->getDays ( $year, $month );
			$start_date = $year . '-' . $month;
			$end_month = $month + 1;
			$end_date = $year . '-' . $end_month;
			$start_date = strtotime ( $start_date );
			$end_date = strtotime ( $end_date );
			$map1 ['cTime'] = $map2 ['cTime'] = array (
					'between',
					array (
							$start_date,
							$end_date 
					) 
			);
		} else {
			$year = time_format ( NOW_TIME, 'Y' );
			$month = intval ( time_format ( NOW_TIME, 'm' ) );
			$day = $this->getDays ( $year, $month );
			$now_month = time_format ( NOW_TIME, 'Y-m' );
			$map1 ['cTime'] = $map2 ['cTime'] = array (
					'egt',
					strtotime ( $now_month ) 
			);
		}
		// 本月总获取积分
		$map1 ['score'] = array (
				'gt',
				0 
		);
		$getTotal = M ( 'credit_data' )->where ( $map1 )->field ( "sum(score) totalScore" )->select ();
		// 本月使用积分
		$map2 ['score'] = array (
				'lt',
				0 
		);
		$useTotal = M ( 'credit_data' )->where ( $map2 )->field ( "sum(score) totalScore" )->select ();
		
		$getTotal = round ( floatval ( $getTotal [0] ['totalScore'] ), 2 );
		$useTotal = 0 - round ( floatval ( $useTotal [0] ['totalScore'] ), 2 );
		
		$this->assign ( 'get_score', $getTotal );
		$this->assign ( 'use_score', $useTotal );
		
		$get_data = M ( 'credit_data' )->where ( $map1 )->field ( "sum(score) totalScore,from_unixtime(cTime,'%m-%d') allday" )->group ( "allday" )->select ();
		$use_data = M ( 'credit_data' )->where ( $map2 )->field ( "sum(score) totalScore,from_unixtime(cTime,'%m-%d') allday" )->group ( "allday" )->select ();
		$month = str_pad ( $month, 2, "0", STR_PAD_LEFT );
		for($i = 1; $i <= $day; $i ++) {
			$i = str_pad ( $i, 2, "0", STR_PAD_LEFT );
			$dateArr [$month . '-' . $i] = $month . '-' . $i;
			$the_date [] = $month . '-' . $i;
		}
		// dump($dateArr);
		// dump($get_data);
		foreach ( $get_data as $v ) {
			// $getAllDay[]=$v['allday'];
			if ($dateArr [$v ['allday']]) {
				$getAllScore [] = round ( floatval ( $v ['totalScore'] ), 2 );
			}
		}
		foreach ( $use_data as $v ) {
			// $useAllDay[]=$v['allday'];
			if ($dateArr [$v ['allday']]) {
				$useAllScore [] = 0 - round ( floatval ( $v ['totalScore'] ), 2 );
			}
		}
		$highcharts ['xAxis'] = $the_date;
		$highcharts ['series1'] = $getAllScore;
		$highcharts ['series2'] = $useAllScore;
		if ($is_ajax) {
			$highcharts ['get_score'] = $getTotal;
			$highcharts ['use_score'] = $useTotal;
			$this->ajaxReturn ( $highcharts );
		} else {
			$highcharts = json_encode ( $highcharts );
			$this->assign ( 'highcharts', $highcharts );
			$this->display ();
		}
	}
	function duihuang() {
		if (IS_POST) {
			if (! $_POST ['sn_id']) {
				$this->error ( '400062:请输入sn码' );
			}
			$map ['sn'] = $_POST ['sn_id'];
			$info = D ( 'Common/SnCode' )->where ( $map )->find ();
			if ($info ['is_use']) {
				$this->error ( '400064:该sn码已被使用过' );
			}
			
			$res = D ( 'Common/SnCode' )->set_use ( $info ['id'] );
			if ($res) {
				$this->success ( '兑换成功' );
			} else {
				$this->error ( '400063:兑换失败' );
			}
		}
		$this->display ();
	}
}
