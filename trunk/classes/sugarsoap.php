<?php

include_once("extension/nmsugarsoap/nusoap/nusoap.php");
include_once( "lib/ezutils/classes/ezini.php" );
include_once( "lib/ezxml/classes/ezdomdocument.php" );
include_once( "lib/ezxml/classes/ezxml.php" );

class SugarSoap
{
    var $proxy;
    var $sess;
    var $ini;
    var $cacheFileContactsList;
    
    function SugarSoap()
    {
    	// initiate INI file
		$this->ini = eZINI::instance( 'sugar.ini' );
    	
    	if($this->ini->hasVariable( 'Cache', 'ContactsListFile' ))
    	{
    		$this->cacheFileContactsList = $this->ini->variable( 'Cache', 'ContactsListFile' );
    	}
    	else
    	{
    		$this->cacheFileContactsList = 'var/cache/contactslist.xml';
    	}
    }
    
    function soapConnect()
    {	
    	/*
    	$http 			= eZHTTPTool::instance();
    	$http->removeSessionVariable( 'EventStartTime');
    	*/
    	
		// get url
		$soap_url = $this->ini->variable( 'SOAP', 'URL' );
		
		// initiate new soap client
        $soapclient = new soapclientnusoap($soap_url,true);
        $this->proxy = $soapclient->getProxy();
        
        eZDebug::writeNotice( "SOAP client initiated", 'SugarSoap:soapConnect' );
    }
    
    function arrayToXML($array, $rootNodeName = 'root_node')
	{
		 // create dom document
		$doc = new eZDOMDocument( $rootNodeName );
		
		// root
		$root =& $doc->createElementNode( $rootNodeName );
        $doc->setRoot( $root );
		
		foreach($array as $k => $v)
		{
			if(is_array($v))
			{
				// create element node
				$elementNode = $doc->createElementNode( 'element' );
				
				foreach($v as $key => $val)
				{
					$this->createXMLNode($doc, $elementNode, $key, $val);
				}
				
				// add element to root
				$root->appendChild($elementNode);
			}
			else
			{
				$this->createXMLNode($doc, $root, $k, $v);
			}
		}
		
		// turn into xml
		$xml = $doc->toString();
		
		return $xml;
	}
	
	function createXMLNode(&$doc, &$root, $key, $value)
	{
		$tmp = $doc->createElementNode( $key );
		$tmp->appendChild( $doc->createTextNode( $value ) );
		$root->appendChild($tmp);
		unset($tmp);
	}

	function xmlToArray($xmlData, $rootNodeName = 'root_node')
	{
		// turn xml into dom tree object
		$xml 	= new eZXML();
		$dom 	= $xml->domTree( $xmlData );

		$array = array();
		$idArray = array();

		// if a dom document was created
		if($dom)
		{
			$elementArray = $dom->elementsByName('element');
			
			// for each element
			$elementData = array();
			foreach($elementArray as $k => $v)
			{
				foreach($v->children() as $childNode)
				{
					$name 		= $childNode->name();
					$content 	= $childNode->children();
					
					if(count($content) > 0 AND is_object($content[0]))
					{
						$data 		= $content[0]->content();
					}
					else
					{
						$data = '';
					}
					
					$elementData[$name] = $data;
					
					if($name == 'id')
					{
						$idArray[] = $data;
					}
				}
				
				$array[] = $elementData;
			}
		}
		
		return array(	'data' 		=> $array, 
						'id_array'	=> $idArray);
	}
    
    function login()
    {
    	// connect to soap client
		$this->soapConnect();
    	
    	// not bother logging in if we already have done so
    	if($this->getLoginSession() == false)
    	{
    		// fetch user name and password from INI file
	    	$userName = $this->ini->variable( 'SOAP', 'UserName' );
	    	$password = $this->ini->variable( 'SOAP', 'Password' );
	    	
	    	// params
		    $params = array('user_name' => $userName,
					        'password'  => md5($password),
					        'version'   => '.01');
		    
		    // perform login and store session id
		    $result = $this->proxy->login($params,'MyApp');
		    // $session = $result['error']['number']==0 ? $result['id'] : null;	
		    
		    if($result['error']['number']==0)
		    {
		    	$this->setLoginSession($result['id']);
		    }
		    
		    if($this->getLoginSession() == false)
		    {
		    	 eZDebug::writeError( "Unable to login to Sugar installation. Sugar said: " . $result['error']['description'], 'class::sugarSoap' );
		    }
		    else
		    {
				eZDebug::writeNotice( "Successfully logged in. Session id " . $this->getLoginSession(), 'SugarSoap::login' );		    	
		    }
    	}
    	else
    	{
    		eZDebug::writeNotice( "Already logged in. Session id " . $this->getLoginSession(), 'SugarSoap::login' );	
    	}
	    
	    return $this->getLoginSession();
	}
	
	function setLoginSession($session)
	{
		/*
		$http 			= eZHTTPTool::instance();
		$http->setSessionVariable( 'SugarLoginSession', $session );
		*/
		$this->sess = $session;
		
		eZDebug::writeNotice( "Login session set to " . $session, 'SugarSoap:setLoginSession' );
	}
	
	function getLoginSession()
	{
		/*
		$http 			= eZHTTPTool::instance();
		return $http->sessionVariable( 'SugarLoginSession' );
		*/
		return $this->sess;
	}
	
	function getUserID()
	{
		return $this->proxy->get_user_id($this->sess);
	}
	
	function getContact($id = '')
	{
		$this->login();
		
		$result = $this->proxy->get_entry(
			$this->getLoginSession(), 
			'Contacts', 
			$id, 
			array(
				'id', 
				'first_name', 
				'last_name', 
				'phone_mobile', 
				'account_name', 
				'email1', 
				'phone_work'));
				
		$formatedData = $this->formatData($result, true);

		return $formatedData[0];
	}
	
	function formatData($result, $ignoreResultCount = false)
	{
		$tmp = array();
				
		if($result['result_count'] > 0 OR $ignoreResultCount)
		{ 
		    foreach($result['entry_list'] as $record)
		    {
		        $tmp[] = $this->nameValuePairToSimpleArray($record['name_value_list']);
			}
		}
		
		return $tmp;
	}
	
	function getContactsList($query='',$maxnum=0,$orderby=' contacts.last_name asc')
	{
		// get cache time limit
		$cacheTimeLimitInMinutes = $this->ini->variable( 'Cache', 'ContactsListMinutes' );
		
		if($cacheTimeLimitInMinutes)
		{
			$cacheTimeLimit = time() - (60 * $cacheTimeLimitInMinutes);
			$useCache = true;
		}
		else
		{
			$useCache = false;
		}
		
		// if a cache file exists and it is not too old
		if(file_exists($this->cacheFileContactsList) AND $useCache AND filectime($this->cacheFileContactsList) > $cacheTimeLimit)
		{
			// read xml file
			$lines = file($this->cacheFileContactsList);
			
			$result = unserialize($lines[0]);
	
			eZDebug::writeNotice( "Fetched contacts list from cache", 'SugarSoap::getContactsList' );
	
			/*
			// for each line
			$xml = '';
			foreach ($lines as $line) {
			   $xml .= $line . "\n";
			}
			*/
		}
		
		// fetch from origin and store in cache
		else
		{			
			$this->login();
		
			// this call takes aprox 7 seconds
			// TODO: Cache result
		    $result = $this->proxy->get_entry_list(
		        $this->getLoginSession(),
		        'Contacts',
		        $query,
		        $orderby,
		        0,
		        array(
		            'id',
		            'first_name',
		            'last_name',
		            'email1', 
		            'phone_work', 
		            'phone_mobile', 
		            'account_name'
		        ),
		        $maxnum,
		        false
		    );
		    
		    eZDebug::writeNotice( "Got contacts list from webservice.", 'SugarSoap::getContactsList' );
		    
		    if($useCache)
		    {
		    	  // serialize result
			    $serialized = serialize($result);
			    
			    // open file handle
				$handle = fopen($this->cacheFileContactsList, 'w+');
				chmod($this->cacheFileContactsList, 0777);
				
				// write to file
				fwrite($handle, $serialized);
				
				// close file handle
				fclose($handle);
				
				eZDebug::writeNotice( "Stored contacts list in cache", 'SugarSoap::getContactsList' );
		    }
		}
	    
	    $formatedData = $this->formatData($result);
	    
	    return $formatedData;
	}
	
	function nameValuePairToSimpleArray($array)
	{
	    $my_array=array();
	    while(list($name,$value)=each($array))
	    {
	        $my_array[$value['name']] = $value['value']; // Here we used to utf8-encode text, but it no longer seems to be neccessary. If it needs to be reinserted, use: utf8_encode($value['value']);
	    }
	    return $my_array;
	} 
}
?>  