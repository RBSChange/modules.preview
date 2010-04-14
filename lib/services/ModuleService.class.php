<?php
/**
 * @package modules.preview.lib.services
 */
class preview_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var preview_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return preview_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return void
	 */
	public function runPreviewGeneration()
	{
		$previewChangePath = Framework::getConfiguration('modules/preview/previewChangePath', false);
		Framework::info(__METHOD__ . ' modules/preview/previewChangePath: ' . $previewChangePath);
		if ($previewChangePath !== null)
		{
			$preview = preview_PreviewService::getInstance()->getPendingPreview();
			if ($preview !== null && $preview->getStartProcessDate() === null)
			{
				// Set the start date.
				$preview->setStartProcessDate(date_Calendar::getInstance()->toString());
				$preview->save();
				
				// Perform the generation.
				$previewDate = date_DateFormat::format($preview->getPreviewDate(), 'Y-m-d');
				// Parameters:
				// 1. preview root path
				// 2. source root path
				// 3. source database
				// 4. preview date (suposed to be in future)
				// 5. fakemail receiver
				$command = 'php ' . f_util_FileUtils::buildAbsolutePath($previewChangePath, 'modules', 'preview', 'lib', 'bin', 'generatePreview.php') . ' ' . $previewChangePath . ' ' . WEBEDIT_HOME . ' ' . Framework::getConfiguration('databases/webapp/database') . ' ' . $previewDate . ' ' . $preview->getFakeMailReceiver();
				Framework::info(__METHOD__ . ' command to execute: ' . $command);
				$processHandle = popen($command, 'r');
				while (($string = fread($processHandle, 1000)) != false)
				{
					Framework::info(__METHOD__ . ' ' . $string);
				}
				pclose($processHandle);
				
				// Set the end date.
				$preview->setEndProcessDate(date_Calendar::getInstance()->toString());
				$preview->save();
				$preview->activate();
			}
		}
	}
	
	/**
	 * @return void
	 */
	public function loadSourceDatabase($sourceDB)
	{
		$localDB = Framework::getConfiguration('databases/webapp/database');
		$dumpFilePathWithoutGz = WEBEDIT_HOME.'/lastDump.sql';
		$dumpFilePath = $dumpFilePathWithoutGz.'.gz';
		
		// Dump the database.
		$command = 'changeadmin.php dumpDB '.$sourceDB.' '.$dumpFilePathWithoutGz.' 2>&1';
		$this->executeCommand($command);

		// Restore the database.
		$command = 'changeadmin.php restoreDB '.$localDB.' '.$dumpFilePath;
		$this->executeCommand($command, 'y');
		
		// Unlink the dump file.
		Framework::info(__METHOD__ . ' unlink dump file.');
		f_util_FileUtils::unlink($dumpFilePath);
		
		// Set site URL.
		$this->executeCommand('change.php set-site-url');
	}
	
	/**
	 * @param String $date
	 */
	public function goToDate($dateAsString)
	{
		$fieldsList = $this->getDateFieldsToShift();
		$this->shiftDates($dateAsString, $fieldsList);
	}
	
	/**
	 * @return void
	 */
	public function rePublishDocuments()
	{
		$phpFile = f_util_FileUtils::buildWebeditPath('modules', 'preview', 'lib', 'bin', 'batchExec.php');
		if (!file_exists($phpFile))
		{
			Framework::error(__METHOD__ . ' file not found: ' . $phpFile);
			return;
		}
		
		Framework::info(__METHOD__ . ' looking for documents to process...');
		f_persistentdocument_PersistentProvider::getInstance()->reset();
		$documents = $this->getDocumentIdsToProcess();
		$documentsArray = array_chunk($documents, 2);
		$nChunks = count($documentsArray);
		Framework::info(__METHOD__ . ' ' . count($documents) . ' documents to process, separated in ' . $nChunks . ' chunks.');
		$index = 1;
		foreach ($documentsArray as $chunk)
		{
			Framework::info(__METHOD__ . ' processing chunk ' . $index++ . ' out of ' . $nChunks . '.');
			$command = 'php ' . $phpFile . ' ' . f_util_FileUtils::buildWebeditPath() . ' ' . implode(' ', $chunk);
			$this->executeCommand($command);
		}
		Framework::info(__METHOD__ . ' end of processing.');
	}
	
	/**
	 * @param String $date
	 */
	public function deactivateBackendUsers()
	{
		$users = users_BackenduserService::getInstance()->createQuery()->find();
		foreach ($users as $user)
		{
			if ($user->isPublished())
			{
				$user->deactivate();
			}
		}
	}
	
	/**
	 * @param String $emailAddress
	 */
	public function setFakeMailReceiver($emailAddress)
	{
		$preferences = ModuleService::getInstance()->getPreferencesDocument('preview');
		if ($preferences === null)
		{
			$preferences = preview_PreferencesService::getInstance()->getNewDocumentInstance();
		}
		$preferences->setFakeMailReceiver($emailAddress);
		$preferences->save();		
	}
	
	/**
	 * @param String $source
	 * @param String $destination
	 */
	public function makeMediaSnapshot($source, $destination)
	{
		if (substr($destination, -1) != '/')
		{
			$destination .= '/';
		}

		if (!is_dir($destination))
		{
			mkdir($destination, 0777, true);
		}

		$mediaDirLength = strlen($source);

		$ite = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($ite as $fileInfo)
		{
			if ($fileInfo->isDir())
			{
				continue;
			}

			$path = $fileInfo->getPathname();
			$targetPath = $destination.substr($path, $mediaDirLength);
			$dir = dirname($targetPath);
			if (!is_dir($dir))
			{
				mkdir($dir, 0777, true);
			}

			if ($fileInfo->isLink())
			{
				// TODO: verify the target of the (potentially) existing symlink ?
				@symlink(readlink($path), $targetPath);
			}
			elseif ($fileInfo->isFile())
			{
				if (is_file($targetPath) && !is_link($targetPath))
				{
					continue;
				}
				link($path, $targetPath);
			}
		}
	}
	
	/**
	 * @return void
	 */
	public function clearCaches()
	{
		// Clear document caches.
		f_persistentdocument_PersistentProvider::getInstance()->reset();
		
		// Clear webapp, template and simple caches.
		$cs = CacheService::getInstance();
		$cs->clearAllWebappCache();
		$cs->clearTemplateCache();
		$cs->clearLocalizedCache();
	}
	
	// Private stuff.
	
	/**
	 * @example:
	 * 	array(
	 *		'modules_website/page' => array('startpublicationdate', 'endpublicationdate'),
	 *		'modules_blog/post' => array('creationdate'),
	 *		'modules_news/news' => array('date')
	 *	)
	 * @return Array
	 */
	private function getDateFieldsToShift()
	{
		$fieldsList = array();
		
		// Automatically get all publication dates for documents with publishOnDayChange set to true.
		foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModels() as $model)
		{
			if ($model->publishOnDayChange())
			{
				$modelName = $model->getName();
				$fieldsList[$modelName] = array('startpublicationdate', 'endpublicationdate');
			}
		}
		
		// Get fields set in the config.
		$extraFields = Framework::getConfiguration('modules/preview/extraDateFieldsToShift', false);
		if (is_array($extraFields))
		{
			foreach ($extraFields as $modelName => $fieldNames)
			{
				if (!isset($fieldsList[$modelName]))
				{
					$fieldsList[$modelName] = array();
				}
				$fieldsList[$modelName] = array_merge($fieldsList[$modelName], explode(',', $fieldNames));
			}
		}
		
		return $fieldsList;
	}
	
	/**
	 * @param String $dateAsString
	 * @param Array<Array> $fieldsList like that: array('modules_website/page' => array('startpublicationdate', 'endpublicationdate', ...), ...)
	 */
	private function shiftDates($dateAsString, $fieldsList)
	{
		$pp = $this->getPersistentProvider();
		foreach ($fieldsList as $modelName => $fieldNames)
		{
			$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
			$propertyInfos = $model->getPropertiesInfos();
			$tableName = $model->getTableName();
			foreach ($fieldNames as $fieldName)
			{
				if ($fieldName && isset($propertyInfos[$fieldName]))
				{
					if (Framework::isDebugEnabled())
					{
						Framework::debug(__METHOD__ . ' model: '.$modelName.', field: '.$fieldName);
					}
					$propertyInfo = $propertyInfos[$fieldName];
					$dbFieldName = $propertyInfo->getDbMapping();
					$sql = "UPDATE `$tableName` SET `$dbFieldName` = ADDDATE(`$dbFieldName`, DATEDIFF(CURRENT_DATE, '$dateAsString')) WHERE `document_model` = '$modelName' AND `$dbFieldName` IS NOT NULL;";
					if (Framework::isDebugEnabled())
					{
						Framework::debug(__METHOD__ . ' ' . $sql);
					}
					$pp->executeSQLScript($sql);
					if ($propertyInfo->isLocalized())
					{
						if (Framework::isDebugEnabled())
						{
							Framework::debug(__METHOD__ . ' ' . $sql);
						}
						$sql = "UPDATE `{$tableName}_i18n` SET `{$dbFieldName}_i18n` = ADDDATE(`{$dbFieldName}_i18n`, DATEDIFF(CURRENT_DATE, '$dateAsString')) WHERE `document_id` IN (SELECT `document_id` FROM `$tableName` WHERE `document_model` = '$modelName') AND `{$dbFieldName}_i18n` IS NOT NULL;";
						$pp->executeSQLScript($sql);
					}					
				}
			}
		}
		
		// Clear f_cache.
		$pp->clearFrameworkCache();
	}
	
	/**
	 * @return Array<String>
	 */
	private function getDocumentIdsToProcess()
	{
		$now = date_Calendar::getInstance()->toString();
		$toProcess = array();
		foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModels() as $model)
		{
			if ($model->publishOnDayChange() === false)
			{
				continue;
			}
			
			$pubproperty = $model->getProperty('publicationstatus');
			if (is_null($pubproperty))
			{
				continue;
			}
			
			$rc = RequestContext::getInstance();
			if ($model->isLocalized())
			{
				$langs = $rc->getSupportedLanguages();
			}
			else
			{
				$langs = array($rc->getDefaultLang());
			}
			
			foreach ($langs as $lang)
			{
				try
				{
					$rc->beginI18nWork($lang);
					$query = f_persistentdocument_PersistentProvider::getInstance()
						->createQuery($model->getName())
						->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')))
						->add(Restrictions::eq('model', $model->getName()))
						->add(Restrictions::orExp(
							Restrictions::le('startpublicationdate', $now), 
							Restrictions::le('endpublicationdate', $now)))
						->setProjection(Projections::property('id', 'id'));
					$results = $query->findColumn('id');
					foreach ($results as $id)
					{
						$toProcess[] = $id . '/' . $lang;
					}
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		return $toProcess;
	}
	
	/**
	 * @param String $command
	 */
	private function executeCommand($command, $input = null)
	{
		Framework::info(__METHOD__ . ' START execute command: '.$command);
		$pipes = array();
		$descriptorspec = array(
		   0 => array("pipe", "r"),
		   1 => array("pipe", "w")
		);
		$processHandle = proc_open($command, $descriptorspec, $pipes, WEBEDIT_HOME);
		
		if ($input !== null)
		{
			fwrite($pipes[0], $input);
		}
		fclose($pipes[0]);
		
		Framework::info(__METHOD__ . ' ' . stream_get_contents($pipes[1]));
		fclose($pipes[1]);
		
		proc_close($processHandle);
		Framework::info(__METHOD__ . ' END execute command: '.$command);
	}
}