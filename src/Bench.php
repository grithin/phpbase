<?
namespace Grithin;

class Bench{
	public $measures = [];
	function __construct($name=null){
		$this->mark($name);
	}
	function mark($name=null){
		$next = count($this->measures);
		$this->measures[$next]['time'] = microtime(true);
		$this->measures[$next]['mem'] = memory_get_usage();
		if($name !== null){
			$this->measures[$next]['name'] = $name;
		}
	}
	function end($name=null){
		$this->mark($name);
		return $this->measure();
	}
	function measure(){
		$current = current($this->measures);
		$mem = ['start'=>$this->measures[0]['mem'], 'end'=>$this->measures[count($this->measures)-1]['mem']];
		$mem['diff'] = $mem['end'] - $mem['start'];
		$time = 0;
		$intervals = [];
		while($next = next($this->measures)){
			$outItem = &$intervals[];

			$time += $outItem['time'] = $next['time'] - $current['time'];
			$outItem['mem.change'] = $next['mem'] - $current['mem'];
			if($next['name'] or $current['name']){
				$outItem['names'] = $current['name'].' > '.$next['name'];
			}
			$current = $next;
		}
		$summary = ['mem'=>$mem, 'time'=>$time];
		return ['intervals'=>$intervals, 'summary'=>$summary];
	}

	/*
	2 : |_|x|_|x|
	3 : |_|_|x|_|_|x|
	*/
	function measure_skip($skip_every=2){
		$current = current($this->measures);
		$mem = ['start'=>$this->measures[0]['mem'], 'end'=>$this->measures[count($this->measures)-1]['mem']];
		$mem['diff'] = $mem['end'] - $mem['start'];
		$time = 0;
		$intervals = [];
		$count = 0;
		while($next = next($this->measures)){
			$count++;

			if($count == $skip_every){
				$count = 0;
			}else{
				$time += $outItem['time'] = $next['time'] - $current['time'];
				$outItem['mem.change'] = $next['mem'] - $current['mem'];
				if($next['name'] or $current['name']){
					$outItem['names'] = $current['name'].' > '.$next['name'];
				}
				# pp([$next, $current, $outItem, $skip_every, $count]);
				$intervals[] = $outItem;
			}
			$current = $next;
		}
		$summary = ['mem'=>$mem, 'time'=>$time];
		return ['intervals'=>$intervals, 'summary'=>$summary];
	}
	function end_out($name=null){
		$this->mark($name);
		return $this->measure_out();
	}

	function measure_out($name=null){
		$end = $this->measure($name);
		Debug::out($end);
	}
	static $accumulaters = [];
	static function accumulate($accumulater_name, $mark_name=null){
		if(!self::$accumulaters[$accumulater_name]){
			$class = __CLASS__;
			self::$accumulaters[$accumulater_name] = new $class($mark_name);
		}else{
			self::$accumulaters[$accumulater_name]->mark($mark_name);
		}
	}
	static function accumulate_measure($accumulater_name, $skip_every=2){
		return self::$accumulaters[$accumulater_name]->measure_skip($skip_every);
	}
	static function accumulate_measure_all($skip_every=2){
		$measures = [];
		foreach(self::$accumulaters as $name=>$bench){
			#pp($name);
			$measures[$name] = $bench->measure_skip($skip_every);
		}
		return $measures;
	}
}