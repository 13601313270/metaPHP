<?php
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 2016/12/21
 * Time: 下午3:29
 */
class classAction extends ReflectionClass{
    private $isInFile = true;
    function __construct($name,$isInFile=true){
        if($isInFile){
            parent::__construct($name);
        }else{
            parent::__construct('stdClass');
        }
    }
    private $filePath;
    //获取文件路径
    public function getFileName(){
        if($this->isInFile){
            return parent::getFileName();
        }else{
            return $this->filePath;
        }
    }
    private $__name;
    //获取类名
    public function getName(){
        if($this->isInFile){
            return parent::getName();
        }else{
            return $this->__name;
        }
    }
    //关于继承和父类
    private $parentClass;
    //获取父类
    public function getParentClass(){
        if($this->isInFile) {
            $temp = parent::getParentClass();
            if($temp==false){
                return false;
            }
            $parentClassName = $temp->getName();
        }else{
            if(empty($this->parentClass)){
                return false;
            }
            $parentClassName = $this->parentClass;
        }
        $temp = get_called_class();
        return new $temp($parentClassName);
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
    public static function createClass($name,$parentClass='',$implements='',$autoLoadClass){
        if($parentClass!='' && !class_exists($parentClass)){
            throw new Exception('创建的类的父类不存在');
        }
        if($implements!='' && !interface_exists($implements)){
            throw new Exception('实现的接口不存在');
        }
        $temp = get_called_class();
        $newCreateClass = new $temp($name,false);
        $newCreateClass->isInFile = false;
        $newCreateClass->filePath = $autoLoadClass($name);
        $newCreateClass->__name = $name;
        $newCreateClass->parentClass = $parentClass;
        if($implements==''){
            $newCreateClass->__implements = array();
        }elseif(is_string($implements)){
            $newCreateClass->__implements = array($implements);
        }else{
            $newCreateClass->__implements = $implements;
        }
        return $newCreateClass;
    }
    //获取函数的代码
    public function getMethodsCode($methodName){
        $method = $this->getMethod(strval($methodName));
        $content = @file($method->getFileName());
        echo implode('',array_slice($content,$method->getStartLine()-1,$method->getEndLine()-$method->getStartLine()+1));
    }
    public function save(){
        if($this->isInFile){
            echo 'exit;hear;';exit;
        }else{
            $code = "<?php
/**
 * Created by metaPHP.
 * User: metaPHPRobot
 * Date: ".date('Y-m-d')."
 * Time: ".date('H:i:s')."
 */\n";
            $code .= "class ".$this->getName();
            if($this->getParentClass()!==false){
                $code.= " extends ".$this->getParentClass()->getName();
            }
            $intefaceArr = $this->getInterfaceNames();
            if(count($intefaceArr)>0){
                $code.= " implements ".$intefaceArr[0];
            }
            $code .= "{\n";
            $code .= "}";

            $methods = $this->getMethods();
            if(count($methods)>0){
                echo 'hear;methods';exit;
            }
            file_put_contents($this->getFileName(),$code);

            $temp = get_called_class();
            return new $temp($this->getName());
        }
//
//
//        $content = @file($this->getFileName());
//        $code = implode('',array_slice($content,$this->getStartLine()-1,$this->getEndLine()-$this->getStartLine()+1));
//        foreach(array(
//                    'isAbstract'=>'abstract',
//                    'isFinal'=>'final'
//                ) as $k=>$v){
//            if(isset($this->functionState[$k])){
//                $preg = '/(((public|private|protected|abstract|final|static)\s*?)*)\s*function\s*(\S*)\(/';
//                preg_match($preg,$code,$match);
//                $functionAbs = explode(' ',$match[1]);
//                if(!in_array('abstract',$functionAbs) && $this->functionState[$k]==true){
//                    $functionAbs[] = $v;
//                }elseif(in_array('abstract',$functionAbs) && $this->functionState[$k]==false){
//
//                }
//                $code = preg_replace($preg,implode(' ',$functionAbs).' function $4(',$code);
//            }
//        }
//        array_splice($content,$this->getStartLine()-1,$this->getEndLine()-$this->getStartLine()+1,$code);
//        $content = implode("\n",$content);
//        file_put_contents($this->getFileName(),$content);
    }
    public function getMethods($filter = null){
        $methods = parent::getMethods($filter);
        $returnArr = array();
        foreach($methods as $v){
            $returnArr[] = new functionAction($v->class,$v->name);
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
        $content = @file($this->getFileName());
        $startLine = $this->getStartLine();
        $endLine = $this->getEndLine();
        $code = implode('',array_slice($content,$startLine-1,$endLine-$startLine+1));
        $content = implode('',$content);
        $content = str_replace($code,'',$content);
        $content = str_replace($this->getDocComment(),'',$content);
        $content = $this->codeClean($content);
        if(preg_match('/<\?php(\s*)$/',$content,$match)){
            //如果删的什么都没有剩下,则整个文件删除掉
            unlink($this->getFileName());
        }else{
            file_put_contents($this->getFileName(),$content);
        }
        echo $content;
    }
    //代码整理
    private function codeClean($content){
        $content = preg_replace("/<\?php(\s*)/","<?php\n",$content);
        $content = preg_replace("/(\s*)(final\s+|abstract\s+)?class/","\n$2class",$content);
        return $content;
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