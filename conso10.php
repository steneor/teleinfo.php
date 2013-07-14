<?php
include_once 'config.include.php';
setlocale(LC_ALL , "fr_FR");
date_default_timezone_set("Europe/Paris") ;
// Adapté du code de Domos.    cf . http://vesta.homelinux.net/wiki/teleinfo_papp_jpgraph.html
// Modif http://enersol.free.fr/teleinfo
// Connexion MySql et requète.
// $serveur="localhost";
// $login="root";
// $base="domotique";
// $table_conso="teleinfo_conso";
// $pass="azerty";
// prix du kWh :
// prix TTC au 1/01/2012 :
$prixHP = 0.1312;
$prixHC = 0.0895;
// Abpnnement pour disjoncteur 45 A
$abo_annuel = 112.87;
// Base de donnée Téléinfo:
/*
   Format de la table:
   timestamp        rec_date       rec_time       hchp            hchc      ptec  papp
   1234998004   2009-02-19   00:00:04   11008467   10490214   HP   400
   1234998065   2009-02-19   00:01:05   11008473   10490214   HP   400
   1234998124   2009-02-19   00:02:04   11008479   10490214   HP   390
   1234998185   2009-02-19   00:03:05   11008484   10490214   HP   330
   1234998244   2009-02-19   00:04:04   11008489   10490214   HP   330
   1234998304   2009-02-19   00:05:04   11008493   10490214   HP   330
   1234998365   2009-02-19   00:06:05   11008498   10490214   HP   320
*/

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta content="no-cache" http-equiv="Pragma">
    <title>graph conso électrique V1.3</title>

<!--		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>   -->
<script type="text/javascript" src="./js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="./js/jquery-ui-1.9.0.custom.min.js"></script>
<!--		<script type="text/javascript" src="./js/themes/gray.js"></script>      -->

<!-- <script type="text/javascript" src="./js/highcharts.js"></script> -->
<script type="text/javascript" src="./js/highstock.js"></script>

<script type='text/javascript'>

var start = <?php echo time() * 1000; ?>;

jQuery(function($) {

  Highcharts.setOptions({
    lang: {
      months: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
      weekdays: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
      decimalPoint: ',',
      thousandsSep: '.',
      rangeSelectorFrom: 'Du',
      rangeSelectorTo: 'au'
    },
    legend: {
      enabled: false
    },
    global: {
      useUTC: false
    }
  });
});
</script>

<?php

setlocale (LC_ALL , "fr_FR");

mysql_connect($serveur, $login, $pass) or die("Erreur de connexion au serveur MySql");
mysql_select_db($base) or die("Erreur de connexion a la base de donnees $base");
mysql_query("SET NAMES 'utf8'");

/*    Graph consommation HC + HP sur n jours    */

$courbe_titre[0] = "Heure Pleines";
$courbe_min[0] = 5000;
$courbe_max[0] = 0;
$courbe_titre[1] = "Heure Creuses";
$courbe_min[1] = 5000;
$courbe_max[1] = 0;

$periode = "10" ; // par défaut affiche 10 jours
if (isset($_GET['periode'])) {
	$periode = $_GET['periode'] ;
}
$nbjours = $periode ;
$periodesecondes = $nbjours * 24 * 3600 ; // Periode en secondes.

$heurecourante = date('H') + 1 ; // Heure courante + 1. exemple: s'il est 10h38 cela donne 10 + 1 = 11
$timestampheure = mktime($heurecourante , 0, 0, date("m"), date("d"), date("Y")); // Timestamp courant à heure fixe (mn et s à 0).
$timestampdebut = $timestampheure - $periodesecondes ; // Recule de periodesecondes pour obtenir date de début

$query = "SELECT timestamp, hchp, hchc, ptec
  FROM `$table_conso`
  WHERE timestamp >= $timestampdebut
  ORDER BY timestamp " ;

$result = mysql_query($query) or die ("<b>Erreur</b> dans la requète <b>" . $query . "</b> : " . mysql_error() . " !<br>");

$nbenreg = mysql_num_rows($result);
$nbenreg--;
$date_deb = 0; // date du 1er enregistrement
	
	echo $nbenreg;

$array_HP = array();
$array_HC = array();
$array_navigator = array();

$row = mysql_fetch_array($result);
$date_deb = intval($row["timestamp"]);

while ($nbenreg > 0) {
	$ts1 = intval($row["timestamp"]);
	$hc1 = floatval($row["hchc"]);
	$hp1 = floatval($row["hchp"]);
	$pt1 = $row["ptec"] ;
	$row = mysql_fetch_array($result); // récupérer prochaine occurence de la table
	$ts2 = intval($row["timestamp"]);
	$hc2 = floatval($row["hchc"]);
	$hp2 = floatval($row["hchp"]);
	$pt2 = $row["ptec"] ;
	
	
//	echo ("nbereng:$nbenreg  ts1:$ts1 hc1:$hc1 hp1:$hp1 ts2:$ts2 hc2:$hc2 hp2:$hp2 ptec1:$pt1 ptec2:$pt2 max:$courbe_max[0] min:$courbe_min[0]<br> ");

	$delta_time = $ts2 - $ts1;
	$delta_base = ($hp2 - $hp1) + ($hc2 - $hc1);
	$delta_papp = intval (($delta_base * 3600) / $delta_time);
	$ts2 = $ts2 * 1000;

	array_push ($array_navigator , array($ts2, $delta_papp));

	if ($pt2 == "HP") { // Test si ptec = heures pleines.
			array_push ($array_HP , array($ts2, $delta_papp));
			
			if ($pt1 == "HC") { // Test si ptec précédent = heures creuses.
				array_push ($array_HC , array($ts2, $delta_papp));
				}else{
					array_push ($array_HC , array($ts2, null));
				}
			if ($courbe_max[0] < $delta_papp) {
				$courbe_max[0] = $delta_papp;
				$courbe_maxdate[0] = $ts2;
			} ;
			if ($courbe_min[0] > $delta_papp) {
				$courbe_min[0] = $delta_papp;
				$courbe_mindate[0] = $ts;
			} ;
	}else {
			array_push ($array_HC , array($ts2, $delta_papp));

			if ($pt1 == "HP") { // Test si ptec précédent = heures pleines.
				array_push ($array_HP , array($ts2, $delta_papp));
				}else{
					array_push ($array_HP , array($ts2, null));
				}
			if ($courbe_max[1] < $delta_papp) {
				$courbe_max[1] = $delta_papp;
				$courbe_maxdate[1] = $ts2;
			} ;
			if ($courbe_min[1] > $delta_papp) {
				$courbe_min[1] = $delta_papp;
				$courbe_mindate[1] = $ts2;
			} ;
	}
	$nbenreg--;
}
$date_fin = $ts2 / 1000;

if ($courbe_max[1] > $courbe_max[0]) $plotlines_max = $courbe_max[1];
else $plotlines_max = $courbe_max[0];
if ($courbe_min[1] > $courbe_min[0]) $plotlines_min = $courbe_min[0];
else $plotlines_min = $courbe_min[1];

mysql_free_result($result) ;

$ddannee = date("Y", $date_deb);
$ddmois = date("m", $date_deb);
$ddjour = date("d", $date_deb);
$ddheure = date("G", $date_deb); //Heure, au format 24h, sans les zéros initiaux
$ddminute = date("i", $date_deb);

$ddannee_fin = date("Y", $date_fin);
$ddmois_fin = date("m", $date_fin);
$ddjour_fin = date("d", $date_fin);
$ddheure_fin = date("G", $date_fin); //Heure, au format 24h, sans les zéros initiaux
$ddminute_fin = date("i", $date_fin);
// $datetext = "$ddjour/$ddmois/$ddannee  $ddheure:$ddminute au $ddjour_fin/$ddmois_fin/$ddannee_fin  $ddheure_fin:$ddminute_fin";
$datetext = "$ddjour/$ddmois/$ddannee  $ddheure:$ddminute au $ddjour_fin/$ddmois_fin/$ddannee_fin  $ddheure_fin:$ddminute_fin";
// print_r ($array_HP);
// echo json_encode($array_HP);
?>

<script type="text/javascript">

var array_HP = <?php echo json_encode($array_HP); ?>;
var array_HC = <?php echo json_encode($array_HC); ?>;
var array_navigator = <?php echo json_encode($array_navigator); ?>;

$(function() {
  // Create the chart
  window.chart = new Highcharts.StockChart({
    chart : {
      renderTo : 'container',
      events: {
        load: function(chart) {
          this.setTitle(null, {
            text: 'Construit en '+ (new Date() - start) +'ms'
          });
        }
      },
      borderColor: '#EBBA95',
      borderWidth: 2,
      borderRadius: 10,
      ignoreHiddenSeries: false
    },
    subtitle: {
      text: 'Construit en...'
    },
    rangeSelector : {
      buttons : [{
        type : 'hour',
        count : 12,
        text : '12h'
      },{
        type : 'day',
        count : 1,
        text : '1j'
      },{
        type : 'day',
        count : 2,
        text : '2j'
      },{
        type : 'day',
        count : 7,
        text : '7j'
      },{
        type : 'all',
        count : 1,
        text : 'All'
      }],
      selected : 4,
      inputEnabled : false
    },
    title : {
        text : '<?php echo "$graph_conso1_titre $datetext";?>'
    },
    xAxis: {
      type: 'datetime',
       dateTimeLabelFormats: {
          hour: '%H:%M',
        	day: '%H:%M',
        	week: '%H:%M',
          month: '%H:%M'
       }
    },
    yAxis: [{
      labels: {
        formatter: function() {
           return this.value +' w';
        }
      },
      title: {
        text: 'Watt'
      },
      lineWidth: 2,
      showLastLabel: true,
      min: 0,
      alternateGridColor: '#FDFFD5',
      minorGridLineWidth: 0,
      plotLines : [{ // lignes min et max
        value : <?php echo $plotlines_min; ?>,
        color : 'green',
        dashStyle : 'shortdash',
        width : 2,
        label : {
          text : 'minimum <?php echo $plotlines_min; ?>w'
        }
      }, {
        value : <?php echo $plotlines_max; ?>,
        color : 'red',
        dashStyle : 'shortdash',
        width : 2,
        label : {
          text : 'maximum <?php echo $plotlines_max; ?>w'
        }
      }]
    }],

    series : [{
        name : '<?php echo $courbe_titre[0] . " / min " . $courbe_min[0] . " max " . $courbe_max[0]; ?>',
        data : array_HP,
        id: 'HP',
        type : 'areaspline',
        threshold : null,
        tooltip : {
            yDecimals : 0
        }
    }, {
        name : '<?php echo $courbe_titre[1] . " / min " . $courbe_min[1] . " max " . $courbe_max[1]; ?>',
        data : array_HC,
        type : 'areaspline',
        threshold : null,
        tooltip : {
            yDecimals : 0
        }
    }],
    legend: {
      enabled: true,
      borderColor: 'black',
      borderWidth: 1,
      shadow: true
    },
    navigator: {
      baseSeries: 2,
      top: 390,
      menuItemStyle: {
        fontSize: '10px'
      },
      series: {
        name: 'navigator',
        data: array_navigator
      }
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
  });
});

</script>

<style>
input[type="submit"]{
width:75px;
height:30px;
margin-left:20px;
font-size:1em;
font-weight:bold;
border:none;
color:#cecece;
text-shadow:0px -1px 0px #000;
background:#1f2026;
background:-moz-linear-gradient(top,#1f2026,#15161a);
background:-webkit-gradient(linear,left top,left bottom,from(#1f2026),to(#15161a));
-webkit-border-radius:5px;
   -moz-border-radius:5px;
        border-radius:5px;
-webkit-box-shadow:0px 0px 1px #000;
   -moz-box-shadow:0px 0px 1px #000;
        box-shadow:0px 0px 1px #000;
}
input[type="submit"]:hover{
background:#343640;
background:-moz-linear-gradient(top,#343640,#15161a);
background:-webkit-gradient(linear,left top,left bottom,from(#343640),to(#15161a));
}
</style>

  </head>
  <body>

    <div id="container" style="width: 800px; height: 500px; margin: 0 auto"></div>
    <br /><br />
    <form method="GET" action="<?php echo $_SERVER['PHP_SELF'];?>" style="text-align: center;" >
      <input type="submit" value="1" name="periode">
      <input type="submit" value="2" name="periode">
      <input type="submit" value="5" name="periode">
      <input type="submit" value="10" name="periode">
      <input type="submit" value="20" name="periode">
      <input type="submit" value="40" name="periode">
      <input type="submit" value="80" name="periode">
      <input type="submit" value="160" name="periode">
    </form>
<!--
    <br />
    <div style="text-align: center;" ><?php echo "Coût sur la période " . round($mnt_total, 2) . " Euro<br />( Abonnement : " . round($mnt_abonnement, 2) . " + HP : " . round($mnt_kwhhp, 2) . " + HC : " . round($mnt_kwhhc, 2) . " )";?></div>
    <br />
-->
	</body>
</html>
