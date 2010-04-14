<?php
/**
 * preview_persistentdocument_preferences
 * @package preview.persistentdocument
 */
class preview_persistentdocument_preferences extends preview_persistentdocument_preferencesbase
{
	/**
	 * @see f_persistentdocument_PersistentDocumentImpl::getLabel()
	 * @return String
	 */
	public function getLabel()
	{
		return f_Locale::translateUI(parent::getLabel());
	}
}