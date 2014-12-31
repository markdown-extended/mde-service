MDE-Service
===========

A webservice tool to parse Markdown-Extended contents online.

The **MDE-Service** webservice handles raw or JSON requests and send 
a JSON content response with significant headers.

The basic usage schema is:

    $ curl -i http://localhost/mde-service/www/mde-api.php?source=%2AMarkdown%2A-__extended__%20content
    
    HTTP/1.1 200 OK
    Last-Modified: Mon, 29 Dec 2014 21:11:01 GMT
    Content-Type: application/json; charset=utf-8
    API-Status: 200 OK
    API-Version: 0.1
    MDE-Version: 0.1-gamma4
    ETag: 8a4d0bef2b45148a6c8df14158704f6c
    
    {
        "source":"*Markdown*-__extended__ content",
        "content":"\n\n<p><em>Markdown<\/em>-<strong>extended<\/strong> content<\/p>\n\n\n",
        "errors":[]
    }

This webservice is available online at <http://api.aboutmde.org/>.


Build the request
-----------------

You can send HTTP requests using verbs *GET*, *POST* or *HEAD*. The GET and POST
requests will have the same results while the HEAD one will only fetch response headers.

Accepted data are:

-   `source` : the original single MDE content to parse
-   `sources` : a table of original MDE contents to parse
-   `options` : a table of options to pass to the MDE parser
-   `format` : the format to use for the MDE parsing (default is *HTML*)
-   `extract` : a specific [`\MarkdownExtended\Content`](http://docs.ateliers-pierrot.fr/markdown-extended/#MarkdownExtended/Content.html) 
    content part to extract and return ; this can be "full" (default), "metadata", "body" or "notes" ;
    see the note below to learn more about the rendering of a content
-   `source_type` : use this to define the type of sources to treat ; default is empty (which means
    "data_input") and you can set it to "file" to treat any attached file
-   `debug` : a boolean value to get some debugging information

If you specify both `source` and `sources` data, the single `source` will be added
**at the end** of the `sources` table.

You can optionally define some special headers:

-   `Accept: application/json` to prepare the response format
-   `Time-Zone: Europe/Paris` to use a specific timezone ; the time-zone name must follow the 
    [the Olson database](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones) and
    defaults to *UTC*.

Using the `source_type=file` option, you can upload one or more files and
their contents will be parsed as the `sources`.


Understand the response
-----------------------

The response is a JSON encoded content with a significant *status* code header.

The JSON content is built like:

-   `errors` : a table of error strings
-   `dump` : the serialized interface object (in debug mode only)
-   `source` : the received MDE source that has been parsed if it was single
-   `content` : the parsed version of the source if it was single
-   `sources` : an indexed table of received MDE sources if they were multiple
-   `contents` : an indexed table of the parsed versions of the MDE sources if they were multiple ; 
    indexes are the same as for the `sources` elements.

The response status can be:

-   `200 OK` if nothing went wrong
-   `304 Not Modified` if the request contains an `ETag` or a `If-Modified-Since` header and the response is not modified
-   `400 Bad Request` if the request seems malformed
-   `405 Method Not Allowed` if the request verb was not allowed (anything else than *GET*, *POST* or *HEAD*)
-   `500 Internal Server Error` if an error occurred during the process.

The default rendering of a parsed content is a full aggregation of its elements:

    $mde_content->getMetadataToString()
    \n
    $mde_content->getBody()
    \n
    $mde_content->getNotesToString()

Using the `extract` argument, you can choose a single element to return.

When the sources came from one or more uploaded files, the filenames are rendered in the `sources` array
with the same index as in the resulting `contents` array.

### Versions information

Each response of the API will have a `API-Version` header with the current API version number. You
can use it to check that you are still working with the right version.

If a content parsing happens, the response will also have a `MDE-Version` header with the version
number of the MarkdownExtended parser used. This reference should be a release of the package 
<http://github.com/piwi/markdown-extended>. 

### Caching responses

The responses of the API are not cached (this would have no sense). But you can make a request with
information to update your content only if the response is modified. You can do so by re-using the
`ETag` header of a previous response you had and send it as a value of a `If-None-Match` header in
your request:

    $ curl --header "If-None-Match: YOUR_PREVIOUS_RESPONSE_ETAG" ...

The `ETag` embedded in each response of the API if an MDE parsing happens is built with the original
sources AND their parsed contents. This way, if you use the header above you will have the `304 Not Modified`
status response only if you sent the same source AND if the result is not updated.


Implementation examples
-----------------------

### Command line usage

The examples below can be run in command line using [curl](http://en.wikipedia.org/wiki/CURL).
Each of them will fetch the response headers and its content on the terminal. To get only
the content ("in-real-life"), delete the `-i` option.

The `options` argument is shown empty in each request but is not required.

A basic POST request usage is:

```bash
$ curl -i \
    --request POST \
    --data-urlencode "source=My *test* MDE **content** ... azerty \`azerty()\` azerty <http://google.com/> azerty." \
    --data-urlencode "options={}" \
    http://api.aboutmde.org/mde-api.php ;

HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
API-Status: 200 OK
API-Version: 0.1
MDE-Version: 0.1-gamma4
Last-Modified: Wed, 31 Dec 2014 00:57:32 GMT
ETag: c6989875115e90dc377c460c4801734c

{
    "source":"My *test* MDE **content** ... azerty `azerty()` azerty <http:\/\/google.com\/> azerty.",
    "content":"\n\n<p>My <em>test<\/em> MDE <strong>content<\/strong> ... azerty <code>azerty()<\/code> azerty <a href=\"http:\/\/google.com\/\" title=\"See online http:\/\/google.com\/\">http:\/\/google.com\/<\/a> azerty.<\/p>\n\n\n",
    "errors":[]
}
```

A basic GET request usage is:

```bash
$ curl -i \
    --get \
    --data-urlencode "source=My *test* MDE **content** ... azerty \`azerty()\` azerty <http://google.com/> azerty." \
    --data-urlencode "options={}" \
    http://api.aboutmde.org/mde-api.php ;
    
# same output ...
```

A POST request with JSON encoded arguments can be made with:

```bash
$ echo "{ \
    \"source\":     \"My *test* MDE **content** ... azerty \`azerty()\` azerty <http://google.com/> azerty.\", \
    \"options\":    \"{}\" \
    }" | curl -i \
        --request POST \
        --header "Content-Type: application/json" \
        --data @- \
        http://api.aboutmde.org/mde-api.php ;

# same output ...
```

You can define a personal header in the request with:

```bash
$ curl -i \
    --header "Time-Zone: Europe/Paris" \
    --request POST \
    --data-urlencode "source=My *test* MDE **content** ... azerty \`azerty()\` azerty <http://google.com/> azerty." \
    --data-urlencode "options={}" \
    http://api.aboutmde.org/mde-api.php ;

# same output ...
```

To upload a file, use:

```bash
$ curl -i \
    --form "source=@my-file.md" \
    --form "source_type=file" \
    http://api.aboutmde.org/mde-api.php ;
    
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
API-Status: 200 OK
API-Version: 0.1
MDE-Version: 0.1-gamma4
Last-Modified: Wed, 31 Dec 2014 00:57:32 GMT
ETag: c6989875115e90dc377c460c4801734c

{
    "source":["my-file.md"],
    "content":"\n\n<p>My <em>test<\/em> MDE <strong>content<\/strong> ... azerty <code>azerty()<\/code> azerty <a href=\"http:\/\/google.com\/\" title=\"See online http:\/\/google.com\/\">http:\/\/google.com\/<\/a> azerty.<\/p>\n\n\n",
    "errors":[]
}
```

In case of error, you will have:

```bash
$ curl -i \
    --request INPUT \
    --data-urlencode "source=My *test* MDE **content** ... azerty \`azerty()\` azerty <http://google.com/> azerty." \
    --data-urlencode "options={}" \
    http://api.aboutmde.org/mde-api.php ;

HTTP/1.1 405 Method Not Allowed
Content-Type: application/json; charset=utf-8
API-Status: 405 Method Not Allowed
API-Version: 0.1

{
    "sources":[],
    "contents":[],
    "errors":["Request method \"INPUT\" is not allowed!"]
}
```


### JavaScript implementation

Below is a sample of JavaScript usage of the interface via an [XMLHttpRequest object](http://en.wikipedia.org/wiki/XMLHttpRequest):

```javascript
// open a new XHR request handler
var xhr = new XMLHttpRequest();

// define a response callback
xhr.onreadystatechange  = function() {
    if (xhr.readyState  == 4) {

        // the response is always a JSON content
        var response = JSON.parse(xhr.response);

        // the response can have multiple "status" header:
        // 200 : no error
        if (xhr.status  == 200) {

            // if response.content is not empty
            if (response.content.length > 0) {
                var ajax_response = response.content;

            // else an error occurred
            } else {
                console.error("Error on XHR response:\n" + response.errors.join('\n'));

            }

        // 400 : bad request
        } else if (xhr.status  == 400) {
            console.error('Bad request!' + response.errors.join('\n'));

        // 405 : bad method (this one should never happen here)
        } else if (xhr.status  == 405) {
            console.error('Bad request method!' + response.errors.join('\n'));

        // 500 : any other error
        } else if (xhr.status  == 500) {
            console.error('Internal server error!' + response.errors.join('\n'));

        }
    }
};

// these are just for the example
var opts = {
    source:     'My *test* MDE **content** ... azerty `azerty()` azerty <http://google.com/> azerty.',
    options:    {}
};

// build the request data
var raw_data = "options=" + encodeURIComponent(JSON.stringify(opts.options)) + "&"
    + "source=" + encodeURIComponent(opts.source);

// make the POST request
xhr.open("POST", "mde_editor_interface.php", true);
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
xhr.send(raw_data);

// you can also make a simple GET request with the same data
xhr.open("GET", "mde_editor_interface.php?" + raw_data, true);
xhr.send();

// you can also post a JSON table
xhr.open("POST", "mde_editor_interface.php", true);
xhr.setRequestHeader("Content-Type", "application/json");
xhr.send(JSON.stringify(opts));

```


### PHP implementation

Below is a sample of a PHP usage of the interface via a [cURL request](http://php.net/manual/en/book.curl.php):

```php
// these are just for the example
$mde_options    = array();
$mde_source     = 'My *test* MDE **content** ... azerty `azerty()` azerty <http://google.com/> azerty.';

// send a post request
$curl_handler = curl_init();
curl_setopt($curl_handler, CURLOPT_URL, '.../www/mde-api.php');
curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_handler, CURLOPT_POST, true);
curl_setopt($curl_handler, CURLOPT_POSTFIELDS, array(
                                                   'options'   => json_encode($mde_options),
                                                   'source'    => $mde_source,
                                               ));

$curl_response = curl_exec($curl_handler);

// print the 'content' result if no error
if (!curl_errno($curl_handler)) {
    $curl_response = json_decode($curl_response, true);
    $curl_info = curl_getinfo($curl_handler);
    echo $curl_info['content'];
} else {
    echo 'ERROR : ' . curl_error($curl_handler);
}
curl_close($curl_handler);
```
