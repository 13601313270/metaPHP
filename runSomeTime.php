<?php
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 2017/4/21
 * Time: 下午5:06
 */
class runSomeTime{
    public static function thenRemove($callback){
        try{
            throw new Exception('判断调用位置');
        }catch (Exception $e){
            $file = current($e->getTrace());
            $content = implode('',@file($file['file']));
            $phpInterpreter = new phpInterpreter($content);
            $selectThisMeta = $phpInterpreter->search('.staticFunction:filter([object=runSomeTime]):filter([name=thenRemove])')->toArray();
            for($i=count($selectThisMeta)-1;$i>=0;$i--){
                if($selectThisMeta[$i]['lineNum']<$file['line']){
                    $selectThisMeta = &$selectThisMeta[$i];break;
                }
            }
            if($callback($selectThisMeta)){
                array_splice($phpInterpreter->codeMeta['child'],array_search($selectThisMeta,$phpInterpreter->codeMeta['child']),1);
            }
            $saveCode = $phpInterpreter->getCode();
            file_put_contents($file['file'],$saveCode);
        }
    }
    public static function thenReplace($callback,$replace){
        try{
            throw new Exception('判断调用位置');
        }catch (Exception $e){
            $file = current($e->getTrace());
            $content = implode('',@file($file['file']));
            $phpInterpreter = new phpInterpreter($content);
            $selectThisMeta = $phpInterpreter->search('.staticFunction:filter([object=runSomeTime]):filter([name=thenReplace])')->toArray();
            for($i=count($selectThisMeta)-1;$i>=0;$i--){
                if($selectThisMeta[$i]['lineNum']<$file['line']){
                    $selectThisMeta = &$selectThisMeta[$i];break;
                }
            }
            if($callback($selectThisMeta)){
                $selectThisMeta = $replace($selectThisMeta);
            }
            $saveCode = $phpInterpreter->getCode();
            file_put_contents($file['file'],$saveCode);
        }
    }
}