<?php
declare(ticks=1);
register_tick_function("debug", "a", 4, 6);
//脚本逻辑，以下监测变量 $a，使用时请注释掉其他输出
$a = 0;
$a += 1;

/**
 * @param string $vName 变量名
 * @param int $startLineNo 声明后的第一个行号
 * @param int $endLineNo 结束行号
 */
function debug($vName, $startLineNo, $endLineNo) {
    global $STEP, $$vName;
    if (!isset($STEP)) {
        $STEP = $startLineNo - 1;
        echo "<FONT COLOR='#00AA00'><B>Variable <FONT COLOR='#FF6600'>\${$vName}</FONT> Debugger</B> </FONT><br/><br/>\r\n\r\n";
    }
    if ($STEP <= $endLineNo) {
        echo "<FONT COLOR='#00DD00'><B>Line {$STEP}. </B> </FONT>\r\n<br/>";
        var_dump(${$vName});
        echo "\r\n<br/>";
        $STEP ++;
    }
}
