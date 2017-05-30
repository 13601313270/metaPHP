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
    public $originBranch = '';
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
        $result = $this->exec('git branch '.$name);
        if($isChangeNow){
            $result = $this->exec('git checkout '.$name);
            $this->log('createBranch','创建分支:['.$name.']并切换到在此分支;结果'.json_encode($result));
        }else{
            $this->log('createBranch','创建分支:['.$name.'];结果'.json_encode($result));
        }
        return $result;
    }
    //删除分支
    public function deleteBranch($name){
        $result = $this->exec('git branch -D '.$name);
        $this->log('deleteBranch','删除分支:['.$name.'];结果'.json_encode($result));
        return $result;
    }
    //切换分支
    public function checkout($name){
        $result = $this->exec('git checkout '.$name);
        $this->log('createBranch','创建分支:'.$name.';结果'.json_encode($result));
        return $result;
    }
    //分支还原
    public function branchClean(){
        $result = $this->exec('git branch -vv');
        foreach($result as $branch){
            if(substr($branch,0,1)=='*'){
                if(preg_match('/\* (\S+) (\S+) \[(.+)\] (\S+)/',$branch,$match)){
                    $orignBranch = $match[3];
                    return $this->exec('git checkout .;git reset --hard '.current(explode(':',$orignBranch)).';git submodule foreach git checkout .');
                }

            }
        }
        return false;
    }
    //合并分支
    public function mergeBranch($branchName){
        $result = $this->exec('git merge --no-ff '.$branchName);
        $this->log('mergeBranch','合并分支:'.$branchName.';结果'.json_encode($result));
        return $result;
    }
    //更新绑定分支代码
    //isHoldWrite是否保留已经修改的工作区内容
    public function pull($isHoldWrite=false){
        if($isHoldWrite){
            $this->exec('git stash');
        }
        //写日志
        $result = $this->exec('git checkout .;git pull;git submodule update');
        if($isHoldWrite){
            $this->exec('git stash pop;git stash clear');
        }
        $this->log('githubReceive',json_encode($result));
        return $result;
    }
    //推送代码push
    public function push(){
        $result = $this->exec('git push origin '.$this->runLocalBranch);
        $this->log('mergeBranch','提交代码;结果'.json_encode($result));
        return $result;
    }
    //代码加入暂存区
    public function add($filePath){
        $result = $this->exec('git add '.$filePath);
        $this->log('commit','加入暂存区;结果'.json_encode($result));
        return $result;
    }
    //提交代码commit
    public function commit($message){
        $result = $this->exec('git commit -m "'.$message.'"');
        $this->log('commit','提交代码;结果'.json_encode($result));
        return $result;
    }
    //执行命令
    public function exec($line){
        $result = array();
        exec('cd ' . $this->webRootDir . ';'.$line.' 2>&1',$result);
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