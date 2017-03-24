<?php
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 2017/3/23
 * Time: 上午11:15
 */
class metaSearch{
    private function isSearch($dom,$search){
        list($search,$filter) = explode(':',$search);
        $isSearch = false;
        if(isset($dom['name']) && substr($search,0,1)=='#' && $dom['name']==substr($search,1)){
            $isSearch = true;
        }elseif(isset($dom['type']) && substr($search,0,1)=='.' && $dom['type']==substr($search,1)){
            $isSearch = true;
        }elseif(substr($search,0,1)=='[') {
            $property = substr($search, 1, -1);
            $property = explode('=', $property);
            if (isset($dom[$property[0]])) {
                if (count($property) == 1) {
                    $isSearch = true;
                } elseif (count($property) == 2 && $dom[$property[0]] == $property[1]) {
                    $isSearch = true;
                }
            }
        }
        //过滤器
        if($isSearch && $filter){
            if(preg_match('/filter\((.*)\)/',$filter,$match)){
                $isSearch = $this->isSearch($dom,$match[1]);
            }
        }
        return $isSearch;
    }
    private $findArr = array();
    function getArrSearchSingle($searchStr, &$arr){
        if($this->isSearch($arr,$searchStr)){
            $this->findArr[] = $arr;
        }elseif($arr[$searchStr]){
            $this->findArr[] = &$arr[$searchStr];
        }else{
            foreach ($arr as $key1=>&$value1) {
                if($this->isSearch($value1,$searchStr)){
                    $this->findArr[] = &$value1;
                }elseif(is_array($value1)){
                    $this->getArrSearchSingle($searchStr, $value1);
                }
            }
        }
        return $this->findArr;
    }
    function getByArr($str,$arrList){
        $returnArr = array();
        foreach($arrList as &$dom){
            $this->findArr = array();
            $returnArr = array_merge($returnArr,$this->getArrSearchSingle($str,$dom));
        }
        return $returnArr;
    }
    public function search(&$codeArr,$sSearch){
        $metaSearchApi = new metaSearch();
        $baseArr = array(&$codeArr);
        foreach(explode(' ',$sSearch) as $str){
            $baseArr =  $metaSearchApi->getByArr($str,$baseArr);
        }
        return $baseArr;
    }

}