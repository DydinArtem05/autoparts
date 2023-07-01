<?php
// Version
define('VERSION', '3.0.2.0');

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}

// vQmod
   require_once('./vqmod/vqmod.php');
   VQMod::bootup();
   
   // VQMODDED Startup
   require_once(VQMod::modCheck(DIR_SYSTEM . 'startup.php'));

   // vQmod
   require_once('./vqmod/vqmod.php');
   VQMod::bootup();
   
   // VQMODDED Startup
   require_once(VQMod::modCheck(DIR_SYSTEM . 'startup.php'));

start('catalog');