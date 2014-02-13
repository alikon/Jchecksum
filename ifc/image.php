<?php
function image2ascii( $image ) 
{ 
    // return value 
    $ret = ''; 

    // open the image 
    $img = ImageCreateFromJpeg($image);  

    // get width and height 
    $width = imagesx($img);  
    $height = imagesy($img);  

    // loop for height 
    for($h=0;$h<$height;$h++) 
    { 
        // loop for height 
        for($w=0;$w<=$width;$w++) 
        { 
            // add color 
            $rgb = @ImageColorAt($img, $w, $h);  
            $r = ($rgb >> 16) & 0xFF;  
            $g = ($rgb >> 8) & 0xFF;  
            $b = $rgb & 0xFF; 
            // create a hex value from the rgb 
            $hex = '#'.str_pad(dechex($r), 2, '0', STR_PAD_LEFT).str_pad(dechex($g), 2, '0', STR_PAD_LEFT).str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

            // now add to the return string and we are done 
            if($w == $width) 
            {  
                $ret .= '<br>';  
            } 
            else 
            {  
                $ret .= '<span style="color:'.$hex.';">#</span>';  
            }  
        }  
    }  
    return $ret; 
} 

// an image to convert 
$image = dirname(__DIR__) . '/cli/test.jpg'; 

// do the conversion 
$ascii = image2ascii( $image ); 

// and show the world 
echo $ascii; 