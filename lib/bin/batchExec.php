<?php
define("WEBEDIT_HOME", $_SERVER['argv'][1]);
require_once WEBEDIT_HOME . "/framework/Framework.php";

$tm = f_persistentdocument_TransactionManager::getInstance();
$rc = RequestContext::getInstance();
$is = indexer_IndexService::getInstance();
$is->setAutocommit(false);
try
{
	$updatedDocumentCount = 0;
	$tm->beginTransaction();
	for ($i = 2; $i < (int)$_SERVER['argc']; $i++)
	{
		$documentIdAndLang = $_SERVER['argv'][$i];
		list($id, $lang) = explode("/", $documentIdAndLang);
		try
		{
			$rc->beginI18nWork($lang);
			$document = DocumentHelper::getDocumentInstance($id);
			$originalStatus = $document->getPublicationStatus();
			$ds = $document->getDocumentService();
			$ds->publishDocument($document);
			$finalStatus = $document->getPublicationStatus();
			if ($finalStatus != $originalStatus)
			{
				$updatedDocumentCount++;	
				echo "$documentIdAndLang has been updated (original status: $originalStatus, new status: $finalStatus)\n";
			}
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
	}
	$tm->commit();
	echo "$updatedDocumentCount documents have been updated\n";
}
catch (Exception $e)
{
	$tm->rollBack($e);
	echo "An error has occured, so rollback!\n";
}
$is->commit();