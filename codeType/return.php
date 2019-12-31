<?php

namespace metaPHP;
class type_return
{
    public function matchKeyWord($keyWord)
    {
        return $keyWord === 'return';
    }

    public function run($inter)
    {
        return array(
            'type' => 'return',
            'value' => current($inter->_getCodeMetaByCode('codeBlock', '', ';'))
        );
    }

    public $key = 'return';

    public function getCodeByMeta($codeMetaArr, $inter, $tab)
    {
        $returnValue = $inter->getCodeByCodeMeta($codeMetaArr['value'], $tab);
        return $inter->getTabStr($tab) . 'return ' . preg_replace('/^\s+/', '', $returnValue);
    }
}