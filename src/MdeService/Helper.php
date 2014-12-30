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
 * Class Helper
 *
 * Global namespace helper with only static methods throwing exceptions.
 *
 * @package MdeService
 */
class Helper
{

    /**
     * Initialize environment: define special error, exception and shutdown handlers
     */
    public static function initEnvironment()
    {
        set_error_handler(array(__CLASS__, 'errorHandler'));
        set_exception_handler(array(__CLASS__, 'exceptionHandler'));
        register_shutdown_function(array(__CLASS__, 'shutdownHandler'));
        date_default_timezone_set('UTC');
    }

    /**
     * Internal error handler
     * @param $errno
     * @param $errstr
     * @param null $errfile
     * @param null $errline
     * @param array $errcontext
     */
    public static function errorHandler($errno, $errstr, $errfile = null, $errline = null, array $errcontext = array())
    {
//echo __METHOD__;
        if (!(error_reporting() & $errno)) {
            return;
        }
        self::exceptionHandler(
            new Error($errstr, Response::STATUS_ERROR, $errfile, $errline)
        );
    }

    /**
     * Internal exception handler
     * @param \Exception $e
     */
    public static function exceptionHandler(\Exception $e)
    {
//echo __METHOD__;
        $error = Error::createFromException($e, Response::STATUS_ERROR);
        Container::get('controller')->addError($error);
        Container::get('response')->setStatus(Response::STATUS_ERROR);
        Container::get('controller')->serve();
    }

    /**
     * Internal shutdown handler
     * @param string $type
     */
    public static function shutdownHandler($type = E_ERROR)
    {
//echo __METHOD__;
        $error = error_get_last();
        if ($error["type"] & $type) {
            self::errorHandler( $error["type"], $error["message"], $error["file"], $error["line"] );
        }
    }

    /**
     * @param string $source
     * @param array $options
     * @return \MarkdownExtended\API\ContentInterface
     * @throws \Exception any caught exception
     */
    public static function parseMdeSource($source, array $options = array())
    {
        if (!empty($source)) {
            try {
                $source      = str_replace('&gt;', '>', $source);
                $source      = str_replace('&lt;', '<', $source);
                $mde_content = Container::get('mde_parser')
                    ->transformString($source, $options);
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            $mde_content = new \MarkdownExtended\Content();
        }
        return $mde_content;
    }

    /**
     * Get a final data, transformed if it was JSON encoded
     * @param $data_str
     * @return mixed
     * @throws \Exception if `json_decode()` fails
     */
    public static function getRawData($data_str)
    {
        if (is_string($data_str) && ($data_str=="''" || $data_str=='""')) {
            $data = '';
        } else {
            $data = $data_str;
        }

        if (!empty($data) && ($data{0} == '{' || $data{0} == '[')) {
            try {
                $data = self::json_decode($data);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $data;
    }

    /**
     * Try to `json_decode()` a string
     * @param $data
     * @return mixed
     * @throws \Exception if `json_decode()` fails
     */
    public static function json_decode($data)
    {
        $data_json = @json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(
                sprintf('An error occurred while trying to decode JSON data [code "%s"]!', json_last_error())
            );
        } else {
            $data = $data_json;
        }
        return $data;
    }

    /**
     * Try to `json_encode()` an array
     * @param array $data
     * @return mixed
     * @throws \Exception if `json_encode()` fails
     */
    public static function json_encode(array $data)
    {
        $json = @json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(
                sprintf('An error occurred while trying to encode data to JSON [code "%s"]!', json_last_error())
            );
        } else {
            $data = $json;
        }
        return $data;
    }

}
