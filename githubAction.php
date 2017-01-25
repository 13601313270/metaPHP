<?php
/**
 * Created by PhpStorm.
 * User: 王浩然
 * Date: 2016/12/28
 * Time: 下午5:16
 */
abstract class githubAction{
    //正在运行的本地分支
    public $runLocalBranch = '';
    public $webRootDir = '';
    public $cachePath = '';
    public function __construct()
    {
        if(empty($this->webRootDir)){
            throw new Exception('必须设置根目录');
        }
    }

    //创建分支
    public function createBranch($name,$isChangeNow=true){
        $result = $this->exec('cd ' . $this->webRootDir . ';git branch '.$name);
        if($isChangeNow){
            $result = $this->exec('cd ' . $this->webRootDir . ';git checkout '.$name);
            $this->log('createBranch','创建分支:['.$name.']并切换到在此分支;结果'.json_encode($result));
        }else{
            $this->log('createBranch','创建分支:['.$name.'];结果'.json_encode($result));
        }
    }
    //删除分支
    public function deleteBranch($name){
        $result = $this->exec('cd ' . $this->webRootDir . ';git branch -D '.$name);
        $this->log('deleteBranch','删除分支:['.$name.'];结果'.json_encode($result));
    }
    //切换分支
    public function checkout($name){
        $result = $this->exec('cd ' . $this->webRootDir . ';git checkout '.$name);
        $this->log('createBranch','创建分支:'.$name.';结果'.json_encode($result));
    }
    //分支还原
    public function branchClean(){
        $result = $this->exec('cd ' . $this->webRootDir . ';git stash;git checkout .');
        return $result;
    }
    //合并分支
    public function mergeBranch($branchName){
        $result = $this->exec('cd ' . $this->webRootDir . ';git merge --no-ff '.$branchName);
        $this->log('mergeBranch','合并分支:'.$branchName.';结果'.json_encode($result));
    }
    //更新绑定分支代码
    public function pull(){
        //写日志
        $result = $this->exec('cd ' . $this->webRootDir . ';git checkout '.$this->webRootDir.';git pull;');
        $this->log('githubReceive',json_encode($result));
    }
    //推送代码push
    public function push(){
        $result = $this->exec('cd ' . $this->webRootDir . ';git push origin '.$this->runLocalBranch);
        $this->log('mergeBranch','提交代码;结果'.json_encode($result));
    }
    //提交代码commit
    public function commit($message){
        $result = $this->exec('cd ' . $this->webRootDir . ';git add --all;git commit -m "'.$message.'"');
        $this->log('commit','提交代码;结果'.json_encode($result));
    }
    //执行命令
    public function exec($line){
        $result = array();
        $return = exec($line.' 2>&1',$result);
        print_r($result);
        print_r($return);
        return $result;
    }
    //记录日志
    public function log($type,$message){
        if(!is_dir($this->cachePath)){
            mkdir($this->cachePath);
        }
//        file_put_contents($this->cachePath.'/'.$type.'.log',$message."\n",FILE_APPEND);
    }
}