<?php

return array(
    'code'=> 'qq',
    'name' => 'QQ登陆',
    'version' => '1.0',
    'author' => 'CLTPHP',
    'desc' => 'QQ登陆插件 ',
    'icon' => 'logo.png',
    'config' => array(
        array('name' => 'appid','label'=>'appid','type' => 'text',   'value' => ''),
        array('name' => 'appkey','label'=>'appkey','type' => 'text',   'value' => ''),
        array('name' => 'callback','label'=>'回调地址','type' => 'text',   'value' => 'http://'.$_SERVER['HTTP_HOST'].'/index/callback/qq'),
        array('name' => 'scope','label'=>'获取字段','type' => 'textarea',   'value' => 'get_user_info,add_share,list_album,add_album,upload_pic,add_topic,add_one_blog,add_weibo,check_page_fans,add_t,add_pic_t,del_t,get_repost_list,get_info,get_other_info,get_fanslist,get_idolist,add_idol,del_idol,get_tenpay_addr'),
        array('name' => 'errorReport','label'=>'错误报告','type' => 'text',   'value' => 'true')
    ),
    'scene'=>'',
    'bank_code'=>''
);