<?php
include_once 'config.include.php';
setlocale(LC_ALL , "fr_FR" );
date_default_timezone_set("Europe/Paris");

// Adapté du code de Domos.
// cf . http://vesta.homelinux.net/wiki/teleinfo_papp_jpgraph.html

// Connexion MySql et requète.
//$serveur="localhost";
//$login="root";
//$base="domotique";
//$table_prod="teleinfo_prod";
//$pass="azerty";

// prix du kWh :
// prix TTC au 1/01/2012 :
$prixHP = 0.58;
$prixHC = 0.00001;
// Abpnnement pour disjoncteur 45 A
$abo_annuel = -50;

// Base de donnée Téléinfo_prod simplifiée:
/*
Format de la table:
timestamp     rec_date    rec_time     base    ptec
1234998004   2009-02-19   00:00:04   11008467   HP
1234998065   2009-02-19   00:01:05   11008473   HP
1234998124   2009-02-19   00:02:04   11008479   HP
1234998185   2009-02-19   00:03:05   11008484   HP
1234998244   2009-02-19   00:04:04   11008489   HP
1234998304   2009-02-19   00:05:04   11008493   HP
1234998365   2009-02-19   00:06:05   11008498   HP
*/

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta content="no-cache" http-equiv="Pragma">
    <title>graph production électrique V1.2</title>
<!--		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>   -->
<script type="text/javascript" src="./js/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="./js/jquery-ui-1.9.0.custom.min.js"></script>

<script type="text/javascript" src="./js/highcharts.js"></script>
<script type="text/javascript" src="./js/highstock.js"></script>
<!--		<script type="text/javascript" src="./js/themes/gray.js"></script>      -->

<script type='text/javascript'>

var start = <?php echo time()*1000; ?>;

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

setlocale   ( LC_ALL , "fr_FR" );

mysql_connect($serveur, $login, $pass) or die("Erreur de connexion au serveur MySql");
mysql_select_db($base) or die("Erreur de connexion a la base de donnees $base");
mysql_query("SET NAMES 'utf8'");

/*    Graph consomation w des 24 dernières heures + en parrallèle consomation d'Hier    */

$courbe_titre[0]="Production PV";
$courbe_min[0]=5000;
$courbe_max[0]=0;
$courbe_titre[1]="";
$courbe_min[1]=5000;
$courbe_max[1]=0;

$courbe_titre[2]="Intensitée";
$courbe_min[2]=45;
$courbe_max[2]=0;

$periodesecondes = 24*3600 ;              // 24h.
$heurecourante = date('H') ;              // Heure courante.
$timestampheure = mktime($heurecourante+1,0,0,date("m"),date("d"),date("Y"));  // Timestamp courant à heure fixe (mn et s à 0).
$timestampdebut = $timestampheure - $periodesecondes ;        // Recule de 24h.

$timestampdebut2 = $timestampdebut;
$timestampdebut = $timestampdebut - $periodesecondes ;        // Recule de 24h.

$query="SELECT timestamp, rec_date, rec_time, papp
  FROM `$table_prod`
  WHERE timestamp >= $timestampdebut
  ORDER BY timestamp " ;

$result=mysql_query($query) or die ("<b>Erreur</b> dans la requète <b>" . $query . "</b> : "  . mysql_error() . " !<br>");

$nbdata=0;
$nbenreg = mysql_num_rows($result);
$nbenreg--;
$date_deb=0; // date du 1er enregistrement
$date_fin=time();

$array_HP = array();
$array_HC = array();
$array_JPrec = array();
$array_navigator = array();

$row = mysql_fetch_array($result);
$ts = intval($row["timestamp"]);

while ($ts < $timestampdebut2 ){
  $ts = ( $ts + 24*3600 ) * 1000;
  $val = floatval(str_replace(",", ".", $row["papp"]));
  array_push ( $array_JPrec , array($ts, $val ));
  $row = mysql_fetch_array($result);
  $ts = intval($row["timestamp"]);
  $nbenreg--;
}


while ($nbenreg > 0 ){
  if ($date_deb==0) {
    $date_deb = $row["timestamp"];
  }
  $ts = intval($row["timestamp"]) * 1000;
    $val = floatval(str_replace(",", ".", $row["papp"]));
    array_push ( $array_HP , array($ts, $val ));
    array_push ( $array_HC , array($ts, null ));
    array_push ( $array_navigator , array($ts, $val ));
    if ($courbe_max[0]<$val) {$courbe_max[0] = $val; $courbe_maxdate[0] = $ts;};
    if ($courbe_min[0]>$val) {$courbe_min[0] = $val; $courbe_mindate[0] = $ts;};

  if ($courbe_max[2]<$val) {$courbe_max[2] = $val; $courbe_maxdate[2] = $ts;};
  if ($courbe_min[2]>$val) {$courbe_min[2] = $val; $courbe_mindate[2] = $ts;};
  // récupérer prochaine occurence de la table
  $row = mysql_fetch_array($result);
  $nbenreg--;
  $nbdata++;
}
$date_fin = $ts/1000;

if ($courbe_max[1] > $courbe_max[0]) $plotlines_max = $courbe_max[1]; else $plotlines_max = $courbe_max[0];
if ($courbe_min[1] > $courbe_min[0]) $plotlines_min = $courbe_min[0]; else $plotlines_min = $courbe_min[1];

mysql_free_result($result) ;

$ddannee = date("Y",$date_deb);
$ddmois = date("m",$date_deb);
$ddjour = date("d",$date_deb);
$ddheure = date("G",$date_deb); //Heure, au format 24h, sans les zéros initiaux
$ddminute = date("i",$date_deb);

$ddannee_fin = date("Y",$date_fin);
$ddmois_fin = date("m",$date_fin);
$ddjour_fin = date("d",$date_fin);
$ddheure_fin = date("G",$date_fin); //Heure, au format 24h, sans les zéros initiaux
$ddminute_fin = date("i",$date_fin);

$datetext = "$ddjour/$ddmois  $ddheure:$ddminute au $ddjour_fin/$ddmois_fin  $ddheure_fin:$ddminute_fin";
?>

<script type="text/javascript">

$(function() {


// Creation du graphique journalier avec production d'hier
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
        count : 1,
        text : '1h'
      },{
        type : 'hour',
        count : 3,
        text : '3h'
      },{
        type : 'hour',
        count : 6,
        text : '6h'
      },{
        type : 'hour',
        count : 9,
        text : '9h'
      },{
        type : 'hour',
        count : 12,
        text : '12h'
      },{
        type : 'all',
        count : 1,
        text : 'All'
      }],
      selected : 5,
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
        name : '<?php echo $courbe_titre[0]." / min ".$courbe_min[0]." max ".$courbe_max[0]; ?>',
        data : array_HP,
        id: 'HP',
        type : 'areaspline',
        threshold : null,
        tooltip : {
            yDecimals : 0
        }
    }, {
      data: array_JPrec,
      name : 'Hier',
      type: 'spline',
      width : 1,
      shape: 'squarepin'
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


var array_HP = <?php echo json_encode($array_HP); ?>;
var array_HC = <?php echo json_encode($array_HC); ?>;
var array_JPrec = <?php echo json_encode($array_JPrec); ?>;
var array_navigator = <?php echo json_encode($array_navigator); ?>;


</script>

<?php

/*    Graph cout sur période [8jours|8semaines|8mois|1an]    */

$periode = $_GET['periode'] ;

if (! $periode) { $periode = "8jours" ; } ;
switch ($periode) {
  case "8jours":
    $nbjours = 7 ;                // nb jours.
    $xlabel = "8 jours" ;
    $periodesecondes = $nbjours*24*3600 ;          // Periode en secondes.
    $timestampheure = gmmktime(0,0,0,date("m"),date("d"),date("Y"));    // Timestamp courant.
    $timestampdebut = $timestampheure - $periodesecondes ;      // Recule de $periodesecondes.
    $dateformatsql = "%a %e" ;
    $abonnement = $abo_annuel / 365;
    break;
  case "8semaines":
    $timestampdebut = gmmktime(0,0,0, date("m")-2, date("d"), date("Y"));
    $nbjour=1 ;
    while ( date("w", $timestampdebut) != 1 )  // Avance d'un jour tant que celui-ci n'est pas un lundi.
    {
      $timestampdebut = gmmktime(0,0,0, date("m")-2, date("d")+$nbjour, date("Y"));
      $nbjour++ ;
    }
    $xlabel = "8 semaines" ;
    $dateformatsql = "sem %v" ;
    $abonnement = $abo_annuel / 52;
    break;
  case "8mois":
    $timestampdebut = gmmktime(0,0,0, date("m")-7, 1, date("Y"));
    $xlabel = "8 mois" ;
    $dateformatsql = "%b" ;
    $abonnement = $abo_annuel / 12;
    break;
  case "1an":
    $timestampdebut = gmmktime(0,0,0, date("m")-11, 1, date("Y"));
    $xlabel = "1 an" ;
    $dateformatsql = "%b" ;
    $abonnement = $abo_annuel / 12;
    break;
  default:
    die("Periode erronée, valeurs possibles: [8jours|8semaines|8mois|1an] !");
    break;
}

$query="SET lc_time_names = 'fr_FR'" ;  // Pour afficher date en français dans MySql.
mysql_query($query) ;
$query="SELECT rec_date, DATE_FORMAT(rec_date, '$dateformatsql') AS 'periode' ,
  ROUND( ((MAX(`base`) - MIN(`base`)) / 1000) ,1 ), MAX(`base`)
  FROM `$table_prod`
  WHERE timestamp > '$timestampdebut'
  GROUP BY periode
  ORDER BY rec_date" ;

$result=mysql_query($query) or die ("<b>Erreur</b> dans la requète <b>" . $query . "</b> : "  . mysql_error() . " !<br>");
$num_rows = mysql_num_rows($result) ;
$no = 0 ;
while ($row = mysql_fetch_array($result))
{
  $date[$no] = $row["rec_date"] ;
  $timestp[$no] = $row["periode"] ;
  $kwhhp[$no]=floatval(str_replace(",", ".", $row[2]));
  $index_base[$no]=intval(str_replace(",", ".", ($row[3]/1000)));
  $kwhhc[$no]=floatval(str_replace(",", ".", $row[2]));

  $no++ ;
}

//print_r($date);
//echo("<br>");
//print_r($timestp);
//echo("<br>");
//print_r($kwhhp);
//echo("<br>");
//print_r($index_base);
//echo("<br>");


$date_digits_dernier_releve=explode("-", $date[count($date) -1]) ;
$date_dernier_releve =  Date('d/m/Y', gmmktime(0,0,0, $date_digits_dernier_releve[1] ,$date_digits_dernier_releve[2], $date_digits_dernier_releve[0])) ;

mysql_free_result($result) ;

mysql_close() ;
?>

    <script type="text/javascript">
<?php
// cf. http://www.phpcs.com/codes/PHP-TO-JS-CONVERSION-VARIABLE-PHP-VERS-JAVASCRIPT_13232.aspx
function php2js ($var) {
    if (is_array($var)) {
        $res = "[";
        $array = array();
        foreach ($var as $a_var) {
            $array[] = php2js($a_var);
        }
        return "[" . join(",", $array) . "]";
    }
    elseif (is_bool($var)) {
        return $var ? "true" : "false";
    }
    elseif (is_int($var) || is_integer($var) || is_double($var) || is_float($var)) {
        return $var;
    }
    elseif ($var=="null") {
        return "" . addslashes(stripslashes($var)) . "";
    }if (is_string($var)) {
        return "\"" . addslashes(stripslashes($var)) . "\"";
    }
    // autres cas: objets, on ne les gère pas
    return FALSE;
}

$ddannee = date("Y",$date_deb);
$ddmois = date("m",$date_deb);
$ddjour = date("d",$date_deb);
$ddheure = date("G",$date_deb); //Heure, au format 24h, sans les zéros initiaux
$ddminute = date("i",$date_deb);

$date_deb_UTC=$date_deb*1000;

$datetext = "$ddjour/$ddmois/$ddannee  $ddheure:$ddminute";
$ddmois=$ddmois-1; // nécessaire pour Date.UTC() en javascript qui a le mois de 0 à 11 !!!

$mnt_kwhhp = 0;
$mnt_kwhhc = 0;
$mnt_abonnement = 0;
$i = 0;
while ($i < count($kwhhp))
{
  $mnt_kwhhp += $kwhhp[$i] * $prixHP;
  $mnt_kwhhc += $kwhhc[$i] * $prixHC;
  $mnt_abonnement += $abonnement;
  $i++ ;
}

$mnt_total = $mnt_abonnement + $mnt_kwhhp + $mnt_kwhhc;

?>

var datakwhhp = <?php echo php2js($kwhhp); ?>;
var datakwhhc = <?php echo php2js($kwhhc); ?>;
var datatimestp = <?php echo php2js($timestp); ?>;
var dataindex_base = <?php echo php2js($index_base); ?>;

var prixHP = <?php echo $prixHP; ?>;
var prixHC = <?php echo $prixHC; ?>;
var abonnement = <?php echo $abonnement; ?>;

var totalHP = 0;
var totalHC = 0;
var totalprix = 0;

// Création graphique production PV sur 8 jours 8 semaines 8 mois 1 ans
var chart_production_PV;
$(document).ready(function() {
  chart_elec2 = new Highcharts.Chart({
    chart: {
      renderTo: 'container2',
      defaultSeriesType: 'column',
      ignoreHiddenSeries: false,
      borderColor: '#EBBA95',
      borderWidth: 2,
      borderRadius: 10
    },
    title: {
      text: 'Production PV sur <?php echo $xlabel;?>'
    },
    xAxis: [{
       categories: datatimestp
    }],
    yAxis: {
      title: {
        text: 'kWh'
      },
      min: 0,
      minorGridLineWidth: 0,
      labels: { formatter: function() { return this.value +' kWh' } }
    },
    tooltip: {
      formatter: function() {
        totalHP=prixHP*((this.series.name == 'Heures Pleines')? this.y :this.point.stackTotal-this.y);
        totalHC=prixHC*((this.series.name == 'Heures Creuses')? this.y :this.point.stackTotal-this.y);
        totalprix=Highcharts.numberFormat(( totalHP + totalHC + abonnement ),2);
        tooltip = '<b> '+ this.x +' <b><br /><b>'+ this.series.name +' '+ Highcharts.numberFormat(this.y, 2) +' kWh<b><br />';
        tooltip += 'HP : '+ Highcharts.numberFormat(totalHP,2) + ' Euro<br />';
        tooltip += 'Abonnement sur la période : '+ Highcharts.numberFormat(abonnement,2) +' Euro<br />';
        tooltip += '<b> Total: '+ totalprix +' Euro<b>';
        return tooltip;
      }
    },
    plotOptions: {
      column: {
        stacking: 'normal',
      }
    },
    series: [{
      name : 'Heures Pleines',
      data : datakwhhp ,
      dataLabels: {
        enabled: true,
        color: '#FFFFFF',
        y: 13,
        formatter: function() {
          return this.y;
        },
        style: {
          font: 'normal 13px Verdana, sans-serif'
        }
      },
      type: 'column'
    }/*,
    {
		name : 'index_base',
		data : dataindex_base,
		visible : false
		/*tooltip: {
					valueDecimals: 2
				}*/
//     }
	],
    navigation: {
      menuItemStyle: {
        fontSize: '10px'
      }
    }
  });
});




// Création graphique Index BASE PV sur 8 jours 8 semaines 8 mois 1 ans
var chart_Index_BASE;
    $(document).ready(function() {
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'container3',
                borderColor: '#EBBA95',
	    		borderWidth: 2,
    	  		borderRadius: 10,
				type: 'column'
            },
            title: {
                text: 'Index BASE PV sur <?php echo $xlabel;?>'
            },
            xAxis: {
                categories: datatimestp,
                labels: {
                    //rotation: -45,
                    align: 'center',
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: dataindex_base[0]- 50,
                title: {
                    text: 'kWh'
                },
                labels: { formatter: function() { return this.value +' kWh' }}
            },
            legend: {
                enabled: false
            },
            tooltip: {
                formatter: function() {
                    return '<b>'+ this.x +'</b><br/>'+
                        '<b>Index BASE: '+ Highcharts.numberFormat(this.y,0)+' kWh</b><br/>'+
                        '<b>Total:'+Highcharts.numberFormat(this.y  * prixHP) +' Euro<br/>';
                }
            },
                series: [{
                name: 'Population',
                data: dataindex_base,
                dataLabels: {
                    enabled: true,
                    rotation: -90,
                    color: '#FFFFFF',
                    align: 'center',
                    x: 3,
                    y: 30,
                    formatter: function() {
                        return this.y;
                    },
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            }]
        });
    });
</script>

<style>
input[type="submit"]{
width:100px;
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
      <input type="submit" value="8jours" name="periode">
      <input type="submit" value="8semaines" name="periode">
      <input type="submit" value="8mois" name="periode">
      <input type="submit" value="1an" name="periode">
    </form>
    <br />
    <div style="text-align: center;" ><?php echo "Coût sur la période ".round($mnt_total,2)." Euro<br />( Abonnement : ".round($mnt_abonnement,2)." + HP : ".round($mnt_kwhhp,2)." )";?></div>
    <br />
    <div id="container2" style="width: 800px; height: 300px; margin: 0 auto"></div>

    <br />
    <div id="container3" style="width: 800px; height: 300px; margin: 0 auto"></div>

  </body>
</html>
