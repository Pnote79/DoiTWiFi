<?php
/*****************************
 *
 * RouterOS PHP API class v1.6
 * Author: Denis Basta
 * Contributors:
 *    Nick Barnes
 *    Ben Menking (ben [at] infotechsc [dot] com)
 *    Jeremy Jefferson (http://jeremyj.com)
 *    Cristian Deluxe (djcristiandeluxe [at] gmail [dot] com)
 *    Mikhail Moskalev (mmv.rus [at] gmail [dot] com)
 *
 * http://www.mikrotik.com
 * http://wiki.mikrotik.com/wiki/API_PHP_class
 *
 ******************************/

class RouterosAPI
{
    var $debug     = false; //  Show debug information
    var $connected = false; //  Connection state
    var $port      = 8728;  //  Port to connect to (default 8729 for ssl)
    var $ssl       = false; //  Connect using SSL (must enable api-ssl in IP/Services)
    var $timeout   = 3;     //  Connection attempt timeout and data read timeout
    var $attempts  = 5;     //  Connection attempt count
    var $delay     = 3;     //  Delay between connection attempts in seconds

    var $socket;            //  Variable for storing socket resource
    var $error_no;          //  Variable for storing connection error number, if any
    var $error_str;         //  Variable for storing connection error text, if any

    /* Check, can be var used in foreach  */
    public function isIterable($var)
    {
        return $var !== null
                && (is_array($var)
                || $var instanceof Traversable
                || $var instanceof Iterator
                || $var instanceof IteratorAggregate
                );
    }

    /**
     * Print text for debug purposes
     *
     * @param string      $text       Text to print
     *
     * @return void
     */
    public function debug($text)
    {
        if ($this->debug) {
            echo $text . "\n";
        }
    }


    /**
     *
     *
     * @param string        $length
     *
     * @return void
     */
    public function encodeLength($length)
    {
        if ($length < 0x80) {
            $length = chr($length);
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $length = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $length = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            $length = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length >= 0x10000000) {
            $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }

        return $length;
    }


    /**
     * Login to RouterOS
     *
     * @param string      $ip         Hostname (IP or domain) of the RouterOS server
     * @param string      $login      The RouterOS username
     * @param string      $password   The RouterOS password
     *
     * @return boolean                If we are connected or not
     */
    public function connect($ip, $login, $password)
    {
        for ($ATTEMPT = 1; $ATTEMPT <= $this->attempts; $ATTEMPT++) {
            $this->connected = false;
            $PROTOCOL = ($this->ssl ? 'ssl://' : '' );
            $context = stream_context_create(array('ssl' => array('ciphers' => 'ADH:ALL', 'verify_peer' => false, 'verify_peer_name' => false)));
            $this->debug('Connection attempt #' . $ATTEMPT . ' to ' . $PROTOCOL . $ip . ':' . $this->port . '...');
            $this->socket = @stream_socket_client($PROTOCOL . $ip.':'. $this->port, $this->error_no, $this->error_str, $this->timeout, STREAM_CLIENT_CONNECT,$context);
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                $this->write('/login', false);
                $this->write('=name=' . $login, false);
                $this->write('=password=' . $password);
                $RESPONSE = $this->read(false);
                if (isset($RESPONSE[0])) {
                    if ($RESPONSE[0] == '!done') {
                        if (!isset($RESPONSE[1])) {
                            // Login method post-v6.43
                            $this->connected = true;
                            break;
                        } else {
                            // Login method pre-v6.43
                            $MATCHES = array();
                            if (preg_match_all('/[^=]+/i', $RESPONSE[1], $MATCHES)) {
                                if ($MATCHES[0][0] == 'ret' && strlen($MATCHES[0][1]) == 32) {
                                    $this->write('/login', false);
                                    $this->write('=name=' . $login, false);
                                    $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $MATCHES[0][1])));
                                    $RESPONSE = $this->read(false);
                                    if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                                        $this->connected = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                fclose($this->socket);
            }
            sleep($this->delay);
        }

        if ($this->connected) {
            $this->debug('Connected...');
        } else {
            $this->debug('Error...');
        }
        return $this->connected;
    }


    /**
     * Disconnect from RouterOS
     *
     * @return void
     */
    
    public function disconnect()
    {
        // let's make sure this socket is still valid.  it may have been closed by something else
        if( is_resource($this->socket) ) {
            fclose($this->socket);
        }
        $this->connected = false;
        $this->debug('Disconnected...');
    }
    

    /**
     * Parse response from Router OS
     *
     * @param array       $response   Response data
     *
     * @return array                  Array with parsed data
     */
    public function parseResponse($response)
    {
        if (is_array($response)) {
            $PARSED      = array();
            $CURRENT     = null;
            $singlevalue = null;
            foreach ($response as $x) {
                if (in_array($x, array('!fatal','!re','!trap'))) {
                    if ($x == '!re') {
                        $CURRENT =& $PARSED[];
                    } else {
                        $CURRENT =& $PARSED[$x][];
                    }
                } elseif ($x != '!done') {
                    $MATCHES = array();
                    if (preg_match_all('/[^=]+/i', $x, $MATCHES)) {
                        if ($MATCHES[0][0] == 'ret') {
                            $singlevalue = $MATCHES[0][1];
                        }
                        $CURRENT[$MATCHES[0][0]] = (isset($MATCHES[0][1]) ? $MATCHES[0][1] : '');
                    }
                }
            }

            if (empty($PARSED) && !is_null($singlevalue)) {
                $PARSED = $singlevalue;
            }

            return $PARSED;
        } else {
            return array();
        }
    }


    /**
     * Parse response from Router OS
     *
     * @param array       $response   Response data
     *
     * @return array                  Array with parsed data
     */
    public function parseResponse4Smarty($response)
    {
        if (is_array($response)) {
            $PARSED      = array();
            $CURRENT     = null;
            $singlevalue = null;
            foreach ($response as $x) {
                if (in_array($x, array('!fatal','!re','!trap'))) {
                    if ($x == '!re') {
                        $CURRENT =& $PARSED[];
                    } else {
                        $CURRENT =& $PARSED[$x][];
                    }
                } elseif ($x != '!done') {
                    $MATCHES = array();
                    if (preg_match_all('/[^=]+/i', $x, $MATCHES)) {
                        if ($MATCHES[0][0] == 'ret') {
                            $singlevalue = $MATCHES[0][1];
                        }
                        $CURRENT[$MATCHES[0][0]] = (isset($MATCHES[0][1]) ? $MATCHES[0][1] : '');
                    }
                }
            }
            foreach ($PARSED as $key => $value) {
                $PARSED[$key] = $this->arrayChangeKeyName($value);
            }
            return $PARSED;
            if (empty($PARSED) && !is_null($singlevalue)) {
                $PARSED = $singlevalue;
            }
        } else {
            return array();
        }
    }


    /**
     * Change "-" and "/" from array key to "_"
     *
     * @param array       $array      Input array
     *
     * @return array                  Array with changed key names
     */
    public function arrayChangeKeyName(&$array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                $tmp = str_replace("-", "_", $k);
                $tmp = str_replace("/", "_", $tmp);
                if ($tmp) {
                    $array_new[$tmp] = $v;
                } else {
                    $array_new[$k] = $v;
                }
            }
            return $array_new;
        } else {
            return $array;
        }
    }


    /**
     * Read data from Router OS
     *
     * @param boolean     $parse      Parse the data? default: true
     *
     * @return array                  Array with parsed or unparsed data
     */
    public function read($parse = true)
    {
        $RESPONSE     = array();
        $receiveddone = false;
        while (true) {
            // Read the first byte of input which gives us some or all of the length
            // of the remaining reply.
            $BYTE   = ord(fread($this->socket, 1));
            $LENGTH = 0;
            // If the first bit is set then we need to remove the first four bits, shift left 8
            // and then read another byte in.
            // We repeat this for the second and third bits.
            // If the fourth bit is set, we need to remove anything left in the first byte
            // and then read in yet another byte.
            if ($BYTE & 128) {
                if (($BYTE & 192) == 128) {
                    $LENGTH = (($BYTE & 63) << 8) + ord(fread($this->socket, 1));
                } else {
                    if (($BYTE & 224) == 192) {
                        $LENGTH = (($BYTE & 31) << 8) + ord(fread($this->socket, 1));
                        $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                    } else {
                        if (($BYTE & 240) == 224) {
                            $LENGTH = (($BYTE & 15) << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                        } else {
                            $LENGTH = ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                        }
                    }
                }
            } else {
                $LENGTH = $BYTE;
            }

            $_ = "";

            // If we have got more characters to read, read them in.
            if ($LENGTH > 0) {
                $_      = "";
                $retlen = 0;
                while ($retlen < $LENGTH) {
                    $toread = $LENGTH - $retlen;
                    $_ .= fread($this->socket, $toread);
                    $retlen = strlen($_);
                }
                $RESPONSE[] = $_;
                $this->debug('>>> [' . $retlen . '/' . $LENGTH . '] bytes read.');
            }

            // If we get a !done, make a note of it.
            if ($_ == "!done") {
                $receiveddone = true;
            }

            $STATUS = socket_get_status($this->socket);
            if ($LENGTH > 0) {
                $this->debug('>>> [' . $LENGTH . ', ' . $STATUS['unread_bytes'] . ']' . $_);
            }

            if ((!$this->connected && !$STATUS['unread_bytes']) || ($this->connected && !$STATUS['unread_bytes'] && $receiveddone)) {
                break;
            }
        }

        if ($parse) {
            $RESPONSE = $this->parseResponse($RESPONSE);
        }

        return $RESPONSE;
    }


    /**
     * Write (send) data to Router OS
     *
     * @param string      $command    A string with the command to send
     * @param mixed       $param2     If we set an integer, the command will send this data as a "tag"
     *                                If we set it to boolean true, the funcion will send the comand and finish
     *                                If we set it to boolean false, the funcion will send the comand and wait for next command
     *                                Default: true
     *
     * @return boolean                Return false if no command especified
     */
    public function write($command, $param2 = true)
    {
        if ($command) {
            $data = explode("\n", $command);
            foreach ($data as $com) {
                $com = trim($com);
                fwrite($this->socket, $this->encodeLength(strlen($com)) . $com);
                $this->debug('<<< [' . strlen($com) . '] ' . $com);
            }

            if (gettype($param2) == 'integer') {
                fwrite($this->socket, $this->encodeLength(strlen('.tag=' . $param2)) . '.tag=' . $param2 . chr(0));
                $this->debug('<<< [' . strlen('.tag=' . $param2) . '] .tag=' . $param2);
            } elseif (gettype($param2) == 'boolean') {
                fwrite($this->socket, ($param2 ? chr(0) : ''));
            }

            return true;
        } else {
            return false;
        }
    }


    /**
     * Write (send) data to Router OS
     *
     * @param string      $com        A string with the command to send
     * @param array       $arr        An array with arguments or queries
     *
     * @return array                  Array with parsed
     */
    public function comm($com, $arr = array())
    {
        $count = count($arr);
        $this->write($com, !$arr);
        $i = 0;
        if ($this->isIterable($arr)) {
            foreach ($arr as $k => $v) {
                switch ($k[0]) {
                    case "?":
                        $el = "$k=$v";
                        break;
                    case "~":
                        $el = "$k~$v";
                        break;
                    default:
                        $el = "=$k=$v";
                        break;
                }

                $last = ($i++ == $count - 1);
                $this->write($el, $last);
            }
        }

        return $this->read();
    }

    /**
     * Standard destructor
     *
     * @return void
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}

/**
 * A simple helper class to
 * persistently store primitive data.
 * 
 * Note:
 * This class is implemented as a singleton
 * you can not instanciate it directly.
 * use LocalStorage::getInstance() to get an instance
 * of LocalStorage.
 * 
 * Usage:
 * LocalStorage::getInstance()->setValue('foo', 'bar');		// Set "foo" to the value "bar".
 * LocalStorage::getInstance()->commit();					// Save all settings to the filesystem.
 * LocalStorage::getInstance()->getValue('foo');			// returns bar.
 * echo (string)LocalStorage::getInstance();				// Casting localstorage to string will return a JSON-representation of all values.
 * 
 * @author Andre Uschmann
 *
 */

 class LocalStorage {
	
	/** Default filepath to the file where the data will be stored. */
	const DEFAULT_FILENAME = "../json/localStorage.json";	
	/** Static reference to hold the singleton instance. */
	protected static $instance = null;
	/** An associative array to store the key value pairs **/
	private $data = array();
	private $filename;
	
	/**
	 * Forbid external instantiation
	 */
	protected function __construct($filename) {
		$this->filename = $filename;
		if(file_exists($this->filename))
			$this->data = json_decode(file_get_contents($this->filename));
		else 
			$this->data = json_decode("{}");
	}
	
	/**
	 * Return the one and only instance
	 * of this Class.
	 * @return LocalStorage
	 */
	public static function getInstance($filename = self::DEFAULT_FILENAME){
		if(self::$instance == null){
			self::$instance = new self($filename);
		}
		return self::$instance;
	}
	
	/**
	 * Sets the name of the file
	 * where all data will be stored when
	 * calling commit().
	 * @param string $filename
	 */
	public function setFilename($filename){
		$this->filename = $filename;
	}
	
	/**
	 * Sets a new value or updates
	 * a value.
	 * @param string $key
	 * @param mixed $value
	 */
	public function setValue($key, $value){
		$this->data->$key = $value;
	}
	
	/**
	 * Unsets the value with the
	 * given key.
	 * @param string $key
	 */
	public function unsetValue($key){
		if(isset($this->data->$key))
			unset($this->data->$key);
	}
	
	/**
	 * Gets the value by its key.
	 * @param unknown $key
	 * @return unknown
	 */
	public function getValue($key){
		if(isset($this->data->$key))
			return $this->data->$key;
		return $key;
	}
	
	/**
	 * Deletes all values.
	 */
	public function clear(){
		$this->data = array();
	}
	
	/**
	 * Saves the data to the filesystem.
	 */
	public function commit(){
		file_put_contents($this->filename, json_encode($this->data));
	}
	
	/**
	 * Returns all data as
	 * a JSON-string
	 */
	public function toJson(){
		return json_encode($this->data);
	}
	
	/**
	 * Override the default
	 * __toString() method to return a JSON-string
	 * of all values.
	 * @return string
	 */
	public function __toString() {
		return $this->toJson();
	}
}

class User{

    // I need a property to store the json file name.
    private $json_file;

    // I will need a property to give me all stored_users form the file.
    private $stored_users;

    // Next i need a property to tell me how many users are stored in the file.
    private $number_of_records;

    // Also i need an array to hold all user ids.
    // I will use this property to autoincrement the user id.
    private $ids = [];

    // And last i need an array that holds all usernames.
    // With this property i will validate the username.
    private $usernames = [];

    // I start with the constructor.
    // In the constructor i am going to set all properties with their values.
    public function __construct($file_path){

        // I will store the filepath in the $json_file property.
        $this->json_file = $file_path;

        // Next i will get the data, from the json file, and store them in the $stored_users property.
        $this->stored_users = json_decode(file_get_contents($this->json_file), true);

        // Next i will set the number of users stored in the file in the number_of_records property.
        $this->number_of_records = count($this->stored_users);

        // First i will check the number_of_records property
        // to see if there are any records in the file.
        if($this->number_of_records != 0){
            // if there are records in the file, i will loop through
            // the stored_users property..
            foreach ($this->stored_users as $user) {
                // .. and add all users ids, in the $ids array property.
                array_push($this->ids, $user['id']);
                // .. and all usernames to the $usernames properties.
                array_push($this->usernames, $user['name']);
            }
        }
    }

    // I will write a method to create and increment the id field.
    private function setUserId(array $user){
        if($this->number_of_records == 0){
            $user['id'] = 1;
        }else{
            $user['id'] = max($this->ids) + 1;
        }
        return $user;
    }

    // I am going to need a method which will store the new data,
    // or the edited data, back to the file.
    private function storeData(){
        file_put_contents(
            $this->json_file,
            json_encode($this->stored_users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX
        );
    }

    // Next i need a method to write the user in the file.
    public function insertNewUser(array $new_user){
        // The first thing i have to do inside the method is to add the
        // id field and it's value.
        $user_with_id_field = $this->setUserId($new_user);
        // Now the $user_with_id_field variable holds the user array plus
        // the id field.

        // Next i add the user to the stored data array property.
        array_push($this->stored_users, $user_with_id_field);

        // Next before i store the user in the file i will run
        // a username validation.
        // I will check the number_of_records property and..
        if($this->number_of_records == 0){ // ..if this is the first record..
            // ..we store the user without validating the username.
            $this->storeData();
        }else{
            // ..but if we have already stored users in the file,
            // i'll first check if the new username is not included in the
            // $usernames array property...
            if(!in_array($new_user['name'], $this->usernames)){
                // ..and then i store the user in the file.
                $this->storeData();
            }
        }
    }

    // I need a method to update the user.
    public function updateUser($user_id, $field, $value){
        // I will loop through the $stored_users array property and search
        // for the $user_id.
        foreach($this->stored_users as $key => $stored_user){
            if($stored_user['id'] == $user_id){ // If i have a match..
                // .. i target the records $field, and set the $value.
                $this->stored_users[$key][$field] = $value;
            }
        }
        // Next i use the storeData method, to write the data to the file.
        $this->storeData();
    }

    // I need a delete user method.
    public function deleteUser(int $user_id){
        // In order to delete the user i have to loop trough all the
        // users and search for the given id.
        foreach($this->stored_users as $key => $stored_user){
            if($stored_user['id'] == $user_id){
                // If there is a match i will unset the whole user.
                unset($this->stored_users[$key]);
                // This will remove the user from the $stored_users array property.
            }
        }
        // And i have to write back to the file, the remaing users.
        $this->storeData();
    }

    // I need a method to delete all users.
    public function deleteAllUsers(){
        // To remove all users, i have to loop trough the $stored_users
        // array and unset every index.
        foreach ($this->stored_users as $key => $value) {
            unset($this->stored_users[$key]);
        }
        // After removing all users, we are left with an epmty array.
        // But still we have to write the empty array to the file.
        $this->storeData();
    }

    // And last i need a method to read the users from the file.
    public function getUsers(){
        // This is the easiest task.
        // We just return the $stored_users property.
        return $this->stored_users;
    }
}