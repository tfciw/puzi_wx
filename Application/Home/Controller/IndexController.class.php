<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
       	$user_info = cookie('user_info');
       	if( is_null($user_info) ) {
       		$user_info = $this -> user();
       		var_dump('从user获取的');
       		var_dump($user_info);
       	} else {
       		var_dump('已经在session里的');
       		var_dump($user_info);
       	}
    }

    public function getToken() { //获取token，这是每一次访问微信api的重要依据
    	// S('access_token', null);
    	// $token = S('access_token');
    	$appid="wxcc78aebc2541eb2d";
		$appsecret="39f1b356e2454e3695b77029c80519aa";
		// var_dump($token);die;
		$ch = curl_init();
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		$token = json_decode($output, true)['access_token'];
		return $token;
    }

    public function getInfo() {  //获取微信信息
    	$user_info = cookie('user_info');
    	if( is_null($user_info) ) {
    		$code = $_GET['code'];
			$appid="wxcc78aebc2541eb2d";
			$appsecret="39f1b356e2454e3695b77029c80519aa";

			// $openid = S('openid');
			$token = $this -> getToken();

			$ch = curl_init();
			$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='. $appid .'&secret='. $appsecret .'&code='. $code .'&grant_type=authorization_code';
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);

			$openid = json_decode($output, true)['openid'];
			// var_dump($openid);die;

			$ch2 = curl_init();
			$url2 = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='. $token .'&openid='. $openid .'&lang=zh_CN';
			curl_setopt($ch2, CURLOPT_URL, $url2);
			curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
			$output2 = curl_exec($ch2);
			curl_close($ch2);
			$info = json_decode($output2, true);

			$checkUser = M('user') -> where('openid = %d', $info['openid']) -> find();
			// var_dump($checkUser);die;
			if(is_null($checkUser)) {
				$addUser['openid'] = $info['openid'];
				M('user') -> data($addUser) -> add();
				cookie('user_info', $info);
			} else {
				cookie('user_info', $info);
			}
			$this -> assign('data', $info);
			$this -> display('index');
    	} else {
    		$this -> assign('data', $user_info);
    		$this -> display('index');
    	}
    	
    }

    public function checkIsVip() { //用户点击按钮先判断他是不是会员
    	$map['openid'] = $_POST['openid'];
    	$user = M('user') -> where($map) -> find();
    	if(is_null($user['username'])) {
    		$data = 'not_vip';
    		$this -> ajaxReturn($data); 
    	}  		
    }

    public function addOrder() { //添加订单
    	$order_info = I('post.');
        $order_info['date'] = date('Y-m-d h:i:s');
        $randomString = $this -> getRandomString();
        $order_info['randomid'] = date('Ymdhis') . $randomString;
    	$order_id = M('order') -> data($order_info) -> add();
    	$this -> ajaxReturn('订单id：' . $order_id .',提交成功，我们会尽快联系你');
    }

    public function getOrderList() { //用户首页获取他的订单信息
    	$openid = I('post.openid');
    	$map['openid'] = $openid;
    	$order_list = M('order') -> where($map) -> select();
    	$this -> ajaxReturn($order_list);
    }

    public function getOrderInfo() { //维修人员获取订单信息
    	$map['id'] = I('get.id');
    	$order_info = M('order') -> where($map) -> find();
    	$this -> assign('data', $order_info);
    	$this -> display('getOrderInfo');
    }

    public function repairtorEnsureOrder() {
    	$map['id'] = I('post.orderid');
    	$map['repairtor'] = I('post.repairtor');
    	$result = M('order') -> save($map);

        $this -> sendTmlMsg(I('post.openid'), I('post.orderid'), I('post.repairtor'));
    }

    public function sendTmlMsg($openid, $orderid, $repairtor) { //发送模板信息
        $token = $this -> getToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token;
        $array = array(
            'touser' => '' . $openid,
            'template_id' => 'cjg2rYFQpLo6Cqao35ZoOy6lBTVMbIsJs6668wdp8cw',
            'url' => 'www.baidu.com',
            'data' => array(
                'first' => array( 'value' => '您好，您提交的维修申请已审核', 'color' => '#173177' ),
                'track_number' => array( 'value' => '' . $orderid, 'color' => '#173177' ),
                'asp_name' => array( 'value' => '' . $repairtor, 'color' => '#df5e5e' ),
                'asp_tel' => array( 'value' => '021-654321xx', 'color' => '#173177' ),
                'remark' => array( 'value' => '请回复“rgfw”', 'color' => '#4c57e4' ),
            ),
        );
        $postJson = json_encode($array);
        // var_dump($postJson);die;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson);
        $output = curl_exec($ch);
        curl_close($ch);
    }

    function getRandomString($len=12) { //获取随机数
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
            $str .= $chars[mt_rand(0, $lc)];  
        }
        return $str;
    }

}