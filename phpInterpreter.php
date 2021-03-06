<?php
/**
 * Created by PhpStorm.
 * User: 王浩然
 * Date: 2017/2/16
 * Time: 上午11:52
 */
namespace metaPHP;
include_once 'codeStyle.php';
include_once 'codeType/return.php';

final class phpInterpreter{
    private $savedCode;
    private $codeArr;
    public $codeMeta;
    private $codeArrPre ='';
    private $hasBeginPos = false;
    private $plubins = [];
    public function __construct($code)
    {
        $this->savedCode = $code;
        $temp = array();
        $this->plubins[] = new type_return();
        //生成运行时下可以的描述符
        foreach($this->dataTypeDesc as $v){
            foreach($v['runEnvironment'] as $runEnvironment){
                foreach($v['desc'] as $desc){
                    if(empty($temp[$runEnvironment])){
                        $temp[$runEnvironment] = array();
                    }
                    if(!in_array($desc,$temp[$runEnvironment])){
                        $temp[$runEnvironment][] = $desc;
                    }
                }
            }
        }
        $this->dataTypeDesc2 = $temp;
        //代码拆分成字符数组
        $this->codeArr = str_split($code);
        //解析php代码,转换成数据化的元代码(数组形式)
        $this->codeMeta = array(
            'type'=>'window',
            'child'=>$this->_getCodeMetaByCode('html','')
        );
        $this->codeArr = str_split($code);
    }
    private function throwWrong($msg){
        var_dump($msg);
        $codeLast = array_splice($this->codeArr, 0, 200);
        echo '错误定位:******     ' . implode('', $codeLast) . "\n";
        throw new Exception($msg);
    }
    /*
     * php代码解释器
     *
     * @param yunxingshiType 运行所在环境
     * @param beginStr 段落开始符
     * @param beginStr 段落结束符
     * @return array
     **/
    public function _getCodeMetaByCode($yunxingshiType, $beginStr, $endStr = false)
    {
        $return = array();
        $zancun = array();//用于描述关键词的一些关键词
        //如果规定了begin字符,则必须遵守
        if($beginStr!==''){
            $temp = $this->forward();
            if($temp!==$beginStr){
                $this->throwWrong($yunxingshiType.'必须由'.$beginStr.'开始结构,现在是'.$temp);
            }
        }
        //验证梭子是否到代码结尾,或者到规定的运行时结束符
        $nextKeyWord = $this->forward(true);
        if($nextKeyWord === $endStr || $nextKeyWord===false || (is_array($endStr) && in_array($nextKeyWord,$endStr))){
            $this->forward();
            return $return;
        }
        while(true){
            if($yunxingshiType=='html'){
                $html = $this->searchInsetStr("<?php");
                if($html!==''){
                    $return[] = array(
                        'lineNum'=>$this->lineNum,
                        'type'=>'html',
                        'value'=> $html
                    );
                }
                $this->lineNum+= substr_count($html,"\n");
                $yunxingshiType = 'window';
                if(trim(implode('',$this->codeArr)) !== ''){
                    $this->codeArrPre = "<?php";
                }
            }else{
                $nextKeyWord = $this->forward();//梭子获取下一个词语
                //注释
                if($nextKeyWord=='//'){
                    $return[] = array(
                        'lineNum'=>$this->lineNum,
                        'type'=>'comment',
                        'value'=> $this->searchInsetStr("\n")
                    );
                }
                //注释段
                elseif($nextKeyWord=='/*'){
                    $childYunxingshi = array(
                        'lineNum'=>$this->lineNum,
                        'type'=>'comments',
                        'value'=>''
                    );
                    do{
                        $childYunxingshi['value'] .= $this->searchInsetStr('/').'/';
                    }while(substr($childYunxingshi['value'],-2)!=='*/');
                    $childYunxingshi['value'] = substr($childYunxingshi['value'],0,-2);
                    $this->lineNum+=substr_count($childYunxingshi['value'],"\n");
                    $return[] = $childYunxingshi;
                }
                else{
                    //当前是否是类属性(在class运行时,并且关键词带有$符号)
                    $isClassProperty = ($yunxingshiType=='class'&&substr($nextKeyWord,0,1)=='$')?true:false;

                    //前置修饰关键词
                    if(isset($this->dataTypeDesc2[$yunxingshiType]) && in_array($nextKeyWord,$this->dataTypeDesc2[$yunxingshiType])){
                        $zancun[] = $nextKeyWord;
                        continue;
                    }
                    //命中运行时下的结构关键词
                    elseif(isset($this->dataTypeDesc[$isClassProperty?'property':$nextKeyWord]) && in_array($yunxingshiType,$this->dataTypeDesc[$isClassProperty?'property':$nextKeyWord]['runEnvironment'])){
                        $type = $isClassProperty?'property':$nextKeyWord;
                        $childResult = array(
                            'lineNum'=>$this->lineNum,
                            'type'=>$type
                        );
                        //取出暂存的描述符
                        if(isset($this->dataTypeDesc[$type]['desc'])){
                            foreach($zancun as $v){
                                if(in_array($v,$this->dataTypeDesc[$type]['desc'])){
                                    $childResult[$v] = true;
                                }else{
                                    $this->throwWrong($type.'类型不允许修饰符'.$v);
                                }
                            }
                            $zancun = array();
                        }
                        //类属性名
                        if($type=='property'){
                            $childResult['name'] = $nextKeyWord;
                            if($this->forward()=='='){
                                $childResult['value'] = current($this->_getCodeMetaByCode('codeBlock','',';'));
                            }
                        }
                        //定义类
                        elseif($type=='class'){
                            $keyName = $this->forward();
                            $childResult['name'] = $keyName;
                            //后置描述符
                            do {
                                $keyTemp = $this->forward();
                                if($keyTemp!=='{'){
                                    if(in_array($keyTemp,array('extends','implements'))){
                                        $childResult[$keyTemp] = $this->forward();
                                    }
                                }
                            } while ($keyTemp !== '{' && $keyTemp !== false);
                            $childResult['child'] = $this->_getCodeMetaByCode($type,'','}');
                        }
                        //定义函数
                        elseif($type=='function'){
                            $keyName = $this->forward();
                            if ($keyName !== '(') { // 非匿名函数
                                $childResult['name'] = $keyName;
                                if($keyName=='&'){
                                    $childResult['&'] = true;
                                    $childResult['name'] = $this->forward();
                                }
                                if($this->forward()!=='('){
                                    $this->throwWrong('函数名后面带上()参数');
                                }
                            }
                            //函数参数
                            $childResult['property'] = array();
                            $childResult['propertyType'] = array();
                            if ($this->forward(true) != ')') {
                                while ($this->forward(true) != '{') {
                                    $nextWord = $this->forward(true);
                                    if (substr($nextWord, 0, 1) == '$') {
                                        $childResult['propertyType'][] = '';
                                    } else {
                                        $childResult['propertyType'][] = $nextWord;
                                        $this->forward();
                                    }
                                    $childResult['property'][] = $this->_getCodeMetaByCode('code', '', array(',', ')'));
                                    if ($this->forward() === ')') {
                                        break;
                                    }
                                }
                            } else {
                                $this->forward();
                            }
                            if ($this->forward(true) === 'use') {
                                $childResult['use'] = $this->_getCodeMetaByCode('code', '', array('{'));
                            }
                            $childResult['child'] = $this->_getCodeMetaByCode($type,'{','}');
                        }
                        $return[] = $childResult;
                    }
                    //运算代码
                    else{
                        $childResult = array(
                            'lineNum'=>$this->lineNum,
                        );
                        $matchPlugin = null;
                        foreach ($this->plubins as $plugin) {
                            if ($plugin->matchKeyWord($nextKeyWord)) {
                                $matchPlugin = $plugin;
                                break;
                            }
                        }
                        if ($matchPlugin !== null) {
                            $childResult = $plugin->run($this);
                        }
                        else if(in_array($nextKeyWord,array('if','else','elseif'))){
                            $nextWord = $this->forward();
                            if($nextKeyWord=='else' && $nextWord=='if'){
                                $nextWord = $this->forward();
                                $nextKeyWord = 'elseif';
                            }
                            $childResult['type'] = $nextKeyWord;
                            if($nextKeyWord!='else'){
                                if($nextWord!='('){
                                    $this->throwWrong('if后面必须得跟着(');
                                }
                                $childResult['value'] = $this->_getCodeMetaByCode('code','',')',true);
                                if($this->forward()!=')'){
                                    $this->throwWrong('if条件后面必须得跟着{');
                                }
                                $childResult['child'] = $this->_getCodeMetaByCode($nextKeyWord,'{','}');
                            }else{
                                $childResult['child'] = $this->_getCodeMetaByCode($nextKeyWord,'','}');
                            }
                        }
                        elseif($nextKeyWord=='<?php'){
                            $childResult['type'] = 'phpBegin';
                        }
                        elseif($nextKeyWord=='?>'){
                            //结束符后面跟的第一个换行符,好像不会真的输出
                            if($this->codeArr[0]=="\n"){
                                array_shift($this->codeArr);
                            }
                            $childResult['type'] = 'phpEnd';
                            $yunxingshiType = 'html';
                            $return[] = $childResult;continue;
                        }
                        elseif($nextKeyWord=='new'){
                            $childResult['type'] = $nextKeyWord;
                            $childResult['name'] = $this->_getCodeMetaByCode('code','',$this->afterShunxu($nextKeyWord),true);
                            if($this->forward(true)==';'){//new后面允许不带()
                            }else{
                                if($this->forward()!='('){
                                    $this->throwWrong('new后面必须跟着(');
                                }
                                if($this->forward(true)==')'){
                                    $this->forward();
                                }else{
                                    do{
                                        $canshuArr[] = $this->_getCodeMetaByCode('code','',array(',',')'));
                                    }while($this->forward()==',');
                                    $childResult['property'] = $canshuArr;
                                }
                            }
                        }
                        elseif($nextKeyWord=='try'){
                            $childResult['type'] = 'try';
                            $childResult['child'] = $this->_getCodeMetaByCode('codeBlock','{','}');
                            if($this->forward()!='catch'){
                                $this->throwWrong('try后面必须跟着catch');
                            }
                            $childResult['catch'] = $this->_getCodeMetaByCode('code','(',')');
                            $this->forward();
                            $childResult['catchChild'] = $this->_getCodeMetaByCode('codeBlock','{','}');
                        }
                        elseif($nextKeyWord=='throw'){
                            $childResult = array(
                                'type'=>$nextKeyWord,
                                'value'=>$this->_getCodeMetaByCode('code','',';')
                            );
                        }
                        elseif($nextKeyWord=='echo'){
                            $childResult['type'] = $nextKeyWord;
                            $childResult['value'] = $this->_getCodeMetaByCode('code','',';');
                        }
                        elseif($nextKeyWord=='exit'){
                            $childResult['type'] = $nextKeyWord;
                            if($this->forward(true)=='('){
                                $this->forward();
                                $canshuArr = array();
                                if($this->forward(true)==')'){
                                    $this->forward();
                                }else{
                                    do{
                                        $canshuArr[] = $this->_getCodeMetaByCode('code','',array(',',')'));
                                    }while($this->forward()==',');
                                }
                                $childResult['property'] = $canshuArr;
                            }
                        }
                        elseif($nextKeyWord=='for'){
                            $childResult['type'] = $nextKeyWord;
                            $childResult['value'] = array(
                            );
                            $childResult['value'][] = $this->_getCodeMetaByCode('code','(',';');
                            $this->forward();
                            $childResult['value'][] = $this->_getCodeMetaByCode('code','',';');
                            $this->forward();
                            $childResult['value'][] = $this->_getCodeMetaByCode('code','',')');
                            $this->forward();
                            $childResult['child'] = $this->_getCodeMetaByCode($nextKeyWord,'{','}',true);
                        }
                        elseif($nextKeyWord=='foreach'){
                            $childResult['type'] = $nextKeyWord;
                            $childResult['value'] = array(
                                'object' => $this->_getCodeMetaByCode('code','(','as')
                            );
                            $this->forward();
                            $next = $this->_getCodeMetaByCode('code','',array('=>',')'));
                            if($this->forward(true)=='=>'){
                                $childResult['value']['key'] = $next;
                                $this->forward();
                                $childResult['value']['value'] = current($this->_getCodeMetaByCode('codeBlock','',')'));
                            }else{
                                $childResult['value']['value'] = $next;
                                $this->forward();
                            }
                            $childResult['child'] = $this->_getCodeMetaByCode($nextKeyWord,'{','}',true);
                        }
                        elseif($nextKeyWord=='while'){
                            $childResult['type'] = $nextKeyWord;
                            $childResult['value'] = $this->_getCodeMetaByCode('code','(',')');
                            $this->forward();
                            $childResult['child'] = $this->_getCodeMetaByCode('codeBlock','{','}');
                        }
                        elseif($nextKeyWord=='do'){
                            $childResult['type'] = 'dowhile';
                            $childResult['child'] = $this->_getCodeMetaByCode('codeBlock','{','}');
                            if($this->forward()!='while'){
                                $this->throwWrong('do循环后面必须加while条件');
                            }
                            $childResult['value'] = $this->_getCodeMetaByCode('code','(',')');
                            $this->forward();
                        }
                        elseif($nextKeyWord=='('){
                            if(count($return)>0){
                                $obj = $return[count($return)-1];
                                if($obj['type']=='variable'){
                                    //变量后面带 "(" 说明是函数调用
                                    array_pop($return);
                                    $childResult['type'] = 'functionCall';
                                    $childResult['name'] = $obj;
                                    $canshuArr = array();
                                    if($this->forward(true)==')'){
                                        $this->forward();
                                    }else{
                                        do{
                                            $canshuArr[] = $this->_getCodeMetaByCode('code','',array(',',')'));
                                        }while($this->forward()==',');
                                    }
                                    $childResult['property'] = $canshuArr;
                                }else{
                                    $childResult['type'] = 'codeBlock';
                                    $childResult['child'] = $this->_getCodeMetaByCode('codeBlock','',')');
                                    if(empty($childResult['child'])){
                                        $childResult = '()';
                                    }
                                }
                            }else{
                                $childResult['type'] = 'codeBlock';
                                $childResult['child'] = $this->_getCodeMetaByCode('codeBlock','',')');
                                if(empty($childResult['child'])){
                                    $childResult = '()';
                                }
                            }
                        }
                        elseif($nextKeyWord=='{'){
                            //{}包裹的代码段
                            $childResult['type'] = 'codeBlock';
                            $childResult['child'] = $this->_getCodeMetaByCode('codeBlock','','}');
                        }
                        elseif(in_array($nextKeyWord,array("'",'"'))){
                            //字符串变量
                            $string = '';
                            do{
                                $appendStr = $this->searchInsetStr($nextKeyWord);
                                if($appendStr==''){
                                    break;
                                }
                                $string .= $appendStr;
                            }while(substr($string,-1)=='\\' && substr($string,-2)!=='\\\\');
                            $childResult['type'] = 'string';
                            $childResult['borderStr'] = $nextKeyWord;
                            $childResult['data'] = $string;
                        }
                        elseif($nextKeyWord=='?'){
                            //三元运算符
                            $obj = $return[count($return)-1];
                            array_pop($return);
                            $childResult['type'] = $nextKeyWord;
                            $childResult['value'] = $obj;
                            $childResult['object1'] = $this->_getCodeMetaByCode('code','',':');
                            $childResult['object2'] = $this->_getCodeMetaByCode('code',':',$this->afterShunxu($nextKeyWord));
                        }
                        elseif(in_array($nextKeyWord,array('->','::'))){
                            //雷属性或方法调用运算符
                            $obj = $return[count($return)-1];
                            array_pop($return);
                            $childResult['object'] = $obj;
                            $childResult['name'] = $this->_getCodeMetaByCode('code','',$this->afterShunxu('&'));
                            if($this->forward(true)=='('
                                &&(
                                    (is_array($endStr) && !in_array('(',$endStr))
                                    ||
                                    (!is_array($endStr) && $endStr !== '(')
                                )
                            ){
                                //因为有些运算的优先级高于(,比如new,这时候不能尝试作为函数运行,而要作为属性返回,再执行new的参数
                                if($childResult['name']=='__construct'){
                                    $childResult['type'] = $childResult['name'];
                                }else{
                                    $childResult['type'] = $nextKeyWord=='->'?'objectFunction':'staticFunction';
                                }
                                $this->forward();
                                if($this->forward(true)==')'){
                                    $this->forward();
                                }else{
                                    $canshuArr = array();
                                    do{
                                        $canshuArr[] = $this->_getCodeMetaByCode('code','',array(',',')'));
                                    }while($this->forward()==',');
                                    $childResult['property'] = $canshuArr;
                                }
                            }else{
                                $childResult['type'] = $nextKeyWord=='->'?'objectParams':'staticParams';
                            }
                        }
                        //二元运算符($1运算符$2),类型的运算
                        elseif(in_array($nextKeyWord,array('&&','^','||','or','[]=','+=','-=','==','===','>=','<=','!==','!=','>','<','.','+','-','=','.='))){
                            $obj = $return[count($return)-1];
                            array_pop($return);
                            $childResult['type'] = $nextKeyWord;
                            $childResult['object1'] = $obj;
                            $childResult['object2'] = $this->_getCodeMetaByCode('code','',$this->afterShunxu($nextKeyWord));
                        }
                        elseif(in_array($nextKeyWord,array('!'))){
                            $childResult['type'] = $nextKeyWord;
                            $childResult['object'] = $this->_getCodeMetaByCode('code','',$this->afterShunxu($nextKeyWord));
                        }
                        elseif(in_array($nextKeyWord,array('true','false'))){
                            //bool变量
                            $childResult['type'] = 'bool';
                            $childResult['data'] = $nextKeyWord;
                        }
                        elseif(in_array($nextKeyWord,array('break','continue'))){
                            $childResult['type'] = $nextKeyWord;
                        }
                        elseif($nextKeyWord=='['){
                            if (count($return) === 0) {
                                $childResult['type'] = 'array';
                                $childResult['child'] = $this->_getCodeMetaByCode('array', '', ']');
                            } else {
                                //数组取值运算符
                                $obj = $return[count($return)-1];
                                array_pop($return);
                                $childResult['type'] = 'arrayGet';
                                $childResult['object'] = $obj;
                                $childResult['key'] = $this->_getCodeMetaByCode('code','',']');
                                $this->forward();
                            }
                        }
                        elseif(in_array($nextKeyWord,array('--','++'))){
                            $obj = $return[count($return)-1];
                            array_pop($return);
                            $childResult['type'] = $nextKeyWord;
                            $childResult['object'] = $obj;
                        }
                        elseif(in_array($nextKeyWord,array('parent','self'))){
                            //父类
                            $childResult['type'] = $nextKeyWord;
                        }
                        elseif($nextKeyWord=='include_once'){//既可以用括号,又可以用空格的关键词
                            $childResult['type'] = 'functionCall';
                            $childResult['name'] = $nextKeyWord;
                            if($this->forward(true)=='('){
                                $this->forward();
                                $canshuArr = array();
                                if($this->forward(true)==')'){
                                    $this->forward();
                                }else{
                                    do{
                                        $canshuArr[] = $this->_getCodeMetaByCode('code','',array(',',')'));
                                    }while($this->forward()==',');
                                }
                                $childResult['property'] = $canshuArr;
                            }else{
                                $childResult['property'] = $this->_getCodeMetaByCode('codeBlock','',';');
                            }
                        }
                        elseif(substr($nextKeyWord,0,1)=='$'){
                            //变量
                            $childResult['type'] = 'variable';
                            $childResult['name'] = $nextKeyWord;
                        }
                        elseif($nextKeyWord=='&'){
                            $childResult['type'] = '&';
                            $childResult['object'] = $this->_getCodeMetaByCode('code','',$this->afterShunxu($nextKeyWord));
                        }
                        elseif(preg_match('/^0(\d+)$/',$nextKeyWord,$match)){
                            //8进制整数
                            $childResult['type'] = '8int';
                            $childResult['data'] = $match[0];
                        }
                        elseif(preg_match('/^(\d+)$/',$nextKeyWord,$match)){
                            //10进制整数
                            $childResult['type'] = 'int';
                            $childResult['data'] = $match[0];
                        }
                        elseif($nextKeyWord == 'array'){
                            //数组定义
                            $childResult['type'] = $nextKeyWord;
                            $childResult['child'] = $this->_getCodeMetaByCode('array','(',')');
                        }
                        elseif($this->forward(true)==='(' && preg_match('/^[A-Z|a-z|_][A-Z|a-z|_|0-9]*$/',$nextKeyWord,$match) && ((is_array($endStr) && !in_array('(',$endStr)) || (!is_array($endStr) && $endStr!='('))){
                            //函数调用带有变量后面带着"("
                            $childResult['type'] = 'functionCall';
                            $childResult['name'] = $nextKeyWord;
                            $this->forward();
                            $canshuArr = array();
                            if($this->forward(true)==')'){
                                $this->forward();
                            }else{
                                do{
                                    $canshuArr[] = $this->_getCodeMetaByCode('code','',array(',',')'));
                                }while($this->forward()==',');
                            }
                            $childResult['property'] = $canshuArr;
                        }
                        elseif(in_array($nextKeyWord,array('null','__FILE__','$this'))){
                            $childResult['type'] = $nextKeyWord;
                        }
                        elseif($nextKeyWord=='#debug' && $this->forward()==='('){
                            $canshuArr = array();
                            if($this->forward(true)==')'){
                                $this->forward();
                            }else{
                                do{
                                    $canshuArr[] = $this->_getCodeMetaByCode('code','',array(',',')'));
                                }while($this->forward()==',');
                            }
                            $childResult['type'] = 'debug';
                            $childResult['property'] = $canshuArr;
                            if($this->forward()!='#'){
                                $this->throwWrong('debug必须以#结束');
                            }
                        }
                        elseif($yunxingshiType=='array'){
                            //数组子值赋值
                            if($nextKeyWord =='=>'){
                                $obj = $return[count($return)-1];
                                array_pop($return);
                                $childResult['type'] = 'arrayValue';
                                $childResult['key'] = $obj;
                                $childResult['value'] = $this->_getCodeMetaByCode('code','',array(',',')'));
                                if($this->forward(true)==','){
                                    $this->forward();
                                }
                            }elseif($nextKeyWord==','){
                                if($this->forward(true)!==')'){
                                    continue;
                                }
                            }
                        }
                        else{
                            if($nextKeyWord==false){
                                break;
                            }
                            if(in_array($nextKeyWord,array(';'))){ }
                            elseif(preg_match('/^[A-Z|a-z|_][A-Z|a-z|_|0-9]*$/',$nextKeyWord,$match)){
                                //类名或系统函数名
                            }
                            else{
                                throw new Exception('未识别的代码'.$nextKeyWord);
                            }
                            $childResult = $nextKeyWord;

                        }
                        if($childResult!==';'){
                            $return[] = $childResult;
                        }
                    }
                }
                //类型结束符号
                $nextKeyWord = $this->forward(true);
                if($yunxingshiType=='code'){
                    if($nextKeyWord === $endStr || $nextKeyWord===false || (is_array($endStr) && in_array($nextKeyWord,$endStr))){
                        return $childResult;
                    }
                }
            }
            if($nextKeyWord === $endStr || $nextKeyWord===false || (is_array($endStr) && in_array($nextKeyWord,$endStr))){
                $this->forward();
                break;
            }
        }
        return $return;
    }
    /**
     * 元代码模块查找
     *
     * @param string $searchStr 查找字符串
     * @return metaSearch
     */
    public function search($searchStr){
        //字符串用空格分割了层级
        //字符串代表了查找子程序name查找
        $metaSearchApi = new metaSearch($this->codeMeta);
        return $metaSearchApi->search($searchStr);
    }

    /*
     * 把meta信息还原成php代码
     *
     * @return string
     **/
    public function getCode(){
        $codeMetaArr = $this->codeMeta;
        return $this->getCodeByCodeMeta($codeMetaArr,0);
//        $createdCode = $this->getCodeByCodeMeta($codeMetaArr,0);
        $codePos2 = 0;
        if(!empty($this->savedCode)){
            $returnCode = '';
            for($codePos=0;$codePos<=strlen($this->savedCode)-1;$codePos++){
                $returnCode.=$this->savedCode[$codePos];
                if(in_array($this->savedCode[$codePos],array(''," ","\t","\n"))){
                    continue;
                }
                do{
                    $code2Word = $createdCode[$codePos2];
                    $codePos2++;
                }while(in_array($code2Word,array(''," ","\t","\n") ));
                if($this->savedCode[$codePos]!==$createdCode[$codePos2-1]){
                    echo "\n=======true=======\n";
                    echo substr($this->savedCode,$codePos,100);
                    echo "\n=======wrong!!=======\n";
                    echo substr($createdCode,$codePos2-1,100);
                    exit;
                }
            }
            return $returnCode;
        }else{
            return $createdCode;
        }
    }

    public function getHtml()
    {
        $api = new getHtmlByMeta();
        return $api->getCodeByCodeMeta($this->codeMeta, 0);
    }

    /*
     * 生成对应的缩进符号
     *
     * @return string
     **/
    public function getTabStr($tab){
        $return = '';
        for($i=0;$i<$tab;$i++){
            $return .= codeStyle::$indent;
        }
        return $return;
    }
    //不用分号结尾的类型
    private $noFenhaoType = array('comment', 'comments', 'phpBegin', 'phpEnd', 'html', 'class');
    private $linkRunSign = false;
    /*
     * 元代码还原代码器
     * @param codeMetaArr 元代码
     * @param tab 缩进
     *
     * @return string
     **/
    public function getCodeByCodeMeta($codeMetaArr,$tab){
        $tabStr = $this->getTabStr($tab);
        $return = '';
        if(isset($codeMetaArr['type'])){

            $matchPlugin = null;
            foreach ($this->plubins as $plugin) {
                if ($plugin->key=== $codeMetaArr['type']) {
                    $matchPlugin = $plugin;
                    break;
                }
            }
            if ($matchPlugin !== null) {
                $return = $matchPlugin->getCodeByMeta($codeMetaArr, $this, $tab);
            }
            else if($codeMetaArr['type']=='window'){
                $return = $tabStr;
                foreach($codeMetaArr['child'] as $v){
                    $return .= $this->getCodeByCodeMeta($v,0);
                    if(isset($v['child']) || in_array($v['type'],$this->noFenhaoType)){
                        // 这些元素本身已经输出了换行，或者不允许换行
                        if (!in_array($v['type'], ['html', 'phpBegin', 'phpEnd'])) {
                            $return .= "\n";
                        }
                    }else{
                        $return .=";\n";
                    }
                }
            }
            elseif($codeMetaArr['type']=='phpBegin'){
                $return = $tabStr."<?php\n";
            }
            elseif($codeMetaArr['type']=='phpEnd'){
                $return = $tabStr."?>\n";
            }
            elseif($codeMetaArr['type']=='comments'){
                $return = $tabStr.'/*'.$codeMetaArr['value']."*/\n";
            }
            elseif($codeMetaArr['type']=='comment'){
                $return = $tabStr . '//' . codeStyle::$spaced_comment . $codeMetaArr['value'];
            }
            elseif(in_array($codeMetaArr['type'],array('boolean','bool'))){
                return ($codeMetaArr['data']===true||$codeMetaArr['data']==='true')?'true':'false';
            }
            elseif($codeMetaArr['type']=='exit'){
                $return = $tabStr.$codeMetaArr['type'];
                if(isset($codeMetaArr['property'])){
                    $allParams = array();
                    foreach($codeMetaArr['property'] as $param){
                        $allParams[] = $this->getCodeByCodeMeta($param,0);
                    }
                    $return .= '('.implode(',',$allParams).')';
                }
                return $return;
            }
            elseif(in_array($codeMetaArr['type'],array('parent','self','break','continue'))){
                return $tabStr.$codeMetaArr['type'];
            }
            elseif(in_array($codeMetaArr['type'],array('null','__FILE__','E_ALL','E_NOTICE','$this'))){
                $return = $codeMetaArr['type'];
            }
            elseif($codeMetaArr['type']=='class'){
                $return = $tabStr;
                foreach($codeMetaArr as $k=>$v){
                    if(in_array($k,$this->dataTypeDesc['class']['desc'])){
                        $return .= $k." ";
                    }
                }
                $return .= 'class '.$codeMetaArr['name'];
                foreach($codeMetaArr as $k=>$v){
                    if(in_array($k,array('extends','implements'))){
                        $return.=' '.$k.' '.$v;
                    }
                }
                $return .= codeStyle::$apace_before_class_lbrace . "{\n";
                if (isset($codeMetaArr['child'])) {
                    foreach ($codeMetaArr['child'] as $v) {
                        $return .= $this->getCodeByCodeMeta($v, $tab + 1) . "\n";
                    }
                }
                $return .= $tabStr."}";
            }
            elseif($codeMetaArr['type']=='property'){
                $return = $tabStr;
                foreach($codeMetaArr as $k=>$v){
                    if( in_array($k, $this->dataTypeDesc['property']['desc']) ){
                        $return .= $k.' ';
                    }
                }
                $return.=$codeMetaArr['name'];
                if(isset($codeMetaArr['value'])){
                    $return .= codeStyle::$spaceInfixOps[0] . '=' . codeStyle::$spaceInfixOps[1];
                    $valuve = $this->getCodeByCodeMeta($codeMetaArr['value'],$tab);
                    $return .= preg_replace('/^\s+/','',$valuve);
                }
                $return.=";";
            }
            elseif($codeMetaArr['type']=='function'){
                $return = $tabStr;
                foreach($codeMetaArr as $key=>$value){
                    if(in_array($key,$this->dataTypeDesc['function']['desc'])){
                        if(isset($codeMetaArr[$key]) && $codeMetaArr[$key]==1){
                            $return .= $key.' ';
                        }
                    }
                }
                if (isset($codeMetaArr['name'])) {
                    $return .= 'function '.$codeMetaArr['name'].'(';
                } else {
                    $return .= 'function(';
                }
                if(isset($codeMetaArr['property'])){
                    foreach($codeMetaArr['property'] as $k=>$v){
                        if($k!=0){
                            $return .= codeStyle::$commaSpacing[0] . ',' . codeStyle::$commaSpacing[1];
                        }
                        if(!empty($codeMetaArr['propertyType'][$k])){
                            $return .= $codeMetaArr['propertyType'][$k].' ';
                        }
                        $return.= $this->getCodeByCodeMeta($v,0);
                    }
                }
                $return .= ")";
                if (isset($codeMetaArr['use'])) {
                    $return .= ' ' . $this->getCodeByCodeMeta($codeMetaArr['use'], 0);
                }
                $return .= codeStyle::$spaceBeforeBlocks . "{\n";
                foreach($codeMetaArr['child'] as $v){
                    $return .= $this->getCodeByCodeMeta($v,$tab+1);
                    if(isset($v['child']) || in_array($v['type'],$this->noFenhaoType)){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return.=$tabStr."}";
            }
            elseif(in_array($codeMetaArr['type'],array('if','else','elseif'))){
                $return = $tabStr.$codeMetaArr['type'];
                if($codeMetaArr['type']!='else'){
                    $return .= '(';
                    $return .= $this->getCodeByCodeMeta($codeMetaArr['value'],0);
                    $return .= ')';
                }
                $return .= codeStyle::$spaceBeforeBlocks . "{\n";
                foreach($codeMetaArr['child'] as $v){
                    $return .= $this->getCodeByCodeMeta($v,$tab+1);
                    if(isset($v['child']) || $v['type']=='comment'){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return .= $tabStr."}";
            }
            elseif($codeMetaArr['type']=='构造函数'){
                $return = $tabStr;
                $return .= $this->getCodeByCodeMeta($codeMetaArr['object'],0);
                $return .= '::';
                $return .= $codeMetaArr['name'];
                $return .= '(';
                foreach($codeMetaArr['property'] as $v){
                    $return.= $this->getCodeByCodeMeta($v,0);
                }
                $return .= ')';
            }
            elseif($codeMetaArr['type']=='父类'){
                $return = $tabStr.$codeMetaArr['name'];
            }
            elseif($codeMetaArr['type']=='variable'){
                if (isset($codeMetaArr['name'])) {
                    $return = $tabStr . $codeMetaArr['name'];
                } else {
                    $return = $tabStr . $codeMetaArr['data'];
                }
            }
            elseif($codeMetaArr['type']=='string'){
                if(isset($codeMetaArr['borderStr']) && $codeMetaArr['borderStr']=='\''){
                    $codeMetaArr['data'] = str_replace("'",'\\\'',$codeMetaArr['data']);
                    $return = $tabStr.'\''.$codeMetaArr['data'].'\'';
                }else{
                    $codeMetaArr['data'] = str_replace('"','\\"',$codeMetaArr['data']);
                    $return = $tabStr.'"'.$codeMetaArr['data'].'"';
                }
            }
            elseif($codeMetaArr['type']=='objectParams'){
                $return = $tabStr.$this->getCodeByCodeMeta($codeMetaArr['object'],0).'->'.$codeMetaArr['name'];
            }
            elseif($codeMetaArr['type']=='return'){
                $returnValue = $this->getCodeByCodeMeta($codeMetaArr['value'], $tab);
                $return = $tabStr.'return '.preg_replace('/^\s+/', '', $returnValue);
            }
            elseif(in_array($codeMetaArr['type'],array('&&','^','||','or','[]=','+=','-=','==','===','>=','<=','!==','!=','>','<','.','+','-','=','.='))){
                $return = $tabStr.$this->getCodeByCodeMeta($codeMetaArr['object1'],0);
                if($codeMetaArr['type']=='or'){
                    $return .= ' '.$codeMetaArr['type'].' ';
                }else{
                    $return .= codeStyle::$spaceInfixOps[0] . $codeMetaArr['type'] . codeStyle::$spaceInfixOps[1];
                }
                $value = $this->getCodeByCodeMeta($codeMetaArr['object2'],$tab);
                $return .= preg_replace('/^\s+/','',$value);
            }
            elseif(in_array($codeMetaArr['type'],array('staticFunction','objectFunction','__construct'))){
                $oldLock = $this->linkRunSign;
                if ($codeMetaArr['type'] == 'objectFunction') {
                    if ($codeMetaArr['object']['type'] === 'objectFunction') {
                        $this->linkRunSign = codeStyle::$linkRunNewline;
                    }
                    $return = $this->getCodeByCodeMeta($codeMetaArr['object'], $tab);
                    if ($this->linkRunSign) {
                        $return .= "\n" . $this->getTabStr($tab + 1) . "->";
                    } else {
                        $return .= '->';
                    }
                } else {
                    $return = $this->getCodeByCodeMeta($codeMetaArr['object'], $tab);
                    $return .= '::';
                }
                $return .=$codeMetaArr['name'];
                $allParams = array();
                if(isset($codeMetaArr['property'])){
                    foreach ($codeMetaArr['property'] as $param) {
                        $paramStr = $this->getCodeByCodeMeta($param, $this->linkRunSign ? $tab + 1 : $tab);
                        $allParams[] = preg_replace('/^' . $this->getTabStr($this->linkRunSign ? $tab + 1 : $tab) . '/', '', $paramStr);
                    }
                    $return .= '('.implode(codeStyle::$commaSpacing[0] . ',' . codeStyle::$commaSpacing[1],$allParams).')';
                }else{
                    $return .= '()';
                }
                if (!$oldLock && $this->linkRunSign) {
                    $this->linkRunSign = false;
                }
            }
            elseif($codeMetaArr['type']=='functionCall'){
                $return = $tabStr.$this->getCodeByCodeMeta($codeMetaArr['name'],0);
                if(isset($codeMetaArr['property'])){
                    $allParams = array();
                    foreach($codeMetaArr['property'] as $param){
                        $allParams[] = $this->getCodeByCodeMeta($param,0);
                    }
                    $return .= '('.implode(codeStyle::$commaSpacing[0] . ',' . codeStyle::$commaSpacing[1],$allParams).')';
                }else{
                    $return .= '()';
                }
            }
            elseif($codeMetaArr['type']=='new'){
                $return = $tabStr.'new '.$this->getCodeByCodeMeta($codeMetaArr['name'],0);
                if(isset($codeMetaArr['property'])){
                    $allParams = array();
                    foreach($codeMetaArr['property'] as $param){
                        $allParams[] = $this->getCodeByCodeMeta($param,0);
                    }
                    $return .= '('.implode(codeStyle::$commaSpacing[0] . ',' . codeStyle::$commaSpacing[1],$allParams).')';
                }else{
                    $return .= '()';
                }
            }
            elseif($codeMetaArr['type']=='foreach'){
                $return = $tabStr.'foreach(';
                $return .=$this->getCodeByCodeMeta($codeMetaArr['object'],0);
                $return .=' as ';
                if(isset($codeMetaArr['key'])){
                    $return .=$this->getCodeByCodeMeta($codeMetaArr['key'],0).' =>';
                }
                $return .= $this->getCodeByCodeMeta($codeMetaArr['value'], 0) . ")" . codeStyle::$spaceBeforeBlocks . "{\n";
                foreach($codeMetaArr['child'] as $child){
                    $return .=$this->getCodeByCodeMeta($child,$tab+1);
                    if(isset($child['child']) || in_array($child['type'],$this->noFenhaoType)){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return .= $tabStr.'}';
            }
            elseif($codeMetaArr['type']=='while'){
                $return = $tabStr.'while(';
                $return .= $this->getCodeByCodeMeta($codeMetaArr['value'], 0) . ")" . codeStyle::$spaceBeforeBlocks . "{\n";
                foreach($codeMetaArr['child'] as $child){
                    $return .=$this->getCodeByCodeMeta($child,$tab+1);
                    if(isset($child['child']) || $child['type']=='comment'){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return .= $tabStr.'}';
            }
            elseif($codeMetaArr['type']=='dowhile'){
                $return = $tabStr . "do" . codeStyle::$spaceBeforeBlocks . "{\n";
                foreach($codeMetaArr['child'] as $child){
                    $return .=$tabStr.$this->getCodeByCodeMeta($child,$tab+1);
                    if(isset($child['child']) || $child['type']=='comment'){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return .= $tabStr.'}while(';
                $return .=$this->getCodeByCodeMeta($codeMetaArr['value'],0).");";
            }
            elseif($codeMetaArr['type']=='arrayGet'){
                $return = $tabStr.$this->getCodeByCodeMeta($codeMetaArr['object'],0);
                $return .= '['.$this->getCodeByCodeMeta($codeMetaArr['key'],0).']';
            }
            elseif($codeMetaArr['type']=='!'){
                $return = $tabStr.'!'.$this->getCodeByCodeMeta($codeMetaArr['value'],0);
            }
            elseif(in_array($codeMetaArr['type'],array('throw','echo'))){
                $return = $tabStr.$codeMetaArr['type'].' '.$this->getCodeByCodeMeta($codeMetaArr['value'],0);
            }
            elseif($codeMetaArr['type']=='array'){
                //这段一直写的不好.未来优化
                if(empty($codeMetaArr['child'])){
                    $return = $tabStr.'array()';
                }else{
                    if (codeStyle::$arrayElementNewline === 'auto') {
                        $isShowMoreLine = false;
                        foreach ($codeMetaArr['child'] as $k => $child) {
                            if ($child === null) {
                                continue;
                            }
                            if (in_array($child['type'], array('comments', 'comment'))) {
                                $isShowMoreLine = true;//注释后面不能加逗号
                                break;
                            } elseif (isset($child['value']['child'])) {
                                $isShowMoreLine = true;
                                break;
                            }
                        }
                        if ($isShowMoreLine === false) {
                            // 键值对数组，数量较多时
                            if (isset($codeMetaArr['child'][0]['key']) && count($codeMetaArr['child']) >= 3) {
                                $isShowMoreLine = true;
                            }
                        }
                        foreach ($codeMetaArr['child'] as $k => $child) {
                            if ($child === null) {
                                continue;
                            }
                            if (in_array($child['type'], array('comments', 'comment'))) {
                                $isShowMoreLine = true;//注释后面不能加逗号
                                break;
                            } elseif (isset($child['value']['child'])) {
                                $isShowMoreLine = true;
                                break;
                            }
                        }
                    } else if (codeStyle::$arrayElementNewline === 'never') {
                        $isShowMoreLine = false;//是否输出了多行
                    } else {
                        $isShowMoreLine = true;//是否输出了多行
                    }
                    $return = $tabStr.'array('.($isShowMoreLine?"\n":'');
                    foreach($codeMetaArr['child'] as $k=>$child){
                        if($child===null){continue;}
                        $return .= $this->getCodeByCodeMeta($child,$isShowMoreLine?($tab+1):0).
                            ($k != count($codeMetaArr['child']) - 1 ? codeStyle::$commaSpacing[0] . ',' . codeStyle::$commaSpacing[1] : '') .
                            ($isShowMoreLine?"\n":'');
                    }
                    $return .= ($isShowMoreLine?$tabStr:'').')';
                }
            }
            elseif(in_array($codeMetaArr['type'],array('int','integer','8int'))){
                if($codeMetaArr['type']=='int'){
                    if(is_int($codeMetaArr['data']) || preg_match('/^\d+$/',$codeMetaArr['data'])){
                    }else{
                        throw new Exception('数据类型int,的值不是数值类型');
                    }
                }
                $return = $tabStr.$codeMetaArr['data'];
            }
            elseif($codeMetaArr['type']=='arrayValue'){
                $return = $tabStr.$this->getCodeByCodeMeta($codeMetaArr['key'],0).' => ';
                $value = $this->getCodeByCodeMeta($codeMetaArr['value'],$tab);
                $return .= preg_replace('/^'.$tabStr.'/','',$value);
            }
            elseif($codeMetaArr['type']=='codeBlock'){
                $return = $tabStr.'(';
                foreach($codeMetaArr['child'] as $k=>$v){
                    $return .= $this->getCodeByCodeMeta($v,0);
                }
                $return .=')';
            }
            elseif($codeMetaArr['type']=='?'){
                //三元运算符
                $return = $tabStr;
                $value = $this->getCodeByCodeMeta($codeMetaArr['value'],$tab).'?';
                $return .= preg_replace('/^'.$tabStr.'/','',$value);
                $object1 = $this->getCodeByCodeMeta($codeMetaArr['object1'],$tab).":";
                $return .= preg_replace('/^'.$tabStr.'/','',$object1);
                $object2 = $this->getCodeByCodeMeta($codeMetaArr['object2'],$tab);
                $return .= preg_replace('/^'.$tabStr.'/','',$object2);
            }
            elseif($codeMetaArr['type']=='try'){
                //三元运算符
                $return = $tabStr . "try" . codeStyle::$spaceBeforeBlocks . "{\n";
                foreach($codeMetaArr['child'] as $k=>$v){
                    $return .= $this->getCodeByCodeMeta($v,$tab+1);
                    if(isset($v['child']) || $v['type']=='comment'){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return .= $tabStr.'}catch(';
                $return .= $this->getCodeByCodeMeta($codeMetaArr['catch'],0);
                $return .= ")" . codeStyle::$spaceBeforeBlocks . "{\n";
                foreach($codeMetaArr['catchChild'] as $k=>$v){
                    $return .= $this->getCodeByCodeMeta($v,$tab+1);
                    if(isset($v['child']) || $v['type']=='comment'){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return .= $tabStr.'}';
            }
            elseif($codeMetaArr['type']=='&'){
                $return = '&';
                $return .= $this->getCodeByCodeMeta($codeMetaArr['value'],0);
            }
            elseif($codeMetaArr['type']=='staticParams'){
                $return = $this->getCodeByCodeMeta($codeMetaArr['object'],0).'::'.$codeMetaArr['type'];
            }
            elseif(in_array($codeMetaArr['type'],array('--','++'))){
                $return = $this->getCodeByCodeMeta($codeMetaArr['object1'],0).$codeMetaArr['type'];
            }
            elseif($codeMetaArr['type']=='html'){
                $return = $codeMetaArr['value'];
            }
            else{
                //异常,上面写的没错的话,进入不到这里,这个只是监控上面代码写的对不对的报警
                return $codeMetaArr['type'];
            }
        }elseif(is_string($codeMetaArr)){
            $return = $codeMetaArr;
        }
        return $return;
    }

    //查找某个字符之前的代码并返回(返回值中不包含结束字符,但是codeArr中已经删除)
    private function searchInsetStr($endStr=false){
        $temp = $this->codeArrPre;
        if($temp==$endStr){
            $this->codeArrPre = '';
            return '';
        }
        while(count($this->codeArr)>0){
            $first = array_shift($this->codeArr);
            if(strlen($endStr)==1 && $first!==$endStr){
                $temp.=$first;
            }elseif(strlen($endStr)>1 && (substr($temp,(strlen($endStr)-1)*-1).$first!==$endStr) ){
                $temp.=$first;
            }else{
                //找到了字符串
                if(strlen($endStr)>1){
                    $temp = substr($temp,0,(strlen($endStr)-1)*-1);
                }
                break;
            }
        }
        return $temp;
    }
    /*
     * php代码梭子
     * 梭子会从php代码开始,逐步往最后移动,切出各种关键词
     * @param putBack 是否停止前进(为true的时候,切出词语之后,梭子不往后移动)
     * @return string
     **/
    private $lineNum = 0;
    private function forward($putBack = false){
        if($this->codeArrPre!==''){
            $pre = $this->codeArrPre;
            if($putBack==false){
                $this->codeArrPre = '';
            }
            return $pre;
        }
        if(count($this->codeArr)==0){
            return false;
        }
        $temp = '';
        while(count($this->codeArr)>0){
            $first = $this->codeArr[0];
            if($first=="\n"){
                $this->lineNum++;
            }
            if($temp=='' && in_array($first,array(''," ","\t","\n"))){
                array_shift($this->codeArr);
                continue;
            }elseif(in_array($first,array(
                '{', ' ', '}',
                ',', ';', '/',
                '/', '*', "\n",
                '(', ')', '=',
                '!', '->', '>',
                '<', "'", '-',
                ':', '"','.','?',
                '&', '[',']',
                '+'
            ))){
                if($temp==''){
                    array_shift($this->codeArr);
                    $temp = $first;
                }
                break;
            }
            array_shift($this->codeArr);
            $temp.=$first;
        }
        //多个关键词组合的复合关键词
        $arrayTemp = array(
            array('==','>=','<=','!=','->','&&','::','=>','[]','.=','//','/*','*/','++','--','+=','-='),
            array('===','!==','[]='),
        );

        if(!in_array($temp,array('"',"'"))){
            while(count($this->codeArr)>0){
                //去除掉空格
                while($this->codeArr[0]==' '){
                    array_shift($this->codeArr);
                }
                if(isset($arrayTemp[strlen($temp)-1]) && in_array($temp.$this->codeArr[0],$arrayTemp[strlen($temp)-1])){
                    $temp = $temp.$this->codeArr[0];
                    array_shift($this->codeArr);
                }else{
                    break;
                }
            }
        }
        //  "<?php"  这个关键词的特殊匹配
        if($temp=='<'){
            $copy = $this->codeArr;
            if(implode('',array_splice($copy,0,4))=='?php'){
                array_shift($this->codeArr);
                array_shift($this->codeArr);
                array_shift($this->codeArr);
                array_shift($this->codeArr);
                $temp = '<?php';
            }
        }
        /*  "?>"  这个关键词的特殊匹配 */
        if($temp=='?' && $this->codeArr[0]=='>'){
            array_shift($this->codeArr);
            $temp = '?>';
        }
        //putBack为真的时候放到暂存池,以供下次使用
        if($putBack){
            $this->codeArrPre = $temp;
        }
        return $temp;
    }
    /*
     * 关键词所允许的运行时和允许的描述符
     * 例如class只能出现在window运行时
     * 例如class只能有final和abstract描述符
     **/
    private $dataTypeDesc = array(
        'class'=>array(
            'runEnvironment'=>array('window'),
            'desc'=>array('final','abstract'),
        ),
        'function'=>array(
            'runEnvironment' => array('window', 'class', 'code'),
            'desc'=>array('private','protected','public','static','final'),
        ),
        'property'=>array(
            'runEnvironment'=>array('class'),
            'desc'=>array('private','protected','public','static'),
        ),
        'codeBlock'=>array(
            'runEnvironment'=>array('window','property'),
            'desc'=>array('*'),
        ),
    );
    private $dataTypeDesc2 = array();//runEnvironment下desc分布
    //算法优先级
    private $actionShunxu = array(
        //越靠上越优先计算
        '&',
        '->',
        'new',
        '(',
        '[',
        '!',
        '+','-','.',
        '==', '===', '!==', '!=', '>=', '<=', '>', '<',
        '&&', '||',
        '.=',
        '=',
        '[]=',
        '?',
        ':',
        ',',
        ']',
        ')',
        ';',
    );
    //返回某个算法符之后的算法符
    private function afterShunxu($key){
        $copy = $this->actionShunxu;
        return array_splice($copy,array_search($key,$this->actionShunxu)+1);
    }
}
