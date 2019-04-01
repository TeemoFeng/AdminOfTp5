<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/31
 * Time: 21:38
 */
namespace app\user\controller;
use think\Db;
use kuange\qqconnect\QC;
use think\Controller;
class Bourse extends Controller
{
    public function index()
    {
        echo exit('<script>top.location.href="'.url("home/index/index").'"</script>');
    }
}