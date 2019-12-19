<?php
/**
 * Created by PhpStorm.
 * User: wanghaoran
 * Date: 2019-12-18
 * Time: 15:50
 */
include_once 'codeStyle.php';

final class getHtmlByMeta
{
    /*
     * 生成对应的缩进符号
     *
     * @return string
     **/
    private function getTabStr($tab)
    {
        $return = '';
        for ($i = 0; $i < $tab; $i++) {
            $return .= codeStyle::$indent;
        }
        return $return;
    }

    //不用分号结尾的类型
    private $noFenhaoType = array('comment', 'comments', 'phpBegin', 'phpEnd', 'html', 'class');

    /*
     * 元代码还原代码器
     * @param codeMetaArr 元代码
     * @param tab 缩进
     *
     * @return string
     **/
    public function getCodeByCodeMeta($codeMetaArr, $tab)
    {
        $tabStr = $this->getTabStr($tab);
        $return = '';
        if (isset($codeMetaArr['type'])) {
            if ($codeMetaArr['type'] == 'window') {
                $return = $tabStr;
                foreach ($codeMetaArr['child'] as $v) {
                    $return .= $this->getCodeByCodeMeta($v, 0);
                    if (isset($v['child']) || in_array($v['type'], $this->noFenhaoType)) {
                        // 这些元素本身已经输出了换行，或者不允许换行
                        if (!in_array($v['type'], ['html', 'phpBegin', 'phpEnd'])) {
                            $return .= "\n";
                        }
                    } else {
                        $return .= ";\n";
                    }
                }
            } elseif ($codeMetaArr['type'] == 'phpBegin') {
                $return = $tabStr . "<?php\n";
            } elseif ($codeMetaArr['type'] == 'phpEnd') {
                $return = $tabStr . "?>\n";
            } elseif ($codeMetaArr['type'] == 'comments') {
                $return = $tabStr . '/*' . $codeMetaArr['value'] . "*/\n";
            } elseif ($codeMetaArr['type'] == 'comment') {
                $return = $tabStr . '<span class="comment">//' . codeStyle::$spaced_comment . $codeMetaArr['value'] . '</span>';
            } elseif (in_array($codeMetaArr['type'], array('boolean', 'bool'))) {
                return ($codeMetaArr['data'] === true || $codeMetaArr['data'] === 'true') ? 'true' : 'false';
            } elseif ($codeMetaArr['type'] == 'exit') {
                $return = $tabStr . $codeMetaArr['type'];
                if (isset($codeMetaArr['property'])) {
                    $allParams = array();
                    foreach ($codeMetaArr['property'] as $param) {
                        $allParams[] = $this->getCodeByCodeMeta($param, 0);
                    }
                    $return .= '(' . implode(',', $allParams) . ')';
                }
                return $return;
            } elseif (in_array($codeMetaArr['type'], array('parent', 'self', 'break', 'continue'))) {
                return $tabStr . $codeMetaArr['type'];
            } elseif (in_array($codeMetaArr['type'], array('null', '__FILE__', 'E_ALL', 'E_NOTICE', '$this'))) {
                $return = $codeMetaArr['type'];
            } elseif ($codeMetaArr['type'] == 'class') {
                $return = $tabStr;
                foreach ($codeMetaArr as $k => $v) {
                    if (in_array($k, $this->dataTypeDesc['class']['desc'])) {
                        $return .= $k . " ";
                    }
                }
                $return .= '<span class="key_words">class</span> ' . '<span class="class_name">' . $codeMetaArr['name'] . '</span>';
                foreach ($codeMetaArr as $k => $v) {
                    if (in_array($k, array('extends', 'implements'))) {
                        $return .= ' ' . '<span class="key_words">' . $k . '</span> ' . $v;
                    }
                }
                $return .= codeStyle::$apace_before_class_lbrace . "{\n";
                if (isset($codeMetaArr['child'])) {
                    foreach ($codeMetaArr['child'] as $v) {
                        $return .= $this->getCodeByCodeMeta($v, $tab + 1) . "\n";
                    }
                }
                $return .= $tabStr . "}";
            } elseif ($codeMetaArr['type'] == 'property') {
                $return = $tabStr;
                foreach ($codeMetaArr as $k => $v) {
                    if (in_array($k, $this->dataTypeDesc['property']['desc'])) {
                        $return .= '<span class="key_words">' . $k . '</span>' . ' ';
                    }
                }
                $return .= '<span class="class_property">' . $codeMetaArr['name'] . '</span>';
                if (isset($codeMetaArr['value'])) {
                    $return .= codeStyle::$spaceInfixOps[0] . '=' . codeStyle::$spaceInfixOps[1];
                    $valuve = $this->getCodeByCodeMeta($codeMetaArr['value'], $tab);
                    $return .= preg_replace('/^\s+/', '', $valuve);
                }
                $return .= ";";
            } elseif ($codeMetaArr['type'] == 'function') {
                $return = $tabStr;
                foreach ($codeMetaArr as $key => $value) {
                    if (in_array($key, $this->dataTypeDesc['function']['desc'])) {
                        if (isset($codeMetaArr[$key]) && $codeMetaArr[$key] == 1) {
                            $return .= $key . ' ';
                        }
                    }
                }
                if (isset($codeMetaArr['name'])) {
                    $return .= 'function ' . $codeMetaArr['name'] . '(';
                } else {
                    $return .= 'function(';
                }
                if (isset($codeMetaArr['property'])) {
                    foreach ($codeMetaArr['property'] as $k => $v) {
                        if ($k != 0) {
                            $return .= ',';
                        }
                        if (!empty($codeMetaArr['propertyType'][$k])) {
                            $return .= $codeMetaArr['propertyType'][$k] . ' ';
                        }
                        $return .= $this->getCodeByCodeMeta($v, 0);
                    }
                }
                $return .= ")";
                if (isset($codeMetaArr['use'])) {
                    $return .= ' ' . $this->getCodeByCodeMeta($codeMetaArr['use'], 0);
                }
                $return .= "{\n";
                foreach ($codeMetaArr['child'] as $v) {
                    $return .= $this->getCodeByCodeMeta($v, $tab + 1);
                    if (isset($v['child']) || in_array($v['type'], $this->noFenhaoType)) {
                        $return .= "\n";
                    } else {
                        $return .= ";\n";
                    }
                }
                $return .= $tabStr . "}";
            } elseif (in_array($codeMetaArr['type'], array('if', 'else', 'elseif'))) {
                $return = $tabStr . $codeMetaArr['type'];
                if ($codeMetaArr['type'] != 'else') {
                    $return .= '(';
                    $return .= $this->getCodeByCodeMeta($codeMetaArr['value'], 0);
                    $return .= ')';
                }
                $return .= "{\n";
                foreach ($codeMetaArr['child'] as $v) {
                    $return .= $this->getCodeByCodeMeta($v, $tab + 1);
                    if (isset($v['child']) || $v['type'] == 'comment') {
                        $return .= "\n";
                    } else {
                        $return .= ";\n";
                    }
                }
                $return .= $tabStr . "}";
            } elseif ($codeMetaArr['type'] == '构造函数') {
                $return = $tabStr;
                $return .= $this->getCodeByCodeMeta($codeMetaArr['object'], 0);
                $return .= '::';
                $return .= $codeMetaArr['name'];
                $return .= '(';
                foreach ($codeMetaArr['property'] as $v) {
                    $return .= $this->getCodeByCodeMeta($v, 0);
                }
                $return .= ')';
            } elseif ($codeMetaArr['type'] == '父类') {
                $return = $tabStr . $codeMetaArr['name'];
            } elseif ($codeMetaArr['type'] == 'variable') {
                if (isset($codeMetaArr['name'])) {
                    $return = $tabStr . $codeMetaArr['name'];
                } else {
                    $return = $tabStr . $codeMetaArr['data'];
                }
            } elseif ($codeMetaArr['type'] == 'string') {
                if (isset($codeMetaArr['borderStr']) && $codeMetaArr['borderStr'] == '\'') {
                    $codeMetaArr['data'] = str_replace("'", '\\\'', $codeMetaArr['data']);
                    $return = $tabStr . '<span class="string">\'' . $codeMetaArr['data'] . '\'</span>';
                } else {
                    $codeMetaArr['data'] = str_replace('"', '\\"', $codeMetaArr['data']);
                    $return = $tabStr . '<span class="string">"' . $codeMetaArr['data'] . '"</span>';
                }
            } elseif ($codeMetaArr['type'] == 'objectParams') {
                $return = $tabStr . $this->getCodeByCodeMeta($codeMetaArr['object'], 0) . '->' . $codeMetaArr['name'];
            } elseif ($codeMetaArr['type'] == 'return') {
                $return = $tabStr . 'return ' . $this->getCodeByCodeMeta($codeMetaArr['value'], 0);
            } elseif (in_array($codeMetaArr['type'], array('&&', '^', '||', 'or', '[]=', '+=', '-=', '==', '===', '>=', '<=', '!==', '!=', '>', '<', '.', '+', '-', '=', '.='))) {
                $return = $tabStr . $this->getCodeByCodeMeta($codeMetaArr['object1'], 0);
                if ($codeMetaArr['type'] == 'or') {
                    $return .= ' ' . $codeMetaArr['type'] . ' ';
                } else {
                    $return .= $codeMetaArr['type'];
                }
                $value = $this->getCodeByCodeMeta($codeMetaArr['object2'], $tab);
                $return .= preg_replace('/^\s+/', '', $value);
            } elseif (in_array($codeMetaArr['type'], array('staticFunction', 'objectFunction', '__construct'))) {
                $return = $tabStr . $this->getCodeByCodeMeta($codeMetaArr['object'], 0);
                $return .= $codeMetaArr['type'] == 'objectFunction' ? '->' : '::';
                $return .= '<span class="class_property_call">' . $codeMetaArr['name'] . '</span>';
                $allParams = array();
                if (isset($codeMetaArr['property'])) {
                    foreach ($codeMetaArr['property'] as $param) {
                        $paramStr = $this->getCodeByCodeMeta($param, $tab);
                        $allParams[] = preg_replace('/^' . $tabStr . '/', '', $paramStr);
                    }
                    $return .= '(' . implode(',', $allParams) . ')';
                } else {
                    $return .= '()';
                }
            } elseif ($codeMetaArr['type'] == 'functionCall') {
                $return = $tabStr . $this->getCodeByCodeMeta($codeMetaArr['name'], 0);
                if (isset($codeMetaArr['property'])) {
                    $allParams = array();
                    foreach ($codeMetaArr['property'] as $param) {
                        $allParams[] = $this->getCodeByCodeMeta($param, 0);
                    }
                    $return .= '(' . implode(',', $allParams) . ')';
                } else {
                    $return .= '()';
                }
            } elseif ($codeMetaArr['type'] == 'new') {
                $return = $tabStr . 'new ' . $this->getCodeByCodeMeta($codeMetaArr['name'], 0);
                if (isset($codeMetaArr['property'])) {
                    $allParams = array();
                    foreach ($codeMetaArr['property'] as $param) {
                        $allParams[] = $this->getCodeByCodeMeta($param, 0);
                    }
                    $return .= '(' . implode(',', $allParams) . ')';
                } else {
                    $return .= '()';
                }
            } elseif ($codeMetaArr['type'] == 'foreach') {
                $return = $tabStr . 'foreach(';
                $return .= $this->getCodeByCodeMeta($codeMetaArr['object'], 0);
                $return .= ' as ';
                if (isset($codeMetaArr['key'])) {
                    $return .= $this->getCodeByCodeMeta($codeMetaArr['key'], 0) . ' =>';
                }
                $return .= $this->getCodeByCodeMeta($codeMetaArr['value'], 0) . "){\n";
                foreach ($codeMetaArr['child'] as $child) {
                    $return .= $this->getCodeByCodeMeta($child, $tab + 1);
                    if (isset($child['child']) || in_array($child['type'], $this->noFenhaoType)) {
                        $return .= "\n";
                    } else {
                        $return .= ";\n";
                    }
                }
                $return .= $tabStr . '}';
            } elseif ($codeMetaArr['type'] == 'while') {
                $return = $tabStr . 'while(';
                $return .= $this->getCodeByCodeMeta($codeMetaArr['value'], 0) . "){\n";
                foreach ($codeMetaArr['child'] as $child) {
                    $return .= $this->getCodeByCodeMeta($child, $tab + 1);
                    if (isset($child['child']) || $child['type'] == 'comment') {
                        $return .= "\n";
                    } else {
                        $return .= ";\n";
                    }
                }
                $return .= $tabStr . '}';
            } elseif ($codeMetaArr['type'] == 'dowhile') {
                $return = $tabStr . "do{\n";
                foreach ($codeMetaArr['child'] as $child) {
                    $return .= $tabStr . $this->getCodeByCodeMeta($child, $tab + 1);
                    if (isset($child['child']) || $child['type'] == 'comment') {
                        $return .= "\n";
                    } else {
                        $return .= ";\n";
                    }
                }
                $return .= $tabStr . '}while(';
                $return .= $this->getCodeByCodeMeta($codeMetaArr['value'], 0) . ");";
            } elseif ($codeMetaArr['type'] == 'arrayGet') {
                $return = $tabStr . $this->getCodeByCodeMeta($codeMetaArr['object'], 0);
                $return .= '[' . $this->getCodeByCodeMeta($codeMetaArr['key'], 0) . ']';
            } elseif ($codeMetaArr['type'] == '!') {
                $return = $tabStr . '!' . $this->getCodeByCodeMeta($codeMetaArr['value'], 0);
            } elseif (in_array($codeMetaArr['type'], array('throw', 'echo'))) {
                $return = $tabStr . $codeMetaArr['type'] . ' ' . $this->getCodeByCodeMeta($codeMetaArr['value'], 0);
            } elseif ($codeMetaArr['type'] == 'array') {
                //这段一直写的不好.未来优化
                if (empty($codeMetaArr['child'])) {
                    $return = $tabStr . '<span class="key_words">array</span>()';
                } else {
                    $isShowMoreLine = false;//是否输出了多行
                    if ($tab > 0) {
                        $isShowMoreLine = true;
                    } else {
                        foreach ($codeMetaArr['child'] as $k => $child) {
                            if ($child === null) {
                                continue;
                            }
                            if (in_array($child['type'], array('comments', 'comment'))) {
                                $isShowMoreLine = true;//注释后面不能加逗号
                            } elseif (isset($child['value']['child'])) {
                                $isShowMoreLine = true;
                            }
                        }
                    }
                    $return = $tabStr . '<span class="key_words">array</span>(' . ($isShowMoreLine ? "\n" : '');
                    foreach ($codeMetaArr['child'] as $k => $child) {
                        if ($child === null) {
                            continue;
                        }
                        $return .= $this->getCodeByCodeMeta($child, $isShowMoreLine ? ($tab + 1) : 0) .
                            ($k != count($codeMetaArr['child']) - 1 ? "," : '') .
                            ($isShowMoreLine ? "\n" : '');
                    }
                    $return .= ($isShowMoreLine ? $tabStr : '') . ')';
                }
            } elseif (in_array($codeMetaArr['type'], array('int', 'integer', '8int'))) {
                if ($codeMetaArr['type'] == 'int') {
                    if (is_int($codeMetaArr['data']) || preg_match('/^\d+$/', $codeMetaArr['data'])) {
                    } else {
                        throw new Exception('数据类型int,的值不是数值类型');
                    }
                }
                $return = $tabStr . $codeMetaArr['data'];
            } elseif ($codeMetaArr['type'] == 'arrayValue') {
                $return = $tabStr . $this->getCodeByCodeMeta($codeMetaArr['key'], 0) . ' => ';
                $value = $this->getCodeByCodeMeta($codeMetaArr['value'], $tab);
                $return .= preg_replace('/^' . $tabStr . '/', '', $value);
            } elseif ($codeMetaArr['type'] == 'codeBlock') {
                $return = $tabStr . '(';
                foreach ($codeMetaArr['child'] as $k => $v) {
                    $return .= $this->getCodeByCodeMeta($v, 0);
                }
                $return .= ')';
            } elseif ($codeMetaArr['type'] == '?') {
                //三元运算符
                $return = $tabStr;
                $value = $this->getCodeByCodeMeta($codeMetaArr['value'], $tab) . '?';
                $return .= preg_replace('/^' . $tabStr . '/', '', $value);
                $object1 = $this->getCodeByCodeMeta($codeMetaArr['object1'], $tab) . ":";
                $return .= preg_replace('/^' . $tabStr . '/', '', $object1);
                $object2 = $this->getCodeByCodeMeta($codeMetaArr['object2'], $tab);
                $return .= preg_replace('/^' . $tabStr . '/', '', $object2);
            } elseif ($codeMetaArr['type'] == 'try') {
                //三元运算符
                $return = $tabStr . "try{\n";
                foreach ($codeMetaArr['child'] as $k => $v) {
                    $return .= $this->getCodeByCodeMeta($v, $tab + 1);
                    if (isset($v['child']) || $v['type'] == 'comment') {
                        $return .= "\n";
                    } else {
                        $return .= ";\n";
                    }
                }
                $return .= $tabStr . '}catch(';
                $return .= $this->getCodeByCodeMeta($codeMetaArr['catch'], 0);
                $return .= "){\n";
                foreach ($codeMetaArr['catchChild'] as $k => $v) {
                    $return .= $this->getCodeByCodeMeta($v, $tab + 1);
                    if (isset($v['child']) || $v['type'] == 'comment') {
                        $return .= "\n";
                    } else {
                        $return .= ";\n";
                    }
                }
                $return .= $tabStr . '}';
            } elseif ($codeMetaArr['type'] == '&') {
                $return = '&';
                $return .= $this->getCodeByCodeMeta($codeMetaArr['value'], 0);
            } elseif ($codeMetaArr['type'] == 'staticParams') {
                $return = $this->getCodeByCodeMeta($codeMetaArr['object'], 0) . '::' . $codeMetaArr['type'];
            } elseif (in_array($codeMetaArr['type'], array('--', '++'))) {
                $return = $this->getCodeByCodeMeta($codeMetaArr['object1'], 0) . $codeMetaArr['type'];
            } elseif ($codeMetaArr['type'] == 'html') {
                $return = $codeMetaArr['value'];
            } else {
                //异常,上面写的没错的话,进入不到这里,这个只是监控上面代码写的对不对的报警
                return $codeMetaArr['type'];
            }
        } elseif (is_string($codeMetaArr)) {
            $return = $codeMetaArr;
        }
        return $return;
    }

    /*
     * 关键词所允许的运行时和允许的描述符
     * 例如class只能出现在window运行时
     * 例如class只能有final和abstract描述符
     **/
    private $dataTypeDesc = array(
        'class' => array(
            'runEnvironment' => array('window'),
            'desc' => array('final', 'abstract'),
        ),
        'function' => array(
            'runEnvironment' => array('window', 'class', 'code'),
            'desc' => array('private', 'protected', 'public', 'static', 'final'),
        ),
        'property' => array(
            'runEnvironment' => array('class'),
            'desc' => array('private', 'protected', 'public', 'static'),
        ),
        'codeBlock' => array(
            'runEnvironment' => array('window', 'property'),
            'desc' => array('*'),
        ),
    );
}
