<?php
include_once 'config.include.php';
setlocale(LC_ALL , "fr_FR");
date_default_timezone_set("Europe/Paris");
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
mysql_connect($serveur, $login, $pass) or die("Erreur de connexion au serveur MySql");
mysql_select_db($base) or die("Erreur de connexion a la base de donnees $base");
mysql_query("SET NAMES 'utf8'");

$query = "SELECT annee,m3
  FROM `$table_eau`
  ORDER BY annee " ;

$result = mysql_query($query) or die ("<b>Erreur</b> dans la requète <b>" . $query . "</b> : " . mysql_error() . " !<br>");

$i = 0;
while ($row = mysql_fetch_assoc($result)) {
    $annee = $row['annee'];
    $m3 = $row['m3'];

    $tab_data[$i]['x'] = $annee; // tableau data avec annee et m3
    $tab_data[$i]['y'] = $m3;
    $tab_annee[$i] = $annee; // tableau tab_annee avec les années
    $i++;
}
// 2) Dans votre modèle, il vous suffit de rejoindre le tableau PHP
// avec un séparateur virgule pour en faire un tableau de JavaScript
// que Highcharts peut lire.
// echo $data1; => [[1.338998402E+12,26.69],[1.339002001E+12,26.69]]
// $tab_data = php2js($d1);
// echo $tab_data;
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<title>conso eau V0.0</title>
		<!--		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>   -->
		<script type="text/javascript" src="./js/jquery-1.8.2.min.js"></script>
		<script src="./js/highstock.js"></script>
		<script src="./js/modules/exporting.js"></script>
		<!--		<script type="text/javascript" src="./js/themes/gray.js"></script>      -->

		<script type="text/javascript">

$(function () {
	var tab_data = <?php echo php2js($tab_data); ?>;
	var tab_annee = <?php echo php2js($tab_annee); ?>;

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

    var chart;
    $(document).ready(function() {
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'container',
                type: 'column',
                borderColor: '#EBBA95',
                borderWidth: 2,
      					borderRadius: 10
            },
            title: {
				text : '<?php echo $graph_eau_titre;?>'
            },
            subtitle: {
                text: 'Relevé à partir des factures'
            },
            xAxis: {
                categories: [tab_annee
                ]
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Consommation eau  (m3)'
                }
            },
            legend: {
                layout: 'vertical',
                backgroundColor: '#FFFFFF',
                align: 'left',
                verticalAlign: 'top',
                x: 100,
                y: 70,
                floating: true,
                shadow: true
            },

            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0
                }
            },
                series: [{
                name: 'eau',
                data: tab_data,
                dataLabels: {
       						 enabled: true,
        					 color: '#FFFFFF',
        					 y: 30
        					},
            }]
        });
    });

});

		</script>
	</head>
	<body>
		<div id="container" style="width: 800px; height: 500px; margin: 0 auto"></div>
	</body>
</html>
