<?php

class IndexController extends Rex_Controller
{
    public function indexAction(){
        $a = '1231231';
        $this->assign('a',$a);
        $this->display();
    }
}