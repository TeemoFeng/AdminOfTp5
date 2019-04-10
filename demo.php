<?php
// 定义参数 94730052
define('ACCOUNT_ID', ''); // your account ID 
define('ACCESS_KEY','6ea6d085-35f58c7d-f5728332-ca86d'); // your ACCESS_KEY 
define('SECRET_KEY','fcf34257-d2b6b694-6b214896-2c2b2'); // your SECRET_KEY

include "lib.php";

//实例化类库
$req = new req();
// 获取account-id, 用来替换ACCOUNT_ID
var_dump($req->get_account_accounts());
// 获取账户余额示例
//var_dump($req->get_balance());

?>
