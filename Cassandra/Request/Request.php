<?php
namespace Cassandra\Request;
use Cassandra\Protocol\Frame;
use Cassandra\Type;

class Request implements Frame{

	const CONSISTENCY_ANY = 0x0000;
	const CONSISTENCY_ONE = 0x0001;
	const CONSISTENCY_TWO = 0x0002;
	const CONSISTENCY_THREE = 0x0003;
	const CONSISTENCY_QUORUM = 0x0004;
	const CONSISTENCY_ALL = 0x0005;
	const CONSISTENCY_LOCAL_QUORUM = 0x0006;
	const CONSISTENCY_EACH_QUORUM = 0x0007;
	const CONSISTENCY_SERIAL = 0x0008;
	const CONSISTENCY_LOCAL_SERIAL = 0x0009;
	const CONSISTENCY_LOCAL_ONE = 0x000A;
	
	/**
	 * @var int
	 */
	protected $version = 0x03;
	
	/**
	 * @var int
	 */
	protected $opcode;
	
	/**
	 * @var int
	 */
	protected $stream = 0;
	
	/**
	 * @var int
	 */
	protected $flags = 0;
	
	/**
	 * @param int $opcode
	 * @param int $stream
	 * @param int $flags
	 */
	public function __construct($opcode, $stream = 0, $flags = 0) {
		$this->opcode = $opcode;
		$this->stream = $stream;
		$this->flags = $flags;
	}
		
	public function getVersion(){
		return $this->version;
	}
	
	public function getFlags(){
		return $this->flags;
	}
	
	public function getStream(){
		return $this->stream;
	}
	
	public function getOpcode(){
		return $this->opcode;
	}
	
	public function getBody(){
		return '';
	}
	
	public function setStream($stream){
		$this->stream = $stream;
	}
	
	/**
	 * @return string
	 */
	public function __toString(){
		$body = $this->getBody();
		return pack(
				'CCnCN',
				$this->version,
				$this->flags,
				$this->stream,
				$this->opcode,
				strlen($body)
		) . $body;
	}
	
	/**
	 * 
	 * @param array $values
	 * @throws Exception
	 * @return string
	 */
	public static function valuesBinary(array $values) {
		$valuesBinary = pack('n', count($values));
		$values = array_change_key_case($values);
		
		foreach($values as $name => $value) {
			if ($value instanceof Type\Base){
				$binary = $value->getBinary();
			}
			elseif ($value === null){
				$binary = null;
			}
			elseif (is_int($value)){
				$binary = pack('N', $value);
			}
			elseif (is_string($value)){
				$binary = $value;
			}
			elseif (is_bool($value)){
				$binary = $value ? chr(1) : chr(0);
			}
			else{
				throw new Exception('Unknown type.');
			}
			
			if ($binary === null)
				$valuesBinary .= pack('N', 4294967295);
			else 
				$valuesBinary .= pack('N', strlen($binary)) . $binary;
		}
		
		return $valuesBinary;
	}
	
	/**
	 * 
	 * @param array $values
	 * @param array $columns
	 * @return array
	 */
	public static function strictTypeValues(array $values, array $columns) {
		$strictTypeValues = array();
		foreach($columns as $index => $column) {
			$key = isset($values[$column['name']]) ? $column['name'] : $index;
			
			if (!isset($values[$key])){
				$strictTypeValues[$key] = null;
			}
			elseif($values[$key] instanceof Type\Base){
				$strictTypeValues[$key] = $values[$key];
			}
			else{
				$strictTypeValues[$key] = Type\Base::getTypeObject($column['type'], $values[$key]);
			}
		}
		
		return $strictTypeValues;
	}
	
	/**
	 * 
	 * @param array $options
	 * @return string
	 */
	public static function queryParameters($consistency, array $values = [], array $options = []){
		$flags = 0;
		$optional = '';
		
		if (!empty($values)) {
			$flags |= Query::FLAG_VALUES;
			$optional .= Request::valuesBinary($values);
		}
		
		// TODO realize all optional parameters
		
		if (isset($options['serial_consistency'])) {
			$flags |= Query::FLAG_WITH_SERIAL_CONSISTENCY;
			$optional .= pack('n', $options['serial_consistency']);
		}
		
		return pack('n', $consistency) . pack('C', $flags) . $optional;
	}
}
