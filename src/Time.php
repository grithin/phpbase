<?php
namespace Grithin;
use Grithin\Tool;
use \Exception;
///Supplementing language time functions
class Time extends \DateTime implements \JsonSerializable{
	use Traits\Common;
	///creates a DateTime object with some additional functionality
	/**


	@param	time	various forms:
		- DateTime object
		- relative time ("-1 day")
		- variously formatted date ("2011-04-04 01:01:01")
	@param	zone	zone as accepted by Time::makeZone

	*/
	public function __construct($time=null,$zone=null){
		# DateTime::__construct will not interpret zones, so must interpret it here
		$zone = self::makeZone($zone);

		# DateTime::__construct does not accept DateTime objects, or ints, so need to construct a string it will accept
		return parent::__construct(self::getDateTimeString($time,$zone),$zone);
	}
	public function __toString(){
		return $this->datetime();
	}
	///many functions do not require parameters.  These functions may just as well be dynamically generated attributes, so let them be.
	public function __get($name){
		if(method_exists($this,$name)){
			return $this->$name();
		}
	}
	public function jsonSerialize(){
		return $this->datetime();
	}
	# create a Time object from input, returning null if error
	static function from($time=null,$zone=null){
		try{
			return new Time($time, $zone);
		}catch(Exception $e){
			return null;
		}
	}
	///creates a DateTimeZone object based on variable input
	/**
	@param	zone	various forms:
		- DateTimeZone objects; will just return the object
		- = false; will use date_default_timezone_get
		- != false; will pass to DateTimeZone constructor and return result
	*/
	static function makeZone($zone){
		if(!is_a($zone,'DateTimeZone')){
			$zone = $zone ? new \DateTimeZone($zone) : new \DateTimeZone(date_default_timezone_get());
		}
		return $zone;
	}
	# Get a string DateTime will accept from variable parameters
	static function getDateTimeString($time,$zone=null){
		$Date = self::getDateTime($time,$zone);
		return $Date->format('Y-m-d H:i:s.u'); # seems only time format with microtime.  Implementer must use same timezone as was passed in for this to work (since timezone is not in string)
	}
	# used to get DateTime object based on variable parameters
	static function getDateTime($time,$zone=null){
		if(is_a($time,'DateTime')){
			return $time;
		}
		$zone = self::makeZone($zone);
		if(is_numeric($time)){
			# create DateTime with same zone used for later interpretation
			$Date = new \DateTime(null,$zone);
			$Date->setTimestamp($time);
			$remainder = floor($time) - $time;
			if($remainder){ # number had a fraction, remake Date with microtime (which can only be done by making a new Date object)
				return new \DateTime($Date->format('Y-m-d H:i:s.').$remainder);
			}else{
				return $Date;
			}
		}
		# normal interpretation
		return new \DateTime($time,$zone);
	}
	///Used to get format of current Time object using relative times
	/**
	@param	format	DateTime::format() format
	@param	zone	The zone of the output time
	*/
	public function format($format,$zone=null){
		if($zone){
			$currentZone = $this->getTimezone();
			$this->setZone($zone);
			$return = parent::format($format);
			$this->setZone($currentZone);
			return $return;
		}else{
			return parent::format($format);
		}
	}
	///Get the common dateTime format "Y-m-d H:i:s"
	public function datetime(){
		if(!$this){
			return self::instance_with_args(func_get_args())->datetime();
		}
		return parent::format("Y-m-d H:i:s");
	}
	///Get the common date format "Y-m-d"
	public function date(){
		return parent::format("Y-m-d");
	}
	# interpret zone and set it
	public function setZone($zone){
		return parent::setTimezone(self::makeZone($zone));
	}
	# deprecated
	///get DateInterval object based on current Time instance.
	/**
	@param	time	see Time::__construct()
	@param	zone	see Time::__construct(); defaults to current instance timezone
	@param	absolute? (positive value)
	*/
	public function instance_diff($time,$zone=null,$absolute=null){
		if(is_a($time,'\DateTime')){
			$absolute = $zone;
		}else{
			$zone = $zone ? $zone : $this->getTimezone();
			$time = new $class($time,$zone);
		}
		return parent::diff($time,$absolute);
	}
	# deprecated
	///get DateInterval without having to separately make DateTime instances
	/**
	@param	time1	see Time::__construct()
	@param	time2	see Time::__construct()
	@param	timeZone1	timezone corresponding to time1; see Time::__construct()
	@param	timeZone2	timezone corresponding to time2; see Time::__construct()
	@param	absolute? (positive value)
	*/
	static function static_diff($time1, $time2=null, $timeZone1 = null, $timeZone2 = null, $absolute = null){
		$class = __class__;
		$Time1 = new $class($time1,$timeZone1);
		$Time2 = new $class($time2,$timeZone2);
		return $Time1->diff($Time2,$absolute);
	}
	# get new Time instance of day start
	function dayStart(){
		return $this->relative('now 00:00:00');
	}
	# alias
	function day_start(){
		return call_user_func_array([$this,'dayStart'], func_get_args());
	}
	# get new Time instance of day end
	function dayEnd(){
		return $this->relative('now 23:59:59');
	}
	# alias
	function day_end(){
		return call_user_func_array([$this,'day_end'], func_get_args());
	}
	///Date validator
	/**
	@param	y	year
	@param	m	month (numeric representation starting a 1)
	@param	d	day (numeric representation starting a 1)
	*/
	static function validate($y,$m,$d){
		$d = abs((int)$d);
		$m = abs((int)$m);
		$y = abs((int)$y);

		if($y && $m && $d){
			$date = explode('-',$date);
			if(in_array($m,array(1,3,5,7,8,10,12))){
				//months with 31 days
				if($d>31){
					return false;
				}
			}elseif(in_array($m,array(4,6,9,11))){
				//months with 30 days
				if($d>30){
					return false;
				}
			}elseif($m == 2){
				//check for leap year
				if($d>(($y % 4 == 0) ? 29 : 28)){
					return false;
				}
			}
			return true;
		}
		return false;
	}
	# setTime accepting different format
	/*
	@param	clock	"HH:MM:SS"
	*/
	function setClock($clock){
		$parts = explode(':', $clock);
		$this->setTime($parts[0],$parts[1],$parts2[2]);
	}
	function diff($time, $format=null){
		if($format){
			return self::interval_convert(parent::diff($time), $format);
		}else{
			return parent::diff($time);
		}
	}

	///Get Diff object comparing current object ot current time
	function age($format=null){
		return $this->diff(new \Datetime(),  $format);
	}
	static function interval_convert($interval, $into){
		$seconds = $interval->s + 60 * $interval->i + 3600 * $interval->h;
		if($interval->days){
			$days = $interval->days; # this is the total number of days, whereas ->d is just the number of days in the remaining month
		}else{
			$days = $interval->d + ($interval->m * 30) + $interval->y * 365;
		}
		$invert = $interval->invert ? -1 : 1;
		$get_absolute = function()use($interval, $into, $seconds, $days){
			switch($into){
				case 'second':
				case 'seconds':
					return $seconds + 86400 * $days;
				case 'minute':
				case 'minutes':
					return $seconds/60 + $days * 1440;
				case 'hour':
				case 'hours':
					return $seconds/3600 + $days * 24;
				case 'day':
				case 'days':
					return $seconds/86400 + $days;
				case 'week':
				case 'weeks':
					return ($seconds/86400 + $days)/7;
				case 'month':
				case 'months':
					return $seconds/2592000 + $interval->d/30 + $interval->m + $interval->y*12 ;
				case 'year':
				case 'years':
					return ($seconds/2592000 + $interval->d/30 + $interval->m)/12 + $interval->y ;
			}
		};
		return $invert * $get_absolute();
	}
	function unix(){
		return $this->format('U');
	}
	function unix_micro(){
		return $this->format('U.u');
	}

	/**
	@param	relative	string to apply as relative to current time object without modifying current time object
	*/
	function relative($relative){
		$copy = clone $this;
		$copy->modify($relative);
		return $copy;
	}
	///Get timezones in the USA
	static function usaTimezones(){
		// US TimeZones based on TimeZone name
		// format 'DateTime Timezone' => 'Human Friendly Timezone'
		$standard = array(
			'America/Puerto_Rico'=>'AST',
			'EDT'=>'EDT',
			'CDT'=>'CDT',
			'America/Phoenix'=>'MST',
			'MDT'=>'MDT',
			'PDT'=>'PDT',
			'America/Juneau'=>'AKDT',
			'HST'=>'HST',
			'Pacific/Guam'=>'ChST',
			'Pacific/Samoa'=>'SST',
			'Pacific/Wake'=>'WAKT',
		);

		// US TimeZones according to DateTime's official  "List of Supported Timezones"
		$cityBased = array(
			'America/Puerto_Rico'=>'AST',
			'America/New_York'=>'EDT',
			'America/Chicago'=>'CDT',
			'America/Boise'=>'MDT',
			'America/Phoenix'=>'MST',
			'America/Los_Angeles'=>'PDT',
			'America/Juneau'=>'AKDT',
			'Pacific/Honolulu'=>'HST',
			'Pacific/Guam'=>'ChST',
			'Pacific/Samoa'=>'SST',
			'Pacific/Wake'=>'WAKT',
		);
		return array('city' => $cityBased, 'standard'=>$standard);
	}

	# offset by actual months (instead of where php will do `30d * 3` for `+3 months`), accounting for variable days in each month, and cutting down the date day-part if necessary
	function offset_months($months){
		return new Time(self::offset_date_by_months($this->unix, $months));
	}
	static function offset_date_by_months($initial_date, $months){
		$start_year = date('Y',$initial_date);
		$start_month = date('m',$initial_date);
		$start_day = date('d',$initial_date);

		if($months > 0){
			$years = floor($months / 12);
		}else{
			$years = -floor(-$months / 12);
		}
		$end_year = $start_year + $years;
		$months = $months % 12;

		$end_month = $months + $start_month;
		if($end_month > 12){
			$end_year++;
			$end_month = $end_month - 12;
		}elseif($end_month < 1){
			$end_year--;
			$end_month = $end_month + 12;
		}

		$max_day = date('t',strtotime($end_year.'-'.$end_month.'-1'));

		if($max_day < $start_day){
			$date = $end_year.'-'.$end_month.'-'.$max_day;
		}else{
			$date = $end_year.'-'.$end_month.'-'.$start_day;
		}

		return strtotime($date.' '.date('H:i:s',$initial_date));
	}
	# return a datetime with some level of precision
	# ensures a standard way of dealing with reduced precision datetime columns
	function datetime_precision($precision){
		if(!$this){
			$args = func_get_args();
			$precision = array_pop($args);
			$function = __FUNCTION__;
			return self::instance_with_args($args)->$function($precision);
		}
		$map = [
			'year'=>'Y-1-1 00:00:00',
			'month'=>'Y-m-1 00:00:00',
			'day'=>'Y-m-d 00:00:00',
			'hour'=>'Y-m-d H:00:00',
			'minute'=>'Y-m-d H:i:00',
			'second'=>'Y-m-d H:i:s',
		];
		return $this->format($map[$precision]);
	}
	# return a date with some level of precision
	# ensures a standard way of dealing with reduced precision date columns
	function date_precision($precision){
		if(!$this){
			$args = func_get_args();
			$precision = array_pop($args);
			$function = __FUNCTION__;
			return self::instance_with_args($args)->$function($precision);
		}
		$map = [
			'year'=>'Y-1-1',
			'month'=>'Y-m-1',
			'day'=>'Y-m-d',
		];
		return $this->format($map[$precision]);
	}
}
