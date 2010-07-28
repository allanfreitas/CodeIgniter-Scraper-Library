<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Scraper Library
 *
 * A library to provide basic XPath scraping support
 *
 * @package        CodeIgniter
 * @author        Kyle J. Dye | www.kyledye.com | kyle@kyledye.com
 * @copyright    Copyright (c) 2010, Kyle J. Dye.
 * @license        http://codeigniter.com/user_guide/license.html
 * @link            http://kyledye.com
 * @version        Version 0.1
 */

class Scraper {
    
    var $CI;
    var $url;
    var $old_setting;
    var $html;
    var $xpath;
    var $raw_file;
    var $elements;
    
    /**
     *    Construct function
     *
     *  Initializes core functionality, clears previous XML errors, etc.
     *  Loads the URL helper for assistance in prepping URLS
     */
    
    function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->helper('url');
        $this->old_setting = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $this->html = new DOMDocument();
    }
    
    /**
     *  Capture DOM function
     *
     *  Initial function that captures the url or from the contents
     *  of a file (usually uses the file_get_contents() function)  
     *
     */
    
    function capture_dom($url = "", $raw_file = null) {
        if(empty($url)):
            if(empty($raw_file))
                return(FALSE);
            $this->raw_file = $raw_file;
            $this->html->loadHtml($this->raw_file);
            $this->xpath = new DOMXPath($this->html);
            return(TRUE);
        else:
            $url = prep_url($url);
            $this->url = $url;
            $this->html->loadHtmlFile($this->url);
            $this->xpath = new DOMXPath($this->html);
            return(TRUE);
        endif;
    }
    

    /**
     *    Find function
     *
     *  Provides a primative interface to capture values from DOM.
     *  Allows for two different styles of queries, examples below.
     *
     *  Example One: A flat query
     *  A typical query where you want to simply capture a single value
     *    Example usage:
     *
     *  $page_title = $this->scraper->find(array('name' => 'results', 'query' => '//title'));
     *
     *  @returns: $page_title = array('name' => 'This is an example website title');
     *
     *
     *  A more complex query can have subqueries (only one level is supported in this version)
     *    For this example, imagine I am scraping a table with many rows and 3 columns (first name,
     *    last_name, and an email link with a mailto href)
     *
     *  Complex Example:
     *
     *  $table_rows = $this->scraper->find(array(
     *        'name' => 'rows', // optional - defaults to 'results'
     *        'query' => '/table[@id="mytable"]/tbody//tr', //required
     *        'subqueries' => array( // optional - but requires associative array for ease of use
     *            'first_name' => '//td[1]',
     *            'last_name' => '//td[2]',
     *            'email' => '//td[3]/a/@href'
     *        )
     *  ));
     *
     *    @returns: $table_rows = array(
     *        'rows' => array(
     *            array('first_name' => 'Kyle', 'last_name' => 'Dye', 'email' => 'mailto:example@example.com'),
     *            array('first_name' => 'Joe', 'last_name' => 'Schmoe', 'email' => 'mailto:tacobellhoe@runsfromtheboarder.com'),
     *            É and so on É
     *    )
     *  );
     *
     */

    function find($xpaths = array()) {

            if(!isset($xpaths['query']))
                return(FALSE);
            if(!isset($xpaths['name']))
                $master_name = "results";
            else
                $master_name = $xpaths['name'];
            
            if(!isset($xpaths['subqueries'])):
                return(array($master_name => $this->xpath->query($xpaths['query'])->item(0)->nodeValue));
            endif;
            
            $returns = array();
            $items = $this->xpath->query($xpaths['query']);
            
            if($items->length == 0)
                return(FALSE);
                
            $inc = 0;
            
            foreach($items as $item):
            
                $tmp_dom = new DOMDocument;
                $tmp_dom->appendChild($tmp_dom->importNode($item, true));
                $xpath = new DOMXPath($tmp_dom);
                foreach($xpaths['subqueries'] as $sq_label => $sq):
                    $returns[$inc][$sq_label] = trim($xpath->query($sq)->item(0)->nodeValue);
                endforeach;
                
                $inc++;
            
            endforeach;
            
            return(array($master_name => $returns));
    }
    
    /**
     *    Cleanup Function
     *
     *    Should be run after querying to clear out XML errors and to have tidy code :)
     */

    public function cleanup() {
        libxml_clear_errors();
        libxml_use_internal_errors($this->old_setting);
    }
    
}

?>