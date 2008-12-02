<?php

/*!
  \class   ezsubscriptiontype ezsubscriptiontype.php
  \ingroup eZDatatype
  \brief   Stores expiration date, subscription cycle and whether or not a subscription should be cancelled.
  \version 1.0
  \date    22. november 2005 14:43:54
  \author  Eirik Johansen
*/

include_once( "kernel/classes/ezdatatype.php" );
require_once("extension/nmsugarsoap/classes/sugarsoap.php");

define( 'EZ_DATATYPESTRING_SUGARCONTACT', "nmsugarcontact" );

class nmSugarContactType extends eZDataType
{
    /*!
      Konstruktor
    */
    
    var $soap;
    
    function nmSugarContactType()
    {
        $this->eZDataType( EZ_DATATYPESTRING_SUGARCONTACT, "Sugar contacts" );
        $this->soap =  new SugarSoap;
    }
    /*!
     Validates input on content object level
     \return EZ_INPUT_VALIDATOR_STATE_ACCEPTED or EZ_INPUT_VALIDATOR_STATE_INVALID if
             the values are accepted or not
    */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    /*!
     Fetches all variables from the object
     \return true if fetching of class attributes are successfull, false if not
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
    	// variable name
    	$varName = $base . '_contact_id_' . $contentObjectAttribute->attribute( 'id' );
    	
    	// get contact id array
    	$contactIDArray = $http->postVariable($varName);
    	
		// for each contact
		$contactData = array();
		foreach($contactIDArray as $contactID)
		{
			// get contact data from contact id
			$contactData[] 	= $this->soap->getContact($contactID);
		}

    	// convert contact array to XML
    	$contactXML = $this->soap->arrayToXML($contactData);
    	    	
    	// set contact id
    	$contentObjectAttribute->setAttribute( 'data_text', $contactXML );
    	
    	return true;
    }

	/*!
     Returns the content.
    */
    function objectAttributeContent( $contentObjectAttribute )
    {
    	// get contact XML
        $contactXML 	= $contentObjectAttribute->attribute( 'data_text' );

	    // convert XML to array
		$contactDataArray = $this->soap->xmlToArray($contactXML);
		
		return $contactDataArray;
    }

    /*!
     Validates all variables given on content class level
     \return EZ_INPUT_VALIDATOR_STATE_ACCEPTED or EZ_INPUT_VALIDATOR_STATE_INVALID if
             the values are accepted or not
    */
    function validateClassAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    /*!
     Fetches all variables inputed on content class level
     \return true if fetching of class attributes are successfull, false if not
    */
    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        return true;
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        return true;
    }

    /*!
     Returns the content data for the given content class attribute.
    */
    function classAttributeContent( $classAttribute )
    {
		// fetch contacts
		$contactsList = $this->soap->getContactsList("", 10000, " contacts.first_name asc");
		
		// return contacts
		return  array( 'contacts_list' => $contactsList);
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( $contentObjectAttribute )
    {
        return $this->title($contentObjectAttribute);
    }

    /*!
     Returns the value as it will be shown if this attribute is used in the object name pattern.
    */
    function title( $objectAttribute, $name = null )
    {
    	$attr = $objectAttribute->content();
    	
    	$i = 0;
    	$title = '';
    	foreach($attr['data'] as $contact)
    	{
    		if($i > 0)
    		{
    			$title .= ', ';
    		}
    		
    		$title .= $contact['first_name'] . ' ' . $contact['last_name'];
    		
    		$i++;
    	}
    	
        return $title;
    }

    /*!
     \return true if the datatype can be indexed
    */
    function isIndexable()
    {
        return true;
    }

}

eZDataType::register( EZ_DATATYPESTRING_SUGARCONTACT, "nmsugarcontacttype" );

?>
