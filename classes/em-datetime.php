<?php
/**
 * Extends DateTime allowing supplied timezone to be a string, which can also be a UTC offset.
 * Also prevents an exception being thrown. Some additional shortcuts added so less coding is required for regular tasks.
 * By doing this, we support WP's option to manually offset time without DST, which is not supported by DateTimeZone in PHP <5.5.10
 * 
 * @since 5.8.2
 */
class EM_DateTime extends DateTime {
	
	/**
	 * The name of this timezone. For example, America/New_York or UTC+3.5
	 * @var string
	 */
	public $timezone_name = false;
	/**
	 * Flag for validation purposes, so we can still have a real EM_DateTime and extract dates but know if the intended datetime failed validation.
	 * A completely invalid date and time will become 1970-01-01 00:00:00 in local timezone, however a valid time can still exist with the 1970-01-01 date.
	 * If the date is invalid, only local timezones should be used since the time will not accurately convert timezone switches.
	 * @var string
	 */
	public $valid = true;
	
	/**
	 * @see DateTime::__construct()
	 * @param string $time
	 * @param string|EM_DateTimeZone $timezone Unlike DateTime this also accepts string representation of a valid timezone, as well as UTC offsets in form of 'UTC -3' or just '-3'
	 */
	public function __construct( $time = null, $timezone = null ){
		//get our EM_DateTimeZone
		$timezone = EM_DateTimeZone::create($timezone);
		//fix DateTime error if a regular timestamp is supplied without prepended @ symbol
		if( is_numeric($time) ) $time = '@'.$time;
		//finally, run parent function with our custom timezone
		try{
			@parent::__construct($time, $timezone);
			$this->valid = true; //if we get this far, supplied time is valid
		}catch( Exception $e ){
			//get current date/time in relevant timezone and set valid flag to false
			parent::__construct('@0');
			$this->setTimezone($timezone);
			$this->setDate(1970,1,1);
			$this->setTime(0,0,0);
			$this->valid = false;
		}
		//save timezone name for use in getTimezone()
		$this->timezone_name = $timezone->getName();
	}
	
	/**
	 * Extends the DateTime::createFromFormat() function by setting the timezone to the default blog timezone if none is provided.
	 * @param string $format
	 * @param string $time
	 * @param string|EM_DateTimeZone $timezone
	 * @return boolean|EM_DateTime
	 */
	public static function createFromFormat( $format, $time, $timezone = null ){
		$timezone = EM_DateTimeZone::create($timezone);
		$DateTime = parent::createFromFormat($format, $time, $timezone);
		if( $DateTime === false ) return false;
		return new EM_DateTime($DateTime->format('Y-m-d H:i:s'), $timezone);
	}
	
	/**
	 * {@inheritDoc}
	 * @see DateTime::format()
	 */
	public function format( $format = 'Y-m-d H:i:s'){
		if( !$this->valid && ($format == 'Y-m-d' || $format == em_get_date_format())) return '';
		//if we deal with offsets, then we offset UTC time by that much
		if( $this->getTimezone()->offset !== false ){
			return date($format, $this->getTimestamp() + $this->getTimezone()->offset );
		}
		return parent::format($format);
	}
	
	/**
	 * Returns a date and time representation in the format stored in Events Manager settings.
	 * @param string $include_hour
	 * @return string
	 */
	public function formatDefault( $include_hour = true ){
		$format = $include_hour ? em_get_date_format() . ' ' . em_get_hour_format() : em_get_date_format();
		$format = apply_filters( 'em_datetime_format_default', $format, $include_hour );
		return $this->i18n( $format );
	}
	
	/**
	 * Provides a translated date and time according to the current blog language. 
	 * Useful if using formats that provide date-related names such as 'Monday' or 'January', which should be translated if displayed in another language.
	 * @param string $format
	 * @return string
	 */
	public function i18n( $format = 'Y-m-d H:i:s' ){
		if( !$this->valid && $format == em_get_date_format()) return '';
		//if we deal with offsets, then we offset UTC time by that much
		$ts = $this->getTimestamp();
		$tswo = $this->getTimestampWithOffset();
		return date_i18n( $format, $this->getTimestampWithOffset() );
	}
	
	/**
	 * Outputs a default mysql datetime formatted string. 
	 * @return string
	 */
	public function __toString(){
		return $this->format('Y-m-d H:i:s');
	}
	
	/**
	 * Modifies the time of this object, if a mysql TIME valid format is provided (e.g. 14:30:00)
	 * @param string $hour
	 * @return EM_DateTime Returns object for chaining.
	 */
	public function setTimeString( $hour ){
		if( preg_match('/^\d{2}:\d{2}:\d{2}$/', $hour) ){
			$time = explode(':', $hour);
			$this->setTime($time[0], $time[1], $time[2]);
		}
		return $this;
	}
	
	/**
	 * Extends DateTime function to allow string representation of argument passed to create a new DateInterval object.
	 * @see DateTime::add()
	 * @param string|DateInterval
	 * @return EM_DateTime Returns object for chaining.
	 */
	public function add( $DateInterval ){
		if( is_object($DateInterval) ){
			return parent::add($DateInterval);
		}else{
			return parent::add( new DateInterval($DateInterval) );
		}
	}
	
	/**
	 * Extends DateTime function to allow string representation of argument passed to create a new DateInterval object.
	 * @see DateTime::sub()
	 * @param string|DateInterval
	 * @return EM_DateTime
	 */
	public function sub( $DateInterval ){
		if( is_object($DateInterval) ){
			return parent::sub($DateInterval);
		}else{
			return parent::sub( new DateInterval($DateInterval) );
		}
	}
	
	/**
	 * Easy chainable cloning function, useful for situations where you may want to manipulate the current date,
	 * such as adding a month and getting the DATETIME string without changing the original value of this object.
	 * @return EM_DateTime
	 */
	public function clone(){
		return clone $this;
	}
	
	/**
	 * Gets a timestamp with an offset, which will represent the local time equivalent in UTC time (so a local time would be produced if supplied to date())
	 */
	public function getTimestampWithOffset(){
		return $this->getOffset() + $this->getTimestamp();
	}
	
	/**
	 * Extends DateTime::getOffset() by checking for timezones with manual offsets, such as UTC+3.5
	 * @see DateTime::getOffset()
	 * @return int
	 */
	public function getOffset(){
		if( $this->getTimezone()->offset !== false ){
			return $this->getTimezone()->offset;
		}
		return parent::getOffset();
	}
	
	/**
	 * Returns an EM_DateTimeZone object instead of the default DateTimeZone object.
	 * @see DateTime::getTimezone()
	 * @return EM_DateTimeZone
	 */
	public function getTimezone(){
		return new EM_DateTimeZone($this->timezone_name);
	}
	
	/**
	 * Returns a MySQL TIME formatted string, with the option of providing the UTC equivalent.
	 * @param bool $utc If set to true a UTC relative time will be provided.
	 * @return string
	 */
	public function getTime( $utc = false ){
		if( $utc ){
			$current_timezone = $this->getTimezone()->getName();
			$this->setTimezone('UTC');
		}
		$return = $this->format('H:i:s');
		if( $utc ) $this->setTimezone($current_timezone);
		return $return;
	}
	
	/**
	 * Returns a MySQL DATE formatted string.
	 * @return string
	 */
	public function getDate( $utc = false ){
		return $this->format('Y-m-d');
	}
	
	/**
	 * Returns a MySQL DATETIME formatted string, with the option of providing the UTC equivalent.
	 * @param bool $utc If set to true a UTC relative time will be provided.
	 * @return string
	 */
	public function getDateTime( $utc = false ){
		if( $utc ){
			$current_timezone = $this->getTimezone()->getName();
			$this->setTimezone('UTC');
		}
		$return = $this->format('Y-m-d H:i:s');
		if( $utc ) $this->setTimezone($current_timezone);
		return $return;
	}
	
	/**
	 * Extends DateTime functionality by accepting a false or string value for a timezone. 
	 * @see DateTime::setTimezone()
	 * @return EM_DateTime Returns object for chaining.
	 */
	public function setTimezone( $timezone ){
		if( $timezone == $this->getTimezone()->getName() ) return $this;
		$timezone = EM_DateTimeZone::create($timezone);
		parent::setTimezone($timezone);
		$this->timezone_name = $timezone->getName();
		return $this;
	}
}

/**
 * Extends the native DateTimeZone object by allowing for UTC manual offsets as supported by WordPress, along with eash creation of a DateTimeZone object with the blog's timezone. 
 * @since 5.8.2
 */
class EM_DateTimeZone extends DateTimeZone {
	
	public $offset = false;
	
	public function __construct( $timezone ){
		//if we're not suppiled a DateTimeZone object, create one from string or implement manual offset
		if( $timezone != 'UTC' ){
			$timezone = preg_replace('/^UTC ?/', '', $timezone);
			if( is_numeric($timezone) ){
				$this->offset = $timezone * 3600;
				$timezone = 'UTC';
			}
		}
		parent::__construct($timezone);
	}
	
	/**
	 * Special function which converts a timezone string, UTC offset or DateTimeZone object into a valid EM_DateTimeZone object.
	 * If no value supplied, a EM_DateTimezone with the default WP environment timezone is created.
	 * @param mixed $timezone
	 * @return EM_DateTimeZone
	 */
	public static function create( $timezone = false ){
		//if we're not suppiled a DateTimeZone object, create one from string or implement manual offset
		if( !empty($timezone) && !is_object($timezone) ){
			//create EM_DateTimeZone object if valid, otherwise allow defaults to override later
			try {
				$timezone = new EM_DateTimeZone($timezone);
			}catch( Exception $e ){
				$timezone = null;
			}
		}elseif( is_object($timezone) && get_class($timezone) == 'DateTimeZone'){
			//if supplied a regular DateTimeZone, convert it to EM_DateTimeZone
			$timezone = new EM_DateTimeZone($timezone->getName());
		}
		if( !is_object($timezone) ){
			//if no valid timezone supplied, get the default timezone in EM environment, otherwise the WP timezone or offset
			$timezone = get_option( 'timezone_string' );
			if( !$timezone ) $timezone = get_option('gmt_offset');
			$timezone = new EM_DateTimeZone($timezone);
		}
		return $timezone;
	}
	
	/**
	 * {@inheritDoc}
	 * @see DateTimeZone::getOffset()
	 */
	public function getOffset( $datetime ){
		if( $this->offset !== false ){
			return $this->offset;
		}elseif( get_class($datetime) == 'EM_DateTime' && $datetime->offset !== false ){
			return $datetime->offset;
		}
		return parent::getOffset( $datetime );
	}
	
	/**
	 * {@inheritDoc}
	 * @see DateTimeZone::getName()
	 */
	public function getName(){
		if( $this->offset !== false ){
			if( $this->offset > 0 ){
				$return = 'UTC+'.$this->offset/3600;
			}else{
				$return = 'UTC'.$this->offset/3600;
			}
			return $return;
		}
		return parent::getName();
	}
	
	/**
	 * If the timezone has a manual UTC offset, then an empty array of transitions is returned.
	 * {@inheritDoc}
	 * @see DateTimeZone::getTransitions()
	 */
	public function getTransitions( $timestamp_begin = null, $timestamp_end = null ){
		if( $this->offset !== false ){
			return array();
		}
		return parent::getTransitions($timestamp_begin, $timestamp_end);
	}
}