<?php
/**
 * @author intportg
 * @package modules.preview.lib.validation
 */
class validation_FutureDateValidator extends validation_ValidatorImpl implements validation_Validator
{
	/**
	 * Validate $data and append error message in $errors.
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 * @return void
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		$value = strval($field->getValue());
		try 
		{
			$date = date_Calendar::getInstanceFromFormat($value, 'd/m/Y H:i:s');
		}
		catch (Exception $e)
		{
			$date = date_Calendar::getInstance($value);
		}
		if (date_Calendar::getInstance()->isAfter($date))
		{
			$this->reject($field->getName(), $errors);
		}
	}
	
	/**
	 * @return String
	 */
	protected function getMessage()
	{
		return f_Locale::translate('&modules.preview.bo.general.Date-future-message;');
	}
}