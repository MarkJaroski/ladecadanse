<?php

require_once("app/bootstrap.php");

use Ladecadanse\Utils\Logger;
use Ladecadanse\HtmlShrink;
use Ladecadanse\Utils\Utils;
use Ladecadanse\Utils\Validateur;
use Ladecadanse\Utils\Text;

$tab_menu_tri = array("pertinence" => "pertinence", "dateEvenement" => "date", "dateAjout" => "date d'ajout");
$tab_periodes = array("futur", "ancien", "tous");

$get = array();

$get['tri'] = "pertinence";
if (isset($_GET['tri']))
{
    try {
        $get['tri'] =  Validateur::validateUrlQueryValue($_GET['tri'], "enum", 1, array("pertinence", "dateEvenement", "dateAjout"));
    } catch (Exception $e) { header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request"); exit; }
}

$get['page'] = 1;
if (isset($_GET['page']))
{try {
	$get['page'] =  Validateur::validateUrlQueryValue($_GET['page'], "int", 1);
    } catch (Exception $e) { header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request"); exit; }
}

$get['periode'] = "futur";
if (isset($_GET['periode']))
{try {
	$get['periode'] =  Validateur::validateUrlQueryValue($_GET['periode'], "enum", 1, $tab_periodes);
    } catch (Exception $e) { header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request"); exit; }
}

$get['mots'] = "";
if (isset($_GET['mots']))
{
    try
    {
        $get['mots'] = Validateur::validateUrlQueryValue($_GET['mots'], "string", 1);
    } catch (Exception $e) { header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request"); exit; }
}

$limite = 10;

$page_titre = "recherche dans l'agenda des événements";
$page_description = "Rechercher un événement culturel à Genève et Lausanne";
include("_header.inc.php");
?>

<div id="contenu" class="colonne rechercher">
	<div id="entete_contenu">
        <h2>Rechercher un événement</h2><?php HtmlShrink::getMenuRegions($glo_regions, $get); ?>
		<div class="spacer"></div>
	</div>

<?php
$et_ou = "AND";
$mot = "";


if (!empty($get['mots']))
{
    $mots = trim($get['mots']);


	if (!empty($_GET['name_as']))
	{
        echo "Veuillez laisser le champ name_as vide";
		exit;
    }


	$mots = mb_strtolower($mots);
	$mots = str_replace("+", " ", trim($mots));
	$mots = str_replace("\"", " ",$mots);
	$mots = str_replace(",", " ", $mots);
	$mots = str_replace(":", " ", $mots);
	$tab_tous_mots = explode(" ", $mots);


	$mots_vides = array();
	if (!$fp = fopen("resources/mots_vides.txt","r"))
	{
		echo "Echec de l'ouverture du fichier";
		exit;
	}
	else
	{
		while(!feof($fp))
		{
		// On récupère une ligne
			$Ligne = fgets($fp, 255);

		// On affiche la ligne

			$mots_vides[] = trim($Ligne);

		}
		fclose($fp); // On ferme le fichier
	}


	$get['mots'] = "";
	$tab_mots = array();
	for ($i = 0; $i < sizeof($tab_tous_mots); $i++)
	{
		//pour l'url
		$get['mots'] .= $tab_tous_mots[$i]."+";

		//exclusion des mots vides
		if (!in_array($tab_tous_mots[$i], $mots_vides))
		{
			$tab_mots[] = $tab_tous_mots[$i];
		}
	}
	$nb_mots = count($tab_mots);


	$get['mots'] = mb_substr($get['mots'], 0, -1);

	$champs_evenement = array("titre", "nomLieu", "description");

	$sql_select = "SELECT SQL_CALC_FOUND_ROWS idEvenement, idPersonne, titre, idLieu, idSalle, nomLieu, description, genre, dateEvenement,
	flyer, prix, horaire_debut, horaire_complement, dateAjout
	FROM evenement WHERE statut='actif' AND region IN ('".$connector->sanitize($_SESSION['region'])."', 'rf', 'hs') AND ";

	$sql_select .= "( ";

	for ($c = 0; $c < count($champs_evenement); $c++)
	{
		if ($champs_evenement[$c] == "nomLieu")
		{
			$et_ou = "OR";
		}

		for ($i = 0; $i < $nb_mots; $i++)
		{
			$sql_select .= $connector->sanitize($champs_evenement[$c])." LIKE '%".$connector->sanitize($tab_mots[$i])."%' ".$et_ou." ";

		}
		$et_ou = "AND";

		$sql_select = mb_substr($sql_select, 0, -4);
		$sql_select .= "OR ";
	}
	if (count($champs_evenement))
		$sql_select = mb_substr($sql_select, 0, -3);

	$sql_select .= ") ";

	if ($get['periode'] == "futur")
	{
		$sql_select .= " AND dateEvenement >= '".$glo_auj."'";
	}
	else if ($get['periode'] == "ancien")
	{
		$sql_select .= " AND dateEvenement < '".$glo_auj."'";
	}

	if ($get['tri'] == "dateAjout" || $get['tri'] == "dateEvenement")
	{
		$sql_select .= " ORDER BY ".$get['tri']." DESC ";
	}

	//$sql_select .= " AND evenement.idLieu = lieu.idLieu";
	//echo $sql_select;
	//echo $sql_select;
	$req_even = $connector->query($sql_select);
	$nb_even = $connector->getNumRows($req_even);

	if ($get['tri'] != "pertinence")
	{
		$sql_select .= " LIMIT ".($get['page'] - 1) * $limite.", ".(($get['page'] - 1)* $limite + $limite);
		$req_even = $connector->query($sql_select);
	}
	else
	{
		$nb_even = $connector->getNumRows($req_even);
	}

    if ($get['page'] == 1)
        $logger->log('global', 'activity', "[recherche] \"" . $get['mots'] . "\" with " . $nb_even . " events found", Logger::GRAN_YEAR);

    $idE_trouves = "";

	if ($nb_even == 0)
	{
	  	HtmlShrink::msgInfo("Pas d'événement trouvé pour <em>".sanitizeForHtml($mots)."</em>");
	}
	else
	{
		if ($get['tri'] == "pertinence")
		{
			$even_points = array();

			$p = 0;

			while ($tab_even = $connector->fetchArray($req_even))
			{
				$even_points[$tab_even['idEvenement']] = 0;
				// $even_points[$p][0] = $tab_even['idEvenement'];
				// $even_points[$p][1] = 0;
				//print_r($tab_even);
				//echo "<br>";

				for ($i = $nb_mots; $i >= 1; $i--)
				{
					//echo "mots de long:".$i."<br>";

					$dep_max = $nb_mots - $i;

					for ($m = 0; $m <= $dep_max; $m++)
					{
						$sous_phrase = "";

						for ($n = $m; $n < $m + $i; $n++)
						{
							$sous_phrase .= $tab_mots[$n]." ";
						}

						//echo $sous_phrase."<br />";

						$sous_phrase = mb_substr($sous_phrase, 0, -1);

						$nb_titre = 0;
						$nb_nomLieu = 0;
						$nb_desc = 0;

						if (mb_strlen($sous_phrase) > 0)
						{
							$nb_titre = mb_substr_count(mb_strtolower($tab_even['titre']), $sous_phrase);
							$nb_nomLieu = mb_substr_count(mb_strtolower($tab_even['nomLieu']), $sous_phrase);
							$nb_desc = mb_substr_count(mb_strtolower($tab_even['description']), $sous_phrase);

						}

						$even_points[$tab_even['idEvenement']] += ($nb_titre * $i) * 5;
						$even_points[$tab_even['idEvenement']] += ($nb_nomLieu * $i) * 5;
						$even_points[$tab_even['idEvenement']] += $nb_desc * $i;
					}

				}

				$p++;
			}

			$tab_res = $connector->fetchAll($req_even);

			arsort($even_points);

		}
		//print_r($even_points);

		$url_tri = "";

		$pluriel = " ";
		if ($nb_even > 1)
		{
            $pluriel = "s ";
		}

?><h3 class="res"><?php echo $nb_even." événement".$pluriel."  trouvé".$pluriel; ?> pour <em><?php echo sanitizeForHtml($mots) ?></em></h3>

    <?php } ?>

		<div id="res_recherche">



		<ul id="menu_periode">
            <?php
            $get['mots'] = urlencode($get['mots']);
            ?>
            <li class="futur<?php if ($get['periode'] == "futur") { echo " ici"; } ?>"><?php echo "<a href=\"".basename(__FILE__)."?".Utils::urlQueryArrayToString($get, "periode")."&amp;periode=futur\" title=\"\">Futurs</a></li>";?>


            <li class="ancien<?php if ($get['periode'] == "ancien") { echo " ici"; } ?>"><?php echo "<a href=\"".basename(__FILE__)."?".Utils::urlQueryArrayToString($get, "periode")."&amp;periode=ancien\" title=\"\">Anciens</a></li>";?>
            <li class="tous<?php if ($get['periode'] == "tous") { echo " ici"; } ?>"><?php echo "<a href=\"".basename(__FILE__)."?".Utils::urlQueryArrayToString($get, "periode")."&amp;periode=tous\" title=\"\">Tous</a></li>"; ?>
            <div class="spacer"></div>
		</ul>
    <?php
	if ($nb_even > 0)
	{
    ?>
        <div style="padding: 0.4em 0;">Trier par :</div>
		<ul id="menu_tri">
		<?php

		foreach ($tab_menu_tri as $tri => $nom_tri)
		{
			echo "<li";

			if ($get['tri'] == $tri)
			{
				echo " id=\"ici\"><a href=\"".basename(__FILE__)."?".Utils::urlQueryArrayToString($get, "tri")."&amp;tri=".$tri."\" title=\"Trier par ".$nom_tri."\">".ucfirst($nom_tri)."</a>";
			}
			else
			{
				echo "><a href=\"".basename(__FILE__)."?".Utils::urlQueryArrayToString($get, "tri")."&amp;tri=".$tri."\" title=\"Trier par ".$nom_tri."\">".ucfirst($nom_tri)."</a>";
			}
			echo "&nbsp;</li>";
		}
		?>
		</ul>
		<div class="spacer"><!-- --></div>
		<?php


		echo HtmlShrink::getPaginationString($nb_even, $get['page'], $limite, 1, basename(__FILE__), "?" . Utils::urlQueryArrayToString($get, "page") . "&amp;page=");
                ?>



		<table>

		<?php



		$no_score = 0;



		if ($get['tri'] == "pertinence")
		{

			foreach ($even_points as $no => $score)
			{

				if ($no_score >= (($get['page'] - 1)*$limite) && $no_score <= (($get['page'] -1 )*$limite + $limite))
				{
					mysqli_data_seek($req_even, 0);

					while ($tab_even = $connector->fetchArray($req_even))
					{

						if ($tab_even['idEvenement'] == $no)
						{
								$idE_trouves .= $no.";";

								//Affichage du lieu selon son existence ou non dans la base
								if ($tab_even['idLieu'] != 0)
								{
									$listeLieu = $connector->fetchArray(
									$connector->query("SELECT nom, adresse, quartier, determinant, URL FROM lieu
									WHERE idlieu='".$tab_even['idLieu']."'"));

									$salle = '';
									if ($tab_even['idSalle'] != 0)
									{
									$tab_salle = $connector->fetchArray($connector->query("SELECT nom from salle where idSalle=".$tab_even['idSalle']));
									$salle = " - ".$tab_salle['nom'];
									}
									$infosLieu = $listeLieu['determinant'] . " <a href=\"/lieu.php?idL=" . $tab_even['idLieu'] . "\" title=\"Voir la fiche du lieu : " . sanitizeForHtml($listeLieu['nom']) . "\" >" . sanitizeForHtml($listeLieu['nom']) . "</a>" . $salle;
                            }
								else
								{

									$listeLieu['nom'] = sanitizeForHtml($tab_even['nomLieu']);
                                $infosLieu = sanitizeForHtml($tab_even['nomLieu']);
                            }
							?>
							<tr

							<?php

								if ($tab_even['dateEvenement'] < $glo_auj_6h)
								{
									echo " class=\"ancien\"";
                            }
								else if ($tab_even['dateEvenement'] == $glo_auj_6h)
								{
									echo " class=\"auj\"";
                            }
								else if ($tab_even['dateEvenement'] > $glo_auj_6h)
								{
									echo " class=\"futur\"";
                            }

							?>>



								<td class="desc_even">
								<?php
								$titre = $tab_even['titre'];
								?>
                                    <h3><a href="/evenement.php?idE=<?php echo $tab_even['idEvenement'] ?>" title="Voir la fiche de l'événement"><?php echo sanitizeForHtml($titre) ?></a></h3>
                                                                <?php
								$maxChar = Text::trouveMaxChar($tab_even['description'], 50, 4);
								if (mb_strlen($tab_even['description']) > $maxChar)
								{
									$texte_court = Text::texteHtmlReduit(Text::wikiToText(sanitizeForHtml($tab_even['description'])), $maxChar, "");
                                                                    /* 									foreach($tab_mots as $n => $mot)
									{
										$texte_court = highlight($texte_court, $mot);
									} */
									//buggé, remplacer par une bonne expreg
								}
								else
								{
									$texte_court = Text::wikiToHtml(sanitizeForHtml($tab_even['description']));
                                                                    /* 									foreach($tab_mots as $n => $mot)
									{
										$texte_court = highlight($texte_court, $mot);
									} */
								}

								echo "<p class=\"description\">".$texte_court."</p>";
								echo "<p>".$infosLieu."</p>";
							?>
							</td>
							<td><a href="/evenement-agenda.php?courant=<?php echo $tab_even['dateEvenement']; ?>"><?php echo date_iso2app($tab_even['dateEvenement']) ?></a></td>
							<td><?php echo $glo_tab_genre[$tab_even['genre']] ?></td>

							<?php if (isset($_SESSION['Sgroupe']) && $_SESSION['Sgroupe'] <= 1) { ?>
							<td><?php echo $score ?></td>
							<?php } ?>
							<td><!-- -->
							<?php
				if (
				(isset($_SESSION['Sgroupe']) && $_SESSION['Sgroupe'] <= 6)
				|| (isset($_SESSION['SidPersonne']) && $_SESSION['SidPersonne'] == $tab_even['idPersonne'])
				|| (isset($_SESSION['Saffiliation_lieu']) && !empty($tab_even['idLieu']) && $tab_even['idLieu'] == $_SESSION['Saffiliation_lieu'])
				)

							{
							?>

							<a href="/evenement-edit.php?action=editer&amp;idE=<?php echo $tab_even['idEvenement'] ?>" title="Éditer cet événement"><?php echo $iconeEditer; ?></a>

							<?php
							}
							?>
							</td>
						</tr>
				<?php
						}

					}


				}
				$no_score++;
			} //foreach ($tab_res as $id =>

		} //tri Pertinence
		else
		{
			while($tab_even = $connector->fetchArray($req_even))
			{


					//Affichage du lieu selon son existence ou non dans la base
					if (!empty($tab_even['idLieu']))
					{
						$listeLieu = $connector->fetchArray(
						$connector->query("SELECT nom, adresse, determinant, quartier, URL
						FROM lieu WHERE idlieu='".$tab_even['idLieu']."'"));

						$salle = '';
						if ($tab_even['idSalle'] != 0)
						{
							$tab_salle = $connector->fetchArray($connector->query("SELECT nom from salle where idSalle=".$tab_even['idSalle']));
							$salle = " - ".$tab_salle['nom'];
						}
						$infosLieu = $listeLieu['determinant']." <a href=\"/lieu.php?idL=".$tab_even['idLieu']."\" title=\"Voir la fiche du lieu : ".htmlspecialchars($listeLieu['nom'])."\" >".htmlspecialchars($listeLieu['nom'])."</a>".$salle;

					}
					else
					{
						$listeLieu['nom'] = htmlspecialchars($tab_even['nomLieu']);
						$infosLieu = htmlspecialchars($tab_even['nomLieu']);
					}


					?>
					<tr

					<?php

						if ($tab_even['dateEvenement'] < $glo_auj_6h)
						{
							echo " class=\"ancien\">";
						}
						else if ($tab_even['dateEvenement'] == $glo_auj_6h)
						{
							echo " class=\"auj\">";
						}
						else if ($tab_even['dateEvenement'] > $glo_auj_6h)
						{
							echo " class=\"futur\">";
						}
						?>


					<td class="desc_even">
					<h3><a href="/evenement.php?idE=<?php echo $tab_even['idEvenement'] ?>" title="Voir la fiche de l'événement"><?php echo $tab_even['titre'] ?></a></h3>
							<?php
								$maxChar = Text::trouveMaxChar($tab_even['description'], 50, 4);
								if (mb_strlen($tab_even['description']) > $maxChar)
								{
									echo "<p class=\"description\">".Text::texteHtmlReduit(Text::wikiToText(htmlspecialchars($tab_even['description'])), $maxChar, "")."</p>";
								}
								else
								{
									echo "<p class=\"description\">".Text::wikiToHtml(htmlspecialchars($tab_even['description']))."</p>";
								}

							echo "<p>".$infosLieu."</p>";
							?>
					</td>
					<td><a href="/evenement-agenda.php?courant=<?php echo $tab_even['dateEvenement']; ?>"><?php echo date_iso2app($tab_even['dateEvenement']) ?></a></td>
					<td><?php echo $tab_even['genre'] ?></td>
					<td>
					<?php
				if (
				(isset($_SESSION['Sgroupe']) && $_SESSION['Sgroupe'] <= 6)
				|| (isset($_SESSION['SidPersonne']) && $_SESSION['SidPersonne'] == $tab_even['idPersonne'])
				|| (isset($_SESSION['Saffiliation_lieu']) && !empty($tab_even['idLieu']) && $tab_even['idLieu'] == $_SESSION['Saffiliation_lieu'])
				){
					?>

					<a href="/evenement-edit.php?action=editer&amp;idE=<?php echo $tab_even['idEvenement'] ?>" title="Éditer cet événement"><?php echo $iconeEditer; ?></a>

					<?php
					}
					?>
					</td>
				</tr>
		<?php
			}

		}

	$dateCourante = ' ';

	//listage des événements


		echo "</table>";
		echo HtmlShrink::getPaginationString($nb_even, $get['page'], $limite, 1, basename(__FILE__), "?" . Utils::urlQueryArrayToString($get, "page") . "&amp;page=");
    } //if evenement trouvé
		echo "</div>"; //res_recherche



}
else
{

	echo "<p>Veuillez entrer un texte à rechercher</p>";

}
?>
</div> <!-- contenu -->

<div id="colonne_gauche" class="colonne">
<?php
include("_navigation_calendrier.inc.php");
?>
</div>

<div id="colonne_droite" class="colonne">
</div>

<!-- Fin Colonne gauche -->
<?php
include("_footer.inc.php");
?>
