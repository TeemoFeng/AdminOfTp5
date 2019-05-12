<?php
namespace app\admin\controller;
use PHPExcel;
use PHPExcel_IOFactory;
use think\Cache;
use think\Controller;
use think\facade\Env;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/11 0011
 * Time: 9:57
 */
class Excel extends Controller {
    public function addExcel()
    {
        $file = request()->file("file");
        $info = $file->move('uploads/user');

        $file_name="";
        if($info){
            $file_name = $info->getSaveName();
        }else{
            $this->error("错误,未获取到文件信息");
        }
        $root_path = Env::get('root_path');
        $filename = $root_path.'public/uploads/user/'.$file_name;
        $filename=iconv('GB2312','UTF-8',$filename);
        $final_arr = $this->_readExcel($filename);
        if(!empty($final_arr)){
            //设置快递excel的数据缓存
            cache("Kdexcel",$final_arr,6000);
            $msg=[
                'code'=>1,
                'msg'=>"解析成功",
            ];
        }else{
            $msg=[
                'code'=>0,
                'msg'=>"解析失败!",
            ];
        }




        return $msg;



    }

    //创建一个读取excel数据，可用于入库
    public function _readExcel($path)
    {
        //引用PHPexcel 类
//        include_once(IWEB_PATH.'core/util/PHPExcel.php');
//        include_once(IWEB_PATH.'core/util/PHPExcel/IOFactory.php');//静态类
        $type = 'Excel5';//设置为Excel5代表支持2003或以下版本，Excel2007代表2007版
        $xlsReader = PHPExcel_IOFactory::createReader($type);
        $xlsReader->setReadDataOnly(true);
        $xlsReader->setLoadSheetsOnly(true);
        $Sheets = $xlsReader->load($path);
        //开始读取上传到服务器中的Excel文件，返回一个二维数组
        $dataArray = $Sheets->getSheet(0)->toArray();
        return $dataArray;

        $inputFileType = \PHPExcel_IOFactory::identify($filename);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        //只设为读取,加快读取速度
        $objReader->setReadDataOnly(true);
        $objExcel = $objReader->load($filename);
    }

}