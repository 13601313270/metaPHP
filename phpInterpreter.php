<?php
/**
 * Created by PhpStorm.
 * User: 王浩然
 * Date: 2017/2/16
 * Time: 上午11:52
 */
final class phpInterpreter{
    private $savedCode;
    private $codeArr;
    private $codeArrPre ='';
    //把php代码提取成meta数据信息
    public function getCodeMetaByCode($code){
        $this->savedCode = $code;
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
    //把meta信息还原成php代码
    public function getCodeByCodeMeta($codeMetaArr){
        return $this->_getCodeByCodeMeta('',$codeMetaArr,0);
    }

    private function getTabStr($tab){
        $return = '';
        for($i=0;$i<$tab;$i++){
            $return.='    ';
        }
        return $return;
    }
    public function _getCodeByCodeMeta($name,$codeMetaArr,$tab){
        $tabStr = $this->getTabStr($tab);
        $return = '';
        if(isset($codeMetaArr['type'])){
            if($codeMetaArr['type']=='window'){
                foreach($codeMetaArr['child'] as $k=>$v){
                    $return .= $this->_getCodeByCodeMeta($k,$v,0);
                }
            }
            elseif($codeMetaArr['type']=='phpBegin'){
                return $tabStr."<?php\n";
            }
            elseif($codeMetaArr['type']=='注释段'){
                return $tabStr.'/*'.$codeMetaArr['value']."*/\n";
            }
            elseif($codeMetaArr['type']=='注释'){
                return $tabStr.'//'.$codeMetaArr['value'];
            }
            elseif($codeMetaArr['type']=='bool'){
                return $codeMetaArr['data'];
            }
            elseif(in_array($codeMetaArr['type'],array('parent','break','continue','exit'))){
                return $tabStr.$codeMetaArr['type'].($codeMetaArr['type']!=='parent'?"\n":'');
            }
            elseif($codeMetaArr['type']=='class'){
                $return = $tabStr.'class '.$name;
                foreach($codeMetaArr as $k=>$v){
                    if(in_array($k,array('extends'))){
                        $return.=' '.$k.' '.$v;
                    }
                }
                $return .= "{\n";
                foreach($codeMetaArr['child'] as $k=>$v){
                    $return .= $this->_getCodeByCodeMeta($k,$v,$tab+1)."\n";
                }
                $return .= $tabStr."}\n";
            }
            elseif($codeMetaArr['type']=='property'){
                $return = $tabStr;
                foreach($codeMetaArr as $k=>$v){
                    if( in_array($k, $this->dataTypeDesc['property']['desc']) ){
                        $return .= $k.' ';
                    }
                }
                $return.=$name;
                if(isset($codeMetaArr['value'])){
                    $return .= '=';
                    $return .= $this->_getCodeByCodeMeta('',$codeMetaArr['value'],0);
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
                $return .= 'function '.$name.'(';
                if(isset($codeMetaArr['property'])){
                    $return.=implode(',',$codeMetaArr['property']);
                }
                $return.="){\n";
                foreach($codeMetaArr['child'] as $k=>$v){
                    $return .= $this->_getCodeByCodeMeta($k,$v,$tab+1);
                    if(isset($v['child']) || $v['type']=='注释'){
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
                    $return .= $this->_getCodeByCodeMeta('',$codeMetaArr['value'],0);
                    $return .= ')';
                }
                $return .= "{\n";
                foreach($codeMetaArr['child'] as $k=>$v){
                    $return .= $this->_getCodeByCodeMeta('',$v,$tab+1);
                    if(isset($v['child']) || $v['type']=='注释'){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return .= $tabStr."}";
            }
            elseif($codeMetaArr['type']=='构造函数'){
                $return = $tabStr;
                $return .= $this->_getCodeByCodeMeta('',$codeMetaArr['object'],0);
                $return .= '::';
                $return .= $codeMetaArr['name'];
                $return .= '(';
                foreach($codeMetaArr['property'] as $k=>$v){
                    $return.= $this->_getCodeByCodeMeta('',$v,0);
                }
                $return .= ')';
            }
            elseif($codeMetaArr['type']=='父类'){
                $return = $tabStr.$codeMetaArr['name'];
            }
            elseif($codeMetaArr['type']=='variable'){
                $return = $codeMetaArr['name'];
            }
            elseif($codeMetaArr['type']=='string'){
                if($codeMetaArr['borderStr']=='\''){
                    $return = $tabStr.'\''.$codeMetaArr['data'].'\'';
                }else{
                    $return = $tabStr.'"'.$codeMetaArr['data'].'"';
                }
            }
            elseif($codeMetaArr['type']=='objectParams'){
                $return = $tabStr.$codeMetaArr['object']['name'].'->'.$codeMetaArr['name'];
            }
            elseif($codeMetaArr['type']=='return'){
                $return = $tabStr.'return '.$this->_getCodeByCodeMeta('',$codeMetaArr['value'],0);
            }
            elseif(in_array($codeMetaArr['type'],array('=','==','===','>=','<=','!=','!==','<','>','.','+','-','.=','&&','||','[]='))){
                $return = $tabStr.$this->_getCodeByCodeMeta('',$codeMetaArr['object1'],0);
                $return .= $codeMetaArr['type'];
                $return .= $this->_getCodeByCodeMeta('',$codeMetaArr['object2'],0);
            }
            elseif(in_array($codeMetaArr['type'],array('staticFunction','objectFunction','__construct'))){
                $return = $tabStr.$this->_getCodeByCodeMeta('',$codeMetaArr['object'],0);

                $return .= $codeMetaArr['type']=='objectFunction'?'->':'::';
                $return .=$codeMetaArr['name'];
                if(isset($codeMetaArr['property'])){
                    $allParams = array();
                    foreach($codeMetaArr['property'] as $param){
                        $allParams[] = $this->_getCodeByCodeMeta('',$param,0);
                    }
                    $return .= '('.implode(',',$allParams).')';
                }else{
                    $return .= '()';
                }
            }
            elseif($codeMetaArr['type']=='functionCall'){
                $return = $tabStr.$this->_getCodeByCodeMeta('',$codeMetaArr['name'],0);
                if(isset($codeMetaArr['property'])){
                    $allParams = array();
                    foreach($codeMetaArr['property'] as $param){
                        $allParams[] = $this->_getCodeByCodeMeta('',$param,0);
                    }
                    $return .= '('.implode(',',$allParams).')';
                }else{
                    $return .= '()';
                }
            }
            elseif($codeMetaArr['type']=='new'){
                $return = $tabStr.'new '.$this->_getCodeByCodeMeta('',$codeMetaArr['className'],0);
                if(isset($codeMetaArr['property'])){
                    $allParams = array();
                    foreach($codeMetaArr['property'] as $param){
                        $allParams[] = $this->_getCodeByCodeMeta('',$param,0);
                    }
                    $return .= '('.implode(',',$allParams).')';
                }else{
                    $return .= '()';
                }
            }
            elseif($codeMetaArr['type']=='foreach'){
                $return = $tabStr.'foreach(';
                $return .=$this->_getCodeByCodeMeta('',$codeMetaArr['object'],0);
                $return .=' as ';
                if(isset($codeMetaArr['key'])){
                    $return .=$this->_getCodeByCodeMeta('',$codeMetaArr['key'],0).' =>';
                }
                $return .=$this->_getCodeByCodeMeta('',$codeMetaArr['value'],0)."){\n";
                foreach($codeMetaArr['child'] as $child){
                    $return .=$this->_getCodeByCodeMeta('',$child,$tab+1);
                    if(isset($child['child']) || $child['type']=='注释'){
                        $return .="\n";
                    }else{
                        $return .=";\n";
                    }
                }
                $return .= $tabStr.'}';
            }
            elseif($codeMetaArr['type']=='arrayGet'){
                $return = $tabStr.$this->_getCodeByCodeMeta('',$codeMetaArr['object'],0);
                $return .= '['.$tabStr.$this->_getCodeByCodeMeta('',$codeMetaArr['key'],0).']';
            }
            elseif($codeMetaArr['type']=='!'){
                $return = $tabStr.'!'.$this->_getCodeByCodeMeta('',$codeMetaArr['value'],0);
            }
            elseif(in_array($codeMetaArr['type'],array('throw','echo'))){
                $return = $tabStr.$codeMetaArr['type'].' '.$this->_getCodeByCodeMeta('',$codeMetaArr['value'],0);
            }
            elseif($codeMetaArr['type']=='array'){
                if(empty($codeMetaArr['child'])){
                    $return = 'array()';
                }else{
                    $return = 'array(';
                    foreach($codeMetaArr['child'] as $k=>$child){
                        if($k!=0){
                            $return .= ',';
                        }
                        $return .= $this->_getCodeByCodeMeta('',$child,0);
                    }
                    $return .= ')';
                }
            }
            elseif(in_array($codeMetaArr['type'],array('int','8int'))){
                $return = $tabStr.$codeMetaArr['data'];
            }
            elseif($codeMetaArr['type']=='arrayValue'){
                $return = $tabStr.$this->_getCodeByCodeMeta('',$codeMetaArr['key'],0).'=>';
                $return .= $tabStr.$this->_getCodeByCodeMeta('',$codeMetaArr['value'],0);
            }
            else{
//                print_r($codeMetaArr);exit;
                return '!!'.$codeMetaArr['type'];
//                var_dump('遇到了暂时没有解析的类型');
//                var_dump($codeMetaArr['type']);
//                print_r($codeMetaArr);
//                exit;
            }
        }elseif(is_string($codeMetaArr)){
            $return = $codeMetaArr;
        }
        return $return;
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
            array('===','!==','[]='),
        );

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
        if($temp=='?' && $this->codeArr[0]=='>'){
            array_shift($this->codeArr);
            $temp = '?>';
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
        //越靠上越优先计算
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
        '=',
        '[]=',
        ',',
        ']',
        ')',
        ';',
    );
    private function afterShunxu($key){
        $copy = $this->actionShunxu;
        return array_splice($copy,array_search($key,$this->actionShunxu)+1);
    }
    public function temp($yunxingshiType,$beginStr,$endStr=false){
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
                    'type'=>'注释段',
                    'value'=>''
                );
                do{
//                    $nextKeyWord = $this->searchInsetStr($nextKeyWord);
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
                            $childResult['value'] = current($this->temp('codeBlock','',';'));
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
                    elseif($nextKeyWord=='<?php'){
                        $childResult = array(
                            'type'=>'phpBegin',
                        );
                    }
                    elseif($nextKeyWord=='new'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'className'=>$this->temp('code','',$this->afterShunxu($nextKeyWord),true)
                        );
                        if($this->forward()!='('){
                            throw new Exception('new后面必须跟着(');
                        }
                        if($this->forward(true)==')'){
                            $this->forward();
                        }else{
                            do{
                                $canshuArr[] = $this->temp('code','',array(',',')'));
                            }while($this->forward()==',');
                            $childResult['property'] = $canshuArr;
                        }
                    }
                    elseif($nextKeyWord=='throw'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'value'=>$this->temp('code','',';'),
                        );
                    }
                    elseif($nextKeyWord=='echo'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'value'=>$this->temp('code','',';')
                        );
                    }
                    elseif($nextKeyWord=='exit'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
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
                            $childResult['value'] = current($this->temp('codeBlock','',')'));
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
                                $childResult['property'] = $canshuArr;
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
                            'borderStr'=>$nextKeyWord,
                            'data'=>$string
                        );

                    }
                    elseif(in_array($nextKeyWord,array('&&','||'))){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'object1'=>$obj,
                            'object2'=>$this->temp('code','',$this->afterShunxu($nextKeyWord)),
                        );
                    }
                    elseif($nextKeyWord=='[]='){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'object1'=>$obj,
                            'object2'=>$this->temp('code','',$this->afterShunxu($nextKeyWord)),
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
                                $childResult['type'] = $childResult['name'];
                            }else{
                                $childResult['type'] = $nextKeyWord=='->'?'objectFunction':'staticFunction';
                            }
                            $this->forward();
                            if($this->forward(true)==')'){
                                $this->forward();
                            }else{
                                do{
                                    $canshuArr[] = $this->temp('code','',array(',',')'));
                                }while($this->forward()==',');
                                $childResult['property'] = $canshuArr;
                            }
                        }else{
                            $childResult['type'] = $nextKeyWord=='->'?'objectParams':'staticParams';
                        }
                    }
                    //$1运算符$2,类型的运算
                    elseif(in_array($nextKeyWord,array('==','===','>=','<=','!==','!=','>','<','.','+','-','=','.='))){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'object1'=>$obj,
                            'object2'=>$this->temp('code','',$this->afterShunxu($nextKeyWord))
                        );
                    }
                    elseif($nextKeyWord=='!'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'value'=>$this->temp('code','',$this->afterShunxu($nextKeyWord))
                        );
                    }
                    elseif(in_array($nextKeyWord,array('true','false'))){
                        $childResult = array(
                            'type'=>'bool',
                            'data'=>$nextKeyWord
                        );
                    }
                    elseif(in_array($nextKeyWord,array('break','continue'))){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                        );
                    }
                    elseif($nextKeyWord=='['){
                        $obj = $return[count($return)-1];
                        array_pop($return);
                        $childResult = array(
                            'type'=>'arrayGet',
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
                            'type'=>$nextKeyWord,
                        );
                    }
                    elseif($nextKeyWord=='return'){
                        $childResult = array(
                            'type'=>$nextKeyWord,
                            'value'=>current($this->temp('codeBlock','',';'))
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
                            'type'=>'8int',
                            'data'=>$match[0],
                        );
                    }
                    elseif(preg_match('/(\d+)/',$nextKeyWord,$match)){
                        $childResult = array(
                            'type'=>'int',
                            'data'=>$match[0],
                        );
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
                        $childResult['property'] = $canshuArr;
                    }

                    else{
                        if($nextKeyWord==false){
                            break;
                        }
                        if(in_array($nextKeyWord,array(';'))){
//                            continue;
//                            var_dump($nextKeyWord);
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
//                    break;
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