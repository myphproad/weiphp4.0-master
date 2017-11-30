<?php

namespace Addons\Servicer\Model;

use Think\Model;

/**
 * Servicer模型
 */
class ServicerModel extends Model {
	function checkRule($uid, $rule) {
		$map ['token'] = get_token ();
		$map ['uid'] = $uid;
		$map ['enable'] = 1;
		
		$role = $this->where ( $map )->getField ( 'role' );
		$role = explode ( ',', $role );
		return in_array ( $rule, $role ) ? true : false;
	}
}
