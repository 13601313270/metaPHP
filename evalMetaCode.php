<?php
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 2017/5/9
 * Time: 下午8:54
 */
class evalMetaCode{
    private $codeMeta;
    public function __construct($codeMeta,$variable)
    {
        $this->codeMeta = $codeMeta;
        foreach($variable as $k=>$v){
            $this->runTimeVariable[$k] = $v;
        }
    }
    public $returnEvalValue = array();
    public function run(){
        $this->isExit = false;
        $this->returnEvalValue = array();
        $result = $this->base($this->codeMeta);
        if(!empty($this->returnEvalValue)){
            return $this->returnEvalValue;
        }else{
            return $result;
        }
    }
    private $runTimeVariable = array();
    private function &getRunVariable($name){
        if(!isset($this->runTimeVariable[$name])){
            $this->runTimeVariable[$name] = 'NULL';
        }
        $return = &$this->runTimeVariable;
        $return = &$return[$name];
        return $return;
    }
    private $isExit = false;//如果遇到执行exit,则赋值true
    //$runStateMeta运行时meta元代码
    //$runTime运行时的值
    public function &base($code,$runStateMeta=false,$runTime=false){
        //所有code都有type属性
        //可以将源码添加returnEvalValue类型的元代码,这种类型会将返回值返回给调用evalMetaCode类的主程序
        if($code==null){//通常删除meta元代码,都是通过制空的形式操作的
        }elseif($code['type']=='window'){
            if(count($code['child'])>0){
                foreach($code['child'] as $v){
                    $this->base($v,$code);
                    if($this->isExit){
                        return null;
                    }
                }
            }
        }elseif($code['type']=='class'){
            $metaApi = new phpInterpreter('');
            $code = $metaApi->getCodeByCodeMeta($code,0);
            eval($code);
        }elseif(in_array($code['type'],array('comments','phpBegin'))){
            return null;
        }elseif($code['type']=='functionCall'){
            if($code['name']=='include_once'){

            }else{
                $funcName = $code['name'];
                $params = array();
                foreach($code['property'] as $v){
                    $params[] = &$this->base($v,$code);
                    if($this->isExit){
                        return null;
                    }
                }
                $reflectionMethod = new ReflectionFunction($funcName);
                return $reflectionMethod->invokeArgs($params);
            }
        }elseif($code['type']=='='){
            $obj1 = &$this->base($code['object1'],$code);
            if($this->isExit){
                return null;
            }
            $obj1 = $this->base($code['object2'],$code);
            if($this->isExit){
                return null;
            }
        }elseif($code['type']=='array'){
            $returnArr = array();
            foreach($code['child'] as $k=>$v){
                if($v['type']=='arrayValue'){
                    $returnArr[$this->base($v['key'],$v)] = $this->base($v['value'],$code);
                    if($this->isExit){return null;}
                }else{
                    $returnArr[] = $this->base($v,$code);
                    if($this->isExit){return null;}
                }
            }
            return $returnArr;
        }elseif($code['type']=='string'){
            return $code['data'];
        }elseif($code['type']=='variable'){
            $var = &$this->getRunVariable($code['name']);
            return $var;
        }elseif($code['type']=='new'){
            $className = $code['className'];
            return new $className();
        }elseif($code['type']=='arrayGet'){
            $obj = &$this->base($code['object'],$code);
            if($this->isExit){return null;}
            return $obj[$this->base($code['key'],$code,$obj)];
        }elseif($code['type']=='objectParams'){
            $obj = &$this->base($code['object'],$code);
            if(is_string($code['name'])){
                $paramName = $code['name'];
            }else{
                $paramName = &$this->base($code['name'],$code,$obj);
            }
            if($this->isExit){return null;}
            return $obj->$paramName;
        }elseif($code['type']=='objectFunction'){
            $obj = &$this->base($code['object'],$code);
            $funcName = $code['name'];
            $params = array();
            if(isset($code['property'])){
                foreach($code['property'] as $v){
                    $params[] = &$this->base($v,$code);
                    if($this->isExit){return null;}
                }
            }
            $reflectionMethod = new ReflectionMethod(get_class($obj),$funcName);
            return $reflectionMethod->invokeArgs($obj, $params);
        }elseif($code['type']=='staticFunction'){
            $className = $code['object'];
            $reflectionMethod = new ReflectionMethod($className,$code['name']);
            return $reflectionMethod->invokeArgs(null, array());
        }elseif($code['type']=='returnEvalValue'){
            $this->returnEvalValue[$this->base($code['key'],$code)] = $this->base($code['value'],$code);
            if($this->isExit){return null;}
        }elseif($code['type']=='echo'){
            echo $this->base($code['value'],$code);
            if($this->isExit){return null;}
        }
        elseif($code['type']=='html'){
            echo $code['value'];
        }
        elseif($code['type']=='phpEnd'){

        }
        elseif($code['type']=='int'){
            return intval($code['data']);
        }
        elseif($code['type']=='bool'){
            return $code['data']==='true';
        }
        elseif($code['type']=='if'){
            $isTrye = $this->base($code['value'],$code);
            if($this->isExit){return null;}
            if($isTrye){
                foreach($code['child'] as $v){
                    $this->base($v,$code);
                    if($this->isExit){return null;}
                }
            }
        }
        elseif($code['type']=='&&'){
            return $this->base($code['object1'],$code) && $this->base($code['object2'],$code);
        }
        elseif($code['type']=='!'){
            return !$this->base($code['value'],$code);
        }
        elseif($code['type']=='null'){
            return null;
        }
        elseif($code['type']=='foreach'){
            foreach($this->base($code['object'],$code) as $k=>$v){
                $name = $code['key']['name'];
                if($code['key']['type']=='objectParams'){
                    $obj = $this->base($code['key']['object'],$code['key']);
                    if($this->isExit){return null;}
                    $obj->$name = $k;
                }else{
                    $this->runTimeVariable[$name] = $k;
                }
                $name2 = $code['value']['name'];
                if($code['value']['type']=='objectParams'){
                    $obj = $this->base($code['value']['object'],$code);
                    if($this->isExit){return null;}
                    $obj->$name2 = $v;
                }else{
                    $this->runTimeVariable[$name2] = $v;
                }
                foreach($code['child'] as $vv){
                    $this->base($vv,$code);
                    if($this->isExit){return null;}
                }
            }
            unset($this->runTimeVariable[$name]);
            unset($this->runTimeVariable[$name2]);
        }
        elseif($code['type']=='=='){
            return $this->base($code['object1'],$code) == $this->base($code['object2'],$code);
        }
        elseif($code['type']=='!=='){
            return $this->base($code['object1'],$code) !== $this->base($code['object2'],$code);
        }
        elseif($code['type']=='exit'){
            $this->isExit = true;
        }
        elseif(in_array($code['type'],array('comment','comments'))){
        }
        elseif($code['type']=='+'){
            return $this->base($code['object1'],$code)+$this->base($code['object2'],$code);
        }
        elseif($code['type']=='-'){
            return $this->base($code['object1'],$code)-$this->base($code['object2'],$code);
        }
        elseif($code['type']=='.'){
            return strval($this->base($code['object1'],$code)).strval($this->base($code['object2'],$code));
        }
        elseif($code['type']=='__FILE__'){
            if(isset($this->runTimeVariable[$code['type']])){
                return $this->runTimeVariable[$code['type']];
            }else{
                var_dump('__FILE__的值,需要再evalMetaCode第二个参数传递进来');
            }
        }
        elseif($code['type']=='debug'){
            $this->returnEvalValue['debug']['runTime'] = $runStateMeta;
            $variable_ = $this->runTimeVariable;
            if($runStateMeta['type']=='objectParams'){
                $variable = array();
                foreach($variable_ as $k=>$v){
                    if(is_string($v)){
                        if(isset($runTime->$v)){
                            $variable[$k] = $v."【".$runTime->$v."】";
                        }else{
                            $variable[$k] = $v."【暂无值】";
                        }
                    }
                }
                foreach(get_object_vars($runTime) as $k=>$v){
                    $variable[$k] = $v;
                }
            }elseif($runStateMeta['type']=='arrayGet'){
                $variable = array();
                foreach($variable_ as $k=>$v){
                    if(is_string($v)){
                        if(isset($runTime->$v)){
                            $variable[$k] = $v."【".$runTime->$v."】";
                        }else{
                            $variable[$k] = $v."【暂无值】";
                        }
                    }
                }
                foreach($runTime as $k=>$v){
                    $variable["'".$k."'"] = "'".$k."'";
                }
            }else{
                $variable = $variable_;
            }
            $this->returnEvalValue['debug']['variable'] = $variable;
            $this->isExit = true;
        }
        else{
            echo "无法识别的meta代码\n";
            print_r($code);
        }
        return null;
    }
}