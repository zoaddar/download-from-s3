<?php
use Aws\S3\Exception\S3Exception;

class Download_csv{
	private $s3_client;
	public $s3_bucket;
	public $s3_folder  = "";
	public $start_date = "";
	public $end_date   = "";
	public $save_to    = "";

	function __construct($region, $version, $s3_key, $s3_secret){
		$this->s3_client = $this->s3Client($region, $version, $s3_key, $s3_secret);
	}

	public function fromS3(){
		try {
			$fileList = $this->getFileList();

			if(count($fileList) > 0){
				foreach($fileList as $file){
					$finfo = explode('/', $file);
					$filename = $finfo[count($finfo)-1];
					$save_to = rtrim($this->save_to.'/'.str_replace($filename,'',$file), '/');

					$result = $this->s3_client->getObject(array(
						'Bucket' => $this->s3_bucket,
						'Key'    => $file,
					));
					$this->save_file($result['Body'], $save_to, $filename);
				}
			}
		} catch (S3Exception $e) {
			echo $e->getMessage() . PHP_EOL;
		}
	}

	private function getFileList(){
		$objects =  $this->s3_client->getPaginator('ListObjects', [
			'Bucket' => $this->s3_bucket,
			'Prefix' => $this->s3_folder
		]);

		$fileList = [];
		foreach($objects as $result){
			if(isset($result['Contents'])){
				foreach($result['Contents'] as $object){
					$cdate = strtotime($object['LastModified']->format(\DateTime::ISO8601));
					$sdate = $this->start_date != "" ? strtotime($this->start_date) : 0;
					$edate = $this->end_date != "" ? strtotime($this->end_date.' 23:59:59') : 0;

					if($cdate >= $sdate && $cdate <= $edate){
						$fileList[] = $object['Key'];
					}
					if($sdate == 0 && $edate == 0){
						$fileList[] = $object['Key'];
					}
				}
			}
		}
		return $fileList;
	}

	private function s3Client($region, $version, $s3_key, $s3_secret){
		return new Aws\S3\S3Client([
			'region'  => $region,
			'version' => $version,
			'credentials' => [
				'key'    => $s3_key,
				'secret' => $s3_secret,
			]
		]);
	}

	# File save
	private function save_file($file_data, $dir_to_save="", $save_as_file_name="just_uploaded", $allowed_file_extentions=array()){
		$dirs = explode("/", $dir_to_save);

		$dir_to_save = "";
		for($i=0; $i<count($dirs); $i++){
			$dir_to_save .= $dirs[$i]."/";
			if(!is_dir($dir_to_save)){
				exec("mkdir $dir_to_save");
				exec("chmod 755 $dir_to_save");
			}
		}
		# ADD START
		if($dir_to_save=="/"){
			$dir_to_save = "";
		}
		# explode file extension
		$parts = explode(".", $save_as_file_name);
		$ext = "";
		if(count($parts) != 0){
			$ext = strtolower($parts[count($parts)-1]);
		}
		# check file extension
		if(count($allowed_file_extentions) > 0){
			if(!in_array($ext, $allowed_file_extentions)){
				return array(false, "Invalid file type [$ext].");
			}
		}

		$save_as_file = "${dir_to_save}${save_as_file_name}";
		if(is_dir($dir_to_save)){
			$fp = fopen($save_as_file, "w");
			fwrite($fp, $file_data);
			fclose($fp);
			exec("chmod -R 777 $save_as_file");
			return array(true, $save_as_file);
		} else{
			return array(false, "Failed to save {$save_as_file_name} into {$dir_to_save}");
		}
	}
}