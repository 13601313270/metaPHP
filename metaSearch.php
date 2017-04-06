<?php
/**
 * Created by PhpStorm.
 * User: 王浩然
 * Date: 2017/3/23
 * Time: 上午11:15
 */
class metaSearch{
    private function isSearch($dom,$search){
        $searchSplit = explode(':',$search);
        $search = $searchSplit[0];
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
        if($isSearch && count($searchSplit)>1){
            for($i=1;$i<count($searchSplit);$i++){
                if(preg_match('/filter\((.*)\)/',$searchSplit[$i],$match)){
                    $isSearch = $this->isSearch($dom,$match[1]);
                }
            }
        }
        return $isSearch;
    }
    private $findArr = array();
    private $findArrKey = array();
    private function getArrSearchSingle($searchStr, &$arr,$searchArr){
        $searchSplit = explode(':',$searchStr);
        $search = $searchSplit[0];
        if($arr[$search]){
            if(count($searchSplit)>1){
                $isPassFilter = false;
                for($i=1;$i<count($searchSplit);$i++){
                    if(preg_match('/filter\((.*)\)/',$searchSplit[$i],$match)){
                        $isPassFilter = $this->isSearch($arr[$search],$match[1]);
                    }elseif(preg_match('/has\((.*)\)/',$searchSplit[$i],$match)){
                        $newApi = new metaSearch($arr[$search]);
                        $isPassFilter = count($newApi->search($match[1])->toArray())>0;
                    }
                    if($isPassFilter==false){
                        break;
                    }
                }
                if($isPassFilter){
                    $this->findArr[] = &$arr[$search];
                    $this->findArrKey[] = array_merge($searchArr,array($search));
                }
            }else{
                $this->findArr[] = &$arr[$search];
                $this->findArrKey[] = array_merge($searchArr,array($search));
            }
        }else{
            foreach ($arr as $key1=>&$value1) {
                if($this->isSearch($value1,$searchStr)){
                    $this->findArr[] = &$value1;
                    $this->findArrKey[] = array_merge($searchArr,array($key1));
                }elseif(is_array($value1)){
                    $this->getArrSearchSingle($searchStr, $value1,array_merge($searchArr,array($key1)));
                }
            }
        }
    }
    private function getByArr($str,$waitAllDom,$baseArrKey){
        $returnArr = array();
        $returnArrKey = array();
        foreach($waitAllDom as $key=>&$dom){
            $this->findArr = array();
            $this->findArrKey = array();
            $this->getArrSearchSingle($str,$dom,$baseArrKey[$key]);
            $returnArr = array_merge($returnArr,$this->findArr);
            $returnArrKey = array_merge($returnArrKey,$this->findArrKey);
        }
        return array($returnArr,$returnArrKey);
    }
    //待运算数组
    private $codeArr = array();
    //search运行结果数组对应key数组,每次运行search会重置
    private $codeResultKey = array();
    public function __construct(&$codeArr)
    {
        $this->codeArr = &$codeArr;
    }
    public function search($sSearch){
        $baseArr = array(&$this->codeArr);
        $baseArrKey = array(array());
        $sSearch = $sSearch.' ';
        preg_match_all('/(\S+?(:\S+\(object .variable\))?)\s/',$sSearch,$match);
        foreach($match[1] as $str){
            $result = $this->getByArr($str,$baseArr,$baseArrKey);
            $baseArr = $result[0];
            $baseArrKey = $result[1];
        }
        $this->codeResultKey = $baseArrKey;
        return $this;
    }
    public function parent(){
        $result = array();
        foreach($this->codeResultKey as $k=>$v){
            array_pop($v);
            if(!in_array($v,$result)){
                $result[] = $v;
            }
        }
        $this->codeResultKey = $result;
        return $this;
    }
    public function toArray(){
        $return = array();
        foreach($this->codeResultKey as $keysItem){
            $resultItem = &$this->codeArr;
            foreach($keysItem as $key){
                $resultItem = &$resultItem[$key];
            }
            $return[] = &$resultItem;
        }
        return $return;
    }
}