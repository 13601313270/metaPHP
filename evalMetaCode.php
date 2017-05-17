<?php
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 2017/5/9
 * Time: 下午8:54
 */
class evalMetaCode{
    private $codeMeta;
    private $get;
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
        if($code['type']=='window'){
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
                print_r($code);
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
            if($code['name']=='$_GET'){
                $var = &$this->get;
            }else{
                $var = &$this->getRunVariable($code['name']);
            }
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
        else{
            print_r($code);
        }
    }
}