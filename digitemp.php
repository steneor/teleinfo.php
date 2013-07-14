<?php
include_once 'config.include.php';
setlocale(LC_ALL , "fr_FR");
date_default_timezone_set("Europe/Paris");

if (isset($_POST['date'])) {
    $date = $_POST['date'];
    $ymd = explode('-', $date);
    $timestampdate = mktime(0, 0, 0, $ymd[1], $ymd[2], $ymd[0]);
}

if (isset($_POST['Zoom'])) {
    switch ($_POST['Zoom']) {
        case "3j":
            $Zoom = 0;
            break;
        case "1s":
            $Zoom = 1;
            break;
        case "2s":
            $Zoom = 2;
            break;
        case "3s":
            $Zoom = 3;
            break;
        case "Tout":
            $Zoom = 4;
            break;
    }
} else
    $Zoom = "1";

function ligne_choix ($message, $name, $tab)
{
    echo $message;
    echo "<select size=\"1\" name=\"$name\">\n";
    for ($i = 0 ; $i < sizeof ($tab); $i++) {
        if ($tab[$i] == $_POST[$name]) {
            echo "<option selected>" . ($tab[$i]) . "</option>\n";
        } else
            echo "<option>" . $tab[$i] . "</option>\n";
    }
    echo "          </select> \n";
}
// cf. http://www.phpcs.com/codes/PHP-TO-JS-CONVERSION-VARIABLE-PHP-VERS-JAVASCRIPT_13232.aspx
function php2js ($var)
{
    if (is_array($var)) {
        $res = "[";
        $array = array();
        foreach ($var as $a_var) {
            $array[] = php2js($a_var);
        }
        return "[" . join(",", $array) . "]";
    } elseif (is_bool($var)) {
        return $var ? "true" : "false";
    } elseif (is_int($var) || is_integer($var) || is_double($var) || is_float($var)) {
        return $var;
    } elseif ($var == "null") {
        return "" . addslashes(stripslashes($var)) . "";
    }
    if (is_string($var)) {
        // return "\"" . addslashes(stripslashes($var)) . "\"";    Modifié par ev
        return "" . addslashes(stripslashes($var)) . "";
    }
    // autres cas: objets, on ne les gère pas
    return false;
}
// 1) Dans votre section PHP , lire les données dans un tableau global
// que vous pouvez accéder à partir de votre modèle
$periodesecondes = 30 * 24 * 3600 ; // xx fois 24h.  ici 30jours
$heurecourante = date('H') ; // Heure courante.
$timestampheure = mktime($heurecourante + 1, 0, 0, date("m"), date("d"), date("Y")); // Timestamp courant à heure fixe (mn et s à 0).
$timestampdebut = $timestampheure - $periodesecondes ; // Recule de 24h.
$timestampfin = $timestampheure;
if ($timestampdate) {
    $timestampdebut = $timestampdate;
    $timestampfin = $timestampdebut + $periodesecondes;
    // echo "timestampdebut1: $timestampdebut <br>";
    // echo "timestampfin1: $timestampfin <br>";
}
// echo "periodesecondes:$periodesecondes <br>";
// echo "heurecourante:$heurecourante <br>";
// echo "timestampheure:$timestampheure <br>";
// echo "timestampdebut:$timestampdebut <br>";
mysql_connect($serveur, $login, $pass) or die("Erreur de connexion au serveur MySql");
mysql_select_db($base) or die("Erreur de connexion a la base de donnees $base");
mysql_query("SET NAMES 'utf8'");

$query = "SELECT MIN(timestamp) FROM `$table_digi`" ;
$sql = mysql_query($query) or die ("<b>Erreur</b> dans la requète <b>" . $query . "</b> : " . mysql_error() . " !<br>");
$res = mysql_fetch_row($sql);
$timestampmin = $res[0];

$query = "SELECT MAX(timestamp) FROM `$table_digi`" ;
$sql = mysql_query($query) or die ("<b>Erreur</b> dans la requète <b>" . $query . "</b> : " . mysql_error() . " !<br>");
$res = mysql_fetch_row($sql);
$timestampmax = $res[0];
// echo "timestampmin:$timestampmin <br>";
// echo "timestampmax:$timestampmax <br>";
$datetimedebut = date('Y-m-d', $timestampmin);
$datetimefin = date('Y-m-d', $timestampmax);
$datedebut = date('Y-m-d', $timestampdebut);
// echo "datetimedebut:$datetimedebut <br>";
// echo "datetimefin:$datetimefin <br>";
$query = "SELECT timestamp,s1,s2,s3,s4
  FROM `$table_digi`
  WHERE timestamp BETWEEN $timestampdebut AND $timestampfin
  ORDER BY timestamp " ;

$result = mysql_query($query) or die ("<b>Erreur</b> dans la requète <b>" . $query . "</b> : " . mysql_error() . " !<br>");
$temp1 = array();
$temp2 = array();
$temp3 = array();
$temp4 = array();

$i = 0;
while ($row = mysql_fetch_assoc($result)) {
    $date = $row['timestamp'];
    $stamp = $date * 1000;
    $temp1 = $row['s1'];
    $temp2 = $row['s2'];
    $temp3 = $row['s3'];
    $temp4 = $row['s4'];

    $d1[$i]['x'] = $stamp; // tableau d1 avec timestamp et s1
    $d1[$i]['y'] = $temp1;
    $d2[$i]['x'] = $stamp; // tableau d2 avec timestamp et s2
    $d2[$i]['Y'] = $temp2;
    $d3[$i]['x'] = $stamp; // tableau d3 avec timestamp et s3
    $d3[$i]['y'] = $temp3;
    $d4[$i]['x'] = $stamp; // tableau d4 avec timestamp et s4
    $d4[$i]['Y'] = $temp4;
    $i++;
}
// 2) Dans votre modèle, il vous suffit de rejoindre le tableau PHP
// avec un séparateur virgule pour en faire un tableau de JavaScript
// que Highcharts peut lire.
// echo $data1; => [[1.338998402E+12,26.69],[1.339002001E+12,26.69]]
$data1 = php2js($d1);
$data2 = php2js($d2);
$data3 = php2js($d3);
$data4 = php2js($d4);
//print_r($d1);
//$dd1 = json_encode($d1);
//echo $dd1;
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<title>digitemp V1.1</title>
<!--		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>   -->
		<link rel="stylesheet" href="./js/css/ui-lightness/jquery-ui-1.9.0.custom.min.css">
		<script type="text/javascript" src="./js/jquery-1.8.2.min.js"></script>
		<script type="text/javascript" src="./js/jquery-ui-1.9.0.custom.min.js"></script>
<!--		<style type="text/css">    .ui-datepicker {        font-family:Garamond;        font-size: 14px;        margin-left:10px     }</style>-->

<!--		<script type="text/javascript" src="./js/main.js"></script>	-->

		<script src="./js/highstock.js"></script>
		<script src="./js/modules/exporting.js"></script>
<!--		<script type="text/javascript" src="./js/themes/gray.js"></script>      -->


		<script type="text/javascript">
$(function() {

	Highcharts.setOptions({
		lang: {
				rangeSelectorFrom: 'Du',
	      		rangeSelectorTo: 'au',
				downloadPNG: 'Exporter en PNG',
				downloadJPEG: 'Exporten en JPG',
				downloadPDF: 'Exporter en PDF',
				downloadSVG: 'Exporta para SVG',
				exportButtonTitle: 'Exporter Graphique',
				loading: 'loading ...',
				months: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
	        				'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
	      		shortMonths: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Aou','Sep','Oct','Nov','Dec'],
				weekdays: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
				printButtonTitle: 'Imprimer le graphique',
				resetZoom: 'Reset Zoom'
			},
		global: {
      		useUTC: false
			}
		});
		// Create the chart
		window.chart = new Highcharts.StockChart({
			chart : {
				renderTo : 'container',
				borderColor: '#EBBA95',
      			borderWidth: 2,
      			borderRadius: 10,
      			spacingBottom:50,
      			ignoreHiddenSeries: false
			},

			rangeSelector : {
				buttons: [{
		            type: 'day',
		            count: 3,
		            text: '3j'
		        }, {
		            type: 'week',
		            count: 1,
		            text: '1s'
		        }, {
		            type: 'week',
		            count: 2,
		            text: '2s'
		        }, {
		            type: 'week',
		            count: 3,
		            text: '3s'
		        }, {
		            type: 'all',
		            text: 'Tout'
		        }],
		        selected:<?php echo $Zoom?>
			},
			legend: {

				enabled: true,
      			borderColor: 'black',
		      	borderWidth: 1,
		      	backgroundColor: '#FCFFC5',
		      	y: 40,
      			shadow: true
        	},

        	scrollbar: { // scrollbar "stylée" grise
      			barBackgroundColor: 'gray',
		    	barBorderRadius: 7,
		    	barBorderWidth: 0,
      			buttonBackgroundColor: 'gray',
      			buttonBorderWidth: 0,
      			buttonBorderRadius: 7,
      			trackBackgroundColor: 'none',
      			trackBorderWidth: 1,
      			trackBorderRadius: 8,
      			trackBorderColor: '#CCC'
   			 },
			title : {
				text : '<?php echo $graph_digi1_titre;?>' //	text : 'Températures: eau piscine, air piscine, air extérieur, garage'
			},

			series : [{
				name : '<?php echo $graph_digi1_serie1_nom;?>',
				data : <?php echo $data1?>,
				tooltip: {
					valueDecimals: 2
				}
			},{
				name : '<?php echo $graph_digi1_serie2_nom;?>',
				data : <?php echo $data2?>,
				tooltip: {
					valueDecimals: 2
				}
			},{
				name : '<?php echo $graph_digi1_serie3_nom;?>',
				data : <?php echo $data3?>,
				tooltip: {
					valueDecimals: 2
				}
			},{
				name : '<?php echo $graph_digi1_serie4_nom;?>',
				data : <?php echo $data4?>,
				color : '#000000' , // noir
				tooltip: {
					valueDecimals: 2
				}
			}]
		});
});

		</script>


		<script type="text/javascript">
		jQuery(function($){
	$('#datepicker').datepicker({
		dateFormat : 'yy-mm-dd',
		minDate: '<?php echo $datetimedebut?>' ,
		maxDate: '<?php echo $datetimefin?>',
		//setDate:"2013-06-05",
		defaultDate: -30,
		appendText: "(yyyy-mm-dd)    ",
		//closeText: 'Fermer',
		//currentText: "Maintenant",
		prevText: '&#x3c;Préc',
		nextText: 'Suiv&#x3e;',
		monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin',
			'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
		monthNamesShort: ['Jan','Fév','Mar','Avr','Mai','Jun',
			'Jul','Aoû','Sep','Oct','Nov','Déc'],
		dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
		dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
		dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: '',
		numberOfMonths: 1,
		//showButtonPanel: true

	});
});
		</script>

	</head>
	<body>

<form action="digitemp.php" method="post">
Selectionnez la date de d&eacute;but d'affichage<input type="text" id="datepicker" name="date" value=<?php echo "\"$datedebut\"" ?>/>
<?php
$TabZoom[0] = "3j";
$TabZoom[] = "1s";
$TabZoom[] = "2s";
$TabZoom[] = "3s";
$TabZoom[] = "Tout";
echo "&nbsp &nbsp &nbsp - &nbsp &nbsp &nbsp";
ligne_choix ("Zoom", "Zoom", $TabZoom);
?>

<input type="submit" id="form_sendbutton" name="BT_Envoyer" value="Envoyer" />
</form>

<div id="container" style="height: 600px; min-width: 600px"></div>
<br><br><br><br><br>
	</body>
</html>