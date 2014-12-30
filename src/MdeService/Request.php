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
 * Class Request
 *
 * This class will handle and parse received request.
 *
 * @package MdeService
 */
class Request
    implements DebuggableInterface
{

// ------------------------------
// Request process
// ------------------------------

    /**
     * @var array
     */
    protected $data     = array();

    /**
     * @var array
     */
    protected $files    = array();

    /**
     * @var array
     */
    protected $headers  = array();

    /**
     * @var string
     */
    protected $method   = null;

    /**
     * Request construction
     */
    public function __construct()
    {
        $this->setMethod($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Special debugging
     * @return array
     */
    public function __sleep()
    {
        return array('data','headers','method');
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function parse()
    {
        try {
            $this
                ->setHeaders(Helper::getAllHeaders())
                ->_processData()
                ->_processFiles()
            ;
        } catch (\Exception $e) {
            throw $e;
        }
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function _processData()
    {
        try {
            $data = array();
            if ($this->isMethod('post') || !empty($_POST)) {
                // transform post data if it is JSON encoded
                $raw_post = Helper::getRawData(
                    @fgets(@fopen('php://input', 'r'))
                );
                if (!empty($raw_post) && is_array($raw_post)) {
                    $data = $raw_post;
                } else {
                    $data = $_POST;
                }
            } elseif ($this->isMethod('get') || !empty($_GET)) {
                $data = $_GET;
            }

            foreach ($data as $name=>$value) {
                $data[$name] = Helper::getRawData(is_string($value) ? urldecode($value) : $value);
            }

            $this->setData($data);

        } catch (\Exception $e) {
            throw $e;
        }
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function _processFiles()
    {
        try {
            if (!empty($_FILES)) {
                foreach ($_FILES as $file) {
                    $error_msgs = array();
                    switch ($file['error']) {
                        case UPLOAD_ERR_INI_SIZE;
                        case UPLOAD_ERR_FORM_SIZE:
                            $error_msgs[] = sprintf('File size limit exceeded (got "%s")!', $file['size']);
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $error_msgs[] = 'File is not fully uploaded!';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $error_msgs[] = 'File seems empty!';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                        case UPLOAD_ERR_CANT_WRITE:
                        case UPLOAD_ERR_EXTENSION:
                            $error_msgs[] = sprintf('Internal error while trying to upload a file! [code %s]', $file['error']);
                            break;
                        case UPLOAD_ERR_OK:
                        default:
                            if (
                                file_exists($file['tmp_name']) &&
                                is_uploaded_file($file['tmp_name']) &&
                                is_readable($file['tmp_name'])
                            ) {
                                $this->addFile($file['tmp_name'], $file['name']);
                            } else {
                                $error_msgs[] = sprintf('File "%s" not found or not readable!', $file['name']);
                            }
                            break;
                    }
                    if (!empty($error_msgs)) {
                        throw new \Exception(
                            sprintf('Failure on upload of "%s" : %s', $file['name'], join(' ', $error_msgs))
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $this;
    }

// ------------------------------
// setters / getters
// ------------------------------

    /**
     * @param array/string $name
     * @param null $value
     * @return $this
     */
    public function setData($name, $value = null)
    {
        if (is_array($name)) {
            $this->data = $name;
        } else {
            $this->data[$name] = $value;
        }
        return $this;
    }

    /**
     * @param null $name
     * @param null $default
     * @return array|null
     */
    public function getData($name = null, $default = null)
    {
        return (is_null($name) ? $this->data : (isset($this->data[$name]) ? $this->data[$name] : $default));
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param $type
     * @return bool
     */
    public function isMethod($type)
    {
        return (bool) (
            $this->getMethod() == $type ||
            $this->getMethod() == strtoupper($type) ||
            $this->getMethod() == strtolower($type)
        );
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @param string/null $name
     * @return null
     */
    public function getHeader($name)
    {
        return (isset($this->headers[$name]) ? $this->headers[$name] : null);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $file_path
     * @param string $name
     * @return $this
     * @throws \Exception if the file does not exist
     */
    public function addFile($file_path, $name)
    {
        if (file_exists($file_path)) {
            $this->files[$name] = $file_path;
        } else {
            throw new \Exception(
                sprintf('File "%s" not found!', $file_path)
            );
        }
        return $this;
    }

    /**
     * @param $name
     * @return null
     */
    public function getFile($name)
    {
        return (isset($this->files[$name]) ? $this->files[$name] : null);
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

}
