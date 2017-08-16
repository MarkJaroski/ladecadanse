<?php
if (is_file("config/reglages.php"))
{
	require_once("config/reglages.php");
}
require_once($rep_librairies."Sentry.php");
$videur = new Sentry();

$page_titre = "Contact";
$page_description = "Formulaire pour envoyer un email au webmaster de La décadanse : proposer un événement, poser une question, etc.";
$nom_page = "contacteznous";
$extra_css = array("formulaires", "contacteznous_formulaire");
//$extra_js = array("freecap", "verif_captcha");
include("includes/header.inc.php");
?>


<!-- Debut Contenu -->
<div id="contenu" class="colonne contacteznous">


	<div id="entete_contenu">
		<h2>Contact</h2>
		<div class="spacer"></div>
	</div>

<?php
/*
* TRAITEMENT DU FORMULAIRE (EDITION OU AJOUT)
* $post_pour : tableau de destinataires membres
*/

require_once($rep_librairies.'Validateur.php');
$verif = new Validateur();

$champs = array("email" => "", "auteur" => "", "affiliation" => "", "sujet" => "", "contenu" => "");

$action_terminee = false;

if (isset($_POST['formulaire']) && $_POST['formulaire'] === 'ok'  && empty($_POST['as_nom']))
{


	foreach ($champs as $c => $v)
	{
		if (get_magic_quotes_gpc())
		{
			$champs[$c] = stripslashes($_POST[$c]);
		}
		else
		{
			$champs[$c] = $_POST[$c];
		}
	}

	/*
	 * VERIFICATION DES CHAMPS ENVOYES par POST
	 */
	$verif = new Validateur();
	$erreurs = array();

	$verif->valider($champs['email'], "email", "email", 4, 80, 1);
	$verif->valider($champs['auteur'], "auteur", "texte", 2, 80, 1);
	$verif->valider($champs['affiliation'], "affiliation", "texte", 2, 80, 0);
	$verif->valider($champs['sujet'], "sujet", "texte", 2, 80, 1);
	$verif->valider($champs['contenu'], "contenu", "texte", 8, 10000, 1);


/*
	if(!empty($_SESSION['freecap_word_hash']) && !empty($_POST['word']))
	{
		// all freeCap words are lowercase.
		// font #4 looks uppercase, but trust me, it's not...
		if($_SESSION['hash_func'](strtolower($_POST['word']))==$_SESSION['freecap_word_hash'])
		{
			// reset freeCap session vars
			// cannot stress enough how important it is to do this
			// defeats re-use of known image with spoofed session id
			$_SESSION['freecap_attempts'] = 0;
			$_SESSION['freecap_word_hash'] = false;


			// now process form


			// now go somewhere else
			// header("Location: somewhere.php");

		} else {
			$verif->setErreur("freecap", "Le texte que vous avez entré ne correspond pas au mot dans l'image");
		}
	} else {
		$verif->setErreur("freecap", "Vous devez entrer un mot correspondant à celui de l'image ci-dessus");
	}

*/
	/*
	 * PAS D'ERREUR, donc envoi executé
	 */
	if ($verif->nbErreurs() == 0)
	{

        require_once "Mail.php";
        $from = $champs['email'];
		$to = '"La décadanse" <'.$glo_email_info.'>';
        $subject = "[La décadanse] ".$champs['sujet'];
		$contenu_message = "Affiliation : ".$champs['affiliation']."\n\n".$champs['contenu'];



        $headers = array (
		"Content-Type" => "text/plain; charset=\"UTF-8\"",
		'From' => $from,
        'To' => $to,
        'Subject' => $subject);
        
        $smtp = Mail::factory('smtp',
        array ('host' => $glo_email_host,
        'auth' => true,
        'username' => $glo_email_username,
        'password' => $glo_email_password));

        $mail = $smtp->send($to, $headers, $contenu_message);

		// HACK : pear http://forum.revive-adserver.com/topic/1597-non-static-method-peariserror-should-not-be-called-statically/
        //if (PEAR::isError($mail)){
        if ((new PEAR)->isError($mail))
		{
            msgErreur('L\'envoi a echoué');			
			echo("<p>" . $mail->getMessage() . "</p>");
        } else {
			msgOk('Merci, votre message a été envoyé au webmaster');
        }


		/*
		* Envoi de l'email
		*/
		/* avant 2014-09-22
		$mail = new PHPMailer();

		$mail->From = $champs['email'];
		$mail->FromName = $champs['auteur'];
		$mail->AddAddress($glo_email_info, "La décadanse");
		$mail->WordWrap = 50;                                 // set word wrap to 50 characters

		$mail->Subject = "[La décadanse] ".$champs['sujet'];
		$mail->Body    = "Affiliation : ".$champs['affiliation']."\n\n".$champs['contenu'];

		if($mail->Send())
		{
			msgOk('Merci, votre message a été envoyé au webmaster');
			
		}
		else
		{
			msgErreur('L\'envoi a echoué');				 
			echo "Mailer Error: " . $mail->ErrorInfo;
		}		
	    */

		$action_terminee = true;
		unset($_POST);


	} //if erreurs == 0


} //POST


if (!$action_terminee)
{
	
if ($verif->nbErreurs() > 0)
{
	msgErreur($verif->getMsgNbErreurs());
}
// onsubmit="return validerEnvoiEmail();
?>


<div style="margin:1em 0 1em 1em">

<p>Pour nous communiquer vos événements, merci de passer par la page <strong><a href="annoncerEvenement.php">Annoncer&nbsp;un&nbsp;événement</a></strong></p>

</div>
<h3>E-mail</h3>
<div style="margin:1em 0 0em 2em">
<p>
<script type="text/javascript" language="javascript">
<!--
// Email obfuscator script 2.1 by Tim Williams, University of Arizona
// Random encryption key feature by Andrew Moulden, Site Engineering Ltd
// This code is freeware provided these four comment lines remain intact
// A wizard to generate this code is at http://www.jottings.com/obfuscator/
{ coded = "SR2w@1DthHDtDRgh.H8"
  key = "hRVNufMo37X6xZEKHsk8QWTI2mwqPjFr5iUYglCa0vdenOBGb4z9SyLA1JDcpt"
  shift=coded.length
  link=""
  for (i=0; i<coded.length; i++) {
    if (key.indexOf(coded.charAt(i))==-1) {
      ltr = coded.charAt(i)
      link += (ltr)
    }
    else {     
      ltr = (key.indexOf(coded.charAt(i))-shift+key.length) % key.length
      link += (key.charAt(ltr))
    }
  }
document.write("<a href='mailto:"+link+"'>"+link+"</a>")
}
//-->
</script><noscript>Sorry, you need Javascript on to email me.</noscript>
</p>
</div>
<?php if (1) { ?>
<h3>Formulaire</h3>
<form method="post" id="ajouter_editer" enctype="multipart/form-data" action="<?php echo basename(__FILE__) ?>">
<p>* indique un champ obligatoire</p>
<span class="mr_as">
	<label for="mr_as">Ne pas remplir ce champ</label><input name="as_nom" id="as_nom" type="text">
</span>

<fieldset>
<legend>Vos coordonnées</legend>

<!-- Email obligatoire (text) -->
<p>
<label for="email" id="label_email">E-mail* </label>
<input name="email" id="email" type="text" size="40" title="email expéditeur" tabindex="1" value="<?php echo securise_string($champs['email']) ?>"  onblur="validerEmail('email', 'false');" />
<?php echo $verif->getErreur("email"); ?>
</p>
<div class="guideChamp">Votre adresse e-mail restera confidentielle.</div>

<!-- Nom obligatoire (text) -->
<p>
<label for="auteur" id="label_nom">Prénom/Nom* </label>
<input name="auteur" id="auteur" type="text" size="30" title="auteur" tabindex="2" value="<?php echo securise_string($champs['auteur']) ?>" />
<?php echo $verif->getErreur("auteur"); ?>
</p>


<!-- Affiliation (text) -->
<p>
<label for="affiliation" id="label_affiliation">Affiliation </label>
<input name="affiliation" id="affiliation" type="text" size="30" tabindex="3" value="<?php echo securise_string($champs['affiliation']) ?>" />
<?php echo $verif->getErreur("affiliation"); ?>
</p>
<div class="guideChamp">Vous pouvez indiquer ici à quel groupe, assoc, etc. vous appartenez.</div>

</fieldset>

<fieldset>

<!-- Sujet obligatoire (text) -->
<legend>Message</legend>

<p>
<label for="sujet" id="label_sujet">Sujet* </label>
<input name="sujet" id="sujet" type="text" size="64" maxlength="100" title="sujet" tabindex="4" value="<?php echo securise_string($champs['sujet']) ?>" />
<?php echo $verif->getErreur("sujet"); ?>
</p>


<!-- Contenu obligatoire (textarea) -->
<p>
<label for="contenu" id="label_contenu">Contenu* </label><textarea name="contenu" id="message" rows="14" title="" tabindex="5">
<?php echo securise_string($champs['contenu']) ?>
</textarea>
<?php echo $verif->getErreur("contenu"); ?>
</p>


</fieldset>

<?php /* ?>
<fieldset>

<legend>Antispam</legend>

<img src="librairies/freecap/freecap.php" id="freecap" alt="captcha" />
<p class="guide_captcha"><a href="#" onClick="this.blur();new_freecap();return false;">Générer un nouveau mot</a></p>
<div class="spacer"></div>
<label for="word">Veuillez copier le mot ci-dessus* :</label>
<input type="text" name="word" id="word" onblur="javascript:callServer();" onFocus="javascript:document.getElementById('captcha_result').innerHTML='';" />
<span id="captcha_result"></span>
<?php echo $verif->getHtmlErreur("freecap"); ?>

</fieldset>
<?php */ ?>

<p class="piedForm">
	<input type="hidden" name="formulaire" value="ok" />

	<input type="submit" value="Envoyer" class="submit" />
	<div class="spacer"><!-- --></div>
</p>

</form>
<?php } // if 0 ?>
<?php
} // if action_terminee
?>

</div>
<!-- fin contenu -->

<div id="colonne_gauche" class="colonne">

<?php include("includes/navigation_calendrier.inc.php"); ?>
</div>
<!-- Fin Colonnegauche -->

<div id="colonne_droite" class="colonne">
</div>

<?php
include("includes/footer.inc.php");
?>
