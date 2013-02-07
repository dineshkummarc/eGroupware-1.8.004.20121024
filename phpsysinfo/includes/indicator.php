<?php

  if (!defined('IN_PHPSYSINFO')) {
    die("No Hacking");
  }

  $start   = $_GET['color1'];
  $end	   = $_GET['color2'];
  $percent = $_GET['percent'];
  $height  = $_GET['height'];
  
  $width   = 300;

  sscanf( $start, "%2x%2x%2x", $rbase, $gbase, $bbase );
  sscanf( $end, "%2x%2x%2x", $rend, $gend, $bend );

  if ( $rbase == $rend) $rend = $rend - 1;
  if ( $gbase == $gend) $gend = $gend - 1;
  if ( $bbase == $bend) $bend = $bend - 1;

  $rmod = ( $rend - $rbase ) / $width;
  $gmod = ( $gend - $gbase ) / $width;
  $bmod = ( $bend - $bbase ) / $width;

  $image = imagecreatetruecolor( $width, $height );
  imagefilledrectangle( $image, 0, 0, $width, $height, imagecolorallocate( $image, 255,255,255));
  
  $step = $width / 100;

  for( $i = 0; $i < $percent * $step; $i = $i + $step + 1 ) {
    $r = ( $rmod * $i ) + $rbase;
    $g = ( $gmod * $i ) + $gbase;
    $b = ( $bmod * $i ) + $bbase;
    $color = imagecolorallocate( $image, $r, $g, $b );
    imagefilledrectangle( $image, $i, 0, $i + $step, $height, $color);
  }
  imagerectangle( $image, 0, 0, $width - 1, $height - 1, imagecolorallocate( $image, 0, 0, 0 ) );
  imagepng( $image );
  imagedestroy( $image );
?>
