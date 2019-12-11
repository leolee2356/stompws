<?php
	
	
	namespace StompWS;
	
	
	use http\Exception\RuntimeException;
	use WebSocket\Client as WebSocketClient;
	
	class StompWSClient
	{
		const BYTE = ['LF'=> '\n', 'NULL'=> '\u0000'];
		const EVENT_CONNECT = 'CONNECT';
		const EVENT_SUBSCRIBE = 'SUBSCRIBE';
		const VERSIONS = '1.0,1.1';
		private $callback = [];
		public $client;
		protected $connected = false;
		
		
		public function __construct($url,$option = ['timeout'=>60])
		{
			$this->client = new WebSocketClient($url,$option);
		}
		
		public function connect(){
			$header = [
				//'host' => 'koudai.17itou.com',
                'accept-version' => self::VERSIONS,
                'heart-beat' => '10000,10000'
			];
			$this->transmit(self::EVENT_CONNECT,$header);
		}
		
		public function subscribe($path,$callback){
			if(!is_callable($callback))
			{
				throw new \RuntimeException('参数错误,不是回调函数',-1);
			}
			$header = [
				'id' => 'sub-1',
                'ack' => 'client',
                'destination' => $path
			];
			$this->callback[$path] = $callback;
			$this->transmit(self::EVENT_SUBSCRIBE,$header);
		}
		
		public function send($destination, $message)
		{
			return $this->client->send($destination.$message);
		}
		
		
		
		public function unsubscribe($destination)
		{
			
		}
		
		public function disconnect()
		{
		
		}
		
		public function onMessage(){
			$msg =  $this->client->receive();
			if( '' == $msg)
			{
				throw new RuntimeException('接收网络请求异常',-2);
			}
			//$msg = 'a["MESSAGE\ndestination:/topic/soccer/match/list/live/2019-12-10/90/2\ncontent-type:text/plain;charset=UTF-8\nsubscription:sub-0\nmessage-id:5itnayyb-601003749\ncontent-length:557\n\n{\"code\":0,\"data\":[{\"matchInfo\":{\"scoreOverTime\":\"\",\"homeRedCount\":\"0\",\"awayRedCount\":\"0\",\"minutes\":\"51\",\"matchTime\":\"2019-12-03 00:30:00\",\"source\":\"1\",\"matchInProgressCool\":\"1\",\"statusNameLotteryDesc\":\"进行中\",\"scoreNormal\":\"0:0\",\"matchInProgress\":\"1\",\"homeYellowCount\":\"0\",\"coolMinutes\":\"51\",\"awayYellowCount\":\"1\",\"statusName\":\"下半场\",\"scorePenalty\":\"\",\"scoreHalf\":\"0:0\",\"matchId\":\"18382329\",\"scoreFull\":\"0:0\",\"statusCode\":\"7\",\"goals\":[]},\"issueInfo\":{\"issue\":\"2019-12-02\",\"num\":\"001\",\"isFinished\":\"\"}}],\"msg\":\"数据正常\",\"stamp\":\"1575308320438\"}\u0000"]';
			$msg = $this->parseMessage($msg);
			if('CONNECTED' == $msg['event']){
				$this->connected = true;
			}
			if('MESSAGE' == $msg['event'] && isset($this->callback[$msg['header']['destination']])){
				$this->callback[$msg['header']['destination']]($msg['body']);
			}
			
		}
		public function parseMessage($msg){
			
			if('a'!= $msg{0})
			{
				return array ('event' => $msg, 'header' => [],'body'=>null);
			}
			$lines = explode('\n',trim($msg,'a["]'));
            $command = $lines[0];
            $header = [];
            //var_dump($lines);
        # get all header
            $i = 1;
			# get key, value from raw header
        while ($lines[$i] != ''){
            	$headerC = explode(':',$lines[$i]);
            	$header[$headerC[0]] = $headerC[1];
            	$i++;
        }
            $i++;
			if (self::BYTE['NULL'] == $lines[$i]){
				$body = null;
			} else {
				$body = rtrim($lines[$i],self::BYTE['NULL']);
			}
			return array ('event' => $command, 'header' => $header,'body'=>$body);
		}
		
		public function transmit($event, $header, $msg = null)
		{
			//var_dump($event,$header);
			# Contruct the frame
			$lines = new \ArrayObject();
			$lines->append($event . self::BYTE['LF']);
			
			# add header
			foreach ($header as $key => $v) {
				$lines->append($key . ':' . $v . self::BYTE['LF']);
			}
			$lines->append(self::BYTE['LF']);
			# add message, if any
			if (!is_null($msg)){
				$lines->append($msg);
			}
			# terminate with null octet
			$lines->append(self::BYTE['NULL']);
			
			$frame = '["'.implode($lines->getArrayCopy(), '').'"]';
			//var_dump($frame);
			# transmit over ws
			$this->client->send($frame);
		}
	}
