<?php
/**
 * Variables pour  conso.php et prod.php et digitemp.php et eau.php
 *
 */
/** Variables pour la base de données + tables + login **/
$serveur="localhost";
$login="xxxxxxxx";
$base="domotique";
$table_conso="teleinfo_conso";
$table_prod="teleinfo_prod";
$table_digi="ev_digitemp";
$table_eau="eau";
$pass="zzzzzzzzz";


/** Variables pour les graphiques production, (prod.php)**/
$graph_prod1_titre="Graph production PV Ernesto du ";

/** Variables pour les graphiques consommation, (conso.php)**/
$graph_conso1_titre="Graph consommation Ernesto du ";

/** Variables pour les graphiques digitemp, (digitemp.php)**/
// Titre du graphique
$graph_digi1_titre="Températures: eau piscine, air piscine, air extérieur, garage";
// Nom de la serie1
$graph_digi1_serie1_nom ="Capteur 1: T° eau piscine ";
// Nom de la serie2
$graph_digi1_serie2_nom ="Capteur 2: T° air piscine ";
// Nom de la serie3
$graph_digi1_serie3_nom ="Capteur 3: T° air extérieur ";
// Nom de la serie4
$graph_digi1_serie4_nom ="Capteur 4: T° garage ";

/** Variables pour le graphique consommation eau , (eau.php)**/
$graph_eau_titre = "Consommation eau Ernesto";
?>
