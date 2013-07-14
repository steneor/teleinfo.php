<?php
include_once 'config.include.php';
setlocale(LC_ALL , "fr_FR");
date_default_timezone_set("Europe/Paris");
// Adapté du code de Domos.
// cf . http://vesta.homelinux.net/wiki/teleinfo_papp_jpgraph.html
// Connexion MySql et requète.
// $serveur="localhost";
// $login="root";
// $base="domotique";
// $table_prod="teleinfo_prod";
// $pass="azerty";
// prix du kWh :
// prix TTC au 1/01/2012 :
$prixHP = 0.58;
// Abpnnement pour compteur disjoncteur location ERDF
$abo_annuel = - 50;
// Base de donnée Téléinfo_prod simplifiée:
/*
Format de la table:
timestamp         rec_date     rec_time      base     papp
1338736023, 2012-06-03, 17:07:03 ,15468132, 390,
1338736083, 2012-06-03, 17:08:03, 15468139, 440,
1338736142, 2012-06-03, 17:09:02, 15468146, 470,
1338736202, 2012-06-03, 17:10:02, 15468154, 500,
1338736263, 2012-06-03, 17:11:03, 15468162, 530;
*/

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta content="no-cache" http-equiv="Pragma">
    <title>graph production électrique V1.3</title>

<!--		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>   -->
<script type="text/javascript" src="./js/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="./js/jquery-ui-1.9.0.custom.min.js"></script>
<link rel="stylesheet" href="./js/css/ui-lightness/jquery-ui-1.9.0.custom.min.css">
<!--		<script type="text/javascript" src="./js/themes/gray.js"></script>      -->

<script type="text/javascript" src="./js/highcharts.js"></script>
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

/*    Graph production PV*/

$courbe_titre = "Production_PV";
$plotlines_min = 5000;
$plotlines_max = 0;

$periode = "10" ;
if (isset($_GET['periode'])) {
    $periode = $_GET['periode'] ;
}

$periodesecondes = $periode * 24 * 3600 ; // 10*24h = 10jours.
$heurecourante = date('H') ; // Heure courante.
$timestampheure = mktime($heurecourante + 1, 0, 0, date("m"), date("d"), date("Y")); // Timestamp courant à heure fixe (mn et s à 0).
$timestampdebut = $timestampheure - $periodesecondes ; // Recule de xx jours

$query = "SELECT timestamp, base
  FROM `$table_prod`
  WHERE timestamp >= $timestampdebut
  ORDER BY timestamp " ;

$result = mysql_query($query) or die ("<b>Erreur</b> dans la requète <b>" . $query . "</b> : " . mysql_error() . " !<br>");

$nbenreg = mysql_num_rows($result);
$nbenreg--;
$date_deb = $row["timestamp"]; // date du 1er enregistrement
$date_fin = time();

$array_HP = array();

$row = mysql_fetch_array($result);
$date_deb = $row["timestamp"];

while ($nbenreg > 0) {
    $ts1 = intval($row["timestamp"]) * 1000;
    $val1 = floatval($row["base"]);
    $row = mysql_fetch_array($result);
    $ts2 = intval($row["timestamp"]) * 1000;
    $val2 = floatval($row["base"]);

    $delta_time = $ts2 - $ts1;
    $delta_base = $val2 - $val1;
    $delta_papp = intval (($delta_base * 3600 * 1000) / $delta_time);

    array_push ($array_HP , array($ts2, $delta_papp));

    if ($plotlines_max < $delta_papp) {
        $plotlines_max = $delta_papp;
    } ;
    if ($plotlines_min > $delta_papp) {
        $plotlines_min = $delta_papp;
    } ;

    $nbenreg--;
}
$date_fin = $ts2 / 1000;

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

$datetext = "$ddjour/$ddmois/$ddannee  $ddheure:$ddminute au $ddjour_fin/$ddmois_fin/$ddannee_fin  $ddheure_fin:$ddminute_fin";

?>

<script type="text/javascript">

$(function() {


// Creation du graphique sur plusieurs jours
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
        text : '<?php echo "$graph_prod1_titre $datetext";?>'
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
        name : '<?php echo $courbe_titre . " / min: " . $plotlines_min . " max: " . $plotlines_max; ?>',
        data : array_HP,
        id: 'HP',
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
      },/*
      series: {
        name: 'navigator',
        data: array_navigator
      }*/
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

var array_HP = <?php echo json_encode($array_HP); ?>;

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
    <br />

  </body>
</html>
