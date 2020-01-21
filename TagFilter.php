<?php
$content = <<<content
需要去标签的内容
content;
$rs = clearHtml($content,'');
//$rs = strip_tags($content);
echo $rs."\n";
exit;

function clearHtml($content,$allowtags='')
{
    mb_regex_encoding('UTF-8');
    //replace MS special characters first 
    $search = array('/‘/u', '/’/u', '/“/u', '/”/u', '/—/u'); 
    $replace = array('\'', '\'', '"', '"', '-'); 
    $content = preg_replace($search, $replace, $content); 
    //make sure _all_ html entities are converted to the plain ascii equivalents - it appears 
    //in some MS headers, some html entities are encoded and some aren't 
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8'); 
    //css filter
    $content=preg_replace("/<(style.*?)>(.*?)<(\/style.*?)>/si","",$content);
    //try to strip out any C style comments first, since these, embedded in html comments, seem to  
    //prevent strip_tags from removing html comments (MS Word introduced combination) 
    if(mb_stripos($content, '/*') !== FALSE){
        $content = mb_eregi_replace('#/\*.*?\*/#s', '', $content, 'm'); 
    }
    //introduce a space into any arithmetic expressions that could be caught by strip_tags so that they won't be  
    //'<1' becomes '< 1'(note: somewhat application specific) 
    $content = preg_replace(array('/<([0-9]+)/'), array('< $1'), $content);
    $content = strip_tags($content, $allowtags); 
    //eliminate extraneous whitespace from start and end of line, or anywhere there are two or more spaces, convert it to one 
    $content = preg_replace(array('/^\s\s+/', '/\s\s+$/', '/\s\s+/u'), array('', '', ' '), $content); 
    //strip out inline css and simplify style tags 
    $search = array('#<(strong|b)[^>]*>(.*?)</(strong|b)>#isu', '#<(em|i)[^>]*>(.*?)</(em|i)>#isu', '#<u[^>]*>(.*?)</u>#isu');
    $replace = array('<b>$2</b>', '<i>$2</i>', '<u>$1</u>');
    $content = preg_replace($search, $replace, $content);
    //on some of the ?newer MS Word exports, where you get conditionals of the form 'if gte mso 9', etc., it appears
    //that whatever is in one of the html comments prevents strip_tags from eradicating the html comment that contains
    //some MS Style Definitions - this last bit gets rid of any leftover comments */
    $num_matches = preg_match_all("/\<!--/u", $content, $matches); 
    if($num_matches){ 
    $content = preg_replace('/\<!--(.)*--\>/isu', '', $content); 
    } 
    return $content; 
}
