<?
namespace Grithin;

class Bench{
	public $measures = [];
	function __construct(){
		$this->mark();
	}
	function mark(){
		$next = count($this->measures);
		$this->measures[$next]['time'] = microtime(true);
		$this->measures[$next]['mem'] = memory_get_usage();
	}
	function end(){
		$this->mark();
		$out[$name] = ['diff'=>[]];

		$current = current($this->measures);
		$mem = ['start'=>$this->measures[0]['mem'], 'end'=>$this->measures[count($this->measures)-1]['mem']];
		$mem['diff'] = $mem['end'] - $mem['start'];
		$time = 0;
		$intervals = [];
		while($next = next($this->measures)){
			$outItem = &$intervals[];

			$time += $outItem['time'] = $next['time'] - $current['time'];
			$outItem['mem.change'] = $next['mem'] - $current['mem'];
			$current = $next;
		}
		$summary = ['mem'=>$mem, 'time'=>$time];
		return ['intervals'=>$intervals, 'summary'=>$summary];
	}
	function end_out(){
		$end = $this->end();
		Debug::out($end);
	}
}