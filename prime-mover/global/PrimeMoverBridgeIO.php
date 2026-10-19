<?php
namespace Codexonics;

if (!defined('ABSPATH')) {
    exit;
}

use TypeError;
use ValueError;
use Exception;
use Throwable;
use ReflectionFunction;

/**
 * Prime Mover File system IO and global level static methods
 */
final class PrimeMoverBridgeIO 
{
    /**
     * Cache container for instantiated ReflectionFunction instances.
     * @var array
     */
    private static $registry = array();    
    
    /**
     * Prevent direct instantiation of this utility class.
     */
    private function __construct() {}
    
    /**
     * Executes native filesystem functions across PHP 5.6 - PHP 8.5+ safely.
     * @param string $function_name Target function string.
     * @return mixed Output or false on exception.
     */
    public static function call($function_name) 
    {        
        if (!isset(self::$registry[$function_name])) {            
            self::$registry[ $function_name ] = new ReflectionFunction($function_name);
        }    
        
        try {            
            $raw_arguments = func_get_args();
            array_shift($raw_arguments);
            
            $referenced_args = array();
            foreach ($raw_arguments as $key => &$value) {
                $referenced_args[$key] = &$value;
            }            
            return self::$registry[$function_name]->invokeArgs($referenced_args);
            
        } catch (TypeError $e) {            
            return false;
        } catch (ValueError $e) {            
            return false;
        } catch (Exception $e) {            
            return false;
        } catch (Throwable $t) {            
            return false;
        }
    }
    
    /**
     * Safely unserialize
     * If $return_if_not_serialized is TRUE, it will behave like maybe_unserialize()
     * @param mixed $serialized_string
     * @param boolean $return_if_not_serialized
     * @return boolean|mixed|mixed|boolean
     */
    public static function unserialize($serialized_string, $return_if_not_serialized = false)
    {
        if (!is_serialized($serialized_string)) {
            return $return_if_not_serialized ? $serialized_string : false;
        }
        
        $serialized_string = trim($serialized_string);
        if (PHP_VERSION_ID >= 70000) {            
            return @unserialize($serialized_string, [
                'allowed_classes' => [
                    'stdClass', 
                ]
            ]);
        }

        if (preg_match('/(^|;|{)(O|C):\d+:"/', $serialized_string)) {
            return false;
        }

        return @unserialize($serialized_string);
    }
}