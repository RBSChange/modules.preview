<?php
/**
 * preview_PreviewService
 * @package preview
 */
class preview_PreviewService extends f_persistentdocument_DocumentService
{
	/**
	 * @var preview_PreviewService
	 */
	private static $instance;

	/**
	 * @return preview_PreviewService
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
	 * @return preview_persistentdocument_preview
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_preview/preview');
	}

	/**
	 * Create a query based on 'modules_preview/preview' model.
	 * Return document that are instance of modules_preview/preview,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_preview/preview');
	}
	
	/**
	 * Create a query based on 'modules_preview/preview' model.
	 * Only documents that are strictly instance of modules_preview/preview
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_preview/preview', false);
	}
	
	/**
	 * @return preview_persistentdocument_preview
	 */
	public function getPendingPreview()
	{
		return $this->createQuery()->add(Restrictions::in('publicationstatus', array('DRAFT')))->findUnique();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $forModuleName
	 * @param unknown_type $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections)
	{
		$resume = parent::getResume($document, $forModuleName, $allowedSections);
		
		$properties = $resume['properties'];
		
		$properties['previewDate'] = $document->getUIPreviewDate();
		$properties['fakeMailReceiver'] = $document->getFakeMailReceiver();
		$properties['startProcessDate'] = $document->getStartProcessDate();
		$properties['endProcessDate'] = $document->getEndProcessDate();
		
		$resume['properties'] = $properties;
		
		return $resume;
	}
	
	/**
	 * @param preview_persistentdocument_preview $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		if ($document->getFakeMailReceiver() === null)
		{
			$user = users_UserService::getInstance()->getCurrentBackEndUser();
			$document->setFakeMailReceiver($user->getEmail());
		}
	}

	/**
	 * @param preview_persistentdocument_preview $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		if ($this->getCurrentPreview() !== null)
		{
			throw new BaseException('There can be only one active preview!', 'modules.preview.bo.general.Error-only-one-preview');
		}
		else
		{
			$currentUser = users_BackenduserService::getInstance()->getCurrentBackEndUser();
			$document->setLabel(f_Locale::translate('&modules.preview.bo.general.Preview-label;', array('username' => $currentUser->getFullname())));
		}
	}

	/**
	 * @return preview_persistentdocument_preview
	 */
	private function getCurrentPreview()
	{
		return $this->createQuery()->add(Restrictions::in('publicationstatus', array('DRAFT', 'ACTIVE', 'PUBLICATED')))->findUnique();
	}
}