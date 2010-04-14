<?php
class preview_RunPreviewGenerationTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$ms = preview_ModuleService::getInstance();
		$ms->runPreviewGeneration();
	}
}