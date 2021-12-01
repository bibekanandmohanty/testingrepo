<?php

class Utils extends StoreComponent
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getXePath()
    {
        $requestUrl = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $protocol = strchr($requestUrl, '//', true);
        $xePathDomain = str_replace($protocol . '//', '', $requestUrl);
        $xePathDomain = strchr($xePathDomain, '/', true);
        $xePath = str_replace(array('www.', '.'), array('', '_'), $xePathDomain);
        return array($xePathDomain, $xePath);
    }

    /*
    @ Purpose : For those server which doesn't provide 'apache_request_headers' method in their request header.
     */
    protected function apache_request_headers()
    {
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                $out[$key] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    /*
    @ Purpose : Recursively copy all the files & folders
    @ Param : SourceFolder and DestinationFolder with path
     */
    protected function recurse_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    @copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /*
    @ Purpose : To log errors during installation
    @ Param : Text(what to log),append(whether append or replace),fileName(where to log)
     */
    protected function xe_log($text, $append = true, $fileName = '')
    {
        $file = ROOTABSPATH . 'xetool_log.log';
        if ($fileName) {
            $file = $fileName;
        }

        // Append the contents to the file to the end of the file and the LOCK_EX flag to prevent anyone else writing to the file at the same time
        if ($append) {
            @file_put_contents($file, $text . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            @file_put_contents($file, $text);
        }
    }

    /*
    @ This is a helper protected function to the above function
     */
    protected function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    protected function sanitizePath($path)
    {
        return $path = str_replace("/", DIRECTORY_SEPARATOR, $path);
    }

    protected function getToolURL()
    {
        $configXMLpath = $this->getNewXEpath() . XECONFIGXML; // xeconfig xml file
        $dom = new DomDocument();
        $dom->load($configXMLpath) or die("Unable to load xml");
        return $this->getDummyProductURL($dom);
    }
    protected function getXetoolDir()
    {
        $configXMLpath = $this->getNewXEpath() . XECONFIGXML; // xeconfig xml file
        $dom = new DomDocument();
        $dom->load($configXMLpath) or die("Unable to load xml");
        return $xetoolDir = $dom->getElementsByTagName('xetool_dir')->item(0)->nodeValue;
    }

    /*
     * Process the api request
     * @param string api method name
     * @return null
     */
    public function processApi()
    {
        $func = '';
        if (isset($_REQUEST['service'])) {
            $func = strtolower(trim(str_replace("/", "", $_REQUEST['service'])));
        } else if (isset($_REQUEST['reqmethod'])) {
            $func = strtolower(trim(str_replace("/", "", $_REQUEST['reqmethod'])));
        }

        if ($func) {
            if (method_exists($this, $func)) {
                $this->$func();
            } else {
                $this->response('invalid service', 406);
            }
        }
    }
}
