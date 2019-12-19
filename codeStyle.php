<?php

/**
 * Created by PhpStorm.
 * User: wanghaoran
 * Date: 2019-12-17
 * Time: 20:38
 */
class codeStyle
{
    // 命名规范参考eslint。https://cn.eslint.org/docs/rules/
    public static $eqeqeq = ';';// 是否使用分号
    public static $spaceInfixOps = [' ', ' '];// 中缀操作符周围有空格
    public static $indent = '    ';// 锁进
    public static $apace_before_class_lbrace = " \n";// 类声明和后面的第一个大括号之间的输出
    public static $spaced_comment = ' ';// 注释和后面内容之间的部分，一般用来设置是否有空格
    public static $commaSpacing = ['', ' '];// 逗号周围使用空格
    public static $arrayElementNewline = 'auto';// 数组每一项独立一行，可选值有 allways->换行(默认)，never->不换行，auto->自动

}
