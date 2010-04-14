<?php
define("WEBEDIT_HOME", $_SERVER['argv'][1]);
require_once WEBEDIT_HOME . "/framework/Framework.php";

$sourceRoot = $_SERVER['argv'][2];
$sourceDB = $_SERVER['argv'][3];
$date = $_SERVER['argv'][4];
$fakeMailReceiver = $_SERVER['argv'][5];
if ($date && $fakeMailReceiver)
{
	$ms = preview_ModuleService::getInstance();
	
	// Get the database.
	$ms->loadSourceDatabase($sourceDB);
	
	// Set fakemail receiver.
	$ms->setFakeMailReceiver($fakeMailReceiver);
	
	// Shift dates.
	$ms->goToDate($date);
		
	// Refresh document statuses.
	$ms->rePublishDocuments();
	
	// Deactivate backend users.
	$ms->deactivateBackendUsers();
	
	// Make links to media.
	$ms->makeMediaSnapshot(f_util_FileUtils::buildAbsolutePath($sourceRoot, 'media'), f_util_FileUtils::buildAbsolutePath(WEBEDIT_HOME, 'media'));
	
	// Clear caches.
	$ms->clearCaches();
		
	echo "Preview generated successfully.\n";
}		
else 
{
	echo "There is no date or fakemail receiver. Preview aborted.\n";
	exit;
}