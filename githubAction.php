<?php
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 2016/12/28
 * Time: 下午5:16
 */
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);
include_once('classAction.php');
abstract class githubAction{
    protected $listenBranch = array();
    public function run(){
        if(empty($this->listenBranch)){
//            exit;
        }
        $response = json_decode(file_get_contents('php://input'));
        if(!in_array($response->ref,$this->listenBranch)){
//            exit;
        }
        if(!is_dir('../metaPHPCacheFile')){
            mkdir('../metaPHPCacheFile');
        }
        $webRootDir = dirname(dirname(__FILE__));
        $cachePath = $webRootDir.'/metaPHPCacheFile';
        //写日志
        file_put_contents($cachePath.'/githubReceive.log',$response->ref."\n",FILE_APPEND);
        //system()
//        $githubListenClass = classAction::createClass('githubListenClass',$cachePath.'/githubListen.php','temp');
//        print_r($githubListenClass->save());exit;
    }
}