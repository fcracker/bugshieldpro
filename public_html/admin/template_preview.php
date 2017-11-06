<?php
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/mcrypt.class.php");
include_once("../lib/cookie.inc.php");
include_once("../lib/menu.class.php");
include_once("../lib/pagerenderer.class.php");
include_once("../lib/templatemanager.class.php");

$tm = new TemplateManager();
$tmpl = $tm->getActivatedTemplate();
$pr = new PageRenderer($tmpl["name"]);

$pr->setTitle("Preview");
$pr->setContent($_POST["content"] ? $_POST["content"] : "");
$pr->render();
$pr->display();
?>