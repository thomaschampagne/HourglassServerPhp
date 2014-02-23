<?php
namespace Hourglass\Model;

class VersionnedFile {

	public static $FILEPATH = "filePath";
	public static $FINGERPRINT = "fingerPrint";
	public static $ISVERSIONNED = "isVersionned";
	public static $NOTE = "note";
	public static $LASTEDITDATE = "lastEditDate";
	public static $ISDELETED = "isDeleted";
	public static $REVNUMBER = "revNumber";
	public static $REVDATE = "revDate";
	
	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $filePath;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $fingerPrint;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $isVersionned;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $note;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $lastEditDate;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $revNumber;


	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $revDate;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $isDeleted;

	public function getStoreFormatArray()
	{
		return array(
			VersionnedFile::$FILEPATH => $this->filePath,
			VersionnedFile::$FINGERPRINT => $this->fingerPrint,
			VersionnedFile::$ISVERSIONNED => $this->isVersionned,
			VersionnedFile::$NOTE => $this->note,
			VersionnedFile::$LASTEDITDATE => $this->lastEditDate,
			VersionnedFile::$ISDELETED =>	$this->isDeleted,
			VersionnedFile::$REVNUMBER =>	$this->revNumber,
			VersionnedFile::$REVDATE => $this->revDate);
	}
	
	public function setFilePath( $filePath )
	{
		$this->filePath = $filePath;
	}

	public function setFingerPrint( $fingerPrint )
	{
		$this->fingerPrint = $fingerPrint;
	}

	public function setIsVersionned( $isVersionned )
	{
		$this->isVersionned = $isVersionned;
	}

	public function setNote( $note )
	{
		$this->note = $note;
	}

	public function setLastEditDate( $lastEditDate )
	{
		$this->lastEditDate = $lastEditDate;
	}

	public function setRevNumber( $revNumber )
	{
		$this->revNumber = $revNumber;
	}

	public function getFilePath()
	{
		return $this->filePath;
	}

	public function getFingerPrint()
	{
		return $this->fingerPrint;
	}
	
	public function getLastEditDate()
	{
		return $this->lastEditDate;
	}

	public function getRevNumber()
	{
		return $this->revNumber;
	}

	public function setIsDeleted( $isDeleted )
	{
		$this->isDeleted = $isDeleted;
	}

	public function getIsDeleted()
	{
		return $this->isDeleted;
	}

	public function setRevDate( $revDate )
	{
		$this->revDate = $revDate;
	}

	public function getRevDate()
	{
		return $this->revDate;
	}

}