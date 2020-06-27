<?php
/**
 * 简单PSR-4规范的实现
 */
$base_dir = "src/";
$prefix = "App\\";
spl_autoload_register (function($class)use($base_dir, $prefix) {
    $base_dir = rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR) . "/" . $base_dir;
    $len = strlen($prefix);
    // 不以命名空间前缀开头，移交给下一个处理器
    if (0 !== strncmp($prefix, $class, $len)) {
        return ;
    }
    // 获得相对类名
    $relative_class_name = substr($class, $len);

    $file_name = $base_dir . str_replace("\\", "/", $relative_class_name) . ".php";

    require $file_name;
});
