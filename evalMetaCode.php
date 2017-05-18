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
        $this->returnEvalValue = array();
        $this->base($this->codeMeta);
        return $this->returnEvalValue;
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
    public function &base($code){
        //所有code都有type属性
        //可以将源码添加returnEvalValue类型的元代码,这种类型会将返回值返回给调用evalMetaCode类的主程序
        if($code==null){//通常删除meta元代码,都是通过制空的形式操作的
        }else if($code['type']=='window'){
            if(count($code['child'])>0){
                foreach($code['child'] as $v){
                    $this->base($v);
                }
            }
        }elseif(in_array($code['type'],array('comments','phpBegin'))){
            return;
        }elseif($code['type']=='functionCall'){
            if($code['name']=='include_once'){

            }else{
                $funcName = $code['name'];
                $params = array();
                foreach($code['property'] as $v){
                    $params[] = &$this->base($v);

                }
                $reflectionMethod = new ReflectionFunction($funcName);
                return $reflectionMethod->invokeArgs($params);
            }
        }elseif($code['type']=='='){
            $obj1 = &$this->base($code['object1']);
            $obj1 = $this->base($code['object2']);
        }elseif($code['type']=='array'){
            $returnArr = array();
            foreach($code['child'] as $k=>$v){
                if($v['type']=='arrayValue'){
                    $returnArr[$this->base($v['key'])] = $this->base($v['value']);
                }else{
                    $returnArr[] = $this->base($v['value']);
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
            $obj = &$this->base($code['object']);
            return $obj[$this->base($code['key'])];
        }elseif($code['type']=='objectParams'){
            $obj = &$this->base($code['object']);
            $paramName = $code['name'];
            return $obj->$paramName;
        }elseif($code['type']=='objectFunction'){
            $obj = &$this->base($code['object']);
            $funcName = $code['name'];
            $params = array();
            foreach($code['property'] as $v){
                $params[] = &$this->base($v);

            }
            $reflectionMethod = new ReflectionMethod(get_class($obj),$funcName);
            return $reflectionMethod->invokeArgs($obj, $params);
        }elseif($code['type']=='staticFunction'){
            $className = $code['object'];
            $reflectionMethod = new ReflectionMethod($className,$code['name']);
            return $reflectionMethod->invokeArgs(null, array());
        }elseif($code['type']=='returnEvalValue'){
            $this->returnEvalValue[$this->base($code['key'])] = $this->base($code['value']);
        }elseif($code['type']=='echo'){
            echo $this->base($code['value']);
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
            $isTrye = $this->base($code['value']);
            if($isTrye){
                foreach($code['child'] as $v){
                    $this->base($v);
                }
            }
        }
        elseif($code['type']=='&&'){
            return $this->base($code['object1']) && $this->base($code['object2']);
        }
        elseif($code['type']=='!'){
            return !$this->base($code['value']);
        }
        elseif($code['type']=='null'){
            return null;
        }
        elseif($code['type']=='foreach'){
            foreach($this->base($code['object']) as $k=>$v){
                $name = $code['key']['name'];
                if($code['key']['type']=='objectParams'){
                    $obj = $this->base($code['key']['object']);
                    $obj->$name = $k;
                }else{
                    $this->runTimeVariable[$name] = $k;
                }
                $name2 = $code['value']['name'];
                if($code['value']['type']=='objectParams'){
                    $obj = $this->base($code['value']['object']);
                    $obj->$name2 = $v;
                }else{
                    $this->runTimeVariable[$name2] = $v;
                }
                foreach($code['child'] as $vv){
                    $this->base($vv);
                }
            }
            unset($this->runTimeVariable[$name]);
            unset($this->runTimeVariable[$name2]);
        }
        elseif($code['type']=='=='){
            return $this->base($code['object1']) == $this->base($code['object2']);
        }
        else{
            print_r($code);
        }
    }
}