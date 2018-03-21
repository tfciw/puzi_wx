<?php
namespace Home\Controller;
use Think\Controller;
class CheckController extends Controller {
    public function checkWechat(){

    	define("TOKEN", "dnpuzi");

		$signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature && $echostr ){
			echo $_GET['echostr'];
		}else{
			$this -> responseMsg();
		}
	}

	public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr)){
                
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";             
            if(!empty( $keyword ))
            {
                $msgType = "text";
                $contentStr = "你发送的是: " .$keyword;
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }else{
                echo "Input something...";
            }

        }else {
            echo "";
            exit;
        }
    }

    public function getToken() {
    	// S('access_token', null);
    	$token = S('access_token');
    	$appid="wxcc78aebc2541eb2d";
		$appsecret="39f1b356e2454e3695b77029c80519aa";
		// var_dump($token);die;
		if( !$token ) {
			$ch = curl_init();
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);
			$token = json_decode($output, true)['access_token'];
			S('access_token', $token, 3000);
			// var_dump(json_decode($output));die;
		}
		return $token;
    }

    //获取所有用户的openid
    public function getOpenid() {
    	$token = $this -> getToken();
    	// var_dump($token);die;
    	$ch = curl_init();
		$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=' . $token;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		var_dump( json_decode($output, true) );die;
		$openid = json_decode($output, true)['data']['openid'];
		var_dump($openid);
    }

    public function getInfo() {
    	$code = $_GET['code'];
    	$appid="wxcc78aebc2541eb2d";
		$appsecret="39f1b356e2454e3695b77029c80519aa";

		$openid = S('openid');
		$token = $this -> getToken();

		if(!$openid) {
			$ch = curl_init();
			$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='. $appid .'&secret='. $appsecret .'&code='. $code .'&grant_type=authorization_code';
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);

			$openid = json_decode($output, true)['openid'];
			S('openid', $openid, 3000);
		}
		
		$ch2 = curl_init();
		$url2 = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='. $token .'&openid='. $openid .'&lang=zh_CN';
		curl_setopt($ch2, CURLOPT_URL, $url2);
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
		$output2 = curl_exec($ch2);
		curl_close($ch2);
		var_dump($output2);
    }

}