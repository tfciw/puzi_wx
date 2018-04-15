<?php
	public function http_url($url,$type='get',$res='json',$arr='')
    {
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

    public function getWxAccessToken()
    {
        //将accessToken存储在session中
        if($_SESSION['accessToken'] && $_SESSION['expire_time']>time()){
            //如果accessToken在session中没有过期
            return $_SESSION['access_token'];
        }else{
            //如果accessToken在session中不存在或者已经过期，重新获取accessToken
            $appid = "wxe20c0f7c81069b62";
            $appsecret = "1740ff15d2a3d0272f43d779d23253e8";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
            $res = $this->http_url($url,'get');
            $access_token = $res['access_token'];
            $_SESSION['access_token'] = $access_token;
            $_SESSION['expire_time'] = time()+7000;
            return $access_token;
        }
    }

        public function definedItem()
    {
        header("Content-type:text/html;charset=utf-8");
        $accessToken = $this->getWxAccessToken();
        echo $url ="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$accessToken;
        echo "<hr/>";
        $postArr = array(
            'button'=>array(
                array(
                    'name'=>urlencode('PHP途酷'),
                    'type'=>'click',
                    'key'=>'phptuku',
                ),//第一个一级菜单
                array(
                    'name'=>urlencode('PHP全栈'),
                    'sub_button'=>array(
                        array(
                            'name'=>urlencode('php'),
                            'type'=>'click',
                            'key'=>'php',
                        ),//第一个二级菜单
                        array(
                            'name'=>urlencode('mysql'),
                            'type'=>'view',
                            'url'=>'http://www.phptuku.com/',
                        ),//第二个二级菜单
                    ),
                ),//第二个一级菜单
                array(
                    'name'=>urlencode('PHP笔记'),
                    'type'=>'view',
                    'url'=>'http://www.phptuku.com/',
                ),//第三个一级菜单
            ),
        );
        echo $postJson = urldecode(json_encode($postArr));
        $res = $this->http_url($url,'post','json',$postJson);
        echo "<hr/>";
        var_dump($res);
    }



?>