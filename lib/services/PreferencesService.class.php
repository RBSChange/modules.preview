<?php
/**
 * preview_PreferencesService
 * @package preview
 */
class preview_PreferencesService extends f_persistentdocument_DocumentService
{
	/**
	 * @var preview_PreferencesService
	 */
	private static $instance;

	/**
	 * @return preview_PreferencesService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return preview_persistentdocument_preferences
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_preview/preferences');
	}

	/**
	 * Create a query based on 'modules_preview/preferences' model.
	 * Return document that are instance of modules_preview/preferences,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_preview/preferences');
	}
	
	/**
	 * Create a query based on 'modules_preview/preferences' model.
	 * Only documents that are strictly instance of modules_preview/preferences
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_preview/preferences', false);
	}
}