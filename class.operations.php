<?php
/*
php5 class (will not work in php4)
for detecting bitrate and duration of regular mp3 files (not VBR files)
*/


define("DB_HOST", "localhost");
define("DB_USER", "erkan_dbuser");
define("DB_PASS", "Au74543S");
define("DB_NAME", "erkandb");
define("MP3_FOLDER", "uploads/");




//-----------------------------------------------------------------------------
class mp3file
{
    protected $block;
    protected $blockpos;
    protected $blockmax;
    protected $blocksize;
    protected $fd;
    protected $bitpos;
    protected $mp3data;
    public function __construct($filename)
    {
        $this->powarr  = array(0=>1,1=>2,2=>4,3=>8,4=>16,5=>32,6=>64,7=>128);
        $this->blockmax= 1024;
        
        $this->mp3data = array();
        $this->mp3data['Filesize'] = filesize($filename);

        $this->fd = fopen($filename,'rb');
        $this->prefetchblock();
        $this->readmp3frame();
    }
    public function __destruct()
    {
    	if($this->fd)
       		fclose($this->fd);
    }
    //-------------------
    public function get_metadata()
    {
        return $this->mp3data;
    }
    protected function readmp3frame()
    {
        $iscbrmp3=true;
        if ($this->startswithid3())
            $this->skipid3tag();
        else if ($this->containsvbrxing())
        {
            $this->mp3data['Encoding'] = 'VBR';
            $iscbrmp3=false;
        }
        else if ($this->startswithpk())
        {
            $this->mp3data['Encoding'] = 'Unknown';
            $iscbrmp3=false;
        }
    
        if ($iscbrmp3)
        {
            $i = 0;
            $max=5000;
            //look in 5000 bytes... 
            //the largest framesize is 4609bytes(256kbps@8000Hz  mp3)
            for($i=0; $i<$max; $i++)
            {
                //looking for 1111 1111 111 (frame synchronization bits)                
                if ($this->getnextbyte()==0xFF)
                    if ($this->getnextbit() && $this->getnextbit() && $this->getnextbit())
                        break;
            }
            if ($i==$max)
                $iscbrmp3=false;
        }
    
        if ($iscbrmp3)
        {
            $this->mp3data['Encoding'         ] = 'CBR';
            $this->mp3data['MPEG version'     ] = $this->getnextbits(2);
            $this->mp3data['Layer Description'] = $this->getnextbits(2);
            $this->mp3data['Protection Bit'   ] = $this->getnextbits(1);
            $this->mp3data['Bitrate Index'    ] = $this->getnextbits(4);
            $this->mp3data['Sampling Freq Idx'] = $this->getnextbits(2);
            $this->mp3data['Padding Bit'      ] = $this->getnextbits(1);
            $this->mp3data['Private Bit'      ] = $this->getnextbits(1);
            $this->mp3data['Channel Mode'     ] = $this->getnextbits(2);
            $this->mp3data['Mode Extension'   ] = $this->getnextbits(2);
            $this->mp3data['Copyright'        ] = $this->getnextbits(1);
            $this->mp3data['Original Media'   ] = $this->getnextbits(1);
            $this->mp3data['Emphasis'         ] = $this->getnextbits(1);
            $this->mp3data['Bitrate'          ] = mp3file::bitratelookup($this->mp3data);
            $this->mp3data['Sampling Rate'    ] = mp3file::samplelookup($this->mp3data);
            $this->mp3data['Frame Size'       ] = mp3file::getframesize($this->mp3data);
            $this->mp3data['Length'           ] = mp3file::getduration($this->mp3data,$this->tell2());
            $this->mp3data['Length mm:ss'     ] = mp3file::seconds_to_mmss($this->mp3data['Length']);
            
            if ($this->mp3data['Bitrate'      ]=='bad'     ||
                $this->mp3data['Bitrate'      ]=='free'    ||
                $this->mp3data['Sampling Rate']=='unknown' ||
                $this->mp3data['Frame Size'   ]=='unknown' ||
                $this->mp3data['Length'     ]=='unknown')
            $this->mp3data = array('Filesize'=>$this->mp3data['Filesize'], 'Encoding'=>'Unknown');
        }
        else
        {
            if(!isset($this->mp3data['Encoding']))
                $this->mp3data['Encoding'] = 'Unknown';
        }
    }
    protected function tell()
    {
        return ftell($this->fd);
    }
    protected function tell2()
    {
        return ftell($this->fd)-$this->blockmax +$this->blockpos-1;
    }
    protected function startswithid3()
    {
        return ($this->block[1]==73 && //I
                $this->block[2]==68 && //D
                $this->block[3]==51);  //3
    }
    protected function startswithpk()
    {
        return ($this->block[1]==80 && //P
                $this->block[2]==75);  //K
    }
    protected function containsvbrxing()
    {
        //echo "<!--".$this->block[37]." ".$this->block[38]."-->";
        //echo "<!--".$this->block[39]." ".$this->block[40]."-->";
        return(
               ($this->block[37]==88  && //X 0x58
                $this->block[38]==105 && //i 0x69
                $this->block[39]==110 && //n 0x6E
                $this->block[40]==103)   //g 0x67
/*               || 
               ($this->block[21]==88  && //X 0x58
                $this->block[22]==105 && //i 0x69
                $this->block[23]==110 && //n 0x6E
                $this->block[24]==103)   //g 0x67*/
              );   

    } 
    protected function debugbytes()
    {
        for($j=0; $j<10; $j++)
        {
            for($i=0; $i<8; $i++)
            {
                if ($i==4) echo " ";
                echo $this->getnextbit();
            }
            echo "<BR>";
        }
    }
    protected function prefetchblock()
    {
        $block = fread($this->fd, $this->blockmax);
        $this->blocksize = strlen($block);
        $this->block = unpack("C*", $block);
        $this->blockpos=0;
    }
    protected function skipid3tag()
    {
        $bits=$this->getnextbits(24);//ID3
        $bits.=$this->getnextbits(24);//v.v flags

        //3 bytes 1 version byte 2 byte flags
        $arr = array();
        $arr['ID3v2 Major version'] = bindec(substr($bits,24,8));
        $arr['ID3v2 Minor version'] = bindec(substr($bits,32,8));
        $arr['ID3v2 flags'        ] = bindec(substr($bits,40,8));
        if (substr($bits,40,1)) $arr['Unsynchronisation']=true;
        if (substr($bits,41,1)) $arr['Extended header']=true;
        if (substr($bits,42,1)) $arr['Experimental indicator']=true;
        if (substr($bits,43,1)) $arr['Footer present']=true;

        $size = "";
        for($i=0; $i<4; $i++)
        {
            $this->getnextbit();//skip this bit, should be 0
            $size.= $this->getnextbits(7);
        }

        $arr['ID3v2 Tags Size']=bindec($size);//now the size is in bytes;
        if ($arr['ID3v2 Tags Size'] - $this->blockmax>0)
        {
            fseek($this->fd, $arr['ID3v2 Tags Size']+10 );
            $this->prefetchblock();
            if (isset($arr['Footer present']) && $arr['Footer present'])
            {
                for($i=0; $i<10; $i++)
                    $this->getnextbyte();//10 footer bytes
            }
        }
        else
        {
            for($i=0; $i<$arr['ID3v2 Tags Size']; $i++)
                $this->getnextbyte();
        }
    }

    protected function getnextbit()
    {
        if ($this->bitpos==8)
            return false;

        $b=0;
        $whichbit = 7-$this->bitpos;
        $mult = $this->powarr[$whichbit]; //$mult = pow(2,7-$this->pos);
        $b = $this->block[$this->blockpos+1] & $mult;
        $b = $b >> $whichbit;
        $this->bitpos++;

        if ($this->bitpos==8)
        {
            $this->blockpos++;
                
            if ($this->blockpos==$this->blockmax) //end of block reached
            {
                $this->prefetchblock();
            }
            else if ($this->blockpos==$this->blocksize) 
            {//end of short block reached (shorter than blockmax)
                return;//eof 
            }
            
            $this->bitpos=0;
        }
        return $b;
    }
    protected function getnextbits($n=1)
    {
        $b="";
        for($i=0; $i<$n; $i++)
            $b.=$this->getnextbit();
        return $b;
    }
    protected function getnextbyte()
    {
        if ($this->blockpos>=$this->blocksize)
            return;

        $this->bitpos=0;
        $b=$this->block[$this->blockpos+1];
        $this->blockpos++;
        return $b;
    }
    //-----------------------------------------------------------------------------
    public static function is_layer1(&$mp3) { return ($mp3['Layer Description']=='11'); }
    public static function is_layer2(&$mp3) { return ($mp3['Layer Description']=='10'); }
    public static function is_layer3(&$mp3) { return ($mp3['Layer Description']=='01'); }
    public static function is_mpeg10(&$mp3)  { return ($mp3['MPEG version']=='11'); }
    public static function is_mpeg20(&$mp3)  { return ($mp3['MPEG version']=='10'); }
    public static function is_mpeg25(&$mp3)  { return ($mp3['MPEG version']=='00'); }
    public static function is_mpeg20or25(&$mp3)  { return ($mp3['MPEG version']{1}=='0'); }
    //-----------------------------------------------------------------------------
    public static function bitratelookup(&$mp3)
    {
        //bits               V1,L1  V1,L2  V1,L3  V2,L1  V2,L2&L3
        $array = array();
        $array['0000']=array('free','free','free','free','free');
        $array['0001']=array(  '32',  '32',  '32',  '32',   '8');
        $array['0010']=array(  '64',  '48',  '40',  '48',  '16');
        $array['0011']=array(  '96',  '56',  '48',  '56',  '24');
        $array['0100']=array( '128',  '64',  '56',  '64',  '32');
        $array['0101']=array( '160',  '80',  '64',  '80',  '40');
        $array['0110']=array( '192',  '96',  '80',  '96',  '48');
        $array['0111']=array( '224', '112',  '96', '112',  '56');
        $array['1000']=array( '256', '128', '112', '128',  '64');
        $array['1001']=array( '288', '160', '128', '144',  '80');
        $array['1010']=array( '320', '192', '160', '160',  '96');
        $array['1011']=array( '352', '224', '192', '176', '112');
        $array['1100']=array( '384', '256', '224', '192', '128');
        $array['1101']=array( '416', '320', '256', '224', '144');
        $array['1110']=array( '448', '384', '320', '256', '160');
        $array['1111']=array( 'bad', 'bad', 'bad', 'bad', 'bad');
        
        $whichcolumn=-1;
        if      (mp3file::is_mpeg10($mp3) && mp3file::is_layer1($mp3) )//V1,L1
            $whichcolumn=0;
        else if (mp3file::is_mpeg10($mp3) && mp3file::is_layer2($mp3) )//V1,L2
            $whichcolumn=1;
        else if (mp3file::is_mpeg10($mp3) && mp3file::is_layer3($mp3) )//V1,L3
            $whichcolumn=2;
        else if (mp3file::is_mpeg20or25($mp3) && mp3file::is_layer1($mp3) )//V2,L1
            $whichcolumn=3;
        else if (mp3file::is_mpeg20or25($mp3) && (mp3file::is_layer2($mp3) || mp3file::is_layer3($mp3)) )
            $whichcolumn=4;//V2,   L2||L3 
        
        if (isset($array[$mp3['Bitrate Index']][$whichcolumn]))
            return $array[$mp3['Bitrate Index']][$whichcolumn];
        else 
            return "bad";
    }
    //-----------------------------------------------------------------------------
    public static function samplelookup(&$mp3)
    {
        //bits               MPEG1   MPEG2   MPEG2.5
        $array = array();
        $array['00'] =array('44100','22050','11025');
        $array['01'] =array('48000','24000','12000');
        $array['10'] =array('32000','16000','8000');
        $array['11'] =array('res','res','res');
        
        $whichcolumn=-1;
        if      (mp3file::is_mpeg10($mp3))
            $whichcolumn=0;
        else if (mp3file::is_mpeg20($mp3))
            $whichcolumn=1;
        else if (mp3file::is_mpeg25($mp3))
            $whichcolumn=2;
        
        if (isset($array[$mp3['Sampling Freq Idx']][$whichcolumn]))
            return $array[$mp3['Sampling Freq Idx']][$whichcolumn];
        else 
            return 'unknown';
    }
    //-----------------------------------------------------------------------------
    public static function getframesize(&$mp3)
    {
        if ($mp3['Sampling Rate']>0)
        {
            return  ceil((144 * $mp3['Bitrate']*1000)/$mp3['Sampling Rate']) + $mp3['Padding Bit'];
        }
        return 'unknown';
    }
    //-----------------------------------------------------------------------------
    public static function getduration(&$mp3,$startat)
    {
        if ($mp3['Bitrate']>0)
        {
            $KBps = ($mp3['Bitrate']*1000)/8;
            $datasize = ($mp3['Filesize'] - ($startat/8));
            $length = $datasize / $KBps;
            return sprintf("%d", $length);
        }
        return "unknown";
    }
    //-----------------------------------------------------------------------------
    public static function seconds_to_mmss($duration)
    {
        return sprintf("%d:%02d", ($duration /60), $duration %60 );
    }
}



Class Operations extends mp3file{
	
	public $mp3FileDuration = 0;
	public $mp3FileName = '';
	public $mp3FileScreenName = ''; 
	public $mp3FileUploadLocation = ''; 
	private $link_id = null;
	public $dbConf = '';
	public $affected_rows = null;
	public $dbtables = '';
	
	function __construct(){
		$dbConfig = func_get_arg(0);
		$this->dbConfig = $dbConfig['DB'];
		$this->mp3FileUploadLocation = $dbConfig['MP3_UPLOAD_FOLDER'];
		$this->dbtables = $dbConfig['TABLES'];
		$this->db_connection();
	}
	
	function db_connection(){
		$this->link_id = @mysql_connect($this->dbConfig['DB_HOST'], $this->dbConfig['DB_USER'], $this->dbConfig['DB_PASS']);
		@mysql_select_db($this->dbConfig['DB_NAME'], $this->link_id);
	}
	
	function query($statement){
		
		$query_id = @mysql_query($statement, $this->link_id);
		$this->affected_rows = mysql_affected_rows();
		if (!$query_id) { 
			return false;
		}
		else
			return $query_id;
		
	}
	
	function mysql_fetch($result){
		
		return @mysql_fetch_array($result); 
		
	}
	
	function file_validation($fileName){
		if($fileName['error'])
		{
			return false;
		}
		else
			return true;
	}
	
	function file_upload($fileName){
		$FileName			= strtolower($fileName['name']); //uploaded file name
		$ImageExt			= substr($FileName, strrpos($FileName, '.')+1); //file extension
		$FileType			= $fileName['type']; //file type
		$FileSize			= $fileName["size"]; //file size 
		$NewFileName 		= md5(uniqid (rand (),true)).".".$ImageExt;
		
		
		if(move_uploaded_file($fileName["tmp_name"], MP3_FOLDER . $NewFileName ))
	   	{
			 return array($NewFileName, $FileName);
			
	   	}else{
	   		return false;
	   	}
		
	}
	
	function save_file_info_in_DB(){
		 
		$uploadedmp3FileInfo = func_get_arg(0); 
		$uploadedThumbnailFileInfo = func_get_arg(1);
		$mp3TDescription = addslashes(func_get_arg(2));
		$statement = "INSERT INTO `".$this->dbtables['TBL_MP3FILES']."` (  `file_name`, `file_screen_name`, `file_description`, `thumbnail_file`, `mp3_duration`) VALUES ( '".$uploadedmp3FileInfo[0]."', '".$uploadedmp3FileInfo[1]."', '$mp3TDescription', '".$uploadedThumbnailFileInfo[0]."', '".$this->mp3FileDuration."')";
		return $this->query($statement);
	}
	
	function save_mp3(){
		$mp3File = func_get_arg(0);
		$mp3ThumbnailImage = func_get_arg(1);
		$mp3TDescription = func_get_arg(2);
		//print_r($mp3ThumbnailImage);
		//if($this->file_validation($mp3File) == false) echo "mp3 error";
		if($this->file_validation($mp3ThumbnailImage) == false) echo "thumbnail error";
		
		//Validating and saving data
		if($this->file_validation($mp3ThumbnailImage)){
			
			$uploadedmp3FileInfo = $mp3File;
			$uploadedThumbnailFileInfo = $this->file_upload($mp3ThumbnailImage);
			
			//Finally saving file in DB
			if($uploadedmp3FileInfo != false && $uploadedThumbnailFileInfo != false){
				//Getting file duration
				 
				$this->mp3FileDuration = $mp3File[2]; 
				
				$data_saved = $this->save_file_info_in_DB($uploadedmp3FileInfo, $uploadedThumbnailFileInfo, $mp3TDescription);
				if($data_saved != false)
					return "upload successfully done.";
				else{
					if($uploadedmp3FileInfo != false){
						@unlink($this->mp3FileUploadLocation . $uploadedmp3FileInfo[0] );
					}
					if($uploadedThumbnailFileInfo != false){
						@unlink($this->mp3FileUploadLocation . $uploadedThumbnailFileInfo[0] );
					} 
					return "sorry data save operation failed.";
				}
			}
			else{
				if($uploadedmp3FileInfo != false){
					@unlink($this->mp3FileUploadLocation . $uploadedmp3FileInfo[0] );
				}
				if($uploadedThumbnailFileInfo != false){
					@unlink($this->mp3FileUploadLocation . $uploadedThumbnailFileInfo[0] );
				}
			}
		}
		else{
			return "File upload error.";
		} 
		
	}
}

class outputXML{
	 
	public $mp3FileUploadLocation = ''; 
	private $link_id = null;
	public $dbConf = '';
	public $dbtables = '';
	
	function __construct(){
		$dbConfig = func_get_arg(0);
		$this->dbConfig = $dbConfig['DB'];
		$this->mp3FileUploadLocation = $dbConfig['MP3_UPLOAD_FOLDER'];
		$this->dbtables = $dbConfig['TABLES'];
		$this->db_connection();
	}
	
	function db_connection(){
		$this->link_id = @mysql_connect($this->dbConfig['DB_HOST'], $this->dbConfig['DB_USER'], $this->dbConfig['DB_PASS']);
		@mysql_select_db($this->dbConfig['DB_NAME'], $this->link_id);
	}
	
	function query($statement){
		
		$query_id = mysql_query($statement, $this->link_id);
	
		if (!$query_id) { 
			return false;
		}
		else
			return $query_id;
		
	}
	
	function getXML(){ 
		
		$namespace = "http://www.itunes.com/dtds/podcast-1.0.dtd";
		$xml = new SimpleXMLElement('<rss xmlns:itunes="'.$namespace.'"/>');
		$xml->addAttribute('version', '2.0');  
		
		$channel = $xml->addChild('channel');
		
		
		$channel->addChild("title", "Lorem Ipsum Dolor Sit Amet");
		$channel->addChild("link", "http://www.erkanyavas.com");
		$channel->addChild("language", "en-us");
		$channel->addChild("copyright", "Lorem Ipsum Dolor Sit Amet");
		$channel->addChild("subtitle", "Lorem Ipsum Dolor Sit Amet", $namespace);
		$channel->addChild("author", "Erkan Yavas", $namespace);
		$channel->addChild("summary", "Lorem Ipsum Dolor Sit Amet", $namespace);
		$channel->addChild("description", "Lorem Ipsum Dolor Sit Amet");
		$owner = $channel->addChild("owner", "", $namespace);
			$owner->addChild("name", "John Doe", $namespace);
			$owner->addChild("email", "john.doe@example.com", $namespace);
		$image = $channel->addChild("image", "", $namespace);
			$image->addAttribute('href', 'http://www.erkanyavas.com/logo.jpg'); 
		$owner = $channel->addChild("category", "", $namespace);
			$owner->addAttribute('href', 'Lorem Ipsum Dolor Sit Amet'); 
			$subcat = $owner->addChild("category", "", $namespace);
			$subcat->addAttribute('text', 'Lorem Ipsum Dolor Sit Amet');
		$cat = $channel->addChild("category", "", $namespace);	
			$cat->addAttribute('text', 'Lorem Ipsum Dolor Sit Amet'); 
		
	
			
		$res = $this->query("SELECT * FROM `".$this->dbtables['TBL_MP3FILES']."` "); 
		if(mysql_affected_rows()>0){
			while($mp3InfoRowDb = mysql_fetch_array($res)){
				$fileSize = '--.--';
				if(file_exists($this->mp3FileUploadLocation.$mp3InfoRowDb['file_name']))
					$fileSize = round((filesize($this->mp3FileUploadLocation.$mp3InfoRowDb['file_name']))/1024, 2);
				$fileSize .= 'M';
				$mp3file = $channel->addChild('item');
				$mp3file->addChild('title', "Lorem Ipsum Dolor Sit Amet");
				$mp3file->addChild("author", "Lorem Ipsum Dolor Sit Amet", $namespace);
				$mp3file->addChild("subtitle", "Lorem Ipsum Dolor Sit Amet", $namespace);
				$mp3file->addChild("summary", stripcslashes($mp3InfoRowDb['file_description']), $namespace);
				$img = $mp3file->addChild("image", "", $namespace);
					$img->addAttribute('href', 'http://www.erkanyavas.com/'.$this->mp3FileUploadLocation.$mp3InfoRowDb['thumbnail_file']); 
				$enclosure = $mp3file->addChild("enclosure");
					$enclosure->addAttribute('url', 'http://www.erkanyavas.com/'.$this->mp3FileUploadLocation.$mp3InfoRowDb['file_screen_name']);
					$enclosure->addAttribute('length',  $fileSize);
					$enclosure->addAttribute('type', 'audio/x-m4a'); 
				
				$mp3file->addChild("guid", "http://www.erkanyavas.com/".$this->mp3FileUploadLocation.$mp3InfoRowDb['file_name']);
				$mp3file->addChild("pubDate", "Wed, 15 Jun 2005 19:00:00 GMT");
				$mp3file->addChild("duration", $mp3InfoRowDb['mp3_duration'], $namespace);
				$mp3file->addChild("keywords", "Wed, 15 Jun 2005 19:00:00 GMT", $namespace);
				if(file_exists(MP3_FOLDER.$mp3InfoRowDb['file_name']))
					$mp3file->addChild("filefound", $fileSize, $namespace);
				 
			}
		} 
	 
		Header('Content-type: text/xml');
		echo $xml->asXML();
		//return $sxe;
	}
}

