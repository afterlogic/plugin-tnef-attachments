<?php

/* -AFTERLOGIC LICENSE HEADER- */

class_exists('CApi') or die();

CApi::Inc('common.plugins.expand-attachment');
include_once dirname(__FILE__).'/class.tnef.php';

class CTnefAttachmentsPlugin extends AApiExpandAttachmentPlugin
{
	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.0', $oPluginManager);
	}

	public function IsMimeTypeSupported($sMimeType, $sFileName = '')
	{
		return (in_array($sMimeType, array('application/tnef', 'application/x-tnef', 'application/ms-tnef')) || 
			('' !== $sFileName && 'winmail.dat' === trim(strtolower($sFileName)))) && class_exists('TNEF');
	}

	public function ExpandAttachment($oAccount, $sMimeType, $sFullFilePath, $oApiFileCache)
	{
		$mResult = array();

		$oTNEF = new TNEF();
		if ($oTNEF)
		{
			$aData = $oTNEF->Decode(file_get_contents($sFullFilePath));
			if (is_array($aData))
			{
				foreach ($aData as $aItem)
				{
					if (is_array($aItem) && isset($aItem['name'], $aItem['stream']))
					{
						$sFileName = \MailSo\Base\Utils::Utf8Clear(basename($aItem['name']));
						$sMimeType = \MailSo\Base\Utils::MimeContentType($sFileName);

						$sTempName = md5(\microtime(true).rand(1000, 9999));
						if ($oApiFileCache->Put($oAccount, $sTempName, $aItem['stream']))
						{
							$mResult[] = array(
								'FileName' => $sFileName,
								'MimeType' => $sMimeType,
								'Size' => strlen($aItem['stream']),
								'TempName' => $sTempName
							);
						}
					}
				}
			}
		}

		return $mResult;
	}
}

return new CTnefAttachmentsPlugin($this);
