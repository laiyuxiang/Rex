<?php

class IndexController extends Rex_Controller
{
    public function indexAction(){
        $a = '1';
        $this->__set('a',$a);
        $this->display();
    }
}