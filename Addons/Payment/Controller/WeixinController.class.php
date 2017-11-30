<?php

namespace Addons\Payment\Controller;

use Think\ManageBaseController;

class WeixinController extends ManageBaseController {
	public $token;
	public $wecha_id;
	public $payConfig;
	public function __construct() {
		parent::__construct ();
		
		$this->token = get_token ();
		$this->wecha_id = get_openid ();
		// 读取配置
		$pay_config_db = M ( 'payment_set' );
		$paymentSet = $pay_config_db->where ( array (
				'token' => $this->token 
		) )->find ();
		$paymentSet ['wxappid'] = trim ( $paymentSet ['wxappid'] );
		$paymentSet ['wxpaysignkey'] = trim ( $paymentSet ['wxpaysignkey'] );
		$paymentSet ['wxappsecret'] = trim ( $paymentSet ['wxappsecret'] );
		$paymentSet ['wxmchid'] = trim ( $paymentSet ['wxmchid'] );
		
		if ($paymentSet ['wx_cert_pem'] && $paymentSet ['wx_key_pem']) {
			$ids [] = $paymentSet ['wx_cert_pem'];
			$ids [] = $paymentSet ['wx_key_pem'];
			$map ['id'] = array (
					'in',
					$ids 
			);
			$fileData = M ( 'file' )->where ( $map )->select ();
			$downloadConfig = C ( DOWNLOAD_UPLOAD );
			foreach ( $fileData as $f ) {
				if ($paymentSet ['wx_cert_pem'] == $f ['id']) {
					
					$certpath = SITE_PATH . str_replace ( '/', '\\', substr ( $downloadConfig ['rootPath'], 1 ) . $f ['savepath'] . $f ['savename'] );
				} else {
					$keypath = SITE_PATH . str_replace ( '/', '\\', substr ( $downloadConfig ['rootPath'], 1 ) . $f ['savepath'] . $f ['savename'] );
				}
			}
			$paymentSet ['cert_path'] = $certpath;
			$paymentSet ['key_path'] = $keypath;
		}
		$this->payConfig = $paymentSet;
		
		session ( 'paymentinfo', $this->payConfig );
	}
	// 处理from URL字符串
	// private function doFromStr($from){
	// if($from){
	// $fromstr=str_replace('_', '/', $from);
	// }
	// return $fromstr;
	// }
	// protected function create_noncestr( $length = 16 ) {
	// $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	// $str ="";
	// for ( $i = 0; $i < $length; $i++ ) {
	// $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
	// //$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
	// }
	// return $str;
	// }
	function getPaymentOpenid() { // echo '444';
		$callback = GetCurUrl ();
		if ((defined ( 'IN_WEIXIN' ) && IN_WEIXIN) || isset ( $_GET ['is_stree'] ))
			return false;
		
		$callback = urldecode ( $callback );
		$isWeixinBrowser = isWeixinBrowser (); // echo '555';die();
		                                       // $info = get_token_appinfo ( $token );
		
		if (strpos ( $callback, '?' ) === false) {
			$callback .= '?';
		} else {
			$callback .= '&';
		}
		
		// if (! $isWeixinBrowser || $info ['type'] != 2 || empty ( $info ['appid'] )) {
		// redirect ( $callback . 'openid=-1' );
		// }
		// $map['token'] = get_token();
		
		// $info=M ( 'payment_set' )->where($map)->find();
		$param ['appid'] = $this->payConfig ['wxappid'];
		
		if (! isset ( $_GET ['getOpenId'] )) {
			$param ['redirect_uri'] = $callback . 'getOpenId=1';
			$param ['response_type'] = 'code';
			$param ['scope'] = 'snsapi_base';
			$param ['state'] = 123;
			
			$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query ( $param ) . '#wechat_redirect';
			redirect ( $url );
		} else if ($_GET ['state']) {
			$param ['secret'] = $this->payConfig ['wxappsecret'];
			$param ['code'] = I ( 'code' );
			$param ['grant_type'] = 'authorization_code';
			
			$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query ( $param );
			$content = get_data ( $url );
			$content = json_decode ( $content, true );
			return $content ['openid'];
		}
	}
	public function pay() {
		require_once ('Weixinpay/WxPayData.class.php');
		require_once ('Weixinpay/WxPayApi.class.php');
		require_once ('Weixinpay/WxPayJsApiPay.php');
		// require_once ('Weixinpay/log.php');
		$paymentId = $_GET ['paymentId'];
		$token = $_GET ['token'];
		$body = $_GET ['orderName'];
		if (strlen ( $body ) > 128) {
			$body = substr ( $body, 0, 128 );
			$body = mb_substr ( $body, 0, mb_strlen ( $body ) - 2 );
		}
		
		$orderNo = $_GET ['orderNumber'];
		if ($orderNo == "") {
			$orderNo = $_GET ['single_orderid'];
		}
		$pMap ['single_orderid'] = $orderNo;
		$pMap ['id'] = $paymentId;
		if (isset ( $pMap ['aim_id'] )) {
			$pMap ['aim_id'] = $pMap ['aim_id'];
		}
		$_GET ['price'] = M ( 'payment_order' )->where ( $pMap )->getField ( 'price' );
		if ($_GET ['price'] < 0) {
			$this->error ( '订单出现错误！' );
			exit ();
		}
		$totalFee = $_GET ['price'] * 100; // 单位为分
		                                   // $paytype=$_GET['paytype'];
		
		$tools = new \JsApiPay ();
		// $openId = $tools->GetOpenid();
		// $openId=$_GET['wecha_id'];
		// $openId=get_openid();
		// dump($openId);
		// die();
		// // dump($openId);
		// $openId='orgF0t-HyMrDJHFOl9GAkENyu6i0';
		// dump('45456');
		$openId = $this->getPaymentOpenid ();
		// dump(session('paymentinfo'));
		// dump($openId);
		// dump('1232');die;
		// 统一下单
		import ( 'Weixinpay.WxPayData' );
		$input = new \WxPayUnifiedOrder ();
		$input->SetBody ( $body );
		// $input->SetAttach("test");
		$input->SetOut_trade_no ( $orderNo );
		$input->SetTotal_fee ( $totalFee );
		// $input->SetTime_start(date("YmdHis"));
		// $input->SetTime_expire(date("YmdHis", time() + 600));
		// $input->SetGoods_tag("test");
		$input->SetNotify_url ( "Weixinpay/notify.php" );
		$input->SetTrade_type ( "JSAPI" );
		$input->SetOpenid ( $openId );
		
		$order = \WxPayApi::unifiedOrder ( $input );
		if ($order ['return_code'] == 'FAIL') {
			$this->error ( '400324:' . $order ['return_msg'] );
			exit ();
		}
		// echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
		// dump($order);
		// die;
		$jsApiParameters = $tools->GetJsApiParameters ( $order );
		// dump($jsApiParameters);
		$from = $_GET ['from'];
		$fromstr = str_replace ( '_', '/', $from );
		if (strpos ( $fromstr, '://' )) {
			$returnUrl = addons_url ( $fromstr );
		} else {
			$returnUrl = U ( $fromstr );
		}
		
		if (empty ( $returnUrl )) {
			$returnUrl = addons_url ( 'Payment://Weixin/payOK' );
		}
		header ( 'Location:' . SITE_URL . '/WxpayAPI/unifiedorder.php?jsApiParameters=' . $jsApiParameters . '&returnurl=' . $returnUrl . '&totalfee=' . $_GET ['price'] . '&paymentId=' . $paymentId );
		
		// echo $jsApiParameters;
		// die;
		// session('jsaparams',$jsApiParameters);
		// $_COOKIE['jsaparams']=$jsApiParameters;
		// $from=$_GET['from'];
		// if($from!='shop'){
		// $from=$this->doFromStr($_GET['from']);
		// }
		// //$returnUrl = '/index.php?g=Wap&m=' . $from . '&a=payReturn&token=' . $_GET ['token'] . '&wecha_id=' . $_GET ['wecha_id'] . '&orderid=' . $orderNo;
		// $returnUrl=addons_url('Payment://Weixin/payOK');
		// //$this->assign ( 'returnUrl', $returnUrl );
		// $this->assign ( 'jsApiParameters', $jsApiParameters );
		// $this->assign ( 'price', $_GET['price'] );
		// die;
		// header('Location:http://'.$_SERVER['HTTP_HOST'].'/weishi/WxpayAPI/unifiedorder.php?body='.$body.'&out_trade_no='.$orderNo.'&totalfee='.$totalFee.'&openid='.$openId.'&returnurl='.$returnUrl);
	}
	public function payOK() {
		$isPay = I ( 'get.ispay', 0, 'intval' );
		$paymentId = I ( 'get.paymentId' );
		// dump($paymentId);
		// dump($isPay);
		if ($isPay) {
			$paymentDao = D ( 'Addons://Payment/PaymentOrder' );
			$info = $paymentDao->getInfo ( $paymentId, true );
			$payResult = $paymentDao->queryorder ( $info ['single_orderid'] );
			addWeixinLog ( $payResult, 'dopayquerystatus_' . $this->mid );
			if ($payResult && $payResult ['status'] == 1 && $payResult ['trade_state'] == 'SUCCESS') {
				$orderDao = D ( 'Addons://Shop/Order' );
				if ($info ['status']) {
					$map ['order_number'] = $info ['single_orderid'];
					$orderid = $orderDao->where ( $map )->getField ( 'id' );
					if (empty ( $orderid ) && $info ['aim_id']) {
						$orderid = $info ['aim_id'];
					} else {
						$yu = substr ( $info ['single_orderid'], - 3 );
						if ($yu == 'yue') {
							$len = strlen ( $info ['single_orderid'] ) - 3;
							$map ['order_number'] = substr ( $info ['single_orderid'], 0, $len );
							addWeixinLog ( $map ['order_number'], 'testweixinlog' );
							$orderid = $orderDao->where ( $map )->getField ( 'id' );
						}
					}
					$url = addons_url ( 'Shop://Wap/orderDetail', array (
							'id' => $orderid 
					) );
					$this->success ( '支付成功,即将跳转到订单详情', $url );
					exit ();
				}
				// 获取订单信息
				$orderInfo = $orderDao->getInfo ( $info ['aim_id'] );
				// $price = floatval($orderInfo['total_price']) + floatval($orderInfo['mail_money']);
				if ($orderInfo ['is_deposit']) {
					if ($orderInfo ['pay_status'] == 0) {
						// 支付定金
						$price = $orderInfo ['deposit_money'];
					} else if ($orderInfo ['pay_status'] == 2) {
						// 支付剩余金额
						$price = $orderInfo ['total_price'] - $orderInfo ['deposit_money']; // 剩余金额
					}
				} else {
					$price = floatval ( $orderInfo ['total_price'] ) + floatval ( $orderInfo ['mail_money'] );
				}
				
				$paymoney = $payResult ['total_fee'] / 100;
				$issuccess = 1;
				if ($price > $paymoney) {
					// 支付金额不对
					$issuccess = 0;
					$save ['order_state'] = 2; // 异常
					$extArr = json_decode ( $orderInfo ['extra'], true );
					$extArr ['order_state_msg'] = '应支付金额' . $price . '元，实际支付金额' . $paymoney . '元';
					$save ['extra'] = json_encode ( $extArr );
				}
				$res = $paymentDao->where ( array (
						'id' => $paymentId 
				) )->setField ( 'status', $isPay );
				$info = $paymentDao->getInfo ( $paymentId, true );
				$map ['order_number'] = $info ['single_orderid'];
				
				// $orderDao->where($map)->setField('pay_status', $isPay);
				$orderid = $orderDao->where ( $map )->getField ( 'id' );
				if (empty ( $orderid ) && $info ['aim_id']) {
					$orderid = $info ['aim_id'];
				} else {
					$yu = substr ( $info ['single_orderid'], - 3 );
					if ($yu == 'yue') {
						$len = strlen ( $info ['single_orderid'] ) - 3;
						$map ['order_number'] = substr ( $info ['single_orderid'], 0, $len );
						addWeixinLog ( $map ['order_number'], 'testweixinlog' );
						$orderid = $orderDao->where ( $map )->getField ( 'id' );
					}
				}
				$orderInfo = $orderDao->getInfo ( $orderid );
				$goodsData = json_decode ( $orderInfo ['goods_datas'], true );
				$shopConfig = get_addon_config ( 'Shop' );
				if (! empty ( $shopConfig ['lock_num_time'] )) {
					$lock_time = $shopConfig ['lock_num_time'];
				} else {
					$lock_time = 3600;
				}
				$sec = NOW_TIME - $orderInfo ['cTime'];
				if ($orderInfo ['is_lock'] == 0 && $issuccess) {
					// 锁定库存已被释放，重新锁定
					$goodsdao = D ( 'Addons://Shop/Goods' );
					foreach ( $goodsData as $goods ) {
						$goodsdao->setLockNum ( $goods ['num'], $goods ['id'], $goods ["spec_option_ids"] );
						if ($orderInfo ['order_from_type'] == '秒杀') {
							$seckillMap ['order_id'] = $orderInfo ['id'];
							$sgoodsMap ['seckill_id'] = M ( 'seckill_order' )->where ( $seckillMap )->getField ( 'seckill_id' );
							D ( 'Addons://Seckill/SeckillGoods' )->reduceCount ( $sgoodsMap ['seckill_id'], $goods ['id'], $goods ['num'] );
						}
					}
					$save ['is_lock'] = 1;
					// D('Addons://Shop/Order')->update($orderid,$save);
				}
				if ($orderInfo ['auto_send']) {
					if ($issuccess)
						$orderDao->autoSend ( $orderid );
				} else {
					if ($orderInfo ['is_deposit'] == 1 && $orderInfo ['pay_status'] == 0) {
						// 未支付订金
						$save ['pay_status'] = 2;
					} else {
						// 全额支付
						$save ['pay_status'] = 1;
					}
					if ($orderInfo ['is_mail'] == 0) {
						$save ['is_send'] = 2;
					}
					$save ['pay_time'] = time ();
					$orderInfo = $orderDao->update ( $orderid, $save );
					addWeixinLog ( $orderid, 'orderpayteste1_' . $this->mid );
					if ($save ['pay_status'] == 1 && $orderInfo ['is_deposit'] == 1 && $issuccess) {
						// 订金全额支付 确认已收款
						$orderDao->setStatusCode ( $orderid, 5 );
						
						// 做分佣处理
						$is_distribution = M ( 'shop_distribution_profit' )->where ( array (
								'order_id' => $orderid 
						) )->getFields ( 'id' );
						if (empty ( $is_distribution )) {
							// 确认已收款，处理分销用户获取的拥金
							D ( 'Addons://Shop/Distribution' )->do_distribution_profit ( $orderid );
						}
						
						// 到店支付返积分
						$payInfo = M ( 'payment_set' )->where ( array (
								'token' => get_token () 
						) )->find ();
						if ($payInfo ['shop_pay_score'] > 0) {
							add_credit ( 'shoppay', 0, array (
									'score' => $payInfo ['shop_pay_score'],
									'title' => '到店支付返积分' 
							) );
						}
						// 设置销售量
						$orderDao->setGoodsSaleCount ( $orderid );
					} else {
						// 待商家确认
						addWeixinLog ( $orderid, 'orderpayteste_' . $this->mid );
						$orderDao->setStatusCode ( $orderid, 1 );
					}
				}
				$url = addons_url ( 'Shop://Wap/orderDetail', array (
						'id' => $orderid 
				) );
				$this->success ( '支付成功,即将跳转到订单详情', $url );
			} else {
				$this->error ( '支付失败，即将跳转到订单列表', addons_url ( 'Shop://Wap/myOrder' ) );
			}
		} else {
			$this->error ( '支付失败，即将跳转到订单列表', addons_url ( 'Shop://Wap/myOrder' ) );
		}
	}
	// 同步数据处理
	public function return_url() {
		S ( 'pay', $_GET );
		$out_trade_no = $this->_get ( 'out_trade_no' );
		if (intval ( $_GET ['total_fee'] ) && ! intval ( $_GET ['trade_state'] )) {
			$okurl = addons_url ( $_GET ['from'], array (
					"token" => $_GET ['token'],
					"wecha_id" => $_GET ['wecha_id'],
					"orderid" => $out_trade_no 
			) );
			redirect ( $okurl );
		} else {
			exit ( '付款失败' );
		}
	}
	public function notify_url() {
		echo "success";
		exit ();
	}
	function api_notice_increment($url, $data) {
		$ch = curl_init ();
		$header = "Accept-Charset: utf-8";
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
		curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		$tmpInfo = curl_exec ( $ch );
		$errorno = curl_errno ( $ch );
		if ($errorno) {
			return array (
					'rt' => false,
					'errorno' => $errorno 
			);
		} else {
			$js = json_decode ( $tmpInfo, 1 );
			if ($js ['errcode'] == '0') {
				return array (
						'rt' => true,
						'errorno' => 0 
				);
			} else {
				$this->error ( error_msg ( $js ) );
			}
		}
	}
	
	// 订单查询
	public function queryorder() {
		require_once ('Weixinpay/WxPayData.class.php');
		require_once ('Weixinpay/WxPayApi.class.php');
		require_once ('Weixinpay/WxPayJsApiPay.php');
		// require_once ('Weixinpay/log.php');
		$paymentId = $_GET ['paymentId'];
		$token = $_GET ['token'];
		// $body = $_GET ['orderName'];
		$orderNo = $_GET ['orderNumber'];
		if ($orderNo == "") {
			$orderNo = $_GET ['single_orderid'];
		}
		if (empty ( $orderNo )) {
			$this->error ( '商户订单号必须！' );
			exit ();
		}
		$tools = new \JsApiPay ();
		import ( 'Weixinpay.WxPayData' );
		$input = new \WxPayUnifiedOrder ();
		$input->SetOut_trade_no ( $orderNo );
		$order = \WxPayApi::orderQuery ( $input );
		dump ( $order );
		$msg = '';
		$status = 0;
		$number = $orderNo;
		$tradeState = '';
		if ($order ['return_code'] == 'SUCCESS') {
			if ($order ['result_code'] == 'FAIL') {
				$msg = $order ['err_code'] == 'ORDERNOTEXIST' ? '此交易订单号不存在' : '系统错误';
			} else {
				$status = 1;
				$tradeState = $order ['trade_state'];
				$msg = $order ['trade_state_desc'];
				$number = $order ['out_trade_no'];
			}
		} else {
			$msg = $order ['return_msg'] == 'OK' || empty ( $order ['return_msg'] ) ? '查询失败' : $order ['return_msg'];
		}
		$returndata ['msg'] = $msg; // 提示信息
		$returndata ['status'] = $status; // 查询状态
		$returndata ['order_number'] = $number; // 订单号
		$returndata ['trade_state'] = $tradeState; // 交易状态
		/*
		 * 交易状态
		 * SUCCESS—支付成功
		 * REFUND—转入退款
		 * NOTPAY—未支付
		 * CLOSED—已关闭
		 * REVOKED—已撤销（刷卡支付）
		 * USERPAYING--用户支付中
		 * PAYERROR--支付失败(其他原因，如银行返回失败)
		 */
		return $returndata;
	}
}
?>