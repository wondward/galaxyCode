<?php
/**
 * ECSHOP 会员中心
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: user.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');

$user_id = $_SESSION['user_id'];
$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);

$smarty->assign('categories',         get_categories_tree());  // 分类树
$back_act='';


// 不需要登录的操作或自己验证是否登录（如ajax处理）的act
$not_login_arr =
array('login','get_imgverify','act_login','register','act_register','act_edit_password','act_edit_paypassword', 'act_editlogin_userphone','get_password','send_pwd_email','password', 'signin', 'add_tag', 'collect', 'return_to_cart', 'logout', 'email_list', 'validate_email', 'send_hash_mail', 'order_query', 'is_registered', 'check_email','check_phone','check_phoneverify','get_phoneverify','clear_history','qpassword_name', 'get_passwd_question', 'check_answer','oath' , 'oath_login', 'other_login');

/* 显示页面的action列表 */
$ui_arr = array('register','ajax_checkoldpassword', 'manage_msg', 'auth_center', 'login','borrow_money','insert_borrow_money','withdraw_password','withdraw_pwadd','bangcard','unbundcard','bangcardadd', 'profile', 'order_list', 'order_detail', 'address_list', 'collection_list',
'message_list','chewithdrawpw', 'che_authwd_pw', 'ajax_center_manadel', 'loginpw_wdpw', 'user_head_img', 'act_bang_email', 'act_rechanger', 'act_withdrawals', 'act_bang_truename', 'tag_list', 'get_password', 'reset_password', 'booking_list', 'loan_list','add_booking', 'account_raply',
'account_deposit','bang_payment','account_log', 'booking_list_month', 'account_rechanger', 'account_withdrawals', 'account_detail', 'act_account', 'pay', 'default', 'bonus', 'group_buy', 'group_buy_detail', 'affiliate', 'comment_list',
'validate_email','track_packages', 'transform_points','qpassword_name', 'get_passwd_question', 'check_answer', 'callback_invest_ajax', 'over_invest_ajax', 'on_invest_ajax', 'callback_fixinvest_ajax', 'over_fixinvest_ajax', 'on_fixinvest_ajax');

/* 未登录处理 */
if (empty($_SESSION['user_id']))
{
    if (!in_array($action, $not_login_arr))
    {
        if (in_array($action, $ui_arr))
        {
            /* 如果需要登录,并是显示页面的操作，记录当前操作，用于登录后跳转到相应操作
            if ($action == 'login')
            {
                if (isset($_REQUEST['back_act']))
                {
                    $back_act = trim($_REQUEST['back_act']);
                }
            }
            else
            {}*/
            if (!empty($_SERVER['QUERY_STRING']))
            {
                $back_act = 'user.php?' . strip_tags($_SERVER['QUERY_STRING']);
            }
            $action = 'login';
        }
        else
        {
            //未登录提交数据。非正常途径提交数据！
            die($_LANG['require_login']);
        }
    }
}

/* 如果是显示页面，对页面进行相应赋值 */
if (in_array($action, $ui_arr))
{
    assign_template();
    $position = assign_ur_here(0, $_LANG['user_center']);
    $smarty->assign('page_title', $position['title']); // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']);
    $sql = "SELECT value FROM " . $ecs->table('shop_config') . " WHERE id = 419";
    $row = $db->getRow($sql);
    $car_off = $row['value'];
    $smarty->assign('car_off',       $car_off);
    /* 是否显示积分兑换 */
    if (!empty($_CFG['points_rule']) && unserialize($_CFG['points_rule']))
    {
        $smarty->assign('show_transform_points',     1);
    }
    $smarty->assign('helps',      get_shop_help());        // 网店帮助
    $smarty->assign('data_dir',   DATA_DIR);   // 数据目录
    $smarty->assign('action',     $action);
    $smarty->assign('lang',       $_LANG);
}

//用户中心欢迎页
if ($action == 'default')
{
    include_once(ROOT_PATH .'includes/lib_clips.php');
    if ($rank = get_rank_info())
    {
        $smarty->assign('rank_name', sprintf($_LANG['your_level'], $rank['rank_name']));
        if (!empty($rank['next_rank_name']))
        {
            $smarty->assign('next_rank_name', sprintf($_LANG['next_level'], $rank['next_rank'] ,$rank['next_rank_name']));
        }
    }
    $smarty->assign('info',        get_user_default($user_id));
    $smarty->assign('user_notice', $_CFG['user_notice']);
    $smarty->assign('prompt',      get_user_prompt($user_id));
    $smarty->display('user_clips.dwt');
}

/* 显示会员注册界面 */
elseif ($action == 'register')
{
    if ((!isset($back_act)||empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
    {
        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
    }

    /* 取出注册扩展字段 
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);
    $smarty->assign('extend_info_list', $extend_info_list);
	*/
    /* 验证码相关设置 */
    if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
    {
        $smarty->assign('enabled_captcha', 1);
        $smarty->assign('rand',            mt_rand());
    }

    /* 密码提示问题 */
    //$smarty->assign('passwd_questions', $_LANG['passwd_questions']);

    /* 增加是否关闭注册 */
    $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
//    $smarty->assign('back_act', $back_act);
    $smarty->display('user_passport.dwt');
}
/* 图形验证码的校验*/
elseif ($action == 'get_imgverify'){
    include_once('includes/cls_captcha.php');
	$captcha = $_POST['imgverify'];
    $validator = new captcha();
    if ($validator->check_word($captcha)){
    	echo "ok";
    	exit;
    }else{
    	echo "error";
    	exit;
    }
    
}
/* 注册会员的处理 */
elseif ($action == 'act_register')
{
    /* 增加是否关闭注册 */
    if ($_CFG['shop_reg_closed'])
    {
        $smarty->assign('action',     'register');
        $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
        $smarty->display('user_passport.dwt');
    }
    else
    {
        include_once(ROOT_PATH . 'includes/lib_passport.php');

        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $phoneverify = isset($_POST['verify']) ? trim($_POST['verify']) : '';
        $mobile_phone = isset($_POST['mobile_phone']) ? trim($_POST['mobile_phone']) : '';
        //$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
        //$other['qq'] = isset($_POST['extend_field2']) ? $_POST['extend_field2'] : '';
        //$other['office_phone'] = isset($_POST['extend_field3']) ? $_POST['extend_field3'] : '';
        //$other['home_phone'] = isset($_POST['extend_field4']) ? $_POST['extend_field4'] : '';
        //$sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
        //$passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';


        $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';
		
        
        if(empty($_POST['agreement']))
        {
            show_message($_LANG['passport_js']['agreement']);
        }
        if (strlen($username) < 3)
        {
            show_message($_LANG['passport_js']['username_shorter']);
        }

        if (strlen($password) < 6)
        {
            show_message($_LANG['passport_js']['password_shorter']);
        }

        if (strpos($password, ' ') > 0)
        {
            show_message($_LANG['passwd_balnk']);
        }
        if(empty($phoneverify))
        {
        	show_message($_LANG['passport_js']['phone_verify']);
        }
        if(!preg_match("/1[34578]{1}\d{9}$/",$mobile_phone))
        {
        	show_message($_LANG['passport_js']['mobile_phone_invalid']);
        }
        /* 验证码检查 */
        if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
        {
            if (empty($_POST['captcha']))
            {
                show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
            }

            /* 检查验证码 */
            include_once('includes/cls_captcha.php');

            $validator = new captcha();
            if (!$validator->check_word($_POST['captcha']))
            {
                show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
            }
        }

        if (register($username, $password, $mobile_phone) !== false)
        {
        	//header("Location:./index.php");
            /*把新注册用户的扩展信息插入数据库
            $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id
            $fields_arr = $db->getAll($sql);

            $extend_field_str = '';    //生成扩展字段的内容字符串
            foreach ($fields_arr AS $val)
            {
                $extend_field_index = 'extend_field' . $val['id'];
                if(!empty($_POST[$extend_field_index]))
                {
                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
                    $extend_field_str .= " ('" . $_SESSION['user_id'] . "', '" . $val['id'] . "', '" . compile_str($temp_field_content) . "'),";
                }
            }
            $extend_field_str = substr($extend_field_str, 0, -1);

            if ($extend_field_str)      //插入注册扩展数据
            {
                $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
                $db->query($sql);
            }

            /* 写入密码提示问题和答案 
            if (!empty($passwd_answer) && !empty($sel_question))
            {
                $sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
                $db->query($sql);
            }
            /* 判断是否需要自动发送注册邮件 
            if ($GLOBALS['_CFG']['member_email_validate'] && $GLOBALS['_CFG']['send_verify_email'])
            {
                send_regiter_hash($_SESSION['user_id']);
            }*/
            $ucdata = empty($user->ucdata)? "" : $user->ucdata;
            show_message(sprintf($_LANG['register_success'], $username . $ucdata), array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act, 'user.php'), 'info');
        }
        else
        {
            $err->show($_LANG['sign_up'], 'user.php?act=register');
        }
    }
}
//  第三方登录接口
elseif($action == 'oath')
{
	
	$type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];
	
	if($type == "taobao"){
		header("location:includes/website/tb_index.php");exit;
	}
	
	include_once(ROOT_PATH . 'includes/website/jntoo.php');
	
	

	$c = &website($type);
	if($c)
	{
		if (empty($_REQUEST['callblock']))
		{
			if (empty($_REQUEST['callblock']) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
			{
				$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? 'index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
			}
			else
			{
				$back_act = 'index.php';
			}
		}
		else
		{
			$back_act = trim($_REQUEST['callblock']);
		}

		if($back_act[4] != ':') $back_act = $ecs->url().$back_act;
		$open = empty($_REQUEST['open']) ? 0 : intval($_REQUEST['open']);

		$url = $c->login($ecs->url().'user.php?act=oath_login&type='.$type.'&callblock='.urlencode($back_act).'&open='.$open);
		if(!$url)
		{
			show_message( $c->get_error() , '首页', $ecs->url() , 'error');
		}
		header('Location: '.$url);
	}
	else
	{
		show_message('服务器尚未注册该插件！' , '首页',$ecs->url() , 'error');
	}
}



//  处理第三方登录接口
elseif($action == 'oath_login')
{
	$type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];
	
	include_once(ROOT_PATH . 'includes/website/jntoo.php');
	$c = &website($type);
	if($c)
	{
		$access = $c->getAccessToken();
		if(!$access)
		{
			show_message( $c->get_error() , '首页', $ecs->url() , 'error');
		}
		$c->setAccessToken($access);
		$info = $c->getMessage();
		if(!$info)
		{
			show_message($c->get_error() , '首页' , $ecs->url() , 'error' , false);
		}
		if(!$info['user_id'])
			show_message($c->get_error() , '首页' , $ecs->url() , 'error' , false);


		$info_user_id = $type .'_'.$info['user_id']; //  加个标识！！！防止 其他的标识 一样  // 以后的ID 标识 将以这种形式 辨认
		$info['name'] = str_replace("'" , "" , $info['name']); // 过滤掉 逗号 不然出错  很难处理   不想去  搞什么编码的了
		if(!$info['user_id'])
			show_message($c->get_error() , '首页' , $ecs->url() , 'error' , false);


		$sql = 'SELECT user_name,password,aite_id FROM '.$ecs->table('users').' WHERE aite_id = \''.$info_user_id.'\' OR aite_id=\''.$info['user_id'].'\'';

		$count = $db->getRow($sql);
		if(!$count)   // 没有当前数据
		{
			if($user->check_user($info['name']))  // 重名处理
			{
				$info['name'] = $info['name'].'_'.$type.(rand(10000,99999));
			}
			$user_pass = $user->compile_password(array('password'=>$info['user_id']));
			$sql = 'INSERT INTO '.$ecs->table('users').'(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES '.
					"('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '".gmtime()."' , '$info[rank_id]' , '1')" ;
			$db->query($sql);
		}
		else
		{
			$sql = '';
			if($count['aite_id'] == $info['user_id'])
			{
				$sql = 'UPDATE '.$ecs->table('users')." SET aite_id = '$info_user_id' WHERE aite_id = '$count[aite_id]'";
				$db->query($sql);
			}
			if($info['name'] != $count['user_name'])   // 这段可删除
			{
				if($user->check_user($info['name']))  // 重名处理
				{
					$info['name'] = $info['name'].'_'.$type.(rand()*1000);
				}
				$sql = 'UPDATE '.$ecs->table('users')." SET user_name = '$info[name]' WHERE aite_id = '$info_user_id'";
				$db->query($sql);
			}
		}
		$user->set_session($info['name']);
		$user->set_cookie($info['name']);
		update_user_info();
		recalculate_price();

		if(!empty($_REQUEST['open']))
		{
			die('<script>window.opener.window.location.reload(); window.close();</script>');
		}
		else
		{
			ecs_header('Location: '.$_REQUEST['callblock']);

		}

	}

}

//  处理其它登录接口
elseif($action == 'other_login')
{
	$type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];
	session_start();
	$info = $_SESSION['user_info'];

	if(empty($info)){
		show_message("非法访问或请求超时！" , '首页' , $ecs->url() , 'error' , false);
	}
	if(!$info['user_id'])
		show_message("非法访问或访问出错，请联系管理员！", '首页' , $ecs->url() , 'error' , false);


	$info_user_id = $type .'_'.$info['user_id']; //  加个标识！！！防止 其他的标识 一样  // 以后的ID 标识 将以这种形式 辨认
	$info['name'] = str_replace("'" , "" , $info['name']); // 过滤掉 逗号 不然出错  很难处理   不想去  搞什么编码的了


	$sql = 'SELECT user_name,password,aite_id FROM '.$ecs->table('users').' WHERE aite_id = \''.$info_user_id.'\' OR aite_id=\''.$info['user_id'].'\'';

	$count = $db->getRow($sql);
	$login_name = $info['name'];
	if(!$count)   // 没有当前数据
	{
		if($user->check_user($info['name']))  // 重名处理
		{
			$info['name'] = $info['name'].'_'.$type.(rand()*1000);
		}
		$login_name = $info['name'];
		$user_pass = $user->compile_password(array('password'=>$info['user_id']));
		$sql = 'INSERT INTO '.$ecs->table('users').'(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES '.
				"('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '".gmtime()."' , '$info[rank_id]' , '1')" ;
		$db->query($sql);
	}
	else
	{
		$login_name = $count['user_name'];
		$sql = '';
		if($count['aite_id'] == $info['user_id'])
		{
			$sql = 'UPDATE '.$ecs->table('users')." SET aite_id = '$info_user_id' WHERE aite_id = '$count[aite_id]'";
			$db->query($sql);
		}
	}
	
	
	
	$user->set_session($login_name);
	$user->set_cookie($login_name);
	update_user_info();
	recalculate_price();

	$redirect_url =  "http://".$_SERVER["HTTP_HOST"].str_replace("user.php", "index.php", $_SERVER["REQUEST_URI"]);
	header('Location: '.$redirect_url);

}
/* 验证用户注册邮件 */
elseif ($action == 'validate_email')
{
    $hash = empty($_GET['hash']) ? '' : trim($_GET['hash']);
    if ($hash)
    {
        include_once(ROOT_PATH . 'includes/lib_passport.php');
        $id = register_hash('decode', $hash);
        if ($id > 0)
        {
            $sql = "UPDATE " . $ecs->table('users') . " SET emailstatus = 1 WHERE user_id='$id'";
            $db->query($sql);
            $sql = 'SELECT user_name, email FROM ' . $ecs->table('users') . " WHERE user_id = '$id'";
            $row = $db->getRow($sql);
            show_message(sprintf($_LANG['validate_ok'], $row['user_name'], $row['email']),$_LANG['profile_lnk'], 'user.php?act=auth_center');
        }
    }
    show_message($_LANG['validate_fail']);

}
/* 验证用户注册用户名是否可以注册 */
elseif ($action == 'is_registered')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    $username = trim($_GET['username']);
    $username = json_str_iconv($username);

    if ($user->check_user($username) || admin_registered($username))
    {
        echo 'false';
    }
    else
    {
        echo 'true';
    }
}

/* 验证用户的身份证信息是否存在*/
elseif($action == 'ajax_checkidcard'){
	$idcard = trim($_POST['idcard']);
	
	$sql = "SELECT user_id FROM ".$GLOBALS['ecs']->table('users').
		" WHERE user_id=".$user_id." AND idcard='".$idcard."'";
		
	$res = $GLOBALS['ecs']->getOne($sql);
	if($res > 0){
		echo "ok";
	}else{
		echo "error";
	}
}

/* 验证用户邮箱地址是否被注册 */
elseif($action == 'check_email')
{
    $email = trim($_REQUEST['email']);
    
    if ($user->check_email($email))
    {
        echo 'false';
    }
    else
    {
        echo 'ok';
    }
}
/* 验证手机号是否被注册*/
elseif($action == 'check_phone')
{
	$phone = trim($_GET['phone']);
	if($user->check_phone($phone))
	{
		echo 'false';
	}
	else 
	{
		echo 'ok';
	}
}

/* 获取短信验证码*/
elseif($action == 'get_phoneverify'){

	include_once(ROOT_PATH . 'includes/cls_sms.php');
	$mobile = trim($_POST['phone']);
	$phone_flag = trim($_POST['phone_flag']);
	$sms = new sms();
	
	if($sms->send($mobile,$phone_flag)){
		echo 'ok';
		exit;
	}else{
		echo 'false';
		exit;
	}
}
/* 验证短信验证码*/
elseif($action == 'check_phoneverify'){

	include_once(ROOT_PATH . 'includes/cls_sms.php');	
	$phoneverify = trim($_POST['phoneverify']);
	$verify_flag = trim($_POST['verify_flag']);
	$sms = new sms();
	
	if($sms->check_verify($phoneverify,$verify_flag))
	{
		echo 'ok';
	}else{
		echo 'false';
	}
}
/* 用户登录界面 */
elseif ($action == 'login')
{
    if (empty($back_act))
    {
        if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
        {
           $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
        }
        else
        {
            $back_act = 'user.php';
        }

    }


    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }
	
    $smarty->assign('user_name',$_COOKIE['ECS']['username']);	//获取cookie中用户的昵称
    $smarty->assign('back_act', $back_act);
    $smarty->display('user_passport.dwt');
}

/* 处理会员的登录 */
elseif ($action == 'act_login')
{
    $username = isset($_POST['username']) ? mysql_real_escape_string(trim($_POST['username'])) : '';
    $password = isset($_POST['password']) ? mysql_real_escape_string(trim($_POST['password'])) : '';
    $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';

	
    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
        }

        /* 检查验证码 */
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
        }
    }
	
    if ($user->login($username, $password,isset($_POST['remember'])))
    {
    	
        update_user_info();
        recalculate_price();
        
        $ucdata = isset($user->ucdata)? $user->ucdata : '';
        show_message($_LANG['login_success'] . $ucdata , array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act,'user.php'), 'info');
    }
    else
    {
        $_SESSION['login_fail'] ++ ;
        show_message($_LANG['login_failure'], $_LANG['relogin_lnk'], 'user.php', 'error');
    }
}

/* 设置提现密码页面*/
elseif ($action == 'withdraw_password')
{
	//获取用户手机号
	$sql = 'select mobile_phone,paypassword from '.$ecs->table('users').' where user_id ='.$user_id;
	$row = $db->getRow($sql);
	$str1 = substr($row['mobile_phone'],0,3);
	$str2 = substr($row['mobile_phone'],-3);
	$str = $str1.'*****'.$str2;
	
	$sign = empty($row['paypassword'])?0:1;
	$smarty->assign('sign',$sign);
	$smarty->assign('phone',$row['mobile_phone']);
	$smarty->assign('mobilestr',$str);
	
	$smarty->display('user_transaction.dwt');
}
/* 判断提现密码与登录密码是否一致*/
elseif ($action == 'loginpw_wdpw'){
	$pw = trim($_POST['wdpw']);
	$newpw = $user->compile_password(array('password'=>$pw));
	
	$sql = "SELECT password FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=".$user_id;
	$res = $GLOBALS['db']->getOne($sql);
	
	if($res == $newpw){
		echo "error";
		exit;
	}else{
		echo "ok";
		exit;
	}
}
/* 添加提现密码*/
elseif ($action == 'withdraw_pwadd')
{
	$withdrawpw = strip_tags(trim($_POST['withdrawpassword']));
	$withdrawpwconfirm = strip_tags(trim($_POST['withdrawpwconfirm']));
	$withdrawverify = intval(trim($_POST['withdrawverify']));
	
	if(empty($withdrawpw) && empty($withdrawpwconfirm) && empty($withdrawverify)){
		show_message($_LANG['withdraw_info_fail'], $_LANG['back_up_page'], 'user.php?act=withdraw_password', 'error');
	}
	
	$rule = '/[0-9a-z-A-Z]{6,30}/';
	$ruleverify = '/[0-9]{4}/';
	if(!preg_match($ruleverify,$withdrawverify)){
		show_message($_LANG['withdraw_verify_fail'], $_LANG['back_up_page'], 'user.php?act=withdraw_password', 'error');
	}elseif(!preg_match($rule,$withdrawpw)){
		show_message($_LANG['withdraw_pw_fail'], $_LANG['back_up_page'], 'user.php?act=withdraw_password', 'error');
	}elseif(!preg_match($rule,$withdrawpw)){
		show_message($_LANG['withdraw_pw_confirm'], $_LANG['back_up_page'], 'user.php?act=withdraw_password', 'error');
	}elseif($withdrawpwconfirm != $withdrawpw){
		show_message($_LANG['withdraw_pw_equal'], $_LANG['back_up_page'], 'user.php?act=withdraw_password', 'error');
	}
	
	$withdrawpw = $user->compile_password(array('password'=>$withdrawpw));
	
	$sql = 'UPDATE '.$GLOBALS['ecs']->table('users').' SET paypassword = "'.$withdrawpw.'" where user_id='.$user_id;
	$result = $GLOBALS['db']->query($sql);
	
	if($result){
		show_message($_LANG['withdrawpw_success'],$_LANG['back_up_page'], 'user.php?act=withdraw_password', 'info');
	}else{
		show_message($_LANG['withdrawpw_fail'],$_LANG['back_up_page'], 'user.php?act=withdraw_password', 'info');
	}
}

/* ajax登录密码中原密码的验证*/
elseif($action == 'ajax_checkoldpassword')
{
	include_once('includes/cls_json.php');
	$json = new JSON;
	$name = $_SESSION['user_name'];
	$password = isset($_POST['oldpassword'])?trim($_POST['oldpassword']):NULL;
	$password = $user->compile_password(array('password'=>$password));
	
	$sql = "SELECT user_id FROM ".$GLOBALS['ecs']->table('users').
		" WHERE user_name='".$name."' AND password='".$password."'";
	$result = $GLOBALS['db']->getOne($sql);

	//$result = $user->check_user($_SESSION['user_name'], $password);
	
	die($json->encode($result));
}

/* 银行卡绑定页面*/
elseif ($action == 'bangcard')
{
	include_once('includes/lib_transaction.php');
	/*判断用户的实名认证状态*/
	$sql = 'select idcard,idcardstatus,realname from '.$ecs->table('users').' where user_id ='.$user_id;
	$row = $db->getRow($sql);
	if(empty($row['idcard']) || empty($row['idcardstatus']) || empty($row['realname'])){
		show_message($_LANG['withdraws_idcard_fail'],$_LANG['back_up_page'], 'user.php?act=auth_center', 'info');
	}
	$realname = substr($row['realname'],0,3)."***";
	
	/* 取得国家的省列表 */
	$province_list[$region_id] = get_regions(1, 1);
	$smarty->assign('province_list',    $province_list);
	$smarty->assign('real_name',$realname);
	
	$cardinfo = getbangcard_success($user_id);	//查询已绑定的银行卡
	if(empty($cardinfo)){
		$smarty->assign('cardflag',0);
	}else{
		$smarty->assign('cardflag',1);
	}
	$cardinfo['cardnum'] = substr($cardinfo['cardnum'],0,4).'********************'.substr($cardinfo['cardnum'],-4);

	$smarty->assign('cardinfo',$cardinfo);
	
	$smarty->display('user_transaction.dwt');
}

/* 绑定银行卡信息的添加*/
elseif ($action == 'bangcardadd')
{
	include_once('includes/lib_transaction.php');
	include_once('includes/cls_mysql.php');
	include_once('includes/lib_llpay.php');
	
	$cardnum = isset($_POST['cardnum'])?mysql_real_escape_string(trim($_POST['cardnum'])):0;
	$cardprovince = isset($_POST['provincename'])?$_POST['provincename']:0;
	$cardcity = isset($_POST['cityname'])?trim($_POST['cityname']):0;
	$cardcounty	= isset($_POST['countyname'])?trim($_POST['countyname']):0;
	$cardbank = isset($_POST['bankname'])?trim($_POST['bankname']):0;
	$cardshop = isset($_POST['cardwang'])?trim($_POST['cardwang']):'0';
	
	if(empty($cardbank)){
		show_message($_LANG['card_bank_error'], $_LANG['back_up_page'],'user.php?act=bangcard', 'info');
	}elseif(empty($cardprovince) || empty($cardcity) || empty($cardcounty)){
		show_message($_LANG['card_addr_error'], $_LANG['back_up_page'],'user.php?act=bangcard', 'info');
	}elseif(empty($cardshop)){
		show_message($_LANG['card_shop_error'], $_LANG['back_up_page'],'user.php?act=bangcard', 'info');
	}elseif(empty($cardnum)){
		show_message($_LANG['card_num_error'], $_LANG['back_up_page'],'user.php?act=bangcard', 'info');
	}
	/* 查询城市编号*/
	$sql = 'SELECT agency_id FROM '.$GLOBALS['ecs']->table('region').' WHERE region_id='.$cardcity;
	$citycode = $GLOBALS['db']->getOne($sql);
	
	/* 判断银行卡是否已被绑定*/
	$sql = 'SELECT cardid FROM '.$GLOBALS['ecs']->table('bang_card').
		' WHERE bangstatus=1 AND cardnum="'.$cardnum.'"';
	$cardexist = $GLOBALS['db']->getOne($sql);
	if($cardexist > 0){
		show_message($_LANG['bang_card_exist'], $_LANG['back_up_page'],'user.php?act=bangcard', 'info');
	}
	
	/* 查询银行卡信息*/
	$check = new conpay;
	$res = $check->select_cardinfo($cardnum);
	$cardinfo = json_decode($res);
	
	/* 判断银行编号是否一致*/
	if($cardinfo->bank_code != $_POST['bankname']){
		show_message($_LANG['bang_cardbank_error'], $_LANG['back_up_page'],'user.php?act=bangcard', 'info');
	}
	
	/* 判断是否为信用卡*/
	if($cardinfo->card_type == 3){
		show_message($_LANG['bang_cardtype_error'], $_LANG['back_up_page'],'user.php?act=bangcard', 'info');
	}
	
	/* 查询大额行号*/
	$banks = array('shopname'=>$cardshop,'bankcode'=>$cardbank,'citycode'=>$citycode);
	$bankres = $check->select_bigbankcode($banks);
	$bankinfo = json_decode($bankres);
	print_r($bankinfo);exit;
	
	if($cardinfo->ret_code == '0000' && $cardinfo->ret_msg == '交易成功'){
		$bangcardinfo = array(
			'user_id'		=>	$user_id,
			'addtime'		=>	gmtime(),
			'cardnum'		=>	$cardnum,
			'cardprovince'	=>	$cardprovince,
			'cardcity'		=>	$cardcity,
			'cardcounty'	=>	$cardcounty,
			'cardbank'		=>	$cardbank,
			'cardshop'		=>	$cardshop,
			'card_type'		=>	$cardinfo->card_type,
			'bangstatus'	=>	'1',
			'no_agree'		=>	'0',
			'bigcode'		=>	$bankinfo->prcptcd
			
		);
	
		if(insert_bangcard($bangcardinfo)){
			show_message($_LANG['bang_card_success'], $_LANG['back_up_page'],'user.php?act=bangcard', 'info');
		}else{
			show_message($_LANG['bang_card_false'], $_LANG['back_up_page'], 'user.php?act=bangcard', 'info');
		}
	}else{
		show_message($_LANG['bang_card_false'], $_LANG['back_up_page'], 'user.php?act=bangcard', 'info');
	}
}

/* 解除绑定银行卡*/
elseif ($action == 'unbundcard'){
	include_once('includes/lib_transaction.php');
	include_once('includes/lib_llpay.php');
	
	$cardinfo = getbangcard_success($user_id);	//查询已绑定的银行卡
	
	if(empty($cardinfo['no_agree'])){
		show_message($_LANG['unbang_card_false'], $_LANG['back_up_page'], 'user.php?act=bangcard', 'info');
	}
	
	$unbind = new conpay;
	$cardinfo['user_id'] = $user_id;
	$unbind->unbind_card($cardinfo);
	if($unbind->ret_code == '0000' && $unbind->msg == '交易成功'){
		/* 更改银行卡的绑定状态*/
		if(update_bangcard($cardinfo)){
			show_message($_LANG['unbang_card_success'], $_LANG['back_up_page'], 'user.php?act=bangcard', 'info');
		}else{
			show_message($_LANG['unbang_card_false'], $_LANG['back_up_page'], 'user.php?act=bangcard', 'info');
		}
	}else{
		show_message($_LANG['unbang_card_false'], $_LANG['back_up_page'], 'user.php?act=bangcard', 'info');
	}
}

/* 安全认证中心页面*/
elseif ($action == 'auth_center')
{
	$sql='SELECT mobile_phone,email,idcard,phonestatus,emailstatus,idcardstatus FROM'.$GLOBALS['ecs']->table('users').' where user_id='.$user_id;
	$userinfo = $GLOBALS['db']->getRow($sql);
	$smarty->assign('truephone',$userinfo['mobile_phone']);
	$userinfo['phone'] = str_replace(substr($userinfo['mobile_phone'],3,5),'*****',$userinfo['mobile_phone']);
	$userinfo['email'] = str_replace(substr($userinfo['email'],3,strlen($userinfo['email'])-10),'*****',$userinfo['email']);
	$userinfo['idcard'] = str_replace(substr($userinfo['idcard'],3,strlen($userinfo['idcard'])-7),'********',$userinfo['idcard']);
	
	$smarty->assign('useruser',$user_id);
	$smarty->assign('userinfostatus',$userinfo);
	$smarty->display('user_transaction.dwt');
}

/* 安全认证中邮箱的绑定*/
elseif ($action == 'act_bang_email')
{
	include_once(ROOT_PATH . 'includes/lib_passport.php');
	
	$email = trim($_POST['authcenter_email']);
	if(empty($email)){
		show_message($_LANG['passport_js']['email_empty']);
	}elseif(ereg("/^[a-z]([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?$/i; ",$email)){
		show_message($_LANG['passport_js']['email_invalid']);
	}
	/* 查询邮箱是否存在*/
	$sql='SELECT * FROM '.$GLOBALS['ecs']->table('users').' where email="'.$email.'"';
	$result = $GLOBALS['db']->getOne($sql);
	if(empty($result)){
		$sql='UPDATE '.$GLOBALS['ecs']->table('users').' SET email="'.$email.'" where user_id='.$user_id;
		$GLOBALS['db']->query($sql);
		
		//发送邮件的函数
        if (send_regiter_hash($user_id,$email))
        {
            show_message($_LANG['send_bangmail_success'] . $email, $_LANG['back_home_lnk'], './', 'info');
        }
        else
        {
            //发送邮件出错
            show_message($_LANG['fail_send_password'], $_LANG['back_page_up'], './', 'info');
        }
		header("location:user.php?act=auth_center");
	}else{
		show_message($_LANG['passport_js']['email_confirm']);
	}
}

/* 注册手机号的更改*/
elseif ($action == 'act_edit_userphone')
{
	$withdrawpw = trim($_POST['withdrawauthcenter_password']);
	$newphone = trim($_POST['newmobile_phone']);
	$idcard = trim($_POST['authcenter_idcard']);
	$idcardwithpw = trim($_POST['withdrawauthidcardcenter_password']);
	if(empty($newphone)){
		show_message($_LANG['passport_js']['newphone_empty']);
	}elseif(ereg("/^1[3|5|8|7]\d{9}$/",$newphone)){
		show_message($_LANG['passport_js']['newphone_invalid']);
	}
	if(empty($idcard)){
	/* 依据已有手机号更换手机号*/
		if(!$user->check_phone($newphone)){
			$sql = 'UPDATE '.$GLOBALS['ecs']->table('users').' SET mobile_phone ='.$newphone.' where user_id='.$user_id;
			$res = $GLOBALS['db']->query($sql);
		}
	}else{
		$sql = 'UPDATE '.$GLOBALS['ecs']->table('users').' SET mobile_phone ='.$newphone.' WHERE idcard="'.$idcard.'" AND user_id='.$user_id;
		$res = $GLOBALS['db']->query($sql);
	}
	if($res){
		header('location:user.php?act=auth_center');
	}else{
		show_message($_LANG['passport_js']['newphone_invalid']);
	}
}

/* 注册手机号更改中提现验证码的验证*/
elseif ($action == 'che_authwd_pw'){
	$password = trim($_POST['password']);
	$password = $user->compile_password(array('password'=>$password));
	$sql = 'SELECT paypassword FROM '.$GLOBALS['ecs']->table('users')." WHERE user_id=".$user_id;
	$pw = $GLOBALS['db']->getOne($sql);
	if(empty($pw)){
		echo "3";
	}elseif($pw == $password){
		echo "1";
	}else{
		echo "2";
	}
}

/* 处理 ajax 的登录请求 */
elseif ($action == 'signin')
{
    include_once('includes/cls_json.php');
    $json = new JSON;

    $username = !empty($_POST['username']) ? json_str_iconv(trim($_POST['username'])) : '';
    $password = !empty($_POST['password']) ? trim($_POST['password']) : '';
    $captcha = !empty($_POST['captcha']) ? json_str_iconv(trim($_POST['captcha'])) : '';
    $result   = array('error' => 0, 'content' => '');

    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($captcha))
        {
            $result['error']   = 1;
            $result['content'] = $_LANG['invalid_captcha'];
            die($json->encode($result));
        }

        /* 检查验证码 */
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {

            $result['error']   = 1;
            $result['content'] = $_LANG['invalid_captcha'];
            die($json->encode($result));
        }
    }

    if ($user->login($username, $password))
    {
        update_user_info();  //更新用户信息
        recalculate_price(); // 重新计算购物车中的商品价格
        $smarty->assign('user_info', get_user_info());
        $ucdata = empty($user->ucdata)? "" : $user->ucdata;
        $result['ucdata'] = $ucdata;
        $result['content'] = $smarty->fetch('library/member_info.lbi');
    }
    else
    {
        $_SESSION['login_fail']++;
        if ($_SESSION['login_fail'] > 2)
        {
            $smarty->assign('enabled_captcha', 1);
            $result['html'] = $smarty->fetch('library/member_info.lbi');
        }
        $result['error']   = 1;
        $result['content'] = $_LANG['login_failure'];
    }
    die($json->encode($result));
}

/* 消息中心页面*/
elseif ($action == 'manage_msg'){
	$articleid = intval($_GET['id']);

	if(!empty($articleid)){
		/* 获得文章的信息 */
	    $sql = "SELECT a.*, IFNULL(AVG(r.comment_rank), 0) AS comment_rank ".
	            "FROM " .$GLOBALS['ecs']->table('article'). " AS a ".
	            "LEFT JOIN " .$GLOBALS['ecs']->table('comment'). " AS r ON r.id_value = a.article_id AND comment_type = 1 ".
	            "WHERE a.is_open = 1 AND a.article_id = '$articleid' GROUP BY a.article_id";
	    $row = $GLOBALS['db']->getRow($sql);
	
	    if ($row !== false)
	    {
	        $row['comment_rank'] = ceil($row['comment_rank']);                              // 用户评论级别取整
	        $row['add_time']     = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']); // 修正添加时间显示
	
	        /* 作者信息如果为空，则用网站名称替换 */
	        if (empty($row['author']) || $row['author'] == '_SHOPHELP')
	        {
	            $row['author'] = $GLOBALS['_CFG']['shop_name'];
	        }
	    }

	    $smarty->assign('ardetail','one');
	    $smarty->assign('artidetail',$row);
	    $smarty->display('user_transaction.dwt');
	}else{
		/* 获得指定的分类ID */
		$cat_id = 11;
		
		/* 获得当前页码 */
		$page   = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
		
	    /* 如果页面没有被缓存则重新获得页面的内容 */
	    $position = assign_ur_here($cat_id);
	    $smarty->assign('page_title',           $position['title']);     // 页面标题
	    $smarty->assign('ur_here',              $position['ur_here']);   // 当前位置
	    $smarty->assign('promotion_info', get_promotion_info());
	
	    /* Meta */
	    $meta = $db->getRow("SELECT keywords, cat_desc FROM " . $ecs->table('article_cat') . " WHERE cat_id = '$cat_id'");
	
	    if ($meta === false || empty($meta))
	    {
	        /* 如果没有找到任何记录则返回首页 */
	        ecs_header("Location: ./\n");
	        exit;
	    }
	
	    $smarty->assign('keywords',    htmlspecialchars($meta['keywords']));
	    $smarty->assign('description', htmlspecialchars($meta['cat_desc']));
	
	    /* 获得文章总数 */
	    $size   = isset($_CFG['article_page_size']) && intval($_CFG['article_page_size']) > 0 ? intval($_CFG['article_page_size']) : 20;
	    $count  = get_article_count($cat_id);

	    $pages  = ($count > 0) ? ceil($count / $size) : 1;
	
	    if ($page > $pages)
	    {
	        $page = $pages;
	    }
	    $artciles_list = get_cat_articles($cat_id, $page, $size ,$keywords);
	    /* 查询删除的促销活动id*/
	    $sql = 'SELECT isdel_article from '.$ecs->table("users").' where user_id='.$user_id;
		$delid = $GLOBALS['db']->getOne($sql);
		$delidarr = explode(',',$delid);
		
	    foreach($artciles_list as $key=>$artcleinfo){
	    	$artciles_list[$key][url] = 'user.php?act=manage_msg&id='.$artcleinfo[id];
	    	if(in_array($artciles_list[$key][id],$delidarr)){
	    		unset($artciles_list[$key]);
	    	}
	    }

		$smarty->assign('artciles_list',    $artciles_list);
		
	    /* 分页 */
	    assign_pager('article_cat', $cat_id, $count, $size, '', '', $page, $goon_keywords);
			
		$smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typearticle_cat" . $cat_id . ".xml" : 'feed.php?type=article_cat' . $cat_id); // RSS URL
		$smarty->assign('ardetail','all');
		$smarty->display('user_transaction.dwt');
	}
}

/* 消息中心里消息的删除*/
elseif ($action == 'ajax_center_manadel'){
	$artlids = $_POST['artlids'];
	if(isset($artlids)){
		/* 查询删除的促销活动id*/
	    $sql = 'SELECT isdel_article from '.$ecs->table("users").' where user_id='.$user_id;
		$delid = $GLOBALS['db']->getOne($sql);
		$artlids = $delid.$artlids;
		
		$sql='update '.$ecs->table('users').' set isdel_article="'.$artlids.'" where user_id='.$user_id;
		$res = $GLOBALS['db']->query($sql);
		if($res){
			echo 'true';
		}else{
			echo 'false';
		}
	}
}

/* 退出会员中心 */
elseif ($action == 'logout')
{
    if ((!isset($back_act)|| empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
    {
        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
    }

    $user->logout();
    $ucdata = empty($user->ucdata)? "" : $user->ucdata;
    show_message($_LANG['logout'] . $ucdata, array($_LANG['back_up_page'], $_LANG['back_home_lnk']), array($back_act, 'index.php'), 'info');
}

/* 个人资料页面 */
elseif ($action == 'profile')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $user_info = get_profile($user_id);

	$user_info['phone'] = str_replace(substr($user_info['mobile_phone'],3,5),'*****',$user_info['mobile_phone']);
	$user_info['truename'] = empty($user_info['realname'])?0:substr($user_info['realname'],0,3).'****';
	$user_info['idcard'] = empty($user_info['idcard'])?0:str_replace(substr($user_info['idcard'],4,strlen($user_info['idcard'])-8),'**********',$user_info['idcard']);
	$user_info['card'] = empty($user_info['card'])?0:substr($user_info['card'],0,4).'**********'.substr($user_info['card'],-4);
	$user_info['user_head_img'] = empty($user_info['user_head_img'])?'./images/user_head_img.jpg':$user_info['user_head_img'];
	
	/* 取出注册扩展字段 */
    /*$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);

    $sql = 'SELECT reg_field_id, content ' .
           'FROM ' . $ecs->table('reg_extend_info') .
           " WHERE user_id = $user_id";
    $extend_info_arr = $db->getAll($sql);

    $temp_arr = array();
    foreach ($extend_info_arr AS $val)
    {
        $temp_arr[$val['reg_field_id']] = $val['content'];
    }

    foreach ($extend_info_list AS $key => $val)
    {
        switch ($val['id'])
        {
            case 1:     $extend_info_list[$key]['content'] = $user_info['msn']; break;
            case 2:     $extend_info_list[$key]['content'] = $user_info['qq']; break;
            case 3:     $extend_info_list[$key]['content'] = $user_info['office_phone']; break;
            case 4:     $extend_info_list[$key]['content'] = $user_info['home_phone']; break;
            case 5:     $extend_info_list[$key]['content'] = $user_info['mobile_phone']; break;
            default:    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']] ;
        }
    }

    $smarty->assign('extend_info_list', $extend_info_list);

    /* 密码提示问题 */
    //$smarty->assign('passwd_questions', $_LANG['passwd_questions']);

    $smarty->assign('profile', $user_info);
    $smarty->display('user_transaction.dwt');
}

/* 个人头像的更改*/
elseif ($action == 'user_head_img'){
	include_once(ROOT_PATH . 'includes/lib_base.php');
	include_once(ROOT_PATH . 'includes/lib_transaction.php');
	
	 $type = $_FILES['filename']['type'];	
	 $name = $_FILES['filename']['name'];
	 $size = $_FILES['filename']['size'];

	 //判断图片的大小与格式
	 $imgarray = array('image/jpq','image/png','image/jpeg','image/gif');
	 if(!in_array($type,$imgarray)){
	 	show_message($_LANG['userhead_img_fail'], $_LANG['back_page_up'], 'user.php?act=profile', 'info');
	 }elseif($size > 1024*1024*2){
	 	show_message($_LANG['userhead_imgsize_fail'], $_LANG['back_page_up'], 'user.php?act=profile', 'info');
	 }
	 /* 创建当月目录 */
	 $dir = date('Ym');
	 $path = './images/'.$dir.'/head_img';
	 if(!file_exists($path)){
	 	make_dir($path);
	 }
	 /* 确定文件名*/
	$img_name = $path.'/'.gmtime().$name;
	
	delete_head_img($user_id);	//删除原有的头像
	
	 if(move_uploaded_file($_FILES['filename']['tmp_name'],$img_name)){
	 	//对用户信息进行更改
	 	if(update_userhead_img($user_id,$img_name)){
	 		show_message($_LANG['userhead_upload_success'], $_LANG['back_page_up'], 'user.php?act=profile', 'info');
	 	}
	 }else{
	 	show_message($_LANG['userhead_upload_fail'], $_LANG['back_page_up'], 'user.php?act=profile', 'info');
	 }
	 
}

/* 修改个人资料的处理 */
elseif ($action == 'act_edit_profile')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $birthday = trim($_POST['birthdayYear']) .'-'. trim($_POST['birthdayMonth']) .'-'.
    trim($_POST['birthdayDay']);
    $email = trim($_POST['email']);
    $other['msn'] = $msn = isset($_POST['extend_field1']) ? trim($_POST['extend_field1']) : '';
    $other['qq'] = $qq = isset($_POST['extend_field2']) ? trim($_POST['extend_field2']) : '';
    $other['office_phone'] = $office_phone = isset($_POST['extend_field3']) ? trim($_POST['extend_field3']) : '';
    $other['home_phone'] = $home_phone = isset($_POST['extend_field4']) ? trim($_POST['extend_field4']) : '';
    $other['mobile_phone'] = $mobile_phone = isset($_POST['extend_field5']) ? trim($_POST['extend_field5']) : '';
    $sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
    $passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';

    /* 更新用户扩展字段的数据 */
    $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
    $fields_arr = $db->getAll($sql);

    foreach ($fields_arr AS $val)       //循环更新扩展用户信息
    {
        $extend_field_index = 'extend_field' . $val['id'];
        if(isset($_POST[$extend_field_index]))
        {
            $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]);
            $sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            if ($db->getOne($sql))      //如果之前没有记录，则插入
            {
                $sql = 'UPDATE ' . $ecs->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            }
            else
            {
                $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
            }
            $db->query($sql);
        }
    }

    /* 写入密码提示问题和答案 */
    if (!empty($passwd_answer) && !empty($sel_question))
    {
        $sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
        $db->query($sql);
    }

    if (!empty($office_phone) && !preg_match( '/^[\d|\_|\-|\s]+$/', $office_phone ) )
    {
        show_message($_LANG['passport_js']['office_phone_invalid']);
    }
    if (!empty($home_phone) && !preg_match( '/^[\d|\_|\-|\s]+$/', $home_phone) )
    {
         show_message($_LANG['passport_js']['home_phone_invalid']);
    }
    if (!is_email($email))
    {
        show_message($_LANG['msg_email_format']);
    }
    if (!empty($msn) && !is_email($msn))
    {
         show_message($_LANG['passport_js']['msn_invalid']);
    }
    if (!empty($qq) && !preg_match('/^\d+$/', $qq))
    {
         show_message($_LANG['passport_js']['qq_invalid']);
    }
    if (!empty($mobile_phone) && !preg_match('/^[\d-\s]+$/', $mobile_phone))
    {
        show_message($_LANG['passport_js']['mobile_phone_invalid']);
    }


    $profile  = array(
        'user_id'  => $user_id,
        'email'    => isset($_POST['email']) ? trim($_POST['email']) : '',
        'sex'      => isset($_POST['sex'])   ? intval($_POST['sex']) : 0,
        'birthday' => $birthday,
        'other'    => isset($other) ? $other : array()
        );


    if (edit_profile($profile))
    {
        show_message($_LANG['edit_profile_success'], $_LANG['profile_lnk'], 'user.php?act=profile', 'info');
    }
    else
    {
        if ($user->error == ERR_EMAIL_EXISTS)
        {
            $msg = sprintf($_LANG['email_exist'], $profile['email']);
        }
        else
        {
            $msg = $_LANG['edit_profile_failed'];
        }
        show_message($msg, '', '', 'info');
    }
}

/* 密码找回-->修改密码界面 */
elseif ($action == 'get_password')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    if (isset($_GET['code']) && isset($_GET['uid'])) //从邮件处获得的act
    {
        $code = trim($_GET['code']);
        $uid  = intval($_GET['uid']);

        /* 判断链接的合法性 */
        $user_info = $user->get_profile_by_id($uid);
        if (empty($user_info) || ($user_info && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) != $code))
        {
            show_message($_LANG['parm_error'], $_LANG['back_home_lnk'], './', 'info');
        }

        $smarty->assign('uid',    $uid);
        $smarty->assign('code',   $code);
        $smarty->assign('action', 'reset_password');
        $smarty->display('user_passport.dwt');
    }
    else
    {
        //显示用户名和email表单
        $smarty->display('user_passport.dwt');
    }
}

/* 密码找回-->输入用户名界面 */
elseif ($action == 'qpassword_name')
{
    //显示输入要找回密码的账号表单
    $smarty->display('user_passport.dwt');
}

/* 密码找回-->根据手机号*/
elseif ($action == 'act_editlogin_userphone'){
	include_once(ROOT_PATH . 'includes/lib_passport.php');
	
	$username = trim($_POST['getpwphone_user']);
	$phone = trim($_POST['getpwphone_phone']);
	$verify = trim($_POST['getpw_verify']);
	$newpw = trim($_POST['newpw']);
	$newpwconfirm = trim($_POST['newpwconfirm']);
	
	if(empty($username) || empty($phone) || empty($verify) || empty($newpw) || empty($newpwconfirm)){
		show_message($_LANG['wrong_submit_info'], $_LANG['back_home_lnk'], './', 'info');
	}
	
	//判断账号和手机号是否匹配
	$user_info = $user->get_user_info($username);
	
	if($user_info && $user_info['mobile_phone'] == $phone){
		if ($user->edit_user(array('username'=> ($user_info['user_name']), 'password'=>$newpw)))
        {
			$sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0' WHERE user_id= '".$user_id."'";
			$db->query($sql);
            $user->logout();
            show_message($_LANG['edit_password_success'], $_LANG['relogin_lnk'], 'user.php?act=login', 'info');
        }
        else
        {
            show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
        }
	}else{
        //用户名与手机号不匹配
        show_message($_LANG['username_no_phone'], $_LANG['back_page_up'], '', 'info');
    }
}

/* 密码找回-->根据注册用户名取得密码提示问题界面 */
elseif ($action == 'get_passwd_question')
{
    if (empty($_POST['user_name']))
    {
        show_message($_LANG['no_passwd_question'], $_LANG['back_home_lnk'], './', 'info');
    }
    else
    {
        $user_name = trim($_POST['user_name']);
    }

    //取出会员密码问题和答案
    $sql = 'SELECT user_id, user_name, passwd_question, passwd_answer FROM ' . $ecs->table('users') . " WHERE user_name = '" . $user_name . "'";
    $user_question_arr = $db->getRow($sql);

    //如果没有设置密码问题，给出错误提示
    if (empty($user_question_arr['passwd_answer']))
    {
        show_message($_LANG['no_passwd_question'], $_LANG['back_home_lnk'], './', 'info');
    }

    $_SESSION['temp_user'] = $user_question_arr['user_id'];  //设置临时用户，不具有有效身份
    $_SESSION['temp_user_name'] = $user_question_arr['user_name'];  //设置临时用户，不具有有效身份
    $_SESSION['passwd_answer'] = $user_question_arr['passwd_answer'];   //存储密码问题答案，减少一次数据库访问

    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }

    $smarty->assign('passwd_question', $_LANG['passwd_questions'][$user_question_arr['passwd_question']]);
    $smarty->display('user_passport.dwt');
}

/* 密码找回-->根据提交的密码答案进行相应处理 */
elseif ($action == 'check_answer')
{
    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
        }

        /* 检查验证码 */
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
        }
    }

    if (empty($_POST['passwd_answer']) || $_POST['passwd_answer'] != $_SESSION['passwd_answer'])
    {
        show_message($_LANG['wrong_passwd_answer'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'info');
    }
    else
    {
        $_SESSION['user_id'] = $_SESSION['temp_user'];
        $_SESSION['user_name'] = $_SESSION['temp_user_name'];
        unset($_SESSION['temp_user']);
        unset($_SESSION['temp_user_name']);
        $smarty->assign('uid',    $_SESSION['user_id']);
        $smarty->assign('action', 'reset_password');
        $smarty->display('user_passport.dwt');
    }
}

/* 发送密码修改确认邮件 */
elseif ($action == 'send_pwd_email')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    /* 初始化会员用户名和邮件地址 */
    $user_name = !empty($_POST['getpwemail_user']) ? trim($_POST['getpwemail_user']) : '';
    $email     = !empty($_POST['getpwemail_email'])     ? trim($_POST['getpwemail_email'])     : '';

    //用户名和邮件地址是否匹配
    $user_info = $user->get_user_info($user_name);
	
    if ($user_info && $user_info['email'] == $email)
    {
        //生成code
         //$code = md5($user_info[0] . $user_info[1]);

        $code = md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']);
        
        //发送邮件的函数
        if (send_pwd_email($user_info['user_id'], $user_name, $email, $code))
        {
            show_message($_LANG['send_success'] . $email, $_LANG['back_home_lnk'], './', 'info');
        }
        else
        {
            //发送邮件出错
            show_message($_LANG['fail_send_password'], $_LANG['back_page_up'], './', 'info');
        }
    }
    else
    {
        //用户名与邮件地址不匹配
        show_message($_LANG['username_no_email'], $_LANG['back_page_up'], '', 'info');
    }
}

/* 重置新密码 */
elseif ($action == 'reset_password')
{
    //显示重置密码的表单
    $smarty->display('user_passport.dwt');
}

/* 修改会员账号密码 */
elseif ($action == 'act_edit_password')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : null;
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $user_id      = isset($_POST['uid'])  ? intval($_POST['uid']) : $user_id;
    $code         = isset($_POST['code']) ? trim($_POST['code'])  : '';

    if (strlen($new_password) < 6){
        show_message($_LANG['passport_js']['password_shorter']);
    }
	$user_name = $_SESSION['user_name'];
	
    if ($user->edit_user(array('username'=>$user_name,'password'=>$new_password))){
		$sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0' WHERE user_id= '".$user_id."'";
		$db->query($sql);
        $user->logout();
        show_message($_LANG['edit_password_success'], $_LANG['relogin_lnk'], 'user.php?act=login', 'info');
    }
    else
    {
        show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
    }
    
}

/* 修改会员提现密码 */
elseif ($action == 'act_edit_paypassword')
{
	include_once(ROOT_PATH . 'includes/lib_passport.php');

	$old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : null;
	$new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
	$user_id      = isset($_POST['uid'])  ? intval($_POST['uid']) : $user_id;
	$code         = isset($_POST['code']) ? trim($_POST['code'])  : '';

	if (strlen($new_password) < 6)
	{
		show_message($_LANG['passport_js']['password_shorter']);
	}

	$user_info = $user->get_profile_by_id($user_id); //论坛记录

	if (($user_info && (!empty($code) && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) == $code)) || ($_SESSION['user_id']>0 && $_SESSION['user_id'] == $user_id && $user->check_user($_SESSION['user_name'], $old_password)))
	{

		if ($user->edit_user(array('username'=> (empty($code) ? $_SESSION['user_name'] : $user_info['user_name']), 'old_password'=>$old_password, 'paypassword'=>$new_password), empty($code) ? 0 : 1))
		{
			$sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0' WHERE user_id= '".$user_id."'";
			$db->query($sql);
			$user->logout();
			show_message($_LANG['edit_password_success'], $_LANG['relogin_lnk'], 'user.php?act=login', 'info');
		}
		else
		{
			show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
		}
	}
	else
	{
		show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
	}

}

/* 添加一个红包 */
elseif ($action == 'act_add_bonus')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $bouns_sn = isset($_POST['bonus_sn']) ? intval($_POST['bonus_sn']) : '';

    if (add_bonus($user_id, $bouns_sn))
    {
        show_message($_LANG['add_bonus_sucess'], $_LANG['back_up_page'], 'user.php?act=bonus', 'info');
    }
    else
    {
        $err->show($_LANG['back_up_page'], 'user.php?act=bonus');
    }
}

/* 查看订单列表 */
elseif ($action == 'order_list')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'");

    $pager  = get_pager('user.php', array('act' => $action), $record_count, $page);

    $orders = get_user_orders($user_id, $pager['size'], $pager['start']);
    $merge  = get_user_merge($user_id);

    $smarty->assign('merge',  $merge);
    $smarty->assign('pager',  $pager);
    $smarty->assign('orders', $orders);
    $smarty->display('user_transaction.dwt');
}

/* 查看订单详情 */
elseif ($action == 'order_detail')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_payment.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    /* 订单详情 */
    $order = get_order_detail($order_id, $user_id);

    if ($order === false)
    {
        $err->show($_LANG['back_home_lnk'], './');

        exit;
    }

    /* 是否显示添加到购物车 */
    if ($order['extension_code'] != 'group_buy' && $order['extension_code'] != 'exchange_goods')
    {
        $smarty->assign('allow_to_cart', 1);
    }

    /* 订单商品 */
    $goods_list = order_goods($order_id);
    foreach ($goods_list AS $key => $value)
    {
        $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
        $goods_list[$key]['goods_price']  = price_format($value['goods_price'], false);
        $goods_list[$key]['subtotal']     = price_format($value['subtotal'], false);
    }

     /* 设置能否修改使用余额数 */
    if ($order['order_amount'] > 0)
    {
        if ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED)
        {
            $user = user_info($order['user_id']);
            if ($user['user_money'] + $user['credit_line'] > 0)
            {
                $smarty->assign('allow_edit_surplus', 1);
                $smarty->assign('max_surplus', sprintf($_LANG['max_surplus'], $user['user_money']));
            }
        }
    }

    /* 未发货，未付款时允许更换支付方式 */
    if ($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED)
    {
        $payment_list = available_payment_list(false, 0, true);

        /* 过滤掉当前支付方式和余额支付方式 */
        if(is_array($payment_list))
        {
            foreach ($payment_list as $key => $payment)
            {
                if ($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance')
                {
                    unset($payment_list[$key]);
                }
            }
        }
        $smarty->assign('payment_list', $payment_list);
    }

    /* 订单 支付 配送 状态语言项 */
    $order['order_status'] = $_LANG['os'][$order['order_status']];
    $order['pay_status'] = $_LANG['ps'][$order['pay_status']];
    $order['shipping_status'] = $_LANG['ss'][$order['shipping_status']];

    $smarty->assign('order',      $order);
    $smarty->assign('goods_list', $goods_list);
    $smarty->display('user_transaction.dwt');
}

/* 取消订单 */
elseif ($action == 'cancel_order')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if (cancel_order($order_id, $user_id))
    {
        ecs_header("Location: user.php?act=order_list\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
    }
}

/* 收货地址列表界面*/
elseif ($action == 'address_list')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');
    $smarty->assign('lang',  $_LANG);

    /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
    $smarty->assign('country_list',       get_regions());
    $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

    /* 获得用户所有的收货人信息 */
    $consignee_list = get_consignee_list($_SESSION['user_id']);

    if (count($consignee_list) < 5 && $_SESSION['user_id'] > 0)
    {
        /* 如果用户收货人信息的总数小于5 则增加一个新的收货人信息 */
        $consignee_list[] = array('country' => $_CFG['shop_country'], 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '');
    }

    $smarty->assign('consignee_list', $consignee_list);

    //取得国家列表，如果有收货人列表，取得省市区列表
    foreach ($consignee_list AS $region_id => $consignee)
    {
        $consignee['country']  = isset($consignee['country'])  ? intval($consignee['country'])  : 0;
        $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
        $consignee['city']     = isset($consignee['city'])     ? intval($consignee['city'])     : 0;

        $province_list[$region_id] = get_regions(1, $consignee['country']);
        $city_list[$region_id]     = get_regions(2, $consignee['province']);
        $district_list[$region_id] = get_regions(3, $consignee['city']);
    }

    /* 获取默认收货ID */
    $address_id  = $db->getOne("SELECT address_id FROM " .$ecs->table('users'). " WHERE user_id='$user_id'");

    //赋值于模板
    $smarty->assign('real_goods_count', 1);
    $smarty->assign('shop_country',     $_CFG['shop_country']);
    $smarty->assign('shop_province',    get_regions(1, $_CFG['shop_country']));
    $smarty->assign('province_list',    $province_list);
    $smarty->assign('address',          $address_id);
    $smarty->assign('city_list',        $city_list);
    $smarty->assign('district_list',    $district_list);
    $smarty->assign('currency_format',  $_CFG['currency_format']);
    $smarty->assign('integral_scale',   $_CFG['integral_scale']);
    $smarty->assign('name_of_region',   array($_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']));

    $smarty->display('user_transaction.dwt');
}

/* 添加/编辑收货地址的处理 */
elseif ($action == 'act_edit_address')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');
    $smarty->assign('lang', $_LANG);

    $address = array(
        'user_id'    => $user_id,
        'address_id' => intval($_POST['address_id']),
        'country'    => isset($_POST['country'])   ? intval($_POST['country'])  : 0,
        'province'   => isset($_POST['province'])  ? intval($_POST['province']) : 0,
        'city'       => isset($_POST['city'])      ? intval($_POST['city'])     : 0,
        'district'   => isset($_POST['district'])  ? intval($_POST['district']) : 0,
        'address'    => isset($_POST['address'])   ? compile_str(trim($_POST['address']))    : '',
        'consignee'  => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee']))  : '',
        'email'      => isset($_POST['email'])     ? compile_str(trim($_POST['email']))      : '',
        'tel'        => isset($_POST['tel'])       ? compile_str(make_semiangle(trim($_POST['tel']))) : '',
        'mobile'     => isset($_POST['mobile'])    ? compile_str(make_semiangle(trim($_POST['mobile']))) : '',
        'best_time'  => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time']))  : '',
        'sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '',
        'zipcode'       => isset($_POST['zipcode'])       ? compile_str(make_semiangle(trim($_POST['zipcode']))) : '',
        );

    if (update_address($address))
    {
        show_message($_LANG['edit_address_success'], $_LANG['address_list_lnk'], 'user.php?act=address_list');
    }
}

/* 删除收货地址 */
elseif ($action == 'drop_consignee')
{
    include_once('includes/lib_transaction.php');

    $consignee_id = intval($_GET['id']);

    if (drop_consignee($consignee_id))
    {
        ecs_header("Location: user.php?act=address_list\n");
        exit;
    }
    else
    {
        show_message($_LANG['del_address_false']);
    }
}

/* 显示收藏商品列表 */
elseif ($action == 'collection_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('collect_goods').
                                " WHERE user_id='$user_id' ORDER BY add_time DESC");

    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);
    $smarty->assign('pager', $pager);
    $smarty->assign('goods_list', get_collection_goods($user_id, $pager['size'], $pager['start']));
    $smarty->assign('url',        $ecs->url());
    $lang_list = array(
        'UTF8'   => $_LANG['charset']['utf8'],
        'GB2312' => $_LANG['charset']['zh_cn'],
        'BIG5'   => $_LANG['charset']['zh_tw'],
    );
    $smarty->assign('lang_list',  $lang_list);
    $smarty->assign('user_id',  $user_id);
    $smarty->display('user_clips.dwt');
}

/* 删除收藏的商品 */
elseif ($action == 'delete_collection')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $collection_id = isset($_GET['collection_id']) ? intval($_GET['collection_id']) : 0;

    if ($collection_id > 0)
    {
        $db->query('DELETE FROM ' .$ecs->table('collect_goods'). " WHERE rec_id='$collection_id' AND user_id ='$user_id'" );
    }

    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}

/* 添加关注商品 */
elseif ($action == 'add_to_attention')
{
    $rec_id = (int)$_GET['rec_id'];
    if ($rec_id)
    {
        $db->query('UPDATE ' .$ecs->table('collect_goods'). "SET is_attention = 1 WHERE rec_id='$rec_id' AND user_id ='$user_id'" );
    }
    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}
/* 取消关注商品 */
elseif ($action == 'del_attention')
{
    $rec_id = (int)$_GET['rec_id'];
    if ($rec_id)
    {
        $db->query('UPDATE ' .$ecs->table('collect_goods'). "SET is_attention = 0 WHERE rec_id='$rec_id' AND user_id ='$user_id'" );
    }
    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}
/* 显示留言列表 */
elseif ($action == 'message_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
    $order_info = array();

    /* 获取用户留言的数量 */
    if ($order_id)
    {
        $sql = "SELECT COUNT(*) FROM " .$ecs->table('feedback').
                " WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id'";
        $order_info = $db->getRow("SELECT * FROM " . $ecs->table('order_info') . " WHERE order_id = '$order_id' AND user_id = '$user_id'");
        $order_info['url'] = 'user.php?act=order_detail&order_id=' . $order_id;
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " .$ecs->table('feedback').
           " WHERE parent_id = 0 AND user_id = '$user_id' AND user_name = '" . $_SESSION['user_name'] . "' AND order_id=0";
    }

    $record_count = $db->getOne($sql);
    $act = array('act' => $action);

    if ($order_id != '')
    {
        $act['order_id'] = $order_id;
    }

    $pager = get_pager('user.php', $act, $record_count, $page, 5);

    $smarty->assign('message_list', get_message_list($user_id, $_SESSION['user_name'], $pager['size'], $pager['start'], $order_id));
    $smarty->assign('pager',        $pager);
    $smarty->assign('order_info',   $order_info);
    $smarty->display('user_clips.dwt');
}

/* 显示评论列表 */
elseif ($action == 'comment_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取用户留言的数量 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('comment').
           " WHERE parent_id = 0 AND user_id = '$user_id'";
    $record_count = $db->getOne($sql);
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page, 5);

    $smarty->assign('comment_list', get_comment_list($user_id, $pager['size'], $pager['start']));
    $smarty->assign('pager',        $pager);
    $smarty->display('user_clips.dwt');
}

/* 添加我的留言 */
elseif ($action == 'act_add_message')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $message = array(
        'user_id'     => $user_id,
        'user_name'   => $_SESSION['user_name'],
        'user_email'  => $_SESSION['email'],
        'msg_type'    => isset($_POST['msg_type']) ? intval($_POST['msg_type'])     : 0,
        'msg_title'   => isset($_POST['msg_title']) ? trim($_POST['msg_title'])     : '',
        'msg_content' => isset($_POST['msg_content']) ? trim($_POST['msg_content']) : '',
        'order_id'=>empty($_POST['order_id']) ? 0 : intval($_POST['order_id']),
        'upload'      => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (!isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none')
         ? $_FILES['message_img'] : array()
     );

    if (add_message($message))
    {
        show_message($_LANG['add_message_success'], $_LANG['message_list_lnk'], 'user.php?act=message_list&order_id=' . $message['order_id'],'info');
    }
    else
    {
        $err->show($_LANG['message_list_lnk'], 'user.php?act=message_list');
    }
}

/* 标签云列表 */
elseif ($action == 'tag_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $good_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $smarty->assign('tags',      get_user_tags($user_id));
    $smarty->assign('tags_from', 'user');
    $smarty->display('user_clips.dwt');
}

/* 删除标签云的处理 */
elseif ($action == 'act_del_tag')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $tag_words = isset($_GET['tag_words']) ? trim($_GET['tag_words']) : '';
    delete_tag($tag_words, $user_id);

    ecs_header("Location: user.php?act=tag_list\n");
    exit;

}

/* 显示绑定支付宝页面*/
elseif ($action == 'bang_payment')
{
	$smarty->display('user_bang.dwt');
}

/* 显示散标投资列表 */
elseif ($action == 'booking_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    
    $catstatus = isset($catstatus)?$catstatus:0;
    
    
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$pay_status = PS_PAYED;
	$startsize = 0;
    
    /* 我的债券 */
	$sql = "SELECT g.goods_number,g.goods_weight,g.add_time " .
			"FROM " .$ecs->table('goods'). " AS g, " .
			$ecs->table('order_goods') . " AS o, " .$ecs->table('category') . " AS c " .
			"WHERE g.goods_id = o.goods_id AND c.cat_id = g.cat_id AND c.is_standalone = 0 AND o.pay_status = ".$pay_status." AND o.user_id = '$user_id'";
	$record_count = $db->getAll($sql);
	
	/* 声明数组*/
	$pagebid = array();
	$pagerecover = array();
	$pagebond = array();
	
	foreach($record_count as $countinfo){
		if($countinfo['goods_weight'] >= gmtime() && gmtime() >=$countinfo['add_time']){
			$pagebid[] = array(
				'add_time'	=>	$countinfo['add_time']
			);
		}elseif(gmtime()>$countinfo['goods_weight'] && gmtime()<$countinfo['goods_number']){
			$pagerecover[] = array(
				'add_time'	=>	$countinfo['add_time']
			);
		}elseif(gmtime()>$countinfo['goods_number']){
			$pagebond[] = array(
				'add_time'	=>	$countinfo['add_time']
			);
		}
	}
	
	
	$pager_bid = get_pager('user.php', array('act' => 'on_invest_ajax'), count($pagebid), $page, $size);
	$pager_recover = get_pager('user.php', array('act' => 'callback_invest_ajax'), count($pagerecover), $page, $size);
	$pager_bond = get_pager('user.php', array('act' => 'over_invest_ajax'), count($pagebond), $page, $size);
	
	$info = get_booking_list($user_id, $catstatus);
	$bid_list_abs = array_slice($info['bid_list_abs'],0,5);
	$recover_list_abs = array_slice($info['bid_list_abs'],0,5);
	$bond_list_abs = array_slice($info['bid_list_abs'],0,5);
	
	$smarty->assign('bid_list_abs',$info['bid_list_abs']);
	$smarty->assign('recover_list_abs',$info['recover_list_abs']);
	$smarty->assign('bond_list_abs',$info['bond_list_abs']);
	$smarty->assign('pager_bid',$pager_bid);
	$smarty->assign('pager_recover',$pager_recover);
	$smarty->assign('pager_bond',$pager_bond);
	
    $smarty->display('user_clips.dwt');
}

/* 散标投资回收中的债券 AJAX分页*/
elseif ($action == 'callback_invest_ajax'){
	$page = isset($_GET['page'])?$_GET['page']:1;
	$catstatus = isset($catstatus)?$catstatus:0;
	$pay_status = PS_PAYED;
	$size = 5;
	$startpage = ($page-1)*$size;
	
	/* 调用分页*/
	$pagestr = ajax_invest_pageinfo($action, $user_id, $page, $size, $pay_status);
	
	$info = get_booking_list($user_id, $catstatus);
	$recover_list_abs = array_slice($info['recover_list_abs'],$startpage,$size);
	
	$str = '<thead><tr class="capitaltr"><th>债券ID</th><th>投资金额(元)</th><th>投资期限(天)</th><th>年化利率(%)</th><th>待收本息(元)</th><th>计息日期</th><tr></thead><tbody>';
	
	foreach($recover_list_abs as $key=>$val){
		if($key%2==0){
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td>'.$val["market_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["time_start"].'</td><tr>';
		}else{
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td>'.$val["market_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["time_start"].'</td><tr>';
		}
	}
	
	echo $str.'</tbody></table>'.$pagestr;
}

/* 散标投资已结清的债券 AJAX分页*/
elseif ($action == 'over_invest_ajax'){
	include_once(ROOT_PATH . 'includes/lib_clips.php');

	$page = isset($_POST['page'])?$_POST['page']:1;
	$catstatus = isset($catstatus)?$catstatus:0;
	$pay_status = PS_PAYED;
	$size = 5;
	$startpage = ($page-1)*$size;
	
	/* 我的债券 */
	$sql = "SELECT g.goods_number,g.goods_weight,g.add_time " .
			"FROM " .$ecs->table('goods'). " AS g, " .
			$ecs->table('order_goods') . " AS o, " .$ecs->table('category') . " AS c " .
			"WHERE g.goods_id = o.goods_id AND c.cat_id = g.cat_id AND c.is_standalone != 0 AND o.pay_status = ".$pay_status." AND o.user_id = '$user_id'";
	$record_count = $db->getAll($sql);
	
	/* 调用分页*/
	$pagestr = ajax_invest_pageinfo($action, $user_id, $page, $size, $pay_status);
	
	$info = get_booking_list($user_id, $catstatus);
	$bond_list_abs = array_slice($info['bond_list_abs'],$startpage,$size);
	
	$str = '<thead><tr class="capitaltr"><th>债券ID</th><th>投资金额(元)</th><th>投资期限(天)</th><th>年化利率(%)</th><th>待收本息(元)</th><th>结清日期</th><tr></thead><tbody>';
	
	foreach($bond_list_abs as $key=>$val){
		if($key%2==0){
			$str.='<tr class="capitaltr" style="background:#EDEBEC;"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td style="color:red;">'.$val["market_price"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["time_end"].'</td><tr>';
		}else{
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td style="color:red;">'.$val["market_price"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["time_end"].'</td><tr>';
		}
	}
	
	echo $str.'</tbody></table>'.$pagestr;
}

/* 散标投资投标中的债券 AJAX分页*/
elseif ($action == 'on_invest_ajax'){
	$page = isset($_GET['page'])?$_GET['page']:1;
	$catstatus = isset($catstatus)?$catstatus:0;
	$pay_status = PS_PAYED;
	$size = 5;
	$startpage = ($page-1)*$size;
	
	/* 我的债券 */
	$sql = "SELECT g.goods_number,g.goods_weight,g.add_time " .
			"FROM " .$ecs->table('goods'). " AS g, " .
			$ecs->table('order_goods') . " AS o, " .$ecs->table('category') . " AS c " .
			"WHERE g.goods_id = o.goods_id AND c.cat_id = g.cat_id AND c.is_standalone != 0 AND o.pay_status = ".$pay_status." AND o.user_id = '$user_id'";
	$record_count = $db->getAll($sql);
	
	/* 调用分页*/
	$pagestr = ajax_invest_pageinfo($action, $user_id, $page, $size, $pay_status);
	
	$info = get_booking_list($user_id, $catstatus);
	$bid_list_abs = array_slice($info['bid_list_abs'],$startpage,$size);
	
	$str = '<thead><tr class="capitaltr"><th>债券ID</th><th>投资金额(元)</th><th>年化利率(%)</th><th>投资期限(天)</th><th>待收本息(元)</th><th>进度</th><tr></thead><tbody>';
	
	foreach($recover_list_abs as $key=>$val){
		if($key%2==0){
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td style="color:red;">'.$val["market_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["invest_temp"].'%</td><tr>';
		}else{
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td style="color:red;">'.$val["market_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["invest_temp"].'%</td><tr>';
		}
	}
	
	echo $str.'</tbody></table>'.$pagestr;
}

/* 显示聚能赚定期列表*/
elseif ($action == 'booking_list_month')
{
	include_once(ROOT_PATH . 'includes/lib_clips.php');

	$catstatus = isset($catstatus)?$catstatus:1;


	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$pay_status = PS_PAYED;
	$startsize = 0;
	$size = 5;

	/* 我的债券 */
	$sql = "SELECT g.goods_number,g.goods_weight,g.add_time " .
			"FROM " .$ecs->table('goods'). " AS g, " .
			$ecs->table('order_goods') . " AS o, " .$ecs->table('category') . " AS c " .
			"WHERE g.goods_id = o.goods_id AND c.cat_id = g.cat_id AND c.is_standalone != 0 AND o.pay_status = ".$pay_status." AND o.user_id = '$user_id'";
	$record_count = $db->getAll($sql);
	
	/* 声明数组*/
	$pagebid = array();
	$pagerecover = array();
	$pagebond = array();
	
	foreach($record_count as $countinfo){
		if($countinfo['goods_weight'] >= gmtime() && gmtime() >=$countinfo['add_time']){
			$pagebid[] = array(
				'add_time'	=>	$countinfo['add_time']
			);
		}elseif(gmtime()>$countinfo['goods_weight'] && gmtime()<$countinfo['goods_number']){
			$pagerecover[] = array(
				'add_time'	=>	$countinfo['add_time']
			);
		}elseif(gmtime()>$countinfo['goods_number']){
			$pagebond[] = array(
				'add_time'	=>	$countinfo['add_time']
			);
		}
	}
	
	
	$pager_bid = get_pager('user.php', array('act' => 'on_fixinvest_ajax'), count($pagebid), $page, $size);
	$pager_recover = get_pager('user.php', array('act' => 'callback_fixinvest_ajax'), count($pagerecover), $page, $size);
	$pager_bond = get_pager('user.php', array('act' => 'over_fixinvest_ajax'), count($pagebond), $page, $size);
	
	$info = get_booking_list($user_id, $catstatus);
	
	$bid_list_abs = array_slice($info['bid_list_abs'],0,$size);
	$recover_list_abs = array_slice($info['recover_list_abs'],0,$size);
	$bond_list_abs = array_slice($info['bond_list_abs'],0,$size);
	
	$smarty->assign('bid_list_abs',$bid_list_abs);
	$smarty->assign('recover_list_abs',$recover_list_abs);
	$smarty->assign('bond_list_abs',$bond_list_abs);
	$smarty->assign('pager_bid',$pager_bid);
	$smarty->assign('pager_recover',$pager_recover);
	$smarty->assign('pager_bond',$pager_bond);
	
	$smarty->display('user_clips.dwt');
	
}

/* 聚能赚定期回收中的债券 AJAX分页*/
elseif ($action == 'callback_fixinvest_ajax'){
	$page = isset($_GET['page'])?$_GET['page']:1;
	$catstatus = isset($catstatus)?$catstatus:1;
	$pay_status = PS_PAYED;
	$size = 5;
	$startpage = ($page-1)*$size;
	
	/* 调用分页*/
	$pagestr = ajax_invest_pageinfo($action, $user_id, $page, $size, $pay_status);
	
	$info = get_booking_list($user_id, $catstatus);
	$recover_list_abs = array_slice($info['recover_list_abs'],$startpage,$size);
	
	$str = '<thead><tr class="capitaltr"><th>债券ID</th><th>投资金额(元)</th><th>投资期限(天)</th><th>年化利率(%)</th><th>待收本息(元)</th><th>计息日期</th><tr></thead><tbody>';
	
	foreach($recover_list_abs as $key=>$val){
		if($key%2==0){
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td>'.$val["market_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["time_start"].'</td><tr>';
		}else{
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td>'.$val["market_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["time_start"].'</td><tr>';
		}
	}
	
	echo $str.'</tbody></table>'.$pagestr;
}

/* 聚能赚定期已结清的债券 AJAX分页*/
elseif ($action == 'over_fixinvest_ajax'){

	include_once(ROOT_PATH . 'includes/lib_clips.php');

	$page = isset($_POST['page'])?$_POST['page']:1;
	$catstatus = isset($catstatus)?$catstatus:1;
	$pay_status = PS_PAYED;
	$size = 5;
	$startpage = ($page-1)*$size;
	/* 调用分页*/
	$pagestr = ajax_invest_pageinfo($action, $user_id, $page, $size, $pay_status);
	
	$info = get_booking_list($user_id, $catstatus);
	$bond_list_abs = array_slice($info['bond_list_abs'],$startpage,$size);
	
	$str = '<table><thead><tr class="capitaltr"><th>债券ID</th><th>投资金额(元)</th><th>投资期限(天)</th><th>年化利率(%)</th><th>待收本息(元)</th><th>结清日期</th><tr></thead><tbody>';
	
	foreach($bond_list_abs as $key=>$val){
		if($key%2==0){
			$str.='<tr class="capitaltr" style="background:#EDEBEC;"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td style="color:red;">'.$val["market_price"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["time_end"].'</td><tr>';
		}else{
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td style="color:red;">'.$val["market_price"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["time_end"].'</td><tr>';
		}
	}
	
	echo $str.'</tbody></table>'.$pagestr;
}

/* 聚能赚定期投标中的债券 AJAX分页*/
elseif ($action == 'on_fixinvest_ajax'){
	$page = isset($_GET['page'])?$_GET['page']:1;
	$catstatus = isset($catstatus)?$catstatus:1;
	$pay_status = PS_PAYED;
	$size = 5;
	$startpage = ($page-1)*$size;
	
	/* 调用分页*/
	$pagestr = ajax_invest_pageinfo($action, $user_id, $page, $size, $pay_status);
	
	$info = get_booking_list($user_id, $catstatus);
	$bid_list_abs = array_slice($info['bid_list_abs'],$startpage,$size);
	
	$str = '<thead><tr class="capitaltr"><th>债券ID</th><th>投资金额(元)</th><th>年化利率(%)</th><th>投资期限(天)</th><th>待收本息(元)</th><th>进度</th><tr></thead><tbody>';
	
	foreach($recover_list_abs as $key=>$val){
		if($key%2==0){
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td style="color:red;">'.$val["market_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["invest_temp"].'%</td><tr>';
		}else{
			$str.='<tr class="capitaltr"><td>'.$val["good_id"].'</td>';
			$str.='<td>'.$val["invest_price"].'</td>';
			$str.='<td style="color:red;">'.$val["market_price"].'</td>';
			$str.='<td>'.$val["invest_time"].'</td>';
			$str.='<td>'.$val["me_money"].'</td>';
			$str.='<td>'.$val["invest_temp"].'%</td><tr>';
		}
	}
	
	echo $str.'</tbody></table>'.$pagestr;
}

/* 我的贷款页面*/
elseif ($action == 'loan_list')
{
	$smarty->display('user_clips.dwt');
}

/* 添加缺货登记页面 */
elseif ($action == 'add_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $goods_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($goods_id == 0)
    {
        show_message($_LANG['no_goods_id'], $_LANG['back_page_up'], '', 'error');
    }

    /* 根据规格属性获取货品规格信息 */
    $goods_attr = '';
    if ($_GET['spec'] != '')
    {
        $goods_attr_id = $_GET['spec'];

        $attr_list = array();
        $sql = "SELECT a.attr_name, g.attr_value " .
                "FROM " . $ecs->table('goods_attr') . " AS g, " .
                    $ecs->table('attribute') . " AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_attr_id " . db_create_in($goods_attr_id);
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);
    }
    $smarty->assign('goods_attr', $goods_attr);

    $smarty->assign('info', get_goodsinfo($goods_id));
    $smarty->display('user_clips.dwt');

}

/* 添加缺货登记的处理 */
elseif ($action == 'act_add_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $booking = array(
        'goods_id'     => isset($_POST['id'])      ? intval($_POST['id'])     : 0,
        'goods_amount' => isset($_POST['number'])  ? intval($_POST['number']) : 0,
        'desc'         => isset($_POST['desc'])    ? trim($_POST['desc'])     : '',
        'linkman'      => isset($_POST['linkman']) ? trim($_POST['linkman'])  : '',
        'email'        => isset($_POST['email'])   ? trim($_POST['email'])    : '',
        'tel'          => isset($_POST['tel'])     ? trim($_POST['tel'])      : '',
        'booking_id'   => isset($_POST['rec_id'])  ? intval($_POST['rec_id']) : 0
    );

    // 查看此商品是否已经登记过
    $rec_id = get_booking_rec($user_id, $booking['goods_id']);
    if ($rec_id > 0)
    {
        show_message($_LANG['booking_rec_exist'], $_LANG['back_page_up'], '', 'error');
    }

    if (add_booking($booking))
    {
        show_message($_LANG['booking_success'], $_LANG['back_booking_list'], 'user.php?act=booking_list',
        'info');
    }
    else
    {
        $err->show($_LANG['booking_list_lnk'], 'user.php?act=booking_list');
    }
}

/* 删除缺货登记 */
elseif ($action == 'act_del_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id == 0 || $user_id == 0)
    {
        ecs_header("Location: user.php?act=booking_list\n");
        exit;
    }

    $result = delete_booking($id, $user_id);
    if ($result)
    {
        ecs_header("Location: user.php?act=booking_list\n");
        exit;
    }
}

/* 确认收货 */
elseif ($action == 'affirm_received')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if (affirm_received($order_id, $user_id))
    {
        ecs_header("Location: user.php?act=order_list\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
    }
}

/* 会员退款申请界面 */
elseif ($action == 'account_raply')
{
    $smarty->display('user_transaction.dwt');
}

/* 会员预付款界面 */
elseif ($action == 'account_deposit')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $account    = get_surplus_info($surplus_id);

    $smarty->assign('payment', get_online_payment_list(false));
    $smarty->assign('order',   $account);
    $smarty->display('user_transaction.dwt');
}

/* 会员账目明细界面 */
elseif ($action == 'account_detail')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $account_type = 'user_money';

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('account_log').
           " WHERE user_id = '$user_id'" .
           " AND $account_type <> 0 ";
    $record_count = $db->getOne($sql);

    //分页函数
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);

    //获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if (empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    //获取余额记录
    $account_log = array();
    $sql = "SELECT * FROM " . $ecs->table('account_log') .
           " WHERE user_id = '$user_id'" .
           " AND $account_type <> 0 " .
           " ORDER BY log_id DESC";
    $res = $GLOBALS['db']->selectLimit($sql, $pager['size'], $pager['start']);
    while ($row = $db->fetchRow($res))
    {
        $row['change_time'] = local_date($_CFG['date_format'], $row['change_time']);
        $row['type'] = $row[$account_type] > 0 ? $_LANG['account_inc'] : $_LANG['account_dec'];
        $row['user_money'] = price_format(abs($row['user_money']), false);
        $row['frozen_money'] = price_format(abs($row['frozen_money']), false);
        $row['rank_points'] = abs($row['rank_points']);
        $row['pay_points'] = abs($row['pay_points']);
        $row['short_change_desc'] = sub_str($row['change_desc'], 60);
        $row['amount'] = $row[$account_type];
        $account_log[] = $row;
    }

    //模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log',    $account_log);
    $smarty->assign('pager',          $pager);
    $smarty->display('user_transaction.dwt');
}

/* 会员账户充值页面*/
elseif ($action == 'account_rechanger')
{
	$sql = "select bangcardstatus,paypassword from ".$GLOBALS['ecs']->table('users').' where user_id='.$user_id;
	$withinfo = $GLOBALS['db']->getRow($sql);
	
	//体现密码与银行卡绑定的判断
	if(empty($withinfo['paypassword'])){
		show_message($_LANG['rechanger_password_fail'],$_LANG['back_up_page'], 'user.php?act=withdraw_password');
	}elseif($withinfo['bangcardstatus'] != 1){
		show_message($_LANG['withdraws_bangcard_fail'],$_LANG['back_up_page'], 'user.php?act=bangcard');
	}
	
	$smarty->display('user_transaction.dwt');
}

/* 会员账户提现页面*/
elseif ($action == 'account_withdrawals')
{
	$sql = "select u.realname,u.user_money,u.idcardstatus,u.bangcardstatus,b.cardnum from ".$GLOBALS['ecs']->table('users')." as u left join ".$GLOBALS['ecs']->table('bang_card')." as b on ".
	"u.user_id = b.user_id and b.bangstatus =1 and u.user_id =".$user_id;
	$withinfo = $GLOBALS['db']->getRow($sql);
	
	//实名与绑定卡的判断
	if(empty($withinfo)){
		show_message($_LANG['withdraws_idandcard_fail'],$_LANG['back_up_page'], 'user.php?act=auth_center');
	}elseif($withinfo['idcardstatus'] != 1){
		show_message($_LANG['withdraws_idcard_fail'],$_LANG['back_up_page'], 'user.php?act=auth_center');
	}elseif($withinfo['bangcardstatus'] != 1){
		show_message($_LANG['withdraws_bangcard_fail'],$_LANG['back_up_page'], 'user.php?act=bangcard');
	}
	
	$withinfo['name'] = str_replace(substr($withinfo['realname'],3),'*',$withinfo['realname']);
	$withinfo['cardnum'] = substr($withinfo['cardnum'],0,4).'*************'.substr($withinfo['cardnum'],-4);
	
	$smarty->assign('withinfo',$withinfo);
	$smarty->display('user_transaction.dwt');
}

/* 提现时提现密码AJAX的验证*/
elseif ($action == 'chewithdrawpw'){
	$pw = $_POST['pw'];
	if(empty($pw)){
		echo "error";exit;
	}
	$withdrawpw = $user->compile_password(array('password'=>$pw));
	
	$sql = 'SELECT user_id FROM '.$GLOBALS['ecs']->table('users').
	' WHERE user_id='.$user_id.' AND paypassword="'.$withdrawpw.'"';

	$res = $GLOBALS['db']->getOne($sql);
	
	if($res > 0){
		echo "right";exit;
	}else{
		echo "error";exit;
	}
}

/* 会员进行充值*/
elseif ($action == 'act_rechanger')
{
	include_once(ROOT_PATH . 'includes/lib_clips.php');
	include_once(ROOT_PATH . 'includes/lib_bfpay.php');
	
	$amount = trim($_POST['rechargernum']);
	$payment = trim($_POST['payment']);
	if(empty($amount) || $payment !=4){
		show_message($_LANG['recharge_no_num'],$_LANG['back_up_page'], 'user.php?act=account_rechanger', 'info');
	}
	
	if(!is_numeric($amount)){
		show_message($_LANG['no_num'],$_LANG['back_up_page'], 'user.php?act=account_rechanger', 'info');
	}
	/* 添加充值信息*/
	$surplus = array(
			'user_id'		=> $user_id,
			'change_sn'		=> "c".date('YmdHis',time()).rand(1,10000),
			'process_type'	=> 0,
			'payment_id'	=> $payment,		//连连支付
			'amount'		=> $amount
	);
	$accountid = insert_user_account($surplus, $amount);
	
	/* 查询个人信息*/
	$sql = 'SELECT u.user_id,u.realname,u.mobile_phone,u.idcard,b.cardbank,b.cardnum,a.change_sn,a.amount,a.add_time FROM'.
			$GLOBALS['ecs']->table('users').' as u,'.
			$GLOBALS['ecs']->table('user_account').' as a,'.
			$GLOBALS['ecs']->table('bang_card').' as b WHERE '.
			'u.user_id=b.user_id AND u.user_id=a.user_id AND u.user_id='.$user_id.
			' AND a.is_paid=0 ORDER BY a.id DESC';
			
	$parainfo = $GLOBALS['db']->getRow($sql);

	/* 调用支付接口进行充值*/
	$conpay = new conpay;
	$conpay->recharge_handle($parainfo);
	
	exit;
}

/* 会员进行提现*/
elseif ($action == 'act_withdrawals')
{
	include_once(ROOT_PATH . 'includes/lib_clips.php');
	include_once(ROOT_PATH . 'includes/lib_bfpay.php');
	
	$number = trim($_POST['withdrawalsnum']);
	$withdrawpw = trim($_POST['withdrawpw']);
	if(!is_numeric($number)){
		show_message($_LANG['no_num'],$_LANG['back_up_page'], 'user.php?act=account_withdrawals', 'info');
	}
	/* 判断提现密码是否一致*/
	$withdrawpw = $user->compile_password(array('password'=>$withdrawpw));
	$sql = 'SELECT user_id FROM '.$GLOBALS['ecs']->table('users').
	' WHERE user_id='.$user_id.' AND paypassword="'.$withdrawpw.'"';

	$res = $GLOBALS['db']->getOne($sql);
	if(empty($res)){
		show_message($_LANG['widthdrawpw_error'],$_LANG['back_up_page'], 'user.php?act=account_withdrawals', 'info');
	}
	/* 查询账户余额与银行卡信息*/
	$sql = "select u.user_money,u.realname,b.cardprovince,b.cardcity,b.cardbank," .
			"b.cardshop,b.cardnum from ".$GLOBALS['ecs']->table('users').
			" as u,".$GLOBALS['ecs']->table('bang_card').
			" as b where u.user_id=b.user_id AND b.bangstatus=1 AND b.user_id =".$user_id;
	$withinfo = $GLOBALS['db']->getRow($sql);
	
	if($number > $withinfo['user_money']){
		show_message($_LANG['no_withdrawals_num'],$_LANG['back_up_page'], 'user.php?act=account_withdrawals', 'info');
	}
	$withinfo['with_sn'] = "t".date('YmdHis',time()).rand(1,10000);
	$withinfo['number'] = $number;
	$withinfo['bankname'] = $_LANG['bank_name'][$withinfo['cardbank']];
	
	/* 银行卡开户省市*/
	$sql = 'SELECT region_name FROM '.$GLOBALS['ecs']->table('region').
			' WHERE region_id ='.$withinfo['cardprovince'];
	$withinfo['proname'] = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT region_name FROM '.$GLOBALS['ecs']->table('region').
			' WHERE region_id ='.$withinfo['cardcity'];
	$withinfo['procity'] = $GLOBALS['db']->getOne($sql);
	
	/* 调用提现接口*/
	$conpay = new conpay;
	$conpay->withdraw_handle($withinfo);
	
	//更改用户的余额
	updateuser_account($surplus, $amount , 3);
	
	//记录日志
	$accountid = insert_user_account($surplus, $amount);
	if($accountid > 0){
		header('location:user.php?act=default');
	}
	
	
}

/* 会员充值和提现申请记录 */
elseif ($action == 'account_log')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('user_account').
           " WHERE user_id = '$user_id'" .
           " AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
    $record_count = $db->getOne($sql);

    //分页函数
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);

    //获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if (empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    //获取余额记录
    $account_log = get_account_log($user_id, $pager['size'], $pager['start']);

    //模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log',    $account_log);
    $smarty->assign('pager',          $pager);
    $smarty->display('user_transaction.dwt');
}

/* 对会员余额申请的处理 */
elseif ($action == 'act_account')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    if ($amount <= 0)
    {
        show_message($_LANG['amount_gt_zero']);
    }

    /* 变量初始化 */
    $surplus = array(
            'user_id'      => $user_id,
            'rec_id'       => !empty($_POST['rec_id'])      ? intval($_POST['rec_id'])       : 0,
            'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0,
            'payment_id'   => isset($_POST['payment_id'])   ? intval($_POST['payment_id'])   : 0,
            'user_note'    => isset($_POST['user_note'])    ? trim($_POST['user_note'])      : '',
            'amount'       => $amount
    );

    /* 退款申请的处理 */
    if ($surplus['process_type'] == 1)
    {
        /* 判断是否有足够的余额的进行退款的操作 */
        $sur_amount = get_user_surplus($user_id);
        if ($amount > $sur_amount)
        {
            $content = $_LANG['surplus_amount_error'];
            show_message($content, $_LANG['back_page_up'], '', 'info');
        }

        //插入会员账目明细
        $amount = '-'.$amount;
        $surplus['payment'] = '';
        $surplus['rec_id']  = insert_user_account($surplus, $amount);

        /* 如果成功提交 */
        if ($surplus['rec_id'] > 0)
        {
            $content = $_LANG['surplus_appl_submit'];
            show_message($content, $_LANG['back_account_log'], 'user.php?act=account_log', 'info');
        }
        else
        {
            $content = $_LANG['process_false'];
            show_message($content, $_LANG['back_page_up'], '', 'info');
        }
    }
    /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
    else
    {
        if ($surplus['payment_id'] <= 0)
        {
            show_message($_LANG['select_payment_pls']);
        }

        include_once(ROOT_PATH .'includes/lib_payment.php');

        //获取支付方式名称
        $payment_info = array();
        $payment_info = payment_info($surplus['payment_id']);
        $surplus['payment'] = $payment_info['pay_name'];

        if ($surplus['rec_id'] > 0)
        {
            //更新会员账目明细
            $surplus['rec_id'] = update_user_account($surplus);
        }
        else
        {
            //插入会员账目明细
            $surplus['rec_id'] = insert_user_account($surplus, $amount);
        }

        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);

        //生成伪订单号, 不足的时候补0
        $order = array();
        $order['order_sn']       = $surplus['rec_id'];
        $order['user_name']      = $_SESSION['user_name'];
        $order['surplus_amount'] = $amount;

        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);

        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $amount + $payment_info['pay_fee'];

        //记录支付log
        $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], $type=PAY_SURPLUS, 0);

        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');

        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  price_format($amount, false));
        $smarty->assign('order',   $order);
        $smarty->display('user_transaction.dwt');
    }
}

/* 删除会员余额 */
elseif ($action == 'cancel')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id == 0 || $user_id == 0)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }

    $result = del_user_account($id, $user_id);
    if ($result)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }
}

/* 会员通过帐目明细列表进行再付款的操作 */
elseif ($action == 'pay')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    include_once(ROOT_PATH . 'includes/lib_payment.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');

    //变量初始化
    $surplus_id = isset($_GET['id'])  ? intval($_GET['id'])  : 0;
    $payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

    if ($surplus_id == 0)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }

    //如果原来的支付方式已禁用或者已删除, 重新选择支付方式
    if ($payment_id == 0)
    {
        ecs_header("Location: user.php?act=account_deposit&id=".$surplus_id."\n");
        exit;
    }

    //获取单条会员帐目信息
    $order = array();
    $order = get_surplus_info($surplus_id);

    //支付方式的信息
    $payment_info = array();
    $payment_info = payment_info($payment_id);

    /* 如果当前支付方式没有被禁用，进行支付的操作 */
    if (!empty($payment_info))
    {
        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);

        //生成伪订单号
        $order['order_sn'] = $surplus_id;

        //获取需要支付的log_id
        $order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);

        $order['user_name']      = $_SESSION['user_name'];
        $order['surplus_amount'] = $order['amount'];

        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);

        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $order['surplus_amount'] + $payment_info['pay_fee'];

        //如果支付费用改变了，也要相应的更改pay_log表的order_amount
        $order_amount = $db->getOne("SELECT order_amount FROM " .$ecs->table('pay_log')." WHERE log_id = '$order[log_id]'");
        if ($order_amount <> $order['order_amount'])
        {
            $db->query("UPDATE " .$ecs->table('pay_log').
                       " SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
        }

        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');

        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('order',   $order);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  price_format($order['surplus_amount'], false));
        $smarty->assign('action',  'act_account');
        $smarty->display('user_transaction.dwt');
    }
    /* 重新选择支付方式 */
    else
    {
        include_once(ROOT_PATH . 'includes/lib_clips.php');

        $smarty->assign('payment', get_online_payment_list());
        $smarty->assign('order',   $order);
        $smarty->assign('action',  'account_deposit');
        $smarty->display('user_transaction.dwt');
    }
}

/* 添加标签(ajax) */
elseif ($action == 'add_tag')
{
    include_once('includes/cls_json.php');
    include_once('includes/lib_clips.php');

    $result = array('error' => 0, 'message' => '', 'content' => '');
    $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tag    = isset($_POST['tag']) ? json_str_iconv(trim($_POST['tag'])) : '';

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['tag_anonymous'];
    }
    else
    {
        add_tag($id, $tag); // 添加tag
        clear_cache_files('goods'); // 删除缓存

        /* 重新获得该商品的所有缓存 */
        $arr = get_tags($id);

        foreach ($arr AS $row)
        {
            $result['content'][] = array('word' => htmlspecialchars($row['tag_words']), 'count' => $row['tag_count']);
        }
    }

    $json = new JSON;

    echo $json->encode($result);
    exit;
}

/* 添加收藏商品(ajax) */
elseif ($action == 'collect')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();
    $result = array('error' => 0, 'message' => '');
    $goods_id = $_GET['id'];

    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }
    else
    {
        /* 检查是否已经存在于用户的收藏夹 */
        $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('collect_goods') .
            " WHERE user_id='$_SESSION[user_id]' AND goods_id = '$goods_id'";
        if ($GLOBALS['db']->GetOne($sql) > 0)
        {
            $result['error'] = 1;
            $result['message'] = $GLOBALS['_LANG']['collect_existed'];
            die($json->encode($result));
        }
        else
        {
            $time = gmtime();
            $sql = "INSERT INTO " .$GLOBALS['ecs']->table('collect_goods'). " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";

            if ($GLOBALS['db']->query($sql) === false)
            {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['db']->errorMsg();
                die($json->encode($result));
            }
            else
            {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['collect_success'];
                die($json->encode($result));
            }
        }
    }
}

/* 删除留言 */
elseif ($action == 'del_msg')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);

    if ($id > 0)
    {
        $sql = 'SELECT user_id, message_img FROM ' .$ecs->table('feedback'). " WHERE msg_id = '$id' LIMIT 1";
        $row = $db->getRow($sql);
        if ($row && $row['user_id'] == $user_id)
        {
            /* 验证通过，删除留言，回复，及相应文件 */
            if ($row['message_img'])
            {
                @unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/'. $row['message_img']);
            }
            $sql = "DELETE FROM " .$ecs->table('feedback'). " WHERE msg_id = '$id' OR parent_id = '$id'";
            $db->query($sql);
        }
    }
    ecs_header("Location: user.php?act=message_list&order_id=$order_id\n");
    exit;
}

/* 删除评论 */
elseif ($action == 'del_cmt')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id > 0)
    {
        $sql = "DELETE FROM " .$ecs->table('comment'). " WHERE comment_id = '$id' AND user_id = '$user_id'";
        $db->query($sql);
    }
    ecs_header("Location: user.php?act=comment_list\n");
    exit;
}

/* 合并订单 */
elseif ($action == 'merge_order')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');
    include_once(ROOT_PATH .'includes/lib_order.php');
    $from_order = isset($_POST['from_order']) ? trim($_POST['from_order']) : '';
    $to_order   = isset($_POST['to_order']) ? trim($_POST['to_order']) : '';
    if (merge_user_order($from_order, $to_order, $user_id))
    {
        show_message($_LANG['merge_order_success'],$_LANG['order_list_lnk'],'user.php?act=order_list', 'info');
    }
    else
    {
        $err->show($_LANG['order_list_lnk']);
    }
}
/* 将指定订单中商品添加到购物车 */
elseif ($action == 'return_to_cart')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    include_once(ROOT_PATH .'includes/lib_transaction.php');
    $json = new JSON();

    $result = array('error' => 0, 'message' => '', 'content' => '');
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if ($order_id == 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['order_id_empty'];
        die($json->encode($result));
    }

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }

    /* 检查订单是否属于该用户 */
    $order_user = $db->getOne("SELECT user_id FROM " .$ecs->table('order_info'). " WHERE order_id = '$order_id'");
    if (empty($order_user))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['order_exist'];
        die($json->encode($result));
    }
    else
    {
        if ($order_user != $user_id)
        {
            $result['error'] = 1;
            $result['message'] = $_LANG['no_priv'];
            die($json->encode($result));
        }
    }

    $message = return_to_cart($order_id);

    if ($message === true)
    {
        $result['error'] = 0;
        $result['message'] = $_LANG['return_to_cart_success'];
        die($json->encode($result));
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['order_exist'];
        die($json->encode($result));
    }

}

/* 编辑使用余额支付的处理 */
elseif ($action == 'act_edit_surplus')
{
    /* 检查是否登录 */
    if ($_SESSION['user_id'] <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单号 */
    $order_id = intval($_POST['order_id']);
    if ($order_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查余额 */
    $surplus = floatval($_POST['surplus']);
    if ($surplus <= 0)
    {
        $err->add($_LANG['error_surplus_invalid']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }

    include_once(ROOT_PATH . 'includes/lib_order.php');

    /* 取得订单 */
    $order = order_info($order_id);
    if (empty($order))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单用户跟当前用户是否一致 */
    if ($_SESSION['user_id'] != $order['user_id'])
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单是否未付款，检查应付款金额是否大于0 */
    if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0)
    {
        $err->add($_LANG['error_order_is_paid']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }

    /* 计算应付款金额（减去支付费用） */
    $order['order_amount'] -= $order['pay_fee'];

    /* 余额是否超过了应付款金额，改为应付款金额 */
    if ($surplus > $order['order_amount'])
    {
        $surplus = $order['order_amount'];
    }

    /* 取得用户信息 */
    $user = user_info($_SESSION['user_id']);

    /* 用户帐户余额是否足够 */
    if ($surplus > $user['user_money'] + $user['credit_line'])
    {
        $err->add($_LANG['error_surplus_not_enough']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }

    /* 修改订单，重新计算支付费用 */
    $order['surplus'] += $surplus;
    $order['order_amount'] -= $surplus;
    if ($order['order_amount'] > 0)
    {
        $cod_fee = 0;
        if ($order['shipping_id'] > 0)
        {
            $regions  = array($order['country'], $order['province'], $order['city'], $order['district']);
            $shipping = shipping_area_info($order['shipping_id'], $regions);
            if ($shipping['support_cod'] == '1')
            {
                $cod_fee = $shipping['pay_fee'];
            }
        }

        $pay_fee = 0;
        if ($order['pay_id'] > 0)
        {
            $pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
        }

        $order['pay_fee'] = $pay_fee;
        $order['order_amount'] += $pay_fee;
    }

    /* 如果全部支付，设为已确认、已付款 */
    if ($order['order_amount'] == 0)
    {
        if ($order['order_status'] == OS_UNCONFIRMED)
        {
            $order['order_status'] = OS_CONFIRMED;
            $order['confirm_time'] = gmtime();
        }
        $order['pay_status'] = PS_PAYED;
        $order['pay_time'] = gmtime();
    }
    $order = addslashes_deep($order);
    update_order($order_id, $order);

    /* 更新用户余额 */
    $change_desc = sprintf($_LANG['pay_order_by_surplus'], $order['order_sn']);
    log_account_change($user['user_id'], (-1) * $surplus, 0, 0, 0, $change_desc);

    /* 跳转 */
    ecs_header('Location: user.php?act=order_detail&order_id=' . $order_id . "\n");
    exit;
}

/* 编辑使用余额支付的处理 */
elseif ($action == 'act_edit_payment')
{
    /* 检查是否登录 */
    if ($_SESSION['user_id'] <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查支付方式 */
    $pay_id = intval($_POST['pay_id']);
    if ($pay_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    include_once(ROOT_PATH . 'includes/lib_order.php');
    $payment_info = payment_info($pay_id);
    if (empty($payment_info))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单号 */
    $order_id = intval($_POST['order_id']);
    if ($order_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得订单 */
    $order = order_info($order_id);
    if (empty($order))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单用户跟当前用户是否一致 */
    if ($_SESSION['user_id'] != $order['user_id'])
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变*/
    if ($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id)
    {
        ecs_header("Location: user.php?act=order_detail&order_id=$order_id\n");
        exit;
    }

    $order_amount = $order['order_amount'] - $order['pay_fee'];
    $pay_fee = pay_fee($pay_id, $order_amount);
    $order_amount += $pay_fee;

    $sql = "UPDATE " . $ecs->table('order_info') .
           " SET pay_id='$pay_id', pay_name='$payment_info[pay_name]', pay_fee='$pay_fee', order_amount='$order_amount'".
           " WHERE order_id = '$order_id'";
    $db->query($sql);

    /* 跳转 */
    ecs_header("Location: user.php?act=order_detail&order_id=$order_id\n");
    exit;
}

/* 保存订单详情收货地址 */
elseif ($action == 'save_order_address')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');
    
    $address = array(
        'consignee' => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee']))  : '',
        'email'     => isset($_POST['email'])     ? compile_str(trim($_POST['email']))      : '',
        'address'   => isset($_POST['address'])   ? compile_str(trim($_POST['address']))    : '',
        'zipcode'   => isset($_POST['zipcode'])   ? compile_str(make_semiangle(trim($_POST['zipcode']))) : '',
        'tel'       => isset($_POST['tel'])       ? compile_str(trim($_POST['tel']))        : '',
        'mobile'    => isset($_POST['mobile'])    ? compile_str(trim($_POST['mobile']))     : '',
        'sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '',
        'best_time' => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time']))  : '',
        'order_id'  => isset($_POST['order_id'])  ? intval($_POST['order_id']) : 0
        );
    if (save_order_address($address, $user_id))
    {
        ecs_header('Location: user.php?act=order_detail&order_id=' .$address['order_id']. "\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
    }
}

/* 我的红包列表 */
elseif ($action == 'bonus')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('user_bonus'). " WHERE user_id = '$user_id'");

    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);
    $bonus = get_user_bouns_list($user_id, $pager['size'], $pager['start']);

    $smarty->assign('pager', $pager);
    $smarty->assign('bonus', $bonus);
    $smarty->display('user_transaction.dwt');
}

/* 我的团购列表 */
elseif ($action == 'group_buy')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    //待议
    $smarty->display('user_transaction.dwt');
}

/* 团购订单详情 */
elseif ($action == 'group_buy_detail')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    //待议
    $smarty->display('user_transaction.dwt');
}

// 用户推荐页面
elseif ($action == 'affiliate')
{
    $goodsid = intval(isset($_REQUEST['goodsid']) ? $_REQUEST['goodsid'] : 0);
    if(empty($goodsid))
    {
        //我的推荐页面

        $page       = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
        $size       = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

        empty($affiliate) && $affiliate = array();

        if(empty($affiliate['config']['separate_by']))
        {
            //推荐注册分成
            $affdb = array();
            $num = count($affiliate['item']);
            $up_uid = "'$user_id'";
            $all_uid = "'$user_id'";
            for ($i = 1 ; $i <=$num ;$i++)
            {
                $count = 0;
                if ($up_uid)
                {
                    $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
                    $query = $db->query($sql);
                    $up_uid = '';
                    while ($rt = $db->fetch_array($query))
                    {
                        $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                        if($i < $num)
                        {
                            $all_uid .= ", '$rt[user_id]'";
                        }
                        $count++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $affiliate['item'][$i-1]['level_point'];
                $affdb[$i]['money'] = $affiliate['item'][$i-1]['level_money'];
            }
            $smarty->assign('affdb', $affdb);

            $sqlcount = "SELECT count(*) FROM " . $ecs->table('order_info') . " o".
        " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
        " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
        " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";

            $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
        " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)".
                    " ORDER BY order_id DESC" ;

            /*
                SQL解释：

                订单、用户、分成记录关联
                一个订单可能有多个分成记录

                1、订单有效 o.user_id > 0
                2、满足以下之一：
                    a.直接下线的未分成订单 u.parent_id IN ($all_uid) AND o.is_separate = 0
                        其中$all_uid为该ID及其下线(不包含最后一层下线)
                    b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

            */

            $affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_register_all'], $affiliate['config']['level_register_up'], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
        }
        else
        {
            //推荐订单分成
            $sqlcount = "SELECT count(*) FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";


            $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" .
                    " ORDER BY order_id DESC" ;

            /*
                SQL解释：

                订单、用户、分成记录关联
                一个订单可能有多个分成记录

                1、订单有效 o.user_id > 0
                2、满足以下之一：
                    a.订单下线的未分成订单 o.parent_id = '$user_id' AND o.is_separate = 0
                    b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

            */

            $affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));

        }

        $count = $db->getOne($sqlcount);

        $max_page = ($count> 0) ? ceil($count / $size) : 1;
        if ($page > $max_page)
        {
            $page = $max_page;
        }

        $res = $db->SelectLimit($sql, $size, ($page - 1) * $size);
        $logdb = array();
        while ($rt = $GLOBALS['db']->fetchRow($res))
        {
            if(!empty($rt['suid']))
            {
                //在affiliate_log有记录
                if($rt['separate_type'] == -1 || $rt['separate_type'] == -2)
                {
                    //已被撤销
                    $rt['is_separate'] = 3;
                }
            }
            $rt['order_sn'] = substr($rt['order_sn'], 0, strlen($rt['order_sn']) - 5) . "***" . substr($rt['order_sn'], -2, 2);
            $logdb[] = $rt;
        }

        $url_format = "user.php?act=affiliate&page=";

        $pager = array(
                    'page'  => $page,
                    'size'  => $size,
                    'sort'  => '',
                    'order' => '',
                    'record_count' => $count,
                    'page_count'   => $max_page,
                    'page_first'   => $url_format. '1',
                    'page_prev'    => $page > 1 ? $url_format.($page - 1) : "javascript:;",
                    'page_next'    => $page < $max_page ? $url_format.($page + 1) : "javascript:;",
                    'page_last'    => $url_format. $max_page,
                    'array'        => array()
                );
        for ($i = 1; $i <= $max_page; $i++)
        {
            $pager['array'][$i] = $i;
        }

        $smarty->assign('url_format', $url_format);
        $smarty->assign('pager', $pager);

		
        $smarty->assign('affiliate_intro', $affiliate_intro);
        $smarty->assign('affiliate_type', $affiliate['config']['separate_by']);

        $smarty->assign('logdb', $logdb);
    }
    else
    {
        //单个商品推荐
        $smarty->assign('userid', $user_id);
        $smarty->assign('goodsid', $goodsid);

        $types = array(1,2,3,4,5);
        $smarty->assign('types', $types);

        $goods = get_goods_info($goodsid);
        $shopurl = $ecs->url();
        $goods['goods_img'] = (strpos($goods['goods_img'], 'http://') === false && strpos($goods['goods_img'], 'https://') === false) ? $shopurl . $goods['goods_img'] : $goods['goods_img'];
        $goods['goods_thumb'] = (strpos($goods['goods_thumb'], 'http://') === false && strpos($goods['goods_thumb'], 'https://') === false) ? $shopurl . $goods['goods_thumb'] : $goods['goods_thumb'];
        $goods['shop_price'] = price_format($goods['shop_price']);

        $smarty->assign('goods', $goods);
    }

    $smarty->assign('shopname', $_CFG['shop_name']);
    $smarty->assign('userid', $user_id);
    $smarty->assign('shopurl', $ecs->url());
    $smarty->assign('logosrc', 'themes/' . $_CFG['template'] . '/images/logo.png');

    $smarty->display('user_clips.dwt');
}

//首页邮件订阅ajax操做和验证操作
elseif ($action =='email_list')
{
    $job = $_GET['job'];

    if($job == 'add' || $job == 'del')
    {
        if(isset($_SESSION['last_email_query']))
        {
            if(time() - $_SESSION['last_email_query'] <= 30)
            {
                die($_LANG['order_query_toofast']);
            }
        }
        $_SESSION['last_email_query'] = time();
    }

    $email = trim($_GET['email']);
    $email = htmlspecialchars($email);

    if (!is_email($email))
    {
        $info = sprintf($_LANG['email_invalid'], $email);
        die($info);
    }
    $ck = $db->getRow("SELECT * FROM " . $ecs->table('email_list') . " WHERE email = '$email'");
    if ($job == 'add')
    {
        if (empty($ck))
        {
            $hash = substr(md5(time()), 1, 10);
            $sql = "INSERT INTO " . $ecs->table('email_list') . " (email, stat, hash) VALUES ('$email', 0, '$hash')";
            $db->query($sql);
            $info = $_LANG['email_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
        }
        elseif ($ck['stat'] == 1)
        {
            $info = sprintf($_LANG['email_alreadyin_list'], $email);
        }
        else
        {
            $hash = substr(md5(time()),1 , 10);
            $sql = "UPDATE " . $ecs->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
            $db->query($sql);
            $info = $_LANG['email_re_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
        }
        die($info);
    }
    elseif ($job == 'del')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_notin_list'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            $hash = substr(md5(time()),1,10);
            $sql = "UPDATE " . $ecs->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
            $db->query($sql);
            $info = $_LANG['email_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=del_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
        }
        else
        {
            $info = $_LANG['email_not_alive'];
        }
        die($info);
    }
    elseif ($job == 'add_check')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_notin_list'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            $info = $_LANG['email_checked'];
        }
        else
        {
            if ($_GET['hash'] == $ck['hash'])
            {
                $sql = "UPDATE " . $ecs->table('email_list') . "SET stat = 1 WHERE email = '$email'";
                $db->query($sql);
                $info = $_LANG['email_checked'];
            }
            else
            {
                $info = $_LANG['hash_wrong'];
            }
        }
        show_message($info, $_LANG['back_home_lnk'], 'index.php');
    }
    elseif ($job == 'del_check')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_invalid'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            if ($_GET['hash'] == $ck['hash'])
            {
                $sql = "DELETE FROM " . $ecs->table('email_list') . "WHERE email = '$email'";
                $db->query($sql);
                $info = $_LANG['email_canceled'];
            }
            else
            {
                $info = $_LANG['hash_wrong'];
            }
        }
        else
        {
            $info = $_LANG['email_not_alive'];
        }
        show_message($info, $_LANG['back_home_lnk'], 'index.php');
    }
}

/* ajax 发送验证邮件 */
elseif ($action == 'send_hash_mail')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    include_once(ROOT_PATH .'includes/lib_passport.php');
    $json = new JSON();

    $result = array('error' => 0, 'message' => '', 'content' => '');

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }

    if (send_regiter_hash($user_id))
    {
        $result['message'] = $_LANG['validate_mail_ok'];
        die($json->encode($result));
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $GLOBALS['err']->last_message();
    }

    die($json->encode($result));
}
else if ($action == 'track_packages')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH .'includes/lib_order.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $orders = array();

    $sql = "SELECT order_id,order_sn,invoice_no,shipping_id FROM " .$ecs->table('order_info').
            " WHERE user_id = '$user_id' AND shipping_status = '" . SS_SHIPPED . "'";
    $res = $db->query($sql);
    $record_count = 0;
    while ($item = $db->fetch_array($res))
    {
        $shipping   = get_shipping_object($item['shipping_id']);

        if (method_exists ($shipping, 'query'))
        {
            $query_link = $shipping->query($item['invoice_no']);
        }
        else
        {
            $query_link = $item['invoice_no'];
        }

        if ($query_link != $item['invoice_no'])
        {
            $item['query_link'] = $query_link;
            $orders[]  = $item;
            $record_count += 1;
        }
    }
    $pager  = get_pager('user.php', array('act' => $action), $record_count, $page);
    $smarty->assign('pager',  $pager);
    $smarty->assign('orders', $orders);
    $smarty->display('user_transaction.dwt');
}
else if ($action == 'order_query')
{
    $_GET['order_sn'] = trim(substr($_GET['order_sn'], 1));
    $order_sn = empty($_GET['order_sn']) ? '' : addslashes($_GET['order_sn']);
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();

    $result = array('error'=>0, 'message'=>'', 'content'=>'');

    if(isset($_SESSION['last_order_query']))
    {
        if(time() - $_SESSION['last_order_query'] <= 10)
        {
            $result['error'] = 1;
            $result['message'] = $_LANG['order_query_toofast'];
            die($json->encode($result));
        }
    }
    $_SESSION['last_order_query'] = time();

    if (empty($order_sn))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['invalid_order_sn'];
        die($json->encode($result));
    }

    $sql = "SELECT order_id, order_status, shipping_status, pay_status, ".
           " shipping_time, shipping_id, invoice_no, user_id ".
           " FROM " . $ecs->table('order_info').
           " WHERE order_sn = '$order_sn' LIMIT 1";

    $row = $db->getRow($sql);
    if (empty($row))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['invalid_order_sn'];
        die($json->encode($result));
    }

    $order_query = array();
    $order_query['order_sn'] = $order_sn;
    $order_query['order_id'] = $row['order_id'];
    $order_query['order_status'] = $_LANG['os'][$row['order_status']] . ',' . $_LANG['ps'][$row['pay_status']] . ',' . $_LANG['ss'][$row['shipping_status']];

    if ($row['invoice_no'] && $row['shipping_id'] > 0)
    {
        $sql = "SELECT shipping_code FROM " . $ecs->table('shipping') . " WHERE shipping_id = '$row[shipping_id]'";
        $shipping_code = $db->getOne($sql);
        $plugin = ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php';
        if (file_exists($plugin))
        {
            include_once($plugin);
            $shipping = new $shipping_code;
            $order_query['invoice_no'] = $shipping->query((string)$row['invoice_no']);
        }
        else
        {
            $order_query['invoice_no'] = (string)$row['invoice_no'];
        }
    }

    $order_query['user_id'] = $row['user_id'];
    /* 如果是匿名用户显示发货时间 */
    if ($row['user_id'] == 0 && $row['shipping_time'] > 0)
    {
        $order_query['shipping_date'] = local_date($GLOBALS['_CFG']['date_format'], $row['shipping_time']);
    }
    $smarty->assign('order_query',    $order_query);
    $result['content'] = $smarty->fetch('library/order_query.lbi');
    die($json->encode($result));
}
elseif ($action == 'transform_points')
{
    $rule = array();
    if (!empty($_CFG['points_rule']))
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    $cfg = array();
    if (!empty($_CFG['integrate_config']))
    {
        $cfg = unserialize($_CFG['integrate_config']);
        $_LANG['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0])? $_LANG['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
        $_LANG['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0])? $_LANG['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
    }
    $sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table('users')  . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    if ($_CFG['integrate_code'] == 'ucenter')
    {
        $exchange_type = 'ucenter';
        $to_credits_options = array();
        $out_exchange_allow = array();
        foreach ($rule as $credit)
        {
            $out_exchange_allow[$credit['appiddesc'] . '|' . $credit['creditdesc'] . '|' . $credit['creditsrc']] = $credit['ratio'];
            if (!array_key_exists($credit['appiddesc']. '|' .$credit['creditdesc'], $to_credits_options))
            {
                $to_credits_options[$credit['appiddesc']. '|' .$credit['creditdesc']] = $credit['title'];
            }
        }
        $smarty->assign('selected_org', $rule[0]['creditsrc']);
        $smarty->assign('selected_dst', $rule[0]['appiddesc']. '|' .$rule[0]['creditdesc']);
        $smarty->assign('descreditunit', $rule[0]['unit']);
        $smarty->assign('orgcredittitle', $_LANG['exchange_points'][$rule[0]['creditsrc']]);
        $smarty->assign('descredittitle', $rule[0]['title']);
        $smarty->assign('descreditamount', round((1 / $rule[0]['ratio']), 2));
        $smarty->assign('to_credits_options', $to_credits_options);
        $smarty->assign('out_exchange_allow', $out_exchange_allow);
    }
    else
    {
        $exchange_type = 'other';

        $bbs_points_name = $user->get_points_name();
        $total_bbs_points = $user->get_points($row['user_name']);

        /* 论坛积分 */
        $bbs_points = array();
        foreach ($bbs_points_name as $key=>$val)
        {
            $bbs_points[$key] = array('title'=>$_LANG['bbs'] . $val['title'], 'value'=>$total_bbs_points[$key]);
        }

        /* 兑换规则 */
        $rule_list = array();
        foreach ($rule as $key=>$val)
        {
            $rule_key = substr($key, 0, 1);
            $bbs_key = substr($key, 1);
            $rule_list[$key]['rate'] = $val;
            switch ($rule_key)
            {
                case TO_P :
                    $rule_list[$key]['from'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] = $_LANG['pay_points'];
                    break;
                case TO_R :
                    $rule_list[$key]['from'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] = $_LANG['rank_points'];
                    break;
                case FROM_P :
                    $rule_list[$key]['from'] = $_LANG['pay_points'];$_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] =$_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    break;
                case FROM_R :
                    $rule_list[$key]['from'] = $_LANG['rank_points'];
                    $rule_list[$key]['to'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    break;
            }
        }
        $smarty->assign('bbs_points', $bbs_points);
        $smarty->assign('rule_list',  $rule_list);
    }
    $smarty->assign('shop_points', $row);
    $smarty->assign('exchange_type',     $exchange_type);
    $smarty->assign('action',     $action);
    $smarty->assign('lang',       $_LANG);
    $smarty->display('user_transaction.dwt');
}
elseif ($action == 'act_transform_points')
{
    $rule_index = empty($_POST['rule_index']) ? '' : trim($_POST['rule_index']);
    $num = empty($_POST['num']) ? 0 : intval($_POST['num']);


    if ($num <= 0 || $num != floor($num))
    {
        show_message($_LANG['invalid_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }

    $num = floor($num); //格式化为整数

    $bbs_key = substr($rule_index, 1);
    $rule_key = substr($rule_index, 0, 1);

    $max_num = 0;

    /* 取出用户数据 */
    $sql = "SELECT user_name, user_id, pay_points, rank_points FROM " . $ecs->table('users') . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    $bbs_points = $user->get_points($row['user_name']);
    $points_name = $user->get_points_name();

    $rule = array();
    if ($_CFG['points_rule'])
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    list($from, $to) = explode(':', $rule[$rule_index]);

    $max_points = 0;
    switch ($rule_key)
    {
        case TO_P :
            $max_points = $bbs_points[$bbs_key];
            break;
        case TO_R :
            $max_points = $bbs_points[$bbs_key];
            break;
        case FROM_P :
            $max_points = $row['pay_points'];
            break;
        case FROM_R :
            $max_points = $row['rank_points'];
    }

    /* 检查积分是否超过最大值 */
    if ($max_points <=0 || $num > $max_points)
    {
        show_message($_LANG['overflow_points'], $_LANG['transform_points'], 'user.php?act=transform_points' );
    }

    switch ($rule_key)
    {
        case TO_P :
            $result_points = floor($num * $to / $from);
            $user->set_points($row['user_name'], array($bbs_key=>0 - $num)); //调整论坛积分
            log_account_change($row['user_id'], 0, 0, 0, $result_points, $_LANG['transform_points'], ACT_OTHER);
            show_message(sprintf($_LANG['to_pay_points'],  $num, $points_name[$bbs_key]['title'], $result_points), $_LANG['transform_points'], 'user.php?act=transform_points');

        case TO_R :
            $result_points = floor($num * $to / $from);
            $user->set_points($row['user_name'], array($bbs_key=>0 - $num)); //调整论坛积分
            log_account_change($row['user_id'], 0, 0, $result_points, 0, $_LANG['transform_points'], ACT_OTHER);
            show_message(sprintf($_LANG['to_rank_points'], $num, $points_name[$bbs_key]['title'], $result_points), $_LANG['transform_points'], 'user.php?act=transform_points');

        case FROM_P :
            $result_points = floor($num * $to / $from);
            log_account_change($row['user_id'], 0, 0, 0, 0-$num, $_LANG['transform_points'], ACT_OTHER); //调整商城积分
            $user->set_points($row['user_name'], array($bbs_key=>$result_points)); //调整论坛积分
            show_message(sprintf($_LANG['from_pay_points'], $num, $result_points,  $points_name[$bbs_key]['title']), $_LANG['transform_points'], 'user.php?act=transform_points');

        case FROM_R :
            $result_points = floor($num * $to / $from);
            log_account_change($row['user_id'], 0, 0, 0-$num, 0, $_LANG['transform_points'], ACT_OTHER); //调整商城积分
            $user->set_points($row['user_name'], array($bbs_key=>$result_points)); //调整论坛积分
            show_message(sprintf($_LANG['from_rank_points'], $num, $result_points, $points_name[$bbs_key]['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
    }
}
elseif ($action == 'act_transform_ucenter_points')
{
    $rule = array();
    if ($_CFG['points_rule'])
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    $shop_points = array(0 => 'rank_points', 1 => 'pay_points');
    $sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table('users')  . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    $exchange_amount = intval($_POST['amount']);
    $fromcredits = intval($_POST['fromcredits']);
    $tocredits = trim($_POST['tocredits']);
    $cfg = unserialize($_CFG['integrate_config']);
    if (!empty($cfg))
    {
        $_LANG['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0])? $_LANG['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
        $_LANG['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0])? $_LANG['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
    }
    list($appiddesc, $creditdesc) = explode('|', $tocredits);
    $ratio = 0;

    if ($exchange_amount <= 0)
    {
        show_message($_LANG['invalid_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    if ($exchange_amount > $row[$shop_points[$fromcredits]])
    {
        show_message($_LANG['overflow_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    foreach ($rule as $credit)
    {
        if ($credit['appiddesc'] == $appiddesc && $credit['creditdesc'] == $creditdesc && $credit['creditsrc'] == $fromcredits)
        {
            $ratio = $credit['ratio'];
            break;
        }
    }
    if ($ratio == 0)
    {
        show_message($_LANG['exchange_deny'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    $netamount = floor($exchange_amount / $ratio);
    include_once(ROOT_PATH . './includes/lib_uc.php');
    $result = exchange_points($row['user_id'], $fromcredits, $creditdesc, $appiddesc, $netamount);
    if ($result === true)
    {
        $sql = "UPDATE " . $ecs->table('users') . " SET {$shop_points[$fromcredits]}={$shop_points[$fromcredits]}-'$exchange_amount' WHERE user_id='{$row['user_id']}'";
        $db->query($sql);
        $sql = "INSERT INTO " . $ecs->table('account_log') . "(user_id, {$shop_points[$fromcredits]}, change_time, change_desc, change_type)" . " VALUES ('{$row['user_id']}', '-$exchange_amount', '". gmtime() ."', '" . $cfg['uc_lang']['exchange'] . "', '98')";
        $db->query($sql);
        show_message(sprintf($_LANG['exchange_success'], $exchange_amount, $_LANG['exchange_points'][$fromcredits], $netamount, $credit['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    else
    {
        show_message($_LANG['exchange_error_1'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
}

/* 安全认证中心实名的认证*/
elseif ($action == 'act_bang_truename')
{
	$name = trim($_POST['truename_authcenter']);
	$idcard = trim($_POST['authcenter_idcardname']);

	if(empty($name)){
		show_message($_LANG['passport_js']['truename_empty']);
	}
	elseif(ereg("/^[\x{4e00}-\x{9fa5}]+$/u",$name)){
		show_message($_LANG['passport_js']['truename_invalid']);
	}
	elseif(empty($idcard)){
		show_message($_LANG['passport_js']['idcard_empty']);
	}
	elseif(ereg("/(^\/d{15}$)|(^\/d{17}([0-9]|X|x)$)/",$idcard)){
		show_message($_LANG['passport_js']['idcard_invalid']);
	}
	/* 查询身份证号是否存在*/
	$sql='SELECT * FROM '.$GLOBALS['ecs']->table('users').' where idcard="'.$idcard.'"';
	$result = $GLOBALS['db']->getOne($sql);
	if(empty($result)){
		$sql='UPDATE '.$GLOBALS['ecs']->table('users').' SET idcard="'.$idcard.'",realname="'.$name.'",idcardstatus=1 where user_id='.$user_id;
		$res = $GLOBALS['db']->query($sql);
		show_message($_LANG['passport_js']['idcard_success'],$_LANG['back_up_page'],'user.php?act=auth_center');
	}else{
		show_message($_LANG['passport_js']['idcard_confirm'],$_LANG['back_up_page'],'user.php?act=auth_center');
	}

}

/* 清除商品浏览历史 */
elseif ($action == 'clear_history')
{
    setcookie('ECS[history]',   '', 1);
}
?>