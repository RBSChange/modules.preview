<?php
/**
 * preview_PreviewScriptDocumentElement
 * @package modules.preview.persistentdocument.import
 */
class preview_PreviewScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return preview_persistentdocument_preview
     */
    protected function initPersistentDocument()
    {
    	return preview_PreviewService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_preview/preview');
	}
}