<?php

namespace Addons\Payment\Model;

use Think\Model;

/**
 * Payment模型
 */
class PaymentOrderModel extends Model {
	function getInfo($id, $update = false, $data = array()) {
		$key = 'PaymentOrder_getInfo_' . $id;
		$info = S ( $key );
		if ($info === false || $update) {
			$info = ( array ) (count ( $data ) == 0 ? $this->find ( $id ) : $data);
			S ( $key, $info );
		}
		return $info;
	}
	
	// 订单查询
	public function queryorder($orderNo) {
		// 读取配置
		$pay_config_db = M ( 'payment_set' );
		$paymentSet = $pay_config_db->where ( array (
				'token' => get_token () 
		) )->find ();
		if (empty ( $paymentSet )) {
			return false;
		}
		$paymentSet ['wxappid'] = trim ( $paymentSet ['wxappid'] );
		$paymentSet ['wxpaysignkey'] = trim ( $paymentSet ['wxpaysignkey'] );
		$paymentSet ['wxappsecret'] = trim ( $paymentSet ['wxappsecret'] );
		$paymentSet ['wxmchid'] = trim ( $paymentSet ['wxmchid'] );
		session ( 'paymentinfo', $paymentSet );
		
		require_once (SITE_PATH . '/Addons/Payment/Controller/Weixinpay/WxPayData.class.php');
		require_once (SITE_PATH . '/Addons/Payment/Controller/Weixinpay/WxPayApi.class.php');
		if (empty ( $orderNo )) {
			$returndata ['status'] = 0; // 查询状态
			$returndata ['msg'] = '商户订单号必须！'; // 提示信息
			$returndata ['order_number'] = $orderNo; // 订单号
			$returndata ['trade_state'] = ''; // 交易状态
			return $returndata;
		}
		// $tools = new \JsApiPay ();
		import ( 'Weixinpay.WxPayData' );
		$input = new \WxPayUnifiedOrder ();
		$input->SetOut_trade_no ( $orderNo );
		$order = \WxPayApi::orderQuery ( $input );
		// addWeixinLog($order,'queryorderdata_'.get_token());
		$msg = '';
		$status = 0;
		$number = $orderNo;
		$tradeState = '';
		$paytime = '';
		$total_fee = 0;
		if ($order ['return_code'] == 'SUCCESS') {
			if ($order ['result_code'] == 'FAIL') {
				$msg = $order ['err_code'] == 'ORDERNOTEXIST' ? '此交易订单号不存在' : '系统错误';
			} else {
				$status = 1;
				$tradeState = $order ['trade_state'];
				$msg = $order ['trade_state_desc'];
				$number = $order ['out_trade_no'];
				$paytime = $order ['time_end'];
				$total_fee = $order ['total_fee'];
			}
		} else {
			$msg = $order ['return_msg'] == 'OK' || empty ( $order ['return_msg'] ) ? '查询失败' : $order ['return_msg'];
		}
		$returndata ['status'] = $status; // 查询状态
		$returndata ['msg'] = $msg; // 提示信息
		$returndata ['order_number'] = $number; // 订单号
		$returndata ['trade_state'] = $tradeState; // 交易状态
		$returndata ['pay_time'] = $paytime;
		$returndata ['total_fee'] = $total_fee; // 订单金额 单位分
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
