#!/usr/bin/env php
<?php
// Test the web search interface
$_POST['action'] = 'search';
$_POST['term'] = '5007L';

require_once 'catalog_direct.php';
?>