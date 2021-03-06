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
        $user_info = cookie('user_info'); //获取cookie的用户微信信息
        // var_dump($user_info);
        if( is_null($user_info) ) { //判断cookie是否为null，是的话就获取微信信息并判断是否添加数据库
            $code = $_GET['code'];
            $appid="wxcc78aebc2541eb2d";
            $appsecret="39f1b356e2454e3695b77029c80519aa";

            $token = $this->getWxAccessToken();

            $ch = curl_init();
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='. $appid .'&secret='. $appsecret .'&code='. $code .'&grant_type=authorization_code';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            $openid = json_decode($output, true)['openid'];

            $ch2 = curl_init();
            $url2 = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='. $token .'&openid='. $openid .'&lang=zh_CN';
            curl_setopt($ch2, CURLOPT_URL, $url2);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
            $output2 = curl_exec($ch2);
            curl_close($ch2);
            $info = json_decode($output2, true);//解码接口返回的数据
            // var_dump($info);
            $map['openid'] = $info['openid']; //组合查询条件数组
            $checkUser = M('user') -> where($map) -> find();
            // var_dump($checkUser);
            if(is_null($checkUser)) {
                // var_dump('没有进数据库');
                $addUser['openid'] = $info['openid'];
                $randomString = $this -> getRandomString();
                $addUser['randomid'] = date('Ymdhis') . $randomString;
                $addUser['wxname'] = $info['nickname'];
                $result = M('user') -> add($addUser);
                // var_dump($result); //输出新添加的id
                cookie('user_info', $info);
            } else {
                cookie('user_info', $info);
            }
            $this -> assign('data', $info);
            $this -> display('index');
        } else { //cookie里有用户信息就直接读取跳转首页
            // var_dump($user_info);
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

    public function showUserList() {
        $userList = M('user') -> order('id desc') -> select();
        $this -> assign('data', $userList);
        $this -> display('showUserList');

    }

    public function getUserInfo() {
        $map['id'] = I('get.id');
        $user_info = M('user') -> where($map) -> find();
        $this -> assign('data', $user_info);
        $this -> display('getUserInfo');
    }

    public function makeHimVip() {
        $map = I('post.');
        $result = M('user') -> save($map);
        header("Content-type: text/html; charset=utf-8"); 
        var_dump('修改成功！');
    }

    public function addOrder() { //添加订单
        $order_info = I('post.');
        $order_info['date'] = date('Y-m-d h:i:s');
        $randomString = $this -> getRandomString();
        $order_info['randomid'] = date('Ymdhis') . $randomString;
        $order_id = M('order') -> data($order_info) -> add();
        //ajax返回给页面参数
        $data['msg'] = '维修申请提交成功，我们会尽快联系您！';
        $data['order_id'] = $order_id;
        $this -> ajaxReturn($data);
    }

    public function noticeRepairtor() {
        $map['id'] = I('post.id');
        $order_info = M('order') -> where($map) -> find();

        $openid = 'oAFMW1G4JcOVZwxvlSI-yxrX7daQ';
        $randomid = $order_info['randomid'];
        $repairtor = '待确定';
        $tourl = 'http://repaire.dnpuzi.com/home/index/getOrderInfo?id=' . I('post.id');
        $this -> sendWxTmlMsg($openid, $randomid, $repairtor, $tourl);
    }

    public function getOrderList() { //用户首页获取他的订单信息
        $map['openid'] = I('post.openid');
        $order_list = M('order') -> where($map) -> select();
        $this -> ajaxReturn($order_list);
    }

    public function getOrderInfo() { //维修人员获取订单信息
        $map['id'] = I('get.id');
        $order_info = M('order') -> where($map) -> find();
        $user['openid'] = $order_info['openid']; //查询订单用户的信息
        $user_info  = M('user') -> where($user) -> find();
        $order_info['tel'] = $user_info['tel'];
        $this -> assign('data', $order_info);
        $this -> display('getOrderInfo');
    }

    public function repairtorEnsureOrder() { //维修人员确认订单去维修
        $map['id'] = I('post.orderid');
        $map['repairtor'] = I('post.repairtor');
        $result = M('order') -> save($map);
        $this -> sendTmlMsg(I('post.openid'), I('post.randomid'), I('post.repairtor'));
    }

    public function sendTmlMsg($openid, $randomid, $repairtor, $tourl) { //发送模板信息 to user
        $token = $this->getWxAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token;
        $array = array(
            'touser' => '' . $openid,
            'template_id' => 'p9LvjBRPIYWtiuF8zABJxg3y3DPLA7k-6pIfnBmnQpE',
            'url' => '' . $tourl,
            'data' => array(
                'first' => array( 'value' => '您好，您提交的维修申请已审核，维修单号：' . $randomid , 'color' => '#173177' ),
                'keyword1' => array( 'value' => '' . $randomid, 'color' => '#173177' ),
                'keyword2' => array( 'value' => '' . $repairtor, 'color' => '#df5e5e' ),
                'remark' => array( 'value' => '如有问题请咨询18008385331', 'color' => '#4c57e4' ),
            ),
        );
        $postJson = json_encode($array);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson);
        $output = curl_exec($ch);
        curl_close($ch);
    }

    public function sendWxTmlMsg($openid, $randomid, $repairtor, $tourl) { //发送模板信息 to us
        $token = $this->getWxAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token;
        $array = array(
            'touser' => '' . $openid,
            'template_id' => 'cjg2rYFQpLo6Cqao35ZoOy6lBTVMbIsJs6668wdp8cw',
            'url' => '' . $tourl,
            'data' => array(
                'first' => array( 'value' => '有会员提交了维修申请，快去审核', 'color' => '#173177' ),
                'track_number' => array( 'value' => '' . $randomid, 'color' => '#173177' ),
                'asp_name' => array( 'value' => '' . $repairtor, 'color' => '#df5e5e' ),
                'asp_tel' => array( 'value' => '', 'color' => '#173177' ),
                'remark' => array( 'value' => '', 'color' => '#4c57e4' ),
            ),
        );
        $postJson = json_encode($array);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson);
        $output = curl_exec($ch);
        curl_close($ch);
    }

    public function http_url($url,$type='get',$res='json',$arr=''){
        //1.初始化curl
        $ch =curl_init();
        //2.设置curl参数
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        if($type == 'post'){
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$arr);
        }
        //3.采集
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        if($res =='json'){
            if(curl_errno($ch)){
                //请求失败，返回错误信息
                return curl_errno($ch);
            }else{
                //请求成功
                return json_decode($output,true);
            }
        }
    }

    public function getWxAccessToken() {
        //将accessToken存储在session中
        if($_SESSION['accessToken'] && $_SESSION['expire_time']>time()){
            //如果accessToken在session中没有过期
            return $_SESSION['access_token'];
        }else{
            //如果accessToken在session中不存在或者已经过期，重新获取accessToken
            $appid="wxcc78aebc2541eb2d";
            $appsecret="39f1b356e2454e3695b77029c80519aa";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
            $res = $this->http_url($url,'get');
            $access_token = $res['access_token'];
            $_SESSION['access_token'] = $access_token;
            $_SESSION['expire_time'] = time()+7000;
            return $access_token;
        }
    }

     public function definedItem() {
        header("Content-type:text/html;charset=utf-8");
        $accessToken = $this->getWxAccessToken();
        echo $url ="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$accessToken;
        echo "<hr/>";
        $postArr = array(
            'button'=>array(
                array(
                    'name'=>urlencode('关于我们'),
                    'type'=>'view',
                    'url'=>'http://repaire.dnpuzi.com/dist/#/',
                ),//第一个一级菜单
                array(
                    'name'=>urlencode('电脑报修'),
                    'type'=>'view',
                    'url'=>'http://dwz.cn/7MLDTV',
                ),//第三个一级菜单
            ),
        );
        echo $postJson = urldecode(json_encode($postArr));
        $res = $this->http_url($url,'post','json',$postJson);
        echo "<hr/>";
        var_dump($res);
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