<?php
namespace Rest;

/** 
  * Class Rest_Request
  * Holds the Request in a RestServer
  */

class Request {

    private $rest ;

    private $requestURI ;
    private $URIParts ; 

    private $requestMethod ;
    private $get ;
    private $post ;
    private $files ;
    private $headers ;

   /**
    * Constructor of Rest\Request
    * @param Rest\Server $rest = null, Parent Rest\Server
    */
    public function __construct(Server $rest=null) {

        // Sets most of the parameters
        $this->rest = $rest ;

        if(isset($_SERVER["REQUEST_METHOD"]))
            $this->requestMethod = $_SERVER["REQUEST_METHOD"];
        if(isset($_SERVER["REDIRECT_URL"])) 
            $this->requestURI = $_SERVER["REDIRECT_URL"];
        else if(isset($_SERVER["REQUEST_URI"]))
            $this->requestURI = $_SERVER["REQUEST_URI"];
        $this->URIParts = explode("/",$this->requestURI);

        $this->domain = $_SERVER["HTTP_HOST"];
        $this->URL = "http://".$this->domain.$this->requestURI;

        $this->get = $_GET?$_GET:array() ;
        $this->post = $_POST?$_POST:array() ;
        $this->files = $_FILES?$_FILES:array() ;

        $this->headers = array();
        /*
        foreach (getallheaders() as $name => $value) {
            $this->headers[$name] = $value;
        }
        */
        foreach($_SERVER as $name=>$value) {
            if(substr($name,0,5) == "HTTP_") {
                $hName = substr($name,5);
                $hName = str_replace("_",' ',$name);
                $hName = str_replace(" ",'-',$name);
                $this->headers[$hName] = $value;
            }
        }
    }

   /**
    * Return  Rest\Server used
    * @return Rest\Server
    */
    public function getRest() {
        return $this->rest;
    }

   /**
    * Returns if Request is GET
    * @return boolean
    */
    public function isGet() {
        if($this->requestMethod == "GET") {
            return true ;
        }
        return false;
    }

   /** 
    * Returns if Request is POST
    * @return boolean
    */
    public function isPost() {
        if($this->requestMethod == "POST") {
            return true ;
        }
        return false;
    }

   /**
    * Return if Request is PUT
    * @return boolean
    */
    public function isPut() {
        if($this->requestMethod == "PUT") {
            return true ;
        }
        return false;
    }

   /**
    * Return true if Request is DELETE
    * @return boolean
    */
    public function isDelete() {
        if($this->requestMethod == "DELETE") {
            return true ;
        }
        return false;
    }


   /** 
    * Get parameters sent with GET (url parameters)
    * @param mixed $k get[$key]
    * @return mixed
    */
    public function getGet($k=null) {
        if($k==null) return $this->get ;
        elseif (isset($this->get[$k])) return $this->get[$k];
        else return null;
    }

   /**
    * Return parameters sent on a POST
    * @param mixed $k post[$key]
    * @return mixed
    */
    public function getPost($k=null) {
        if($k==null) return $this->post ;
        elseif (isset($this->post[$k])) return $this->post[$k];
        else return null;
    }

   /**
    * Return FILES sent on a POST
    * @param mixed $k file[$key]
    * @return mixed
    */
    public function getFiles($k=null) {
        if($k==null) return $this->files ;
        else return $this->files[$k];
    }

   /**
    * Return content sent with PUT
    * @param mixed $k
    * @return mixed 
    */
    public function getPut($k=null) {
        $_PUT  = array();
        if($_SERVER['REQUEST_METHOD'] == 'PUT') {
            $putdata = file_get_contents('php://input');
            $exploded = explode('&', $putdata); 
            foreach($exploded as $pair) {
                $item = explode('=', $pair);
                if(count($item) == 2) {
                   $_PUT[urldecode($item[0])] = urldecode($item[1]);
                }
            }
        }
        if($k==null)return $_PUT ;
        else return $_PUT[$k];
    }

   /**
    * Return content sent with PUT
    * @return mixed 
    */
    public function getInput() {
        return file_get_contents('php://input');
    }

   /**
    * Return request BODY
    * @return string 
    */
    public function getBody() {
        return $this->getInput();
    }

   /**
    * Return Request Method(PUT, DELETE, OPTION, GET...)
    * @return string
    */
    public function getMethod() {
        return $this->requestMethod ;
    }

    /**
     * Return value of http header
     * @param string $k
     * @return string
     */
    public function getHeader($k) {
        if(isset($this->headers[$k])) return $this->headers[$k];
        else return null;
    }

    /**
     * Return E-tag/if-match requested
     * @return string
     */
    public function getETag() {
        return $this->getheader("If-Match");
    }

   /**
    * Set request method
    * @param string $method
    * @return Rest\Request
    */
    public function setMethod($method) {
        $this->requestMethod = $method ;
        return $this;
    }

   /**
    * Return the URI requested
    * @return string
    */
    public function getRequestURI() {
        return $this->requestURI ;
    }

   /**
    * Return part of the URL
    * @param int $i part of the uri
    * @return string
    */
    public function getParameter($i) {
        if(is_string($i)) {
            $map = $this->getRest()->getMatch();
            if(is_array($map)) {
                foreach($map as $n=>$name) {
                    if($name == ":".$i) {
                        return $this->getURI($n);
                    }else if($name == ":?".$i) {
                        return $this->getURI($n);
                    }
                }
            } else {
                return null;
            }
        } else if(is_int($i) && isset($this->URIParts[$i])) {
            return $this->URIParts[$i];
        } else {
            return null;
        }
    }

   /**
    * Return the URI or part of it
    * @param int $i part of the uri
    * @return string
    */
    public function getURI($i=null) {
        if($i !== null) return $this->getParameter($i);
        return $this->getRequestURI() ;
    }

   /**
    * Sets the URI to deal
    * @param string $uri
    * @return Rest\Request 
    */
    public function setURI($uri) {
        $this->requestURI = $uri;
        $this->URIParts = explode("/",$this->requestURI);
        return $this ;
    }

    /**
    * Return the extension of the URI (if any)
    * @return string
    */
    public function getExtension() {
        $reg = array();
        preg_match('@\.([a-zA-Z0-9]{1,5})$@',$this->getURI(),$reg);
        if(isset($reg[1])) {
            return $reg[1];
        }
        else {
            return false;
        }
    }

    /**
    * Return true if given mime is accepted
    * @param string $mime to check
    * @return boolean
    */
    public function acceptMime($mime) {
        if(($pos = strpos($_SERVER["HTTP_ACCEPT"],$mime)) !== false) {
            return true ;
        } else {
            return false ;
        }
    }

    /**
     */
    public function getSession($k) {
        if(!isset($_SESSION[$k])) return null;
        else return $_SESSION[$k];
    }

    /**
     */
    public function setSession($k,$v) {
        $_SESSION[$k] = $v;
        $$k = $v;
        @session_register($k);
        return $this;
    }

    /**
     */
    public function getCookie($k) {
        if(!isset($_COOKIE[$k])) return null;
        else return $_COOKIE[$k];
    }

    /*
     */
    public function setCookie($k,$v) {
        $_COOKIE[$k] = $v;
        setcookie($k,$v);
    }

}

