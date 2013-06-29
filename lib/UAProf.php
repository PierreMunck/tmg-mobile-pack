<?php 
/**
 * zzap core application framework 
 * 
 * Copyright (C) 2008 Dirk Ollmetzer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Core
 * @author Dirk Ollmetzer <hide@address.com>
 * @copyright 2008 Dirk Ollmetzer
 * @version $Id: UAProf.php 145 2008-10-21 19:37:09Z dollmetzer $
 * @license GPLv3 http://www.gnu.org/copyleft/gpl.html
 */


/**
 * UAProf Parser Class
 *
 * UAProf files are containing the technical data of Mobile User Agents (cell phones).
 * This class extract some of the key features, nacessary for adapted content delivery
 * in WEB/WAP Enviroments.
 *
 * PHP version 5
 *
 * @package Core
 * @author Dirk Ollmetzer <hide@address.com>
 * @copyright 2008 Dirk Ollmetzer
 * @version $Id: UAProf.php 145 2008-10-21 19:37:09Z dollmetzer $
 * @license GPLv3 http://www.gnu.org/copyleft/gpl.html
 */  
class UAProf
{

  protected $data;
  protected $cdata;
  protected $context;


  /**
   * Processes the UAProf XML File
   *
   * The XML file, normally provided by the hardware vendor descibes the
   * properties and attributes of a mobile handset.
   *
   * @param string $file Name of the XML-Filename. Usually a URL, sent as a
   *                     http-header ('HTTP_X_WAP_PROFILE') from the mobile handset.
   *
   * @return array User agent profile data with the following keys:
   *               - vendor
   *               - model
   *               - screensize
   *               - java_midp
   *               - java_configuration
   *               - java_package
   *               - accept_image
   *               - accept_audio
   *               - accept_video
   */
  public function process($file) {
    $this->data = array(
      'vendor'=>'',
      'model'=>'',
      'screensize'=>'',
      'java_midp'=>'',
      'java_configuration'=>'',
      'java_package'=>'',
      'accept_image'=>'',
      'accept_audio'=>'',
      'accept_video'=>''
    );

    $xmlParser = xml_parser_create();
    xml_set_element_handler($xmlParser, array(&$this,"tagStart"), array(&$this,"tagEnd"));
    xml_set_character_data_handler($xmlParser, array(&$this,"tagData"));
    xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, false);
    if (!($fp = fopen($file, "r"))) {
      die("could not open XML input from '$file'");
    }
    while ($data = fread($fp, 4096)) {
      if (!xml_parse($xmlParser, $data, feof($fp))) {
        die(sprintf("XML error: %s at line %d",
        xml_error_string(xml_get_error_code($xmlParser)),
        xml_get_current_line_number($xmlParser)));
      }
    }
    xml_parser_free($xmlParser);
    
    return $this->data;
  } /* process($file) */



  /**
   * returns UAProf data
   *
   * The associative contains several attributes of the user agent
   * (the mobile handset) as an array.
   *
   * @return array User agent profile data with the following keys:
   *               - vendor
   *               - model
   *               - screensize
   *               - java_midp
   *               - java_configuration
   *               - java_package
   *               - accept_image
   *               - accept_audio
   *               - accept_video
   */
  public function getData() {
    return $this->data;
  } /* getData() */
  
  public function getInfo($value) {
    if(isset($this->data[$value])){
      return $this->data[$value];
    }
    if($value == 'screenwidth' && isset($this->data['screensize'])){
      $width = substr ( $this->data['screensize'], 0, strpos($this->data['screensize'], 'x'));
      return $width;
    }
    if($value == 'screenheight' && isset($this->data['screensize'])){
      $height = substr( $this->data['screensize'], strpos($this->data['screensize'], 'x') + 1);
      return $height;
    }
    return FALSE;
  }
  
  /**
   * XML-parser start tag processing
   *
   * @param object $parser  XML-Parser
   * @param string $tagname Name des aktuellen XML-Tags
   * @param array  $attr    Attribute des aktuellen XML-Tags
   *
   * @return NULL
   */
  protected function tagStart($parser, $tagname, $attr) {
    switch($tagname) {
      case "prf:JavaPlatform":
        $this->context = 'java';
        break;
      case "prf:JavaPackage":
        $this->context = 'javapackage';
        break;
      case "prf:CcppAccept":
        $this->context = 'MIME Accept';
        break;
    }
  } /* function tagStart($parser, $tagname, $attr) */



  /**
   * XML-parser stop tag processing
   *
   * @param object $parser  XML-Parser
   * @param string $tagname Name des geschlossenen XML-Tags
   *
   * @return NULL
   */
  protected function tagEnd($parser, $tagname) {
    switch($tagname) {
      case "prf:Vendor":
        $this->data['vendor'] = $this->cdata;
        break;
      case "prf:Model":
        $this->data['model'] = $this->cdata;
        break;
      case "prf:ScreenSize":
        $this->data['screensize'] = $this->cdata;
        break;

      case "prf:JavaPlatform":
        $this->context = '';
        break;
      case "prf:JavaPackage":
        $this->context = '';
        break;
      case "prf:CcppAccept":
        $this->context = '';
        break;
      case "rdf:li":
        if ($this->context == 'java') {
          if (preg_match("/^Profile\/MIDP-/", $this->cdata)) {
            $this->data['java_midp'] = preg_replace("/^Profile\/MIDP-/", '', $this->cdata);
          }
          if (preg_match("/^Configuration\/CLDC-/", $this->cdata)) {
            $this->data['java_configuration'] = preg_replace("/^Configuration\/CLDC-/", '', $this->cdata);
          }
        }
        if ($this->context == 'javapackage') {
          $this->data['java_package'] .= $this->cdata." ";
        }
        if ($this->context == 'MIME Accept') {
          if (preg_match("/image\//", $this->cdata)) {
            $this->data['accept_image'] .= $this->cdata." ";
          }
          if (preg_match("/audio\//", $this->cdata)) {
            $this->data['accept_audio'] .= $this->cdata." ";
          }
          if (preg_match("/video\//", $this->cdata)) {
            $this->data['accept_video'] .= $this->cdata." ";
          }
        }
        break;
    }
    $this->cdata = "";
  } /* tagEnd($parser, $tagname) */


  
  /**
   * XML-parser data processing
   *
   * @param object $parser XML-Parser
   * @param string $data   Data of the XML-Tag
   *
   * @return NULL
   */
  protected function tagData($parser, $data) {
    $this->cdata .= trim($data);
  } /* tagData($parser, $data) */

}
?>