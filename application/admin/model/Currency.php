<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/11 0011
 * Time: 22:20
 */
namespace app\admin\model;
use think\Model;
use think\Db;
class Currency extends Model
{
    const SHATE     = 1;
    const ACTIVE    = 2;
    const XIAOFEI   = 3;
    const TRADE     = 4;
    const BONUS     = 5;
}