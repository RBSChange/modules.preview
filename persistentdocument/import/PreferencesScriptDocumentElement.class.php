<?php
/**
 * preview_PreferencesScriptDocumentElement
 * @package modules.preview.persistentdocument.import
 */
class preview_PreferencesScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return preview_persistentdocument_preferences
     */
    protected function initPersistentDocument()
    {
    	return preview_PreferencesService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_preview/preferences');
	}
}