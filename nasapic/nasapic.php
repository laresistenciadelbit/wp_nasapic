<?php
/*
* Plugin Name: nasapic
* Description: Shortcode for the last uploaded image from nasa.
* Version: 1.0
* Author: laresistenciadelbit
* Author URI: https://www.laresistenciadelbit.com
*/

function nasapic($atts)//variables de entrada: cuenta,repositorio,tipo,x,y
{
	$img_file='nasa.jpg';
	$noimgerror=false;

	if( !file_exists(plugin_dir_path( __FILE__ ).$img_file) ||  filesize(plugin_dir_path( __FILE__ ).$img_file) <=0 || date("d/m",filemtime(plugin_dir_path( __FILE__ ).$img_file)) != date("d/m") )
	{
		$ch = curl_init("https://apod.nasa.gov/apod/astropix.html");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$contenido = curl_exec($ch);
		curl_close($ch);
		 
		$a=stripos($contenido,'<img src="');
		$real_start=$a+strlen('<img src="');
		$b=stripos($contenido,'"',$real_start);
		$imgurl=substr($contenido,$real_start,$b-$real_start);

		if(file_exists(plugin_dir_path( __FILE__ ).$img_file) && filesize(plugin_dir_path( __FILE__ ).$img_file)>0)	//si existía la renombra por si la nueva no la obtiene bien
			rename(plugin_dir_path( __FILE__ ).$img_file,plugin_dir_path( __FILE__ ).$img_file.".old");
		
		grab_image('https://apod.nasa.gov/apod/'.$imgurl , plugin_dir_path( __FILE__ ).$img_file);
		
		if( !file_exists(plugin_dir_path( __FILE__ ).$img_file) || filesize(plugin_dir_path( __FILE__ ).$img_file) <=0  ) //si no existe o su tamaño es 0  le ponemos la imagen del día antes
		{
			if(file_exists(plugin_dir_path( __FILE__ ).$img_file.".old"))
				$img_file=$img_file.".old";
			else
				$noimgerror=true;	//si tampoco pudo coger ni la imagen anterior, marcamos flag de error para que no aparezca nada
		}
		else
		{
			if( isset($atts['x']) && isset($atts['y']) )
				$img = resize_image(plugin_dir_path( __FILE__ ).$img_file, $atts['x'], $atts['y']);
			else
				$img = resize_image(plugin_dir_path( __FILE__ ).$img_file, 350, 350);
			
			//imagejpeg($img, plugin_dir_path( __FILE__ ).$img_file ,90); //90 es la calidad (de 1-100)
		}
	}
	
	if(isset($atts["border"]) && $atts["border"]=="radius")$border_var="border-radius: 10px;";
	else $border_var="";

	if(isset($atts['x']))$x=' width="'.$atts['x'].'" ';
	else $x="";
	
	if(isset($atts['y']))$y=' height="'.$atts['y'].'" ';
	else $y="";
	
	if(!$noimgerror)	//si no hubo error, es decir, si encontró la imagen o la de la vez anterior
		echo '<a href="https://apod.nasa.gov/"> <img src="'.plugin_dir_url( __FILE__ ).$img_file.'" '.$x.$y.' style="'.$border_var.'" alt="nasa pic of the day"></a>';
}
add_shortcode('nasapic', 'nasapic');


function nasapic_button_script() 
{
    if(wp_script_is("quicktags"))
    {
        ?>
            <script type="text/javascript">
                QTags.addButton( 
					"nasapic_shortcode",//"code_shortcode",
					"nasapic", 
                    callback
                );
                function callback()
                {
                    QTags.insertContent('[nasapic x="300" y="300" border="radius"]');
                }
            </script>
        <?php
    }
}
add_action("admin_print_footer_scripts", "nasapic_button_script");


//ensure that in php.ini allow_url_fopen is enabled.
function grab_image($url,$saveto){ //https://stackoverflow.com/questions/6476212/save-image-from-url-with-curl-php#6476232
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $raw=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($saveto)){
        unlink($saveto);
    }
    $fp = fopen($saveto,'x');
    fwrite($fp, $raw);
    fclose($fp);
}

//needs gd lib (xampp has it for default)
//$img = resize_image(‘/path/to/some/image.jpg’, 200, 200);
function resize_image($file, $w, $h, $crop=FALSE) {//https://stackoverflow.com/questions/14649645/resize-image-in-php#14649689
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($crop) {
        if ($width > $height) {
            $width = ceil($width-($width*abs($r-$w/$h)));
        } else {
            $height = ceil($height-($height*abs($r-$w/$h)));
        }
        $newwidth = $w;
        $newheight = $h;
    } else {
        if ($w/$h > $r) {
            $newwidth = $h*$r;
            $newheight = $h;
        } else {
            $newheight = $w/$r;
            $newwidth = $w;
        }
    }
    $src = imagecreatefromjpeg($file);
    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    return $dst;
}

?>