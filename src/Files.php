<?
namespace Grithin;
use Grithin\Debug;

///Used to keep track of inclusions and to get better errors on failed requires
/**
	@note	For this case, since phpe doesn't like it when you use "include" or "require" for method names, I have abbreviated the names.
*/
class Files{

	static private $included;///<an array of included files along with other arguments
	static private $currentInclude;///<internal use
	///used to factor out common functionality
	static function __callStatic($name,$arguments){
		self::$currentInclude = array(
				'file'=>$arguments[0],
				'globals'=>$arguments[1],
				'vars'=>$arguments[2],
				'type'=>$name
			);
		if(method_exists(__CLASS__,$name)){
			return call_user_func_array(array(__CLASS__,$name),$arguments);
		}else{
			Debug::toss('No such method');
		}

	}
	///include a file
	/*
	@param	_file	file path
	@param	_vars	[ < name > : < value >, ...]  < array of keyed variables to extract into the file >
	@param	_options	[
			extract: < variables to extract from the file context >
			globals: < string-names of variables to introduce to the file as global variables >
		]

	@return
		if successful
			if _options['extract']
				keyed extracted variable array
			else
				value from the inclusion function (generally `1` if the file does not explicitly use a return statement)
		if not successful
			false


	@Example
		File `bob.php`:
			<?
			$bill = [$bob]
			$bill[] = 'monkey'
			return 'blue'
		Use
			Files::inc('bob.php')
			#< 'blue'

			Files::inc('bob.php',['bob'=>'sue'], ['extract'=>['bill']])
			#< ['sue', 'monkey']



	*/
	private static function inc($_file, $_vars=null, $_options=[]){
		if(is_file($_file)){
			self::logIncluded(true);

			if($_options['globals']){
				foreach($_options['globals'] as $_global){
					global $$_global;
				}
			}
			if($_vars){
				extract($_vars,EXTR_SKIP);#don't overwrite existing
			}

			$_return = include($_file);

			if($_options['extract']){
				$_return = [];
				foreach($_options['extract'] as $_var){
					$_return[$_var] = $$_var;
				}
				return $_return;
			}
			return $_return;
		}
		self::logIncluded(false);
		return false;
	}
	///include a file once
	/**
	@param	file	file path
	@param	globalize	list of strings representing variables to globalize for the included file
	@param	vars	variables to extract for use by the file
	@param	extract	variables to extract from the included file to be returned
	@return	true or extracted varaibles if file successfully included, else false
	*/
	private static function incOnce($_file, $_vars=null, $_options=[]){
		if(is_file($_file)){
			self::logIncluded(true);
			if($_options['globals']){
				foreach($_options['globals'] as $_global){
					global $$_global;
				}
			}
			if($_vars){
				extract($_vars,EXTR_SKIP);#don't overwrite existing
			}

			$_return = include_once($_file);

			if($_options['extract']){
				$_return = [];
				foreach($_options['extract'] as $_var){
					$_return[$_var] = $$_var;
				}
				return $_return;
			}
			return $_return;
		}
		self::logIncluded(false);
		return false;
	}
	///require a file
	/**
	see self::inc
	@return	on failure, runs Debug::toss
	*/
	private static function req($_file, $_vars=null, $_options=[]){
		if(is_file($_file)){
			self::logIncluded(true);
			if($_options['globals']){
				foreach($_options['globals'] as $_global){
					global $$_global;
				}
			}
			if($_vars){
				extract($_vars,EXTR_SKIP);#don't overwrite existing
			}

			$_return = include($_file);

			if($_options['extract']){
				$_return = [];
				foreach($_options['extract'] as $_var){
					$_return[$_var] = $$_var;
				}
				return $_return;
			}
			return $_return;
		}
		self::logIncluded(false);
		Debug::toss('Could not include file "'.$_file.'"');
	}
	///require a file once
	/**
	see self::inc
	@return	on failure, runs Debug::toss
	*/
	private static function reqOnce($_file, $_vars=null, $_options=[]){
		if(is_file($_file)){
			self::logIncluded(true);
			if($_options['globals']){
				foreach($_options['globals'] as $_global){
					global $$_global;
				}
			}
			if($_vars){
				extract($_vars,EXTR_SKIP);#don't overwrite existing
			}

			$_return = include_once($_file);

			if($_options['extract']){
				$_return = [];
				foreach($_options['extract'] as $_var){
					$_return[$_var] = $$_var;
				}
				return $_return;
			}
			return $_return;
		}
		self::logIncluded(false);
		Debug::toss('Could not include file "'.$_file.'"');
	}
	private static function logIncluded($result){
		self::$currentInclude['result'] = $result;
		self::$included[] = self::$currentInclude;
	}
	///get all the included files included by functions from this class
	static function getIncluded(){
		return self::$included;
	}
	///remove relative parts of a path that could be used for exploits
	static function removeRelative($path){
		return preg_replace(array('@((\.\.)(/|$))+@','@//+@'),'/',$path);
	}
	///backwards compatibility
	static function fileList(){
		return call_user_func_array(['self','scan'],func_get_args());
	}
	///prefix in case desire to use relative path or absolute path
	/**
	@param	options {
		mimic:<return paths prefixes with @:dir>,
		prefix:<prefix returned file paths with>,
		filter:<function to filter returned paths>,
		ghost:<don't error on non-existent>}
	*/
	static function scan($dir,$options=[]){
		if(!$options['prefix'] && $options['mimic']){
			$options['prefix'] = $dir;	}

		if(isset($options['maxDepth']) && $options['maxDepth'] == 0){
			return [];	}

		$realPath = realpath($dir);
		if(!$realPath){
			if($options['ghost']){
				return [];
			}else{
				Debug::toss('No such directory');	}
		}
		$realPath .= '/';
		$files = array();
		foreach(scandir($realPath) as $v){
			if($v != '.' && $v != '..'){
				$item_path = $realPath.$v;
				if(is_dir($item_path)){
					if($options['progress']){
						stderr($item_path."\n");
					}
					if(!$options['filter_folder'] || $options['filter_folder']($options['prefix'].$v, ['name'=>$v, 'path'=>$item_path])){
						$newOptions = array_merge($options,['prefix'=>$options['prefix'].$v.'/']);
						if(isset($newOptions['maxDepth'])){
							$newOptions['maxDepth']--;
						}
						$newFiles = self::scan($item_path,$newOptions);
						$files = Arrays::merge($files,$newFiles);
					}
				}else{
					if(!$options['filter'] || $options['filter']($options['prefix'].$v, ['name'=>$v, 'path'=>$item_path])){
						$files[] = $options['prefix'].$v;
					}
				}
			}
		}
		return $files;
	}
	/**
	@note	the normal mkdir does not apply mode to created parent directories.  This does
	@note	php file handling functions consistenly fail me (mode not set, etc), so not using them
	*/
	static function mkdir($path,$mode=0777){
		$parts = explode('/',$path);
		while(!is_dir(implode('/',$parts))){
			$missing[] = array_pop($parts);
		}
		if($missing){
			exec('mkdir -p '.$path);
			$parts[] = array_pop($missing);
			self::chmod(implode('/',$parts),$mode);	}
	}
	static function chmod($path,$mode=0777){
		if(is_int($mode)){
			$mode = decoct($mode);//< makes string 777, instead of 511
		}
		exec('chmod -R '.$mode.' '.$path);
	}
	static function mime($path){
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $path);
		finfo_close($finfo);

		/* file -ib command does not determine what type of text a text file is.  So, use the type that the browser was expecting*/
		if($_SERVER['HTTP_ACCEPT'] && substr($mime,0,5) == 'text/'){
			$mime = array_shift(explode(',',$_SERVER['HTTP_ACCEPT']));
		}
		return $mime;
	}
	///take a mimetype and return the extension for it
	static function mimeExt($mime){
		$mime = explode('/',$mime);
		$mimes = array(
				'jpeg'=>'jpg',
				'javascript'=>'js'
			);
		if(key_exists($mime[1],$mimes)){
			return $mimes[$mime[1]];
		}
		return $mime[1];
	}
	///to handle various contents and to allow writing to folders that don't previously exist
	static function write($location,$content){
		$directory = dirname($location);
		if(!is_dir($directory)){
			mkdir($directory,0777,true);
		}
		file_put_contents($location,$content);
	}

	///get the size of a directory
	/**
	@param	dir	path to a directory
	*/
	static function dirSize($dir){//directory size
		if(is_array($subs=scandir($dir))){
			$size = 0;
			$subs=array_slice($subs,2,count($subs)-2);
			if($sub_count=count($subs)){
				for($i=0;$i<$sub_count;$i++){
					$temp_sub=$dir.'/'.$subs[$i];
					if(is_dir($temp_sub)){
						$size+=self::dirSize($temp_sub);
					}else{
						$size+=filesize($temp_sub);
					}
				}
			}
			return $size;
		}
	}

	///does not care whether relative folders exist (unlike file include functions).  Does not work when |relative-to object| not given
	///Found here b/c can be applied to HTTP paths, not just file paths
	static function absolutePath($pathParts){
		if(!is_array($pathParts)){
			$pathParts = explode('/',$pathParts);
		}
		$absParts = array();
		foreach($pathParts as $pathPart){
			if($pathPart == '..'){
				array_pop($absParts);
			}elseif($pathPart != '.'){
				$absParts[] = $pathPart;
			}
		}
		return implode('/',$absParts);
	}

}


