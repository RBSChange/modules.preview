<?php
/**
 * Auto-generated doc comment
 * @package modules.preview.lib.services
 */
class preview_FakeMailService extends MailService
{
	/**
	 * @return Mailer
	 */
	protected function buildMailer($mailMessage)
	{
		$receiver = ModuleService::getInstance()->getPreferenceValue('preview', 'fakeMailReceiver');
		if (!$receiver)
		{
			throw new Exception('The fakemail receiver is not set in the preferences.');
		}
		
		$mailer = $this->getMailer();
		// Pass the mailMessage to the mailer
		$mailer->setSender($mailMessage->getSender());
		$mailer->setReceiver($receiver);
		$mailer->setBcc(null);
		$mailer->setCc(null);
		$mailer->setEncoding($mailMessage->getEncoding());
		$mailer->setHtmlAndTextBody($mailMessage->getHtmlContent(), $mailMessage->getTextContent());
		$mailer->setReplyTo($mailMessage->getReplyTo());
		$mailer->setSubject('(Fake) ' . $mailMessage->getSubject());
		
		if ($mailMessage->hasNotificationTo())
		{
			$mailer->setHeader('Disposition-Notification-To', $receiver);
		}
		
		foreach ($mailMessage->getAttachment() as $attachement)
		{
			$mailer->addAttachment($attachement);
		}
		return $mailer;
	}
}