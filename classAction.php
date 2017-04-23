<?php
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 2016/12/21
 * Time: 下午3:29
 */
class classAction extends ReflectionClass{
    private $isInFile = true;
    private $isSysClass = false;
    /**
     * phpInterpreter类的一个实例,用来保存此类的元代码
     * @var phpInterpreter
     */
    public $phpInterpreter;
    function __construct($name,$isInFile=true){
        if($isInFile){
            parent::__construct($name);
            $this->filePath = parent::getFileName();
            if($this->filePath!=null){
                $content = implode('',@file($this->filePath));
                $this->phpInterpreter = new phpInterpreter($content);
            }else{
                $this->isSysClass = true;
            }
        }else{
            parent::__construct('stdClass');
            $this->phpInterpreter = new phpInterpreter('');
            $this->filePath = '';
        }
    }
    private $filePath;
    //获取文件路径
    public function getFileName(){
        return $this->filePath;
    }
    //获取类名
    public function getName(){
        if($this->isSysClass){
            return parent::getName();
        }else{
            $name = $this->phpInterpreter->search('.class name')->toArray();
            return $name[0];
        }
    }
    //获取父类
    public function getParentClass(){
        $parentClassName =  $this->phpInterpreter->search('.class extends');
        return new classAction($parentClassName,true);
    }
    private $__implements = array();
    //获取实现的接口
    public function getInterfaces(){
        if($this->isInFile) {
            return parent::getInterfaces();
        }else{
            $returnArr = array();
            foreach($this->__implements as $item){
                $returnArr[$item] = new ReflectionClass($item);
            }
            return $returnArr;
        }
    }
    /**
     * create
     * 函数的含义说明
     *
     * @access public
     * @since 1.0
     * @return $this
     */
    public static function createClass($name,$parentClass='',$implements=''){
        if($parentClass!='' && !class_exists($parentClass)){
            throw new Exception('创建的类的父类不存在');
        }
        if($implements!='' && !interface_exists($implements)){
            throw new Exception('实现的接口不存在');
        }
        $temp = get_called_class();
        $newCreateClass = new $temp($name,false);
        $newCreateClass->phpInterpreter->codeMeta['child'] = array(
            array(
                'type'=>'phpBegin',
            ),
            array(
                'type'=>'comments',
                'value'=>'*
 * Created by PhpStorm.
 * User: metaPHP
 * Date: '.date('Y/m/d').'
 * Time: '.date('H:i').'
 '
            ),
            array(
                'type'=>'class',
                'name'=>$name,
                'child'=>array(),
            ),
        );
        $class = $newCreateClass->phpInterpreter->search('#'.$name)->toArray();
        if($parentClass!==''){
            $class[0]['extends'] = $parentClass;
        }
        $newCreateClass->isInFile = false;
//        $newCreateClass->filePath = $filePath;
        return $newCreateClass;
    }
    //获取函数的代码
    public function getMethodsCode($methodName){
        $method = $this->getMethod(strval($methodName));
        $content = @file($method->getFileName());
        echo implode('',array_slice($content,$method->getStartLine()-1,$method->getEndLine()-$method->getStartLine()+1));
    }
    public function save(){
        $code = $this->phpInterpreter->getCode();
        $filePath = dirname($this->getFileName());
        if(!is_dir($filePath)){
            mkdir($filePath,0777,true);
        }
        file_put_contents($this->getFileName(),$code);
        $temp = get_called_class();
        return new $temp($this->getName());
    }
    public function getMethods($filter = null){
        $methods = parent::getMethods($filter);
        $returnArr = array();
        foreach($methods as $v){
            $returnArr[]= new functionAction($v->class,$v->name);
        }
        return $returnArr;
    }
    public function getInterfaceNames(){
        if($this->isInFile){
            return parent::getInterfaceNames();
        }else{
            return $this->__implements;
        }
    }
    //移除
    public function remove(){
        $class = &$this->phpInterpreter->search('#'.$this->getName());
        $class = null;
        $ifHas = false;//是否还含有有用信息
        foreach($this->phpInterpreter->codeMeta['child'] as $type){
            if($type!=null && !in_array($type['type'],array('phpBegin','comments','comment'))){
                $ifHas = true;
            }
        }
        if($ifHas){
            //如果删的什么都没有剩下,则整个文件删除掉
//            unlink($this->getFileName());
        }else{
//            file_put_contents($this->getFileName(),$content);
        }
    }

    /**
     * 添加属性
     *
     * @param string $key 属性名
     * @param array $value 属性值(元代码)
     * @param string $type 类型('private','protected','public','static')
     * @return array
     */
    public function setProperty($key,$value,$type){
        // $contrast为null的时候,$positionType的before和after代表添加到类的和开始或最后
        // $contrast不为null的时候,$positionType的before和after代表添加到$contrast的和开始或最后
        $class = $this->phpInterpreter->search('#'.$this->getName())->toArray();
        $class[0]['child'][] = array(
            'type'=>'property',
            'name'=>'$'.$key,
            $type=>true,
            'value'=>$value
        );
    }
}
class functionAction extends ReflectionMethod{
    public function __construct($class, $name)
    {
        parent::__construct($class, $name);
    }

    public $functionState = array();
    public function isAbstract($val=''){
        if($val==''){
            return parent::isAbstract();
        }else{
            $this->functionState['isAbstract'] = $val;
            return $this;
        }
    }
    public function isFinal($val=''){
        if($val==''){
            return parent::isFinal();
        }else{
            $this->functionState['isFinal'] = $val;
            return $this;
        }
    }
    public function isDeprecated($val='')
    {
        if($val!==''){
            $this->functionState['isDeprecated'] = $val;
            return $this;
        }
        return parent::isDeprecated();
    }
    public function save(){
        $content = @file($this->getFileName());
        $code = implode('',array_slice($content,$this->getStartLine()-1,$this->getEndLine()-$this->getStartLine()+1));
        foreach(array(
                    'isAbstract'=>'abstract',
                    'isFinal'=>'final'
                ) as $k=>$v){
            if(isset($this->functionState[$k])){
                $preg = '/(((public|private|protected|abstract|final|static)\s*?)*)\s*function\s*(\S*)\(/';
                preg_match($preg,$code,$match);
                $functionAbs = explode(' ',$match[1]);
                if(!in_array('abstract',$functionAbs) && $this->functionState[$k]==true){
                    $functionAbs[] = $v;
                }elseif(in_array('abstract',$functionAbs) && $this->functionState[$k]==false){

                }
                $code = preg_replace($preg,implode(' ',$functionAbs).' function $4(',$code);
            }
        }
        array_splice($content,$this->getStartLine()-1,$this->getEndLine()-$this->getStartLine()+1,$code);
        $content = implode("\n",$content);
        file_put_contents($this->getFileName(),$content);
    }
}