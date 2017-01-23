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
    public $runLocalBranch = '';//正在运行的本地分支
    public $runBranch = '';//正在运行的本地分支对应的远程分支
    public $webRootDir = '';
    public $cachePath = '';
    protected $listenBranch = array();
    //创建分支
    public function createBranch($name,$isChangeNow=true){
        $result = exec('cd ' . $this->webRootDir . ';git branch '.$name);
        if($isChangeNow){
            $result = exec('cd ' . $this->webRootDir . ';git checkout '.$name);
            $this->log('createBranch','创建分支:['.$name.']并切换到在此分支;结果'.$result);
        }else{
            $this->log('createBranch','创建分支:['.$name.'];结果'.$result);
        }
    }
    //删除分支
    public function deleteBranch($name){
        $result = exec('cd ' . $this->webRootDir . ';git branch -d '.$name);
        $this->log('deleteBranch','删除分支:['.$name.'];结果'.$result);
    }
    //切换分支
    public function checkout($name){
        $result = exec('cd ' . $this->webRootDir . ';git checkout '.$name);
        $this->log('createBranch','创建分支:'.$name.';结果'.$result);
    }
    //合并分支
    public function mergeBranch($branchName){
        $result = exec('cd ' . $this->webRootDir . ';git merge '.$branchName);
        $this->log('mergeBranch','合并分支:'.$branchName.';结果'.$result);
    }
    //更新绑定分支代码
    public function pull(){
        if(empty($this->listenBranch)){
            exit;
        }
        if(empty($this->webRootDir)){
            exit;
        }
        $response = json_decode(file_get_contents('php://input'));
        if(!in_array($response->ref,$this->listenBranch)){
            exit;
        }
        //写日志
        $this->checkout($this->runLocalBranch);
        $result = exec('cd ' . $this->webRootDir . ';git pull');
        $this->log('githubReceive',$result);
    }
    //提交代码
    public function push(){
        $result = exec('cd ' . $this->webRootDir . ';git push');
        $this->log('mergeBranch','提交代码;结果'.$result);
    }
    public function log($type,$message){
        if(!is_dir($this->cachePath)){
            mkdir($this->cachePath);
        }
        file_put_contents($this->cachePath.'/'.$type.'.log',$message."\n",FILE_APPEND);
    }
}