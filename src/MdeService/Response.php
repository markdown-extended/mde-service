<?php
/**
 * This file is part of MDE-Service
 * <http://github.com/piwi/mde-service>
 *
 * Copyright 2014-2015 Pierre Cassat <me@e-piwi.fr>
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
 * Class Response
 *
 * This class will handle the HTTP response object.
 *
 * @package MdeService
 */
class Response
    implements DebuggableInterface
{

// ------------------------------
// HTTP statuses
// ------------------------------

    const STATUS_OK                     = '200 OK';
    const STATUS_NOT_MODIFIED           = '304 Not Modified';
    const STATUS_BAD_REQUEST            = '400 Bad Request';
    const STATUS_METHOD_NOT_ALLOWED     = '405 Method Not Allowed';
    const STATUS_ERROR                  = '500 Internal Server Error';

// ------------------------------
// Response process
// ------------------------------

    /**
     * @var string
     */
    protected $status   = null;

    /**
     * @var string
     */
    protected $charset  = 'utf-8';

    /**
     * @var array
     */
    protected $headers  = array();

    /**
     * @var null
     */
    protected $content  = null;

    /**
     * Response creator
     */
    public function __construct()
    {
//        $this->addHeader('Cache-Control', 'max-age=0, private, must-revalidate');
    }

    /**
     * Special debugging
     * @return array
     */
    public function __sleep()
    {
        return array('status','headers','content');
    }

    /**
     * @param array $response_data
     * @param int $exit_status
     * @throws \Exception
     */
    public function send(array $response_data = array(), $exit_status = 0)
    {
        try {
            $content = Helper::json_encode($response_data);
        } catch (\Exception $e) {
            throw $e;
        }
        $this
            ->setContent($content)
            ->fetch($exit_status)
        ;
    }

    /**
     * @param int $exit_status
     */
    public function fetch($exit_status = 0)
    {
        $this->fetchHeaders();
        echo $this->getContent().PHP_EOL;
        exit($exit_status);
    }

    /**
     * Fetch headers
     */
    public function fetchHeaders()
    {
        if (!$this->hasStatus()) {
            $this->setStatus(self::STATUS_OK);
        }

        if (!headers_sent()) {
            @header_remove('X-Powered-By');
            header('HTTP/1.1 ' . $this->getStatus());
            header('Content-type: application/json; charset=' . $this->getCharset());
            foreach ($this->getHeaders() as $name=>$value) {
                @header_remove(ucfirst($name));
                header(ucfirst($name) . ': ' . $value);
            }
        }
    }

// ------------------------------
// setters / getters
// ------------------------------

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
     * @param $name
     * @param $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return (bool) (isset($this->headers[$name]) && !empty($this->headers[$name]));
    }

    /**
     * @param $name
     * @return null
     */
    public function getHeader($name)
    {
        return ($this->hasHeader($name) ? $this->headers[$name] : null);
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        if (
            $status == self::STATUS_OK ||
            $status == self::STATUS_NOT_MODIFIED ||
            $status == self::STATUS_BAD_REQUEST ||
            $status == self::STATUS_METHOD_NOT_ALLOWED ||
            $status == self::STATUS_ERROR
        ) {
            $this->status = $status;
            $this->setHeader('API-Status', $status);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Unknown response status "%s"!', $status)
            );
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function hasStatus()
    {
        return (bool) !is_null($this->status);
    }

    /**
     * @param $status
     * @return bool
     */
    public function isStatus($status)
    {
        return (bool) ($this->getStatus() == $status);
    }

    /**
     * @param $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return null
     */
    public function getContent()
    {
        return $this->content;
    }

}
