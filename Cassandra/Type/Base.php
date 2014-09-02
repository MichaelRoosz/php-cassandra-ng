<?php
namespace Cassandra\Type;

abstract class Base{
	
	const CUSTOM = 0x0000;
	const ASCII = 0x0001;
	const BIGINT = 0x0002;
	const BLOB = 0x0003;
	const BOOLEAN = 0x0004;
	const COUNTER = 0x0005;
	const DECIMAL = 0x0006;
	const DOUBLE = 0x0007;
	const FLOAT = 0x0008;
	const INT = 0x0009;
	const TEXT = 0x000A;
	const TIMESTAMP = 0x000B;
	const UUID = 0x000C;
	const VARCHAR = 0x000D;
	const VARINT = 0x000E;
	const TIMEUUID = 0x000F;
	const INET = 0x0010;
	const COLLECTION_LIST = 0x0020;
	const COLLECTION_MAP = 0x0021;
	const COLLECTION_SET = 0x0022;
	const UDT = 0x0030;
	const TUPLE = 0x0031;
	
	protected $_value;
	
	/**
	 * @return string
	 */
	abstract public function getBinary();
	
	/**
	 * @return string
	 */
	public function getValue(){
		return $this->_value;
	}
	
	/**
	 * @return string
	 */
	public function __toString(){
		return (string) $this->_value;
	}
	
	public static function getTypeObject(array $dataType, $value) {
		switch($dataType['type']) {
			case self::BLOB:
			case self::CUSTOM:
				return new Blob($value);
			case self::TIMESTAMP:
				if (is_double($value) && preg_match('~^\d{10}(.\d+)?$~', $value)) {
					$value = (int)str_pad(substr(str_replace('.', '', $value), 0, 13), 13, '0');
				} elseif (strlen($value) < 13) {
					throw new Exception('Value of timestamp must have 13 digits.');
				}
			case self::COUNTER:
			case self::BIGINT:
			case self::VARINT:
				return new Bigint($value);
	
			case self::BOOLEAN:
				return new Boolean($value);
	
			case self::COLLECTION_SET:
			case self::COLLECTION_LIST:
				return new CollectionList($value, $dataType['value']);
	
			case self::COLLECTION_MAP:
				return new Map($value, $dataType['key'], $dataType['value']);
	
			case self::DECIMAL:
				return new Decimal($value);
	
			case self::DOUBLE:
				return new Double($value);
	
			case self::FLOAT:
				return new Float($value);
	
			case self::INET:
				return new Inet($value);
	
			case self::INT:
				return new Int($value);
	
			case self::ASCII:
			case self::VARCHAR:
			case self::TEXT:
				return new Text($value);
	
			case self::TIMEUUID:
			case self::UUID:
				return new Uuid($value);
	
			default:
				trigger_error('Unknown type.');
		}
	
		return '';
	}
}
