<?php
namespace app\mobile\controller;
use clt\Lunar;
class Index extends Common{
    public function initialize(){
        parent::initialize();

    }
    public function index(){
        return view();
    }


    public function zoushitu()
    {
        return $this->fetch('zoushitu');
    }

    public function btc()
    {
        return $this->fetch('btc');
    }

    public function bch()
    {
        return $this->fetch('bch');
    }

    public function eos()
    {
        return $this->fetch('eos');
    }

    public function eth()
    {
        return $this->fetch('eth');
    }

    public function ltc()
    {
        return $this->fetch('ltc');
    }

    public function xlm()
    {
        return $this->fetch('xlm');
    }

    public function xrp()
    {
        return $this->fetch('xrp');
    }
}