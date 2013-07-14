<!DOCTYPE HTML>
<head>
<meta charset="iso-8859-1" />
<title>teleinfo</title>
<link href="style_pv.css" rel="stylesheet" type="text/css" />
</head>

<body>
<h1>Téleinfo: production PV et consommation électrique</h1>
<div id="global">
<div id="blocmenu">
<p>Bloc menu</p>
<p>Ernesto</p>
<ul id="menu">
<li><a href="./index.php?action=ev_conso">conso</a></li>
<li><a href="./index.php?action=ev_conso10">conso 10 Jours</a></li>
<li><a href="./index.php?action=ev_prod">prod</a></li>
<li><a href="./index.php?action=ev_prod10">prod 10 jours</a></li>
<li><a href="./index.php?action=ev_digitemp">digitemp</a></li>
<li><a href="./index.php?action=ev_eau">Conso eau</a></li>
<li><a href="/admin/phpMyAdmin">phpMyAdmin</a></li>
<li><a href="#">admin</a></li>
<br>
<li><a href="http://blog.steneor.com">blog</a></li>
<br>
<br>
</ul>
<br>
<p>Patrice</p>
<ul id="menu">
<li><a href="./index.php?action=pp_index">index</a></li>
<li><a href="./index.php?action=pp_conso">conso</a></li>
<li><a href="./index.php?action=pp_prod">prod</a></li>
<li><a href="./index.php?action=pp_digitemp">digitemp</a></li>
<li><a href="./index.php?action=pp_eau">Conso eaudigitemp</a></li>
<li><a href="/admin/phpMyAdmin">phpMyAdmin</a></li>
<li><a href="#">admin</a></li>
<br>
<li><a href="http://mozacvert.perso.sfr.fr/">blog</a></li>
</ul>
</div>
<div id="contenu">
<?
switch ($_GET['action']) {
    case "ev_conso" :
        include 'conso.php';
        break;
    case "ev_conso10" :
        include 'conso10.php';
        break;
    case "ev_prod" :
    	include 'prod.php';
    	break;
    case "ev_prod10" :
    	include 'prod10.php';
    	break;
    case "ev_digitemp" :
    	include 'digitemp.php';
    	break;
    case "ev_eau" :
    	include 'eau.php';
    	break;
    case "ev_phpMyadmin" :
        break;
    case "ev_admin" :
        break;
    case "pp_index" :
?>
	<iframe name="tonNom" src="http://mozacvert.dyndns.org:8080/teleinfo/index.html" width="810" height="1250"></iframe>
    <?
    break;
    case "pp_conso" :
    ?>
	<iframe name="tonNom" src="http://mozacvert.dyndns.org:8080/teleinfo/conso.php" width="810" height="1250"></iframe>
    <?
    break;
    case "pp_prod" :
    ?>
    <iframe name="tonNom" src="http://mozacvert.dyndns.org:8080/teleinfo/prod.php" width="810" height="1250"></iframe>
    <?
        break;
    case "pp_digitemp" :
    ?>
    <iframe name="tonNom" src="http://mozacvert.dyndns.org:8080/teleinfo/digitemp.php" width="810" height="1250"></iframe>
    <?
        break;
    case "pp_eau" :
    ?>
    <iframe name="tonNom" src="http://mozacvert.dyndns.org:8080/teleinfo/eau.php" width="810" height="1250"></iframe>
    <?
        break;
    case "pp_phpMyadmin" :
        break;
    case "pp_admin" :
        break;

    default:
        include 'prod.php';

} // switch



//include 'teleinfov2_prod.php'; ?>
</div>
<p id="pied">Pied de page</p>
</div>
</body>