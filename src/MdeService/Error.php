<?php
/**
 * This file is part of MDE-Service
 * <http://github.com/piwi/mde-service>
 *
 * Copyright 2014 Pierre Cassat
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace MdeService;

/**
 * Class Error
 * @package MdeService
 */
class Error
    extends \ErrorException
    implements DebuggableInterface
{

    /**
     * Special static Error creator from any Exception
     * @param \Exception $e
     * @param string $http_status
     * @return Error
     */
    public static function createFromException(\Exception $e, $http_status = Response::STATUS_ERROR)
    {
        return new Error(
            $e->getMessage(),
            $http_status,
            $e->getFile(),
            $e->getLine(),
            $e
        );
    }

    /**
     * @var int|string
     */
    protected $status;

    /**
     * @var string
     */
    protected $full_message;

    /**
     * @param string $message
     * @param int|string $http_status
     * @param null $filename
     * @param null $lineno
     * @param \Exception $previous
     */
    public function __construct(
        $message = '', $http_status = Response::STATUS_ERROR, $filename = null, $lineno = null, \Exception $previous = null
    ){
        $code = 0;
        $this->status = $http_status;
        switch($this->status) {
            case Response::STATUS_BAD_REQUEST: $code = 1; break;
            case Response::STATUS_METHOD_NOT_ALLOWED: $code = 2; break;
            case Response::STATUS_ERROR: $code = 3; break;
        }

        $info = array();
        if (!empty($previous)) {
            $info[] = 'caught ' . get_class($previous);
        }
        if (!empty($filename)) {
            $info[] = 'in ' . $filename;
        }
        if (!empty($lineno)) {
            $info[] = 'at line ' . $lineno;
        }

        if (!empty($info)) {
            $this->full_message = $message . ' [' . join(' ', $info) . ']';
        } else {
            $this->full_message = $message;
        }

        parent::__construct(
            $message, $code, 1,
            is_null($filename) ? __FILE__ : $filename,
            is_null($lineno) ? __LINE__ : $lineno,
            $previous
        );
    }

    /**
     * Special debugging
     * @return array
     */
    public function __sleep()
    {
        return array('full_message','status');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFullMessage();
    }

    /**
     * @return string
     */
    final public function getFullMessage()
    {
        return $this->full_message;
    }

    /**
     * @return int|string
     */
    final public function getStatus()
    {
        return $this->status;
    }

}
