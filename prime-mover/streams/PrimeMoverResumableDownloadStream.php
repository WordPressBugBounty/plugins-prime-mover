<?php
namespace Codexonics\PrimeMoverFramework\streams;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 * 
 * ORIGINAL CREDITS: kosinix: https://gist.github.com/kosinix/4cf0d432638817888149
 */

use Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions;
use Codexonics\PrimeMoverBridgeIO;

if (! defined('ABSPATH')) {
    exit;
}

class PrimeMoverResumableDownloadStream 
{
    
    private $file;
    private $name;
    private $boundary;
    private $delay = 0;
    private $size = 0;
    private $system_functions;
    
    public function __construct(PrimeMoverSystemFunctions $system_functions) 
    {
        $this->system_functions = $system_functions;
    }
    
    public function getSystemFunctions()
    {
        return $this->system_functions;
    }
    
    public function initializeProperties($file = '', $delay = 0)
    {
        $this->setSize($file);
        $this->setFile($file);
        $this->setBoundary($file);
        $this->setDelay($delay);
        $this->setName($file);
    }
 
    public function getSystemAuthorization()
    {
        return $this->getSystemFunctions()->getSystemAuthorization();
    }
    
    private function canProcess()
    {
        return ( ! empty($this->file) && ! empty($this->size) && $this->getSystemAuthorization()->isUserAuthorized() );
    }
    
    private function setSize($file = '')
    {
        if ( ! $file ) {
            return;
        }
        $this->size = filesize($file);
    }
    
    private function setFile($file = '')
    {
        if ( ! $file ) {
            return;
        }
        $handle = PrimeMoverBridgeIO::call('fopen', $file, "r");
        if ($handle) {
            $this->file = $handle;
        }
    }
    
    private function setBoundary($file = '')
    {
        if ( ! $file ) {
            return;
        }
        $this->boundary = md5($file);  
    }
    
    private function setDelay($delay = 0)
    {
        $this->delay = $delay;
    }
    
    private function setName($file = '')
    {
        if ( ! $file ) {
            return;
        }
        $this->name = basename($file);
    }
    
    /**
     * Refactored
     * since version 2.2
     *
     */
    public function process()
    {
        if ( ! $this->canProcess() ) {
            return;
        }
        $ranges = NULL;
        $t = 0;
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_SERVER['HTTP_RANGE']) && $range = stristr(trim($_SERVER['HTTP_RANGE']), 'bytes=')) {
            $range = substr($range, 6);
            $ranges = explode(',', $range);
            $t = count($ranges);
        }
        header("Accept-Ranges: bytes");
        header("Content-Transfer-Encoding: binary");
        header(sprintf('Content-Disposition: attachment; filename="%s"', $this->name));
        
        if ($t > 0) {
            header("HTTP/1.1 206 Partial Content");
            
            if ($t === 1) {
                header("Content-Type: application/octet-stream");                
                $this->pushSingle($ranges[0]);
            } else {
                $this->pushMulti($ranges);
            }
        } else {
            header("Content-Type: application/octet-stream");
            header("Content-Length: " . $this->size);
            $this->getSystemFunctions()->flush();
            $this->readFile();
        }
    }    
    
    /**
     * Refactored
     * since version 2.2
     *
     */
    private function pushSingle($range)
    {
        $start = $end = 0;
        $this->getRange($range, $start, $end);
        
        header("Content-Length: " . ($end - $start + 1));        
        header(sprintf("Content-Range: bytes %d-%d/%d", $start, $end, $this->size));
        
        PrimeMoverBridgeIO::call('fseek', $this->file, $start);
        $this->getSystemFunctions()->flush();        
        
        $this->readFile();
    }
    
    /**
     * Refactored
     * since version 2.2
     * 
     */
    private function pushMulti($ranges)
    {
        $length = $start = $end = 0;
        $tl = "Content-type: application/octet-stream\r\n";
        $formatRange = "Content-range: bytes %d-%d/%d\r\n\r\n";
        foreach ( $ranges as $range ) {
            $this->getRange($range, $start, $end);
            $length += strlen("\r\n--$this->boundary\r\n");
            $length += strlen($tl);
            $length += strlen(sprintf($formatRange, $start, $end, $this->size));
            $length += $end - $start + 1;
        }
        $length += strlen("\r\n--$this->boundary--\r\n");
        header("Content-Length: $length");
        header("Content-Type: multipart/x-byteranges; boundary=$this->boundary");
        $this->getSystemFunctions()->flush();        
       
        $output_stream = PrimeMoverBridgeIO::call('fopen', 'php://output', 'wb');        
        
        if ( $output_stream ) {
            foreach ( $ranges as $range ) {
                $this->getRange($range, $start, $end);
                
                PrimeMoverBridgeIO::call('fwrite', $output_stream, "\r\n--$this->boundary\r\n");                
                PrimeMoverBridgeIO::call('fwrite', $output_stream, $tl);                
                PrimeMoverBridgeIO::call('fwrite', $output_stream, sprintf($formatRange, (int)$start, (int)$end, (int)$this->size));                
                
                PrimeMoverBridgeIO::call('fseek', $this->file, $start);                
                $this->readBuffer($output_stream, $end - $start + 1);
            }
            
            PrimeMoverBridgeIO::call('fwrite', $output_stream, "\r\n--$this->boundary--\r\n");            
            PrimeMoverBridgeIO::call('fclose', $output_stream);            
        }
    }
    
    /**
     * Refactored
     * since version 2.2
     *
     */
    private function getRange($range, &$start, &$end)
    {
        $range_parts = explode('-', (string)$range);
        $start_raw = isset($range_parts[0]) ? trim($range_parts[0]) : '';
        $end_raw = isset($range_parts[1]) ? trim($range_parts[1]) : '';
        
        $fileSize = (int)$this->size;
        
        if ($start_raw === '') {
            $tmp = (int)$end_raw;
            $end = $fileSize - 1;
            $start = $fileSize - $tmp;
            if ($start < 0) {
                $start = 0;
            }
        } else {
            $start = (int)$start_raw;
            if ($end_raw === '' || (int)$end_raw > $fileSize - 1) {
                $end = $fileSize - 1;
            } else {
                $end = (int)$end_raw;
            }
        }
        
        if ($start > $end || $start < 0 || $start >= $fileSize) {
            http_response_code(416);
            header("Content-Range: bytes */" . $fileSize);
            exit();
        }
        
        return array((int)$start, (int)$end);
    }
    
    /**
     * Refactored
     * since version 2.2
     *
     */
    private function readFile()
    {        
        $output_stream = PrimeMoverBridgeIO::call('fopen', 'php://output', 'wb');        
        
        if ( $output_stream ) {
            while (!feof($this->file)) {
                $buffer = PrimeMoverBridgeIO::call('fread', $this->file, 1024*1024);                
                PrimeMoverBridgeIO::call('fwrite', $output_stream, $buffer);                
                
                flush();
                usleep($this->delay);
            }            
            
            PrimeMoverBridgeIO::call('fclose', $output_stream);            
        }
    }
    
    /**
     * Refactored
     * since version 2.2
     *
     */
    private function readBuffer($output_stream, $bytes = 0, $size = 1024)
    {
        $bytesLeft = $bytes;        
        $current_stream = is_resource($output_stream) ? $output_stream : PrimeMoverBridgeIO::call('fopen', 'php://output', 'wb');        
        
        while ( $bytesLeft > 0 && ! feof($this->file) ) {
            $bytesLeft > $size ? $bytesRead = $size : $bytesRead = $bytesLeft;
            $bytesLeft -= $bytesRead;            
            
            $data = PrimeMoverBridgeIO::call('fread', $this->file, $bytesRead);            
            if ( $current_stream ) {
                PrimeMoverBridgeIO::call('fwrite', $current_stream, $data);                
            }
            
            flush();
        }        
        
        if ( ! is_resource($output_stream) && $current_stream ) {
            PrimeMoverBridgeIO::call('fclose', $current_stream);            
        }
    }
}