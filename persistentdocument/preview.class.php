<?php
/**
 * preview_persistentdocument_preview
 * @package modules.preview.persistentdocument
 */
class preview_persistentdocument_preview extends preview_persistentdocument_previewbase 
{
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */	
	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
	{
		$nodeAttributes['previewStatus'] = $this->getPublicationstatus();
	}
}