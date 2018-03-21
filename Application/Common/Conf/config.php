<?php
return array(
	//'配置项'=>'配置值'

	//数据库配置
	'DB_TYPE'  		=>        'mysqli',       //数据库类型
	'DB_HOST'  		=>        'localhost',   //服务器地址
	'DB_NAME'  		=>        'tp5',    //数据库名
	'DB_USER'  		=>        'root',         //用户名
	'DB_PWD'   		=>        '',         //密码
	'DB_PORT'  		=>        '3306',         //端口
	'DB_PREFIX'		=>        '',          //表前缀
	'charset'  		=>        'utf8',

	//开启show_page_trace
	'SHOW_PAGE_TRACE'    =>		false,

	/* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 2, //URL模式
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符

	//默认路径配置(特殊配置可以使用，下面的东西不写也是默认的)
	'DEFAULT_MODULE'     =>        'Index',         //默认模块
    'DEFAULT_CONTROLLER' =>        'Index',        //默认控制器名称
    'DEFAULT_ACTION'     =>        'index',        //默认操作名称

	//开启自动写入时间戳
	'auto_timestamp' => true,
	'auto_timestamp' => 'date',
	'URL_MODEL' => 3,

	//模板解析路径配置
	'TMPL_PARSE_STRING'=>array(
		'__PUBLIC__' => '/Public/'
	),
);
