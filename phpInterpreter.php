<?php
/**
 * Created by PhpStorm.
 * User: 王浩然
 * Date: 2017/2/16
 * Time: 上午11:52
 */
final class phpInterpreter{
    /*添加属性
     * $contrast为null的时候,$positionType的before和after代表添加到类的和开始或最后
     * $contrast不为null的时候,$positionType的before和after代表添加到$contrast的和开始或最后
     */
    public function setProperty($key,$value,$positionType='after',$contrast=null){

    }
    private $codeArr;
    private $codeArrPre ='';
    public function getCodeMetaByCode($code){
        $temp = array();
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
        $this->codeArr = str_split($code);
        return array(
            'type'=>'window',
            'child'=>$this->temp('window','')
        );
    }
    private function searchInsetStr($endStr=false){
        $temp = '';

        while(count($this->codeArr)>0){
            $first = array_shift($this->codeArr);
            if($first!==$endStr){
                $temp.=$first;
            }else{
                break;
            }
        }
        return $temp;
    }
    //false自动前进,true使用完放回
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
                    break;
                }else{
                    break;
                }
            }
            array_shift($this->codeArr);
            $temp.=$first;
        }
        $arrayTemp = array(
            array('==','>=','<=','!=','->','&&','::','=>','[]','.=','//','/*','*/','++','--'),
            array('===','!=='),
        );

        while(count($this->codeArr)>0){
            if(isset($arrayTemp[strlen($temp)-1]) && in_array($temp.$this->codeArr[0],$arrayTemp[strlen($temp)-1])){
                $temp = $temp.$this->codeArr[0];
                array_shift($this->codeArr);
            }else{
                break;
            }
        }
        if($temp=='<'){
            $copy = $this->codeArr;
            if(implode('',array_splice($copy,0,4))=='?php'){
                array_shift($this->codeArr);
                array_shift($this->codeArr);
                array_shift($this->codeArr);
                array_shift($this->codeArr);
                return '<?php';
            }
        }
        if($temp=='?' && $this->codeArr[0]=='>'){
            array_shift($this->codeArr);
            return '?>';
        }
        if($putBack){
            $this->codeArrPre = $temp;
        }
        return $temp;
    }
    private $dataTypeDesc = array(
        'class'=>array(
            'runEnvironment'=>array('window'),
            'desc'=>array('final','abstract'),
        ),
        'function'=>array(
            'runEnvironment'=>array('window','class'),
            'desc'=>array('private','public','static','final'),
        ),
        'property'=>array(
            'runEnvironment'=>array('class'),
            'desc'=>array('private','public','static'),
        ),
        'codeBlock'=>array(
            'runEnvironment'=>array('window','property'),
            'desc'=>array('*'),
        ),
    );
    private $dataTypeDesc2 = array();//runEnvironment下desc分布
    private $actionShunxu = array(
        '->',
        'new',
        '(',
        '[',
        '!',
        '+','-','.',
        '==', '===', '!==', '!=', '>=', '<=', '>', '<',
        '&&', '||',
        '?',
        '.=',
        ',',
        ']',
        ')',
        ';',
    );
    private function afterShunxu($key){
        $copy = $this->actionShunxu;
        return array_splice($copy,array_search($key,$this->actionShunxu)+1);
    }
    public function temp($yunxingshiType,$beginStr,$endStr=false,$test=false){
        $return = array();
        $zancun = array();//用于描述关键词的一些关键词
        if($beginStr!==''){
            $temp = $this->forward();
            if($temp!==$beginStr){
                throw new Exception($yunxingshiType.'必须由'.$beginStr.'开始结构'.'现在是'.$temp);
            }
        }
        $nextKeyWord = $this->forward(true);
        if($nextKeyWord === $endStr || $nextKeyWord===false || (is_array($endStr) && in_array($nextKeyWord,$endStr))){
            $this->forward();
            return $return;
        }
        while(true){
            $nextKeyWord = $this->forward();
            //注释
            if($nextKeyWord=='//'){
                $return[] = array(
                    'type'=>'注释',
                    'value'=> $this->searchInsetStr("\n")
                );
            }
            elseif($nextKeyWord=='/*'){
                $childYunxingshi = array(
                    'type'=>'注释',
                    'value'=>''
                );
                do{
                    $nextKeyWord = $this->forward();
                    if($nextKeyWord!=="*/"){
                        $childYunxingshi['value'].=$nextKeyWord;
                    }else{
                        break;
                    }
                }while($nextKeyWord!=="*/");
                $return[] = $childYunxingshi;
            }
            else{
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
                        'type'=>$type
                    );
                    //取出暂存的描述符
                    if(isset($this->dataTypeDesc[$type]['desc'])){
                        foreach($zancun as $v){
                            if(in_array($v,$this->dataTypeDesc[$type]['desc'])){
                                $childResult[$v] = true;
                            }else{
                                throw new Exception($type.'类型不允许修饰符'.$v);
                            }
                        }
                        $zancun = array();
                    }
                    //类属性名
                    if($type=='property'){
                        $keyName = $nextKeyWord;
                        if($this->forward()=='='){
                            $childResult['value'] = $this->temp('codeBlock','',';');
                        }
                    }
                    elseif($type=='class'){
                        $keyName = $this->forward();
                        //后置描述符
                        do{
                            $keyTemp = $this->forward();
                            if($keyTemp!=='{'){
                                if(in_array($keyTemp,array('extends'))){
                                    $childResult[$keyTemp] = $this->forward();
                                }
                            }
                        }while($keyTemp!=='{');
                        $childResult['child'] = $this->temp($type,'','}');
                    }
                    elseif($type=='function'){
                        $keyName = $this->forward();
                        if($this->forward()!=='('){
                            throw new Exception('函数名后面带上()参数');
                        }
                        //参数
                        $canshu = $this->searchInsetStr(')');
                        if(!empty($canshu)){
                            $childResult['property'] = explode(',',$canshu);
                        }
                        $childResult['child'] = $this->temp($type,'{','}');
                    }
                    elseif($nextKeyWord=='='){
                        $childYunxingshi['type'] = '=';
                        $childYunxingshi['name'] = $zancun[count($zancun)-1];
                        var_dump('hear');exit;
                        $childResult['child'] = $this->temp($childYunxingshi['type']);
                        $return[] = $childResult;
                    }
                    $return[$keyName] = $childResult;
                }
                //运算代码
                else{
                    if(in_array($nextKeyWord,array('if','else','elseif'))){
                        $nextWord = $this->forward();
                        if($nextKeyWord=='else' && $nextWord=='if'){
                            $nextWord = $this->forward();
                            $nextKeyWord = 'elseif';
                        }
                        $childResult = array(
                            'type'=>$nextKeyWord,
                        );
                        if($nextKeyWord!='else'){
                            if($nextWord!='('){
                                throw new Exception('if后面必须得跟着(');
                            }
                            $childResult['value'] = $this->temp('code','',')',true);
                            $ddd = $this->forward();
                            if($ddd!=')'){
                                throw new Exception('if条件后面必须得跟着{');
                            }
                            $childResult['child'] = $this->temp($nextKeyWord,'{','}');
                        }else{
                            $childResult['child'] = $this->temp($nextKeyWord,'','}');
                        }
                    }
                    elseif($nextKeyWord=='new'){
                        $childResult = array(
                            'type'=>'创建对象',
                            'className'=>$this->temp('code','',$this->afterShunxu($nextKeyWord),true)
                        );
                        if($this->forward()!='('){
                            print_r($childResult);
                            print_r($this->afterShunxu($nextKeyWord));
                            var_dump($this->forward(true));
                            print_r($this->codeArr);
                            throw new Exception('new后面必须跟着(');
                        }
                        if($this->forward(true)==')'){
                            $this->forward();
                        }else{
                            do{
                                $item = $this->temp('code','',array(',',')'));
                                $canshuArr[] = $item[0];
                            }while($this->forward()==',');
                            $childResult['property'] = $canshuArr;
                        }
                    }
                    elseif($nextKeyWord=='throw'){
                        $childResult = array(
                            'type'=>'抛出throw',
                            'value'=>$this->temp('code','',';'),
                        );
                    }
                    elseif($nextKeyWord=='echo'){
                        $childResult = array(
                            'type'=>'echo输出',
                            'value'=>$this->temp('codeBlock','',';')
                        );
                    }
                    elseif($nextKeyWord=='exit'){
                        $childResult = array(
                            'type'=>'exit退出',
                        );
                    }
                    elseif($nextKeyWord=='foreach'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                        );
                        $childResult['object'] = $this->temp('code','(','as');
                        $this->forward();
                        $next = $this->temp('code','',array('=>',')'));
                        if($this->forward(true)=='=>'){
                            $childResult['key'] = $next;
                            $this->forward();
                            $childResult['value'] = $this->temp('codeBlock','',')');
                        }else{
                            $childResult['value'] = $next;
                            $this->forward();
                        }
                        $childResult['child'] = $this->temp($nextKeyWord,'{','}',true);
                    }
                    elseif($nextKeyWord=='while'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'value'=>$this->temp('code','(',')'),
                        );
                        $this->forward();
                        $childResult['child'] = $this->temp('codeBlock','{','}');
                    }
                    elseif($nextKeyWord=='do'){
                        $childResult = array(
                            'type'=>'dowhile',
                            'child'=>$this->temp('codeBlock','{','}'),
                        );
                        if($this->forward()!='while'){
                            throw new Exception('do循环后面必须加while条件');
                        }
                        $childResult['value'] = $this->temp('code','(',')');
                        $this->forward();
                    }
                    elseif(in_array($nextKeyWord,array('break','continue'))){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                        );
                    }
                    elseif($nextKeyWord=='('){
                        if(count($return)>0){
                            $obj = $return[count($return)-1];
                            if($obj['type']=='variable' && $obj['name']=='$autoLoadClass'){
                                array_pop($return);
                                $childResult = array(
                                    'type'=>'functionCall',
                                    'name'=>$obj
                                );
                                $canshuArr = array();
                                if($this->forward(true)==')'){
                                    $this->forward();
                                }else{
                                    do{
                                        $canshuArr[] = $this->temp('code','',array(',',')'));
                                    }while($this->forward()==',');
                                }
                                $childResult['property'] = array(
                                    'type'=>'codeBlock',
                                    'child'=>$canshuArr,
                                );
                            }else{
                                $childResult = array(
                                    'type' => 'codeBlock',
                                    'child'=> $this->temp('codeBlock','',')')
                                );
                                if(empty($childResult['child'])){
                                    $childResult = '()';
                                }
                            }
                        }else{
                            $childResult = array(
                                'type' => 'codeBlock',
                                'child'=> $this->temp('codeBlock','',')')
                            );
                            if(empty($childResult['child'])){
                                $childResult = '()';
                            }
                        }
                    }
                    elseif($nextKeyWord=='{'){
                        $childResult = array(
                            'type'=>'codeBlock',
                            'child'=>$this->temp('codeBlock','','}')
                        );
                    }
                    elseif(in_array($nextKeyWord,array("'",'"'))){
                        $string = '';
                        do{
                            $string .= $this->searchInsetStr($nextKeyWord);
                        }while(substr($string,-1)=='\\' && substr($string,-2)!=='\\\\');
                        $childResult = array(
                            'type'=>'string',
                            'value'=>$string
                        );

                    }
                    elseif(in_array($nextKeyWord,array('&&','||'))){
                        $childResult = array(
                            'type'=>$nextKeyWord=='&&'?'and':'or',
                            'name'=>$nextKeyWord
                        );
                    }
                    elseif($nextKeyWord=='[]'){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>'数组追加',
                            'object'=>$obj,
                            'name'=>$this->temp('codeBlock','=',';')
                        );
                    }
                    elseif($nextKeyWord=='.='){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>'字符串追加',
                            'object'=>$obj,
                            'object2'=>$this->temp('code','',$this->afterShunxu($nextKeyWord))
                        );
                    }
                    elseif($nextKeyWord=='?'){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>'三元运算符',
                            'value'=>$obj,
                            'object1'=>$this->temp('code','',':'),
                            'object2'=>$this->temp('code',':',$this->afterShunxu($nextKeyWord))
                        );
//                        print_r($childResult);
                    }
                    elseif($nextKeyWord == 'array'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'child'=>$this->temp('array','(',')'),
                        );
                    }
                    elseif($yunxingshiType=='array'){
                        if($nextKeyWord =='=>'){
                            $obj = $return[count($return)-1];
                            array_pop($return);
                            $childResult = array(
                                'type'=>'arrayValue',
                                'key'=>$obj,
                                'value'=>$this->temp('code','',array(',',')')),
                            );
                            if($this->forward(true)==','){
                                $this->forward();
                            }
                        }elseif($nextKeyWord==','){
                            if($this->forward(true)!==')'){
                                continue;
                            }
                        }
                    }
                    elseif(in_array($nextKeyWord,array('->','::'))){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>'',
                            'object'=>$obj,
                            'name'=>$this->forward(),

                        );
                        if($this->forward(true)=='('
                            &&(
                                (is_array($endStr) && !in_array('(',$endStr))
                                ||
                                (!is_array($endStr) && $endStr !== '(')
                            )
                        ){
                            //因为有些运算的优先级高于(,比如new,这时候不能尝试作为函数运行,而要作为属性返回,再执行new的参数
                            if($childResult['name']=='__construct'){
                                $childResult['type'] = '构造函数';
                            }else{
                                $childResult['type'] = $nextKeyWord=='->'?'执行对象方法':'执行类方法';
                            }
                            $this->forward();
                            if($this->forward(true)==')'){
                                $this->forward();
                            }else{
                                do{
                                    $item = $this->temp('code','',array(',',')'));
                                    $canshuArr[] = $item[0];
                                }while($this->forward()==',');
                                $childResult['property'] = $canshuArr;//$this->temp('codeBlock','(',')');
                            }
                        }else{
                            $childResult['type'] = $nextKeyWord=='->'?'对象属性':'类静态属性';
                        }
                    }
                    elseif(in_array($nextKeyWord,array('==','===','>=','<=','!==','!=','>','<'))){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $title = array(
                            '=='=>'相等',
                            '==='=>'完全相等',
                            '>='=>'大于等于',
                            '<='=>'小于等于',
                            '>'=>'大于',
                            '<'=>'小于',
                            '!=='=>'不完全等于',
                            '!='=>'不等于',
                        );
                        $childResult = array(
                            'type'=>$title[$nextKeyWord],
                            'object1'=>$obj,
                            'object2'=>$this->temp('code','',$this->afterShunxu($nextKeyWord))
                        );
                    }
                    elseif($nextKeyWord=='.'){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>'字符串叠加',
                            'object1'=>$obj,
                            'object2'=>$this->temp('code','',$this->afterShunxu($nextKeyWord))
                        );
                    }
                    elseif(in_array($nextKeyWord,array('+','-'))){
                        $obj = $return[count($return)-1];
                        $title = array(
                            '+'=>'相加',
                            '-'=>'相减',
                        );
                        array_pop($return);
                        $childResult = array(
                            'type'=>$title[$nextKeyWord],
                            'object1'=>$obj,
                            'object2'=>$this->temp('code','',$this->afterShunxu($nextKeyWord))
                        );
                    }
                    elseif($nextKeyWord=='='){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>'赋值',
                            'object1'=>$obj,
                            'object2'=>$this->temp('code','',array(';',')'))
                        );
                    }
                    elseif($nextKeyWord=='!'){
                        $childResult = array(
                            'type'=>'非',
                            'value'=>$this->temp('code','',$this->afterShunxu($nextKeyWord))
                        );
                    }
                    elseif(in_array($nextKeyWord,array('true','false'))){
                        $childResult = array(
                            'type'=>'bool',
                            'name'=>$nextKeyWord
                        );
                    }
                    elseif($nextKeyWord=='['){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>'数组某一项',
                            'object'=>$obj,
                            'key'=>$this->temp('code','',']'),
                        );
                        $this->forward();
                    }
                    elseif($nextKeyWord =='--'){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'object1'=>$obj,
                        );
                    }
                    elseif($nextKeyWord=='parent'){
                        $childResult = array(
                            'type'=>'父类',
                            'name'=>$nextKeyWord
                        );
                    }
                    elseif($nextKeyWord=='return'){
                        $childResult = array(
                            'type'=>'返回',
                            'name'=>$nextKeyWord,
                            'value'=>$this->temp('codeBlock','',';')
                        );
                    }
                    elseif(substr($nextKeyWord,0,1)=='$'){
                        $childResult = array(
                            'type'=>'variable',
                            'name'=>$nextKeyWord,
                        );
                    }
                    elseif(preg_match('/0(\d+)/',$nextKeyWord,$match)){
                        $childResult = array(
                            'type'=>'8进制数字',
                            'child'=>$match[0],
                        );
                    }
                    elseif(preg_match('/(\d+)/',$nextKeyWord,$match)){
                        $childResult = array(
                            'type'=>'整数',
                            'child'=>$match[0],
                        );
                    }
                    elseif($this->forward(true)==='(' && ((is_array($endStr) && !in_array('(',$endStr)) || (!is_array($endStr) && $endStr!='('))){
                        $childResult = array(
                            'type'=>'functionCall',
                            'name'=>$nextKeyWord
                        );
                        $this->forward();
                        $canshuArr = array();
                        if($this->forward(true)==')'){
                            $this->forward();
                        }else{
                            do{
                                $canshuArr[] = $this->temp('code','',array(',',')'));
                            }while($this->forward()==',');
                        }
                        $childResult['property'] = array(
                            'type'=>'codeBlock',
                            'child'=>$canshuArr,
                        );
                    }
                    else{
                        if($nextKeyWord==false){
                            break;
                        }
                        if(!in_array($nextKeyWord,array(';'))){
//                            var_dump($nextKeyWord);
                        }
                        $childResult = $nextKeyWord;
                    }
                    $return[] = $childResult;
                }
            }
            //类型结束符号
            $nextKeyWord = $this->forward(true);
            if($yunxingshiType=='code'){
                if($nextKeyWord === $endStr || $nextKeyWord===false || (is_array($endStr) && in_array($nextKeyWord,$endStr))){
                    break;
                }
            }
            if($nextKeyWord === $endStr || $nextKeyWord===false || (is_array($endStr) && in_array($nextKeyWord,$endStr))){
                $this->forward();
                break;
            }
        }
        return $return;
    }
}