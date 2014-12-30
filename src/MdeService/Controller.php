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
 * Class Controller
 *
 * This is the global webservice controller.
 *
 * @package MdeService
 */
class Controller
    implements DebuggableInterface
{

// ------------------------------
// controller internals
// ------------------------------

    /**
     * @var string
     */
    protected $source_type = null;

    /**
     * @var array  Request MDE source contents (original "source" or "sources" data)
     */
    protected $sources  = array();

    /**
     * @var array  Response contents (parsed "source" or "sources" data)
     */
    protected $contents = array();

    /**
     * @var array   Collection of \MdeService\Error
     */
    protected $errors   = array();

    /**
     * @var bool    Debugging flag
     */
    private $_debug     = false;

    /**
     * Public static constructor
     * @return Controller
     */
    public static function create()
    {
        return new self;
    }

    /**
     * Controller constructor
     */
    public function __construct()
    {
        Helper::initEnvironment();
        Container::set('controller', $this);
        Container::set('request', new Request());
        Container::set('response', new Response());
    }

    /**
     * Prepare the object for debugging
     * @return array
     */
    public function __sleep()
    {
        return array('request','response','sources','contents','errors');
    }

// ------------------------------
// interface API
// ------------------------------

    /**
     * Parse the request
     * @return self
     * @api
     */
    public function distribute()
    {
        Container::get('request')->parse();

        if (
            !Container::get('request')->isMethod('get') &&
            !Container::get('request')->isMethod('post') &&
            !Container::get('request')->isMethod('head')
        ) {
            $this->error(
                sprintf('Request method "%s" is not allowed!', Container::get('request')->getMethod()),
                Response::STATUS_METHOD_NOT_ALLOWED
            );
        }

        // analyze request headers
        foreach (Container::get('request')->getHeaders() as $name=>$value) {
            switch($name) {
                case 'Time-Zone':
                    if (in_array($value, timezone_identifiers_list())) {
                        date_default_timezone_set($value);
                    } else {
                        $this->warning(
                            sprintf('The "%s" timezone defined is not valid!', $value)
                        );
                    }
                    break;
                default: break;
            }
        }

        // user files
        $type = Container::get('request')->getData('source_type', 'data_input');
        $this->setSourceType($type);
        if ($this->getSourceType() == 'file') {
            $files = Container::get('request')->getFiles();
            if (!empty($files)) {
                foreach ($files as $name=>$path) {
                    $this->addSource(
                        file_get_contents($path), $name
                    );
                }
            }
        }

        // end here if no 'source' or 'sources' post data
        $source     = Container::get('request')->getData('source');
        $sources    = Container::get('request')->getData('sources');
        $_sources   = $this->getSources();
        if (empty($source) && empty($sources) && empty($_sources)) {
            $this
                ->warning('No source to parse!')
                ->serve();
        } else {
            if (!empty($sources)) {
                $this->setSources(array_merge(
                    $_sources, $sources
                ));
            }
            if (!empty($source)) {
                $this->addSource($source);
            }
        }

        // debug mode on?
        $this->setDebug(Container::get('request')->getData('debug', false));

        // load the MDE parser
        if (!class_exists('\MarkdownExtended\MarkdownExtended')) {
            $this->error('Class "\MarkdownExtended\MarkdownExtended" not found!');
        }
        Container::set('mde_parser', \MarkdownExtended\MarkdownExtended::create());

        return $this;
    }

    /**
     * Parse the "sources" contents
     * @return self
     * @throws \Exception any caught exception
     * @api
     */
    public function parse()
    {
        Container::get('response')->setHeader('Last-Modified', gmdate('D, d M Y H:i:s').' GMT');
        if (!Container::get('response')->hasHeader('Date')) {
            Container::get('response')->setHeader('Date', gmdate('D, d M Y H:i:s').' GMT');
        }

        $etag       = '';
        $sources    = $this->getSources();
        $options    = Container::get('request')->getData('options', array());
        $format     = Container::get('request')->getData('format');
        $extract    = Container::get('request')->getData('extract', 'full');
        if (!empty($format)) {
            $options['output_format'] = $format;
        }
        if (!empty($sources)) {
            try {
                foreach ($sources as $index=>$source) {
                    $etag .= md5($source);

                    /* @var \MarkdownExtended\Content $mde_content */
                    $mde_content = Helper::parseMdeSource($source, $options);
                    //var_export($mde_content);

                    switch ($extract) {
                        case 'metadata':
                            $parsed_content = $mde_content->getMetadataToString();
                            break;
                        case 'body':
                            $parsed_content = $mde_content->getBody();
                            break;
                        case 'notes':
                            $parsed_content = $mde_content->getNotesToString();
                            break;
                        case 'full':
                        default:
                            $parsed_content =
                                $mde_content->getMetadataToString()
                                .PHP_EOL
                                .$mde_content->getBody()
                                .PHP_EOL
                                .$mde_content->getNotesToString()
                            ;
                            break;
                    }
                    $content_index = $this->addContent($parsed_content);
                    if ($this->getSourceType() == 'file' && is_string($index)) {
                        $source = $index;
                    }
                    if ($content_index !== $index) {
                        $this
                            ->unsetSource($index)
                            ->setSource($content_index, $source)
                        ;
                    }
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
        Container::get('response')->setHeader('ETag', $etag);

        Container::get('response')->setHeader('X-MDE-Version', \MarkdownExtended\MarkdownExtended::MDE_VERSION);

        return $this;
    }

    /**
     * Serve the JSON response and exit
     * @return void
     * @api
     */
    public function serve()
    {
        $type       = $this->getSourceType();
        $sources    = $this->getSources();
        $contents   = $this->getContents();
        if ($type!='file' && count($sources)==1) {
            $response_data = array(
                'source'    => $sources[0],
                'content'   => $contents[0],
            );
        } else {
            $response_data = array(
                'sources'   => $sources,
                'contents'  => $contents,
            );
        }

        $errors_collection = $this->getErrors();
        $errors = array();
        foreach ($errors_collection as $_error) {
            $errors[] = $_error->getFullMessage();
        }
        $response_data['errors'] = $errors;

        if ($this->isDebug()) {
            $response_data['dump'] = serialize($this);
        }

//var_export($this);exit('yo');

        Container::get('response')->send($response_data);
    }

// ------------------------------
// Error process
// ------------------------------

    /**
     * Add a simple not-fatal error message
     * @param $str
     * @param string $status
     * @return $this
     */
    public function warning($str, $status = Response::STATUS_OK)
    {
        $this->addError(new Error($str, $status));
        return $this;
    }

    /**
     * Handle errors adding the error message to the response and serving it if needed
     * @param $str
     * @param string $code
     */
    public function error($str, $code = Response::STATUS_ERROR)
    {
        $this->addError(new Error($str, $code));
        Container::get('response')->setStatus($code);
        if (!Container::get('response')->isStatus(Response::STATUS_OK)) {
            $this->serve();
        }
    }

// ------------------------------
// setters/getters/checkers
// ------------------------------

    /**
     * @param \MdeService\Error $error
     * @return $this
     */
    public function addError(Error $error)
    {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $str
     * @param string $index
     * @return int/string inserted index
     */
    public function addContent($str, $index = null)
    {
        if (!is_null($index)) {
            $this->contents[$index] = $str;
        } else {
            $this->contents[] = $str;
            end($this->contents);
            $index = key($this->contents);
        }
        return $index;
    }

    /**
     * @param string $str
     * @param string $index
     * @return $this
     */
    public function setContent($index, $str)
    {
        $this->contents[$index] = $str;
        return $this;
    }

    /**
     * @param $index
     * @return null
     */
    public function getContent($index)
    {
        return (isset($this->contents[$index]) ? $this->contents[$index] : null);
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param array $sources
     * @return $this
     */
    public function setSources(array $sources)
    {
        $this->sources = $sources;
        return $this;
    }

    /**
     * @param string $str
     * @return int/string inserted index
     */
    public function addSource($str, $index = null)
    {
        if (!is_null($index)) {
            $this->sources[$index] = $str;
        } else {
            $this->sources[] = $str;
            end($this->sources);
            $index = key($this->sources);
        }
        return $index;
    }

    /**
     * @param int/string $index
     * @param string $str
     * @return int/string inserted index
     */
    public function setSource($index, $str)
    {
        $this->sources[$index] = $str;
        return $this;
    }

    /**
     * @param int/string $index
     * @return $this
     */
    public function unsetSource($index)
    {
        unset($this->sources[$index]);
        return $this;
    }

    /**
     * @param $index
     * @return null
     */
    public function getSource($index)
    {
        return (isset($this->sources[$index]) ? $this->sources[$index] : null);
    }

    /**
     * @return string
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * @param $string
     * @return $this
     */
    public function setSourceType($string)
    {
        $this->source_type = $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getSourceType()
    {
        return $this->source_type;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setDebug($bool)
    {
        if (is_string($bool)) {
            $bool = ($bool=='true' || $bool=='1');
        }
        $this->_debug = (bool) $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return (bool) ($this->_debug===true);
    }

}
