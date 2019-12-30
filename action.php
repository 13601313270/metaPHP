<?php
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 2016/12/21
 * Time: 下午6:53
 */
namespace metaPHP;
$class = new classAction('className');
foreach($class->getMethods() as $item){
    if($item->class==$class->getName()){
        $item->isFinal(true)->save();
        exit;
    }
}
print_r($class->getMethods());
exit;
//
////commit 检查代码事件,A类发生改变
//class jiance{
//    //文件正文从这个通道过去,来触发不同的事件
//    public function contentThrow($oldContent,$newContent){
//
//    }
//    private static $listenList = array(
//        'class'=>array(
//            //调用构造函数
//            'create'=>array(),
//            //添加方法
//            'addFunction'=>array(),
//            //删除方法
//            'removeFunction'=>array(),
//            //添加属性
//            'addPro'=>array(),
//            //删除属性
//            'removePro'=>array(),
//            //修改属性
//            'update'=>array(),
//            //调用方法
//            'runFunction'=>array(),
//        ),
//    );
//    public static function addEventListen($obj,$action,$func){
//
//    }
//}
//
//
//
//jiance::addEventListen('*类','addFunction',function($class,$functionName){
//    $class->childClass->add
//});
//
//addEventLi
//
//
//某个基类添加方法