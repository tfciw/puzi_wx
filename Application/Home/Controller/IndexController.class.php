<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
       	$user = D('user') -> select();
       	// $user = serialize($user);
       	// var_dump($user);
       	$this -> ajaxReturn($user);
    }

    public function user() {
    	$this -> display('user');
    }
}