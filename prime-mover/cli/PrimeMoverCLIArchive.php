<?php
namespace Codexonics\PrimeMoverFramework\cli;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemChecks;
use Codexonics\PrimeMoverFramework\classes\PrimeMoverProgressHandlers;

if (! defined('ABSPATH')) {
    exit;
}

class PrimeMoverCLIArchive
{
 
    private $system_checks;
    private $progress_handlers;
    
    /**
     * Constructor
     * @param PrimeMoverSystemChecks $system_checks
     * @param PrimeMoverProgressHandlers $progress_handlers
     */
    public function __construct(PrimeMoverSystemChecks $system_checks, PrimeMoverProgressHandlers $progress_handlers)
    {
        $this->system_checks = $system_checks;
        $this->progress_handlers = $progress_handlers;
    }    
    
     /**
     * Get shutdown utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverShutdownUtilities
     */
    public function getShutDownUtilities()
    {
        return $this->getProgressHandlers()->getShutDownUtilities();
    }
    /**
     * Get system checks
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemChecks
     */
    public function getSystemChecks()
    {
        return $this->system_checks;
    }
    
    /**
     * Get system authorization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization
     */
    public function getSystemAuthorization()    
    {
        return $this->getSystemChecks()->getSystemAuthorization();
    }
    
    /**
     * Get system initialization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemInitialization
     */
    public function getSystemInitialization()
    {
        return $this->getSystemChecks()->getSystemInitialization();
    }
    
    /**
     * Get system functions
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions
     */
    public function getSystemFunctions()
    {
        return $this->getSystemChecks()->getSystemFunctions();
    }
    
    /**
     * Get progress handlers
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverProgressHandlers
     */
    public function getProgressHandlers()
    {
        return $this->progress_handlers;
    }
        
    /**
     * Checks if we need to process using shell functions. These are no longer supported - always return FALSE.
     * @return boolean
     */
    public function maybeArchiveMediaByShell()
    {
        return false;
    } 
    
    /**
     * Open master tmp file resource
     * @param array $ret
     * @param string $entity
     * @param string $mode
     * @return NULL|resource[]|resource
     */
    public function openMasterTmpFileResource($ret = [], $entity = '', $mode = 'wb')
    {
        if (! $this->getSystemAuthorization()->isUserAuthorized()) {
            return null;
        }
        $is_cli = $this->getSystemInitialization()->isCliEnvironment();
        $copymediabyshell = $this->maybeArchiveMediaByShell();
        if ( ! $copymediabyshell && ! $is_cli) {
            return null;
        }
        if (empty($ret['master_tmp_shell_files']) || empty($ret['master_tmp_shell_dirs'])) {
            return null;
        }
        if (! $entity) {
            return [fopen($ret['master_tmp_shell_files'], $mode), fopen($ret['master_tmp_shell_dirs'], $mode)];
        }
        if ('file' === $entity) {
            return fopen($ret['master_tmp_shell_files'], $mode);
        }
        if ('dir' === $entity) {
            return fopen($ret['master_tmp_shell_dirs'], $mode);
        }
        return null;
    }
    
    /**
     * Write master tmp log
     * @param string $data
     * @param resource $resource
     * @param array $ret
     * @param string $mode
     * @param boolean $close
     * @return void|NULL
     */
    public function writeMasterTmpLog($data = '', $resource = null, $ret = [], $mode = 'wb', $close = false)
    {
        if (! $this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }    
        $is_cli = $this->getSystemInitialization()->isCliEnvironment();
        $copymediabyshell = $this->maybeArchiveMediaByShell();
        if ( ! $copymediabyshell && ! $is_cli) {
            return null;
        }
        if (! $data) {
            return;
        }
        $data = wp_normalize_path($data);
        if (! is_resource($resource) && is_file($data)) {
            $resource = $this->openMasterTmpFileResource($ret, 'file', $mode);
        }
        if ( ! is_resource($resource) && is_dir($data)) {
            $resource = $this->openMasterTmpFileResource($ret, 'dir', $mode);
        }
        if (is_resource($resource)) {
            fwrite($resource, $data . PHP_EOL);
            if ($close) {
                $this->closeMasterTmpLog($resource);
            }
        }       
    }
    
    /**
     * Close master tmp log resource
     * @param mixed $resources
     */
    public function closeMasterTmpLog($resources)
    {
        if (! $this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        $file_handle = null;
        $dir_handle = null;
        if (is_array($resources) && !empty($resources)) {
            list($file_handle, $dir_handle) = $resources;
        }
        if (is_resource($file_handle)) {
            fclose($file_handle);
        }
        if (is_resource($dir_handle)) {
            fclose($dir_handle);
        }
        if (is_resource($resources)) {
            fclose($resources);
        }
    }
}
