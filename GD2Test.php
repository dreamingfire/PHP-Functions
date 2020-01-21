<?php
/**
 * Created by PhpStorm.
 * User: LiDafei
 * Date: 2018/3/5
 * Time: 19:30
 *  图片的处理：缩放、裁剪、翻转、旋转、透明、锐化等图片操作
 *      一、创建图片资源
 *          imagecreatefromgif(图片地址) //可以将gif换成jpeg、png等
 *          imagedestroy(图片资源) //销毁资源
 *          可以在图片上绘制各种图形
 *      二、获取图片的属性
 *          imagesx(图片资源) //返回图片的宽度
 *          imagesy(图片资源) //返回图片的高度
 *          getimagesize(图片地址) //返回图片信息数组
 *      三、图片处理
 *          1、图片的缩放
 *              imagecopyresampled(目标图片资源，源图片资源，dst_x，dst_y，src_x，src_y，dst_w，dst_h，src_w，src_h)
 *          2、透明处理
 *              png、jpg透明色正常，gif透明色不正常
 *              imagecolortransparent() //将某个颜色定义为透明色 可用来判断透明色
 *              imagecolorstotal() //取得图片的调色板中颜色的数目
 *              imagecolorsforindex() //取得某索引的颜色
 *              先查找图片中是否有透明色，若有透明色查找将什么设为透明色，再进行还原
 *          3、图片的裁剪
 *              imagecopyresized()
 *              imagecopyresampled()
 *          4、添加水印（文字、图片）
 *              文字水印仅在图片上绘制字符
 *              imagecopy(目标图片，源图片,d_x，d_y，s_x，s_y，s_w，s_h) //将源图片的一部分拷贝到目标图片上
 *          5、图片的旋转
 *              imagerotate(图片资源，角度，旋转后没有覆盖部分的颜色)
 *          6、图片的翻转
 *              沿Y轴 沿X轴
 *          7、图片的锐化
 *              imagecolorsforindex()
 *              imagecolorat()
 *              imagecolorexact() //取得指定颜色的索引值
 */
 
    //加图片水印
    function mark_pic($srcpic,$waterpic,$x,$y,$newfilename){
        $back = imagecreatefromjpeg($srcpic);
        $water = imagecreatefromjpeg($waterpic);
        $w_w = imagesx($water);
        $w_h = imagesy($water);
        imagecopy($back,$water,$x,$y,0,0,$w_w,$w_h);
        imagejpeg($back,$newfilename);
        imagedestroy($back);
        imagedestroy($water);
    }
    
    //图片的翻转（沿Y轴）
    function turnByY($srcName,$dstName){
        $img = imagecreatefromjpeg($srcName);
        list($w,$h) = getimagesize($srcName);
        $newImg = imagecreatetruecolor($w,$h);
        $bg = imagecolorallocate($newImg,255,255,255);
        imagefill($newImg,0,0,$bg);
        for($x=0;$x<$w;$x++){
            for($y=0;$y<$h;$y++){
                $color = imagecolorat($img,$x,$y);
                imagesetpixel($newImg,$w-$x,$y,$color);
            }
        }
        imagepng($newImg,$dstName);
        imagedestroy($newImg);
        imagedestroy($img);
    }
    
    //锐化
    function sharp($background,$degree,$save){
        $back = imagecreatefromjpeg($background);
        $b_x = imagesx($back);
        $b_y = imagesy($back);
        $dst = imagecreatefromjpeg($background);
        for($i=0;$i<$b_x;$i++){
            for($j=0;$j<$b_y;$j++){
                $b_clr1 = imagecolorsforindex($back,imagecolorat($back,$i-1,$j-1));
                $b_clr2 = imagecolorsforindex($back,imagecolorat($back,$i,$j));

                $r = intval($b_clr2["red"]+$degree*($b_clr2["red"]-$b_clr1["red"]));
                $g = intval($b_clr2["green"]+$degree*($b_clr2["green"]-$b_clr1["green"]));
                $b = intval($b_clr2["blue"]+$degree*($b_clr2["blue"]-$b_clr1["blue"]));

                $r = min(255,max($r,0));
                $g = min(255,max($g,0));
                $b = min(255,max($b,0));

                if(($d_clr = imagecolorexact($dst,$r,$g,$b))==-1){
                    $d_clr = imagecolorallocate($dst,$r,$g,$b);
                }
                imagesetpixel($dst,$i,$j,$d_clr);
            }
        }
        imagepng($dst,$save);
        imagedestroy($dst);
        imagedestroy($back);
    }
    
    //透明背景图片替换成白色
    function imageBgTransparent($oldSrc,$newSrc){
        $infoArr = getimagesize($oldSrc);
        $type = $infoArr['mime'];
        switch($type){
        case 'image/jpeg':
        case 'image/jpg':
            $imgOld = imagecreatefromjpeg($oldSrc);
            break;
        case 'image/png':
            $imgOld = imagecreatefrompng($oldSrc);
            break;
        case 'image/gif':
            $imgOld = imagecreatefromgif($oldSrc);
            break;
        default:
            return false;
        }   
        if($imgOld){
            $width = imagesx($imgOld);
            $height = imagesy($imgOld);
            $imgNew = imagecreatetruecolor($width,$height);
            imagecopyresampled($imgNew,$imgOld,0,0,0,0,$width,$height,$width,$height);
            $bg = imagecolorallocatealpha($imgNew,255,255,255,127);
            imagesavealpha($imgNew,true);
            imagefill($imgNew,0,0,$bg);
            header("Content-Type:image/png");
            imagepng($imgNew,$newSrc);
            imagedestroy($imgOld);
            imagedestroy($imgNew);
        }else{
            return false;
        }
    }
    
    //将图片中每个颜色为白色的点转化为透明色 ps 黑白转为透明色
    function imageBgTransparent2($oldSrc,$newSrc,$alpha){
        $begin_r = 30;
        $begin_g = 100;
        $begin_b = 100;
        list($src_w, $src_h) = getimagesize($oldSrc);// 获取原图像信息
        $src_im = imagecreatefromjpeg($oldSrc);
        $i = 0;
        $src_white = imagecolorallocate($src_im, 255, 255, 255);
        for ($x = 0; $x < $src_w; $x++) {
           for ($y = 0; $y < $src_h; $y++) {
                $rgb = imagecolorat($src_im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if($r==255 && $g==255 && $b == 255){
                    $i ++;
                    continue;
                }
                if (($r <= $begin_r && $g <= $begin_g && $b <= $begin_b)) {
                    imagefill($src_im, $x, $y, $src_white);//替换成白色
                }
                else{
                    $r = floor($r/$alpha)>255 ?255:floor($r/$alpha);
                    $g = floor($g/$alpha)>255 ?255:floor($g/$alpha);
                    $b = floor($b/$alpha)>255 ?255:floor($b/$alpha);
                    $src_alpha = imagecolorallocate($src_im,$r,$g,$b);
                    imagefill($src_im, $x, $y, $src_alpha);//将颜色还原
                }
            }
        }
        $target_im = imagecreatetruecolor($src_w, $src_h);//新图
        $tag_white = imagecolorallocate($target_im, 255, 255, 255);
        imagefill($target_im, 0, 0, $tag_white);
        imagecolortransparent($target_im, $tag_white);
        //imagecopymerge($target_im, $src_im, 0, 0, 0, 0, $src_w, $src_h, 100);
        imagecopyresampled($target_im, $src_im, 0, 0, 0, 0, $src_w, $src_h, $src_w, $src_h);
        header("Content-Type:image/png");
        imagepng($target_im,$newSrc);
        imagedestroy($src_im);
        imagedestroy($target_im);
    }
 
