<?php
namespace Codexonics\PrimeMoverFramework\utilities;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

if (! defined('ABSPATH')) {
    exit;
}
/**
 * Search and Replace Class - from Duplicator Plugin
 *
 * @package Duplicator
 * @link https://github.com/lifeinthegrid/duplicator Duplicator GitHub Project
 * @link http://www.lifeinthegrid.com/duplicator/
 * @link http://www.snapcreek.com/duplicator/
 * @author Snap Creek
 * @copyright 2011-2017  SnapCreek LLC
 * @license GPLv2 or later

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 * SOURCE CONTRIBUTORS:
 * David Coveney of Interconnect IT Ltd
 * https://github.com/interconnectit/Search-Replace-DB/
 */
class DupxUpdateEngine
{
	/**
     * Test if a string in properly serialized
     *
     * @param string $data  Any string type
     * @codeCoverageIgnore
     * @return bool Is the string a serialized string
     */
    public static function isSerialized($data)
    {        
        $test = @unserialize(($data));
        return ($test !== false || $test === 'b:0;') ? true : false;
    }

	/**
	 *  Fixes the string length of a string object that has been serialized but the length is broken
	 *
	 *  @param string $data	The string ojbect to recalculate the size on.
	 *  @codeCoverageIgnore
	 *  @return string  A serialized string that fixes and string length types
	 */
	public static function fixSerialString($data)
	{
	    $result = array('data' => $data, 'fixed' => false, 'tried' => false);
	    	        
        if (preg_match("/s:[0-9]+:/", $data)) {
            if (!self::isSerialized($data)) {
                $regex			 = '!(?<=^|;)s:(\d+)(?=:"(.*?)";(?:}|a:|s:|b:|d:|i:|o:|N;))!s';
                /** @var mixed $matches Matches*/
                $serial_string	 = preg_match('/^s:[0-9]+:"(.*$)/s', trim($data), $matches);
                //Nested serial string
                if ($serial_string) {
                    $inner				 = preg_replace_callback($regex, 'Codexonics\PrimeMoverFramework\utilities\DupxUpdateEngine::fixStringCallback', rtrim($matches[1], '";'));
                    $serialized_fixed	 = 's:'.strlen($inner).':"'.$inner.'";';
                } else {
                    $serialized_fixed = preg_replace_callback($regex, 'Codexonics\PrimeMoverFramework\utilities\DupxUpdateEngine::fixStringCallback', $data);
                }
                
                if (self::isSerialized($serialized_fixed)) {
                    $result['data']	 = $serialized_fixed;
                    $result['fixed'] = true;
                }
                $result['tried'] = true;
            }
        }
		
		return $result;
	}

	/**
	 *  @codeCoverageIgnore
	 *  The call back method call from via fixSerialString
	 */
	private static function fixStringCallback($matches)
	{
		return 's:'.strlen(($matches[2]));
	}
}