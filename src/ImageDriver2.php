<?php
/**
 * Traite des images envoyées par upload via un champ 'file' de formulaire et
 * les stocke dans images/
 * Ce sont des flyers, photos et logos envoyés par les membres pour illustrer
 * un événement, une brêve ou un lieu
 *
 * PHP versions 4 and 5
 *
 * @category   librairie
 * @author     Michel Gaudry <michel@ladecadanse.ch>
 * @see        ajouterBreve.php, ajouterEvenement.php, ajouterLieu.php
 */

require_once ('SystemComponent.php');

class ImageDriver2 extends SystemComponent {

/**
 * La liste des formats d'images acceptés
 *
 * @var array string
 */
var $formats;

/**
 * Nom du répertoire des images du site
 *
 * @var string
 */
var $IMGracine;

/**
 * Nom du répertoire des images des lieux
 *
 * @var string
 */
var $IMGlieux;

var $IMGtype;

var $erreur;

/**
* Constructeur
* Crée le nom des répertoires d'images devant reçevoir les images envoyées par les visiteurs
* Établit les formats d'image acceptés
*
* @access public
* @see config/reglages.php
*/
function ImageDriver2($IMGtype)
{
	global $rep_images;
	$this->IMGracine = $rep_images;
	//TEST
	//echo $this->IMGracine;
	//
	$this->IMGlieux = $rep_images."lieux/";
	$this->IMGlieuxGaleries = $this->IMGlieux."galeries/";
	$this->formats = array('image/jpeg', 'image/pjpeg','image/gif','image/png', 'image/x-png');

	if ($IMGtype == "evenement")
	{
		$this->IMGtype = "";
	}
	else
	{
		$this->IMGtype = $IMGtype;
	}
}

/**
* Renvoye la list des formats acceptés
*
* @access public
* @return array La liste des formats acceptés
*/
function getFormats()
{
	return $this->formats;
}

function is_ani($filename)
{
        $filecontents=file_get_contents($filename);

        $str_loc=0;
        $count=0;
        while ($count < 2) # There is no point in continuing after we find a 2nd frame
        {

                $where1=strpos($filecontents,"\x00\x21\xF9\x04",$str_loc);
                if ($where1 === FALSE)
                {
                        break;
                }
                else
                {
                        $str_loc=$where1+1;
                        $where2=strpos($filecontents,"\x00\x2C",$str_loc);
                        if ($where2 === FALSE)
                        {
                                break;
                        }
                        else
                        {
                                if ($where1+8 == $where2)
                                {
                                        $count++;
                                }
                                $str_loc=$where2+1;
                        }
                }
        }

        if ($count > 1)
        {
                return(true);

        }
        else
        {
                return(false);
        }
}
 /**
	* Fonction principale, redimensionne une image reçue et crée deux nouvelles images
   * une réduite, une mini qui sont ensuite stockée dans le répertoire images du site
   *
   * @access public
   * @param string $imageSource Adresse de l'image uploadée
   * @param string $imageCreated Nom à donner à l'image réduite crée
   * @param string $typeIMG (breves, evenements, lieux) Genre d'utilisation de l'image
   * @param int $maxWidth Largeur maximale voulue pour l'image réduite
   * @param int $maxHeight Hauteur maximale voulue pour l'image réduite
   * @param int $maxWidth_s Largeur maximale voulue pour l'image mini
   * @param int $maxWidth_s Hauteur maximale voulue pour l'image mini
   * @param string $selon ('', w, h) Pour imposer la largeur ou la hauteur de l'image réduite selon $maxWidth ou $maxHeigth
   * @param string $selon_s ('', w, h) Pour imposer la largeur ou la hauteur de l'image mini selon $maxWidth ou $maxHeigth
   * @return string $msgErreur Messag s'il y a eu une erreur
   * @see        ajouterBreve.php, ajouterEvenement.php, ajouterLieu.php
   */
function processImage($imageSource, $imageCreated, $maxWidth = 0, $maxHeigth = 0, $selon = '', $rognage = 0)
{
	$slash = "";
	if ($this->IMGtype != "evenement" && $this->IMGtype != "")
		$slash = "/";

	//echo "slash : ".$slash."<br>";
	$cheminImage = $this->IMGracine.$this->IMGtype.$slash.$imageCreated;

	$imgInfo = getimagesize($imageSource['tmp_name']);
	//TEST
	// echo "tmp_name :".$imageSource['tmp_name']."<br>";
	// echo "<br>chem. image :".$cheminImage;
	// printr($imgInfo);

	//
	if ($imgInfo['mime'] == "image/jpeg")
	{
		$img = ImageCreateFromJpeg($imageSource['tmp_name']);

		//echo "img:".$img;

		if ($img == '')
		{
			$this->erreur = "jpeg non créé";
			return false;
		}
	}
	elseif ($imgInfo['mime'] == "image/gif")
	{
		$img = ImageCreateFromGif($imageSource['tmp_name']);
		$originaltransparentcolor = imagecolortransparent($img);

	}
	elseif ($imgInfo['mime'] == "image/png")
	{
		$img = ImageCreateFrompng($imageSource['tmp_name']);
	}
	else
	{
		$this->erreur = "Le format de l'image '".$imageSource['type']."' n'est pas accepté";
		return false;
	}
	
	

	$imgX2 = $imgX = imagesx($img);
	$imgY2 = $imgY = imagesy($img);

	if ($maxWidth > 0 || $maxHeigth > 0)
	{
		if ($imgY > $imgX)
		{
			if ((empty($selon) || $selon == 'w') && $imgX > $maxWidth)
			{
				$imgX2 = $maxWidth;

				$diffX = $imgX - $maxWidth;
				$propX = $diffX / $imgX;
				$imgY2 = (int)($imgY * (1 - $propX));
			}
			elseif ($selon == 'h' && $imgY > $maxHeigth)
			{
				$imgY2 = $maxHeigth;

				$diffY = $imgY - $maxHeigth;
				$propY = $diffY / $imgY;
				$imgX2 = (int)($imgX * (1 - $propY));
			}
		}
		elseif ($imgY <= $imgX)
		{
			if ($selon == 'w' && $imgX > $maxWidth)
			{
				$imgX2 = $maxWidth;

				$diffX = $imgX - $maxWidth;
				$propX = $diffX / $imgX;
				$imgY2 = (int)($imgY * (1 - $propX));
			 }
			 elseif ((empty($selon) || $selon == 'h') && $imgY > $maxHeigth)
			 {
				$imgY2 = $maxHeigth;
				$diffY = $imgY - $maxHeigth;
				$propY = $diffY / $imgY;

				$imgX2 = (int)($imgX * (1 - $propY));
			}
		}

		if (strstr($imageSource['type'], "gif"))
		{
			$img2 = ImageCreate($imgX2, $imgY2);

			if($originaltransparentcolor >= 0 && $originaltransparentcolor < imagecolorstotal($img))
			{
			    $transparentcolor = imagecolorsforindex($img, $originaltransparentcolor);
			    $newtransparentcolor = imagecolorallocate(
			        $img2,
			        $transparentcolor['red'],
			        $transparentcolor['green'],
			        $transparentcolor['blue']
			    );
			    // for true color image, we must fill the background manually
			    imagefill( $img2, 0, 0, $newtransparentcolor );
			    // assign the transparent color in the thumbnail image
			    imagecolortransparent( $img2, $newtransparentcolor );
			}
		}
		else
		{
			$img2 = ImageCreateTrueColor($imgX2, $imgY2);
			imagealphablending($img2, false );
			imagesavealpha($img2, true);
		}

		ImageCopyResampled($img2, $img, 0, 0, 0, 0, $imgX2, $imgY2, $imgX, $imgY);
	}
	else
	{
		$img2 = $img;
	}

	if ($rognage)
	{
		if ($imgX2 > $maxWidth)
		{
			$img2_r = ImageCreateTrueColor($maxWidth, $imgY2);
			imagealphablending($img2, false );
			imagesavealpha($img2, true);
			ImageCopy($img2_r, $img2, 0, 0, ($imgX2/4), 0, $maxWidth, $imgY2);
			$img2 = $img2_r;
		}
		elseif ($imgY2 > $maxHeigth)
		{
			$img2_r = ImageCreateTrueColor($imgX2, $maxHeigth);
			imagealphablending($img2, false );
			imagesavealpha($img2, true);
			ImageCopy($img2_r, $img2, 0, 0, 0, 0, $imgX2, $maxHeigth);
			$img2 = $img2_r;
		}

	}

	//echo "cheminImage:".$cheminImage."<br>";
	
	$fp = fopen($cheminImage, "w");


	if (!$fp)
	{
		$this->erreur = "Problème de création du fichier";
		return false;
	}

	fclose($fp);

    $messageErreur = "Échec dans la création des images";

	if ($imgInfo['mime'] == "image/jpeg")
	{
		if (!imagejpeg($img2, $cheminImage, 80))
		{
			$this->erreur = "pas OK pour jpeg";
			return false;
		}
		return true;
	}
	elseif ($imgInfo['mime'] == "image/gif")
	{
		if (!imagegif($img2, $cheminImage))
		{
			$this->erreur = "Erreur dans la création du fichier GIF";
			return false;
		}
		return true;
	}
	elseif ($imgInfo['mime'] == "image/png")
	{
		if (!imagepng($img2, $cheminImage))
		{
			$this->erreur = "Erreur dans la création du fichier PNG";
		}
		return true;
	}
	else
	{
		$this->erreur = "Type mime ne correspond pas";
		return false;
	}

}

function getErreur()
{
	return $this->erreur;
}



} //class
?>
