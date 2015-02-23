<?php
/*
Module Name:  xml_file
Module Source: http://www.moreglory.net/
Description: A convenient XML file wrapper designed for datastore and retrieval.
Version: 1.0
Author: Benjamin Hoogterp
Author URI: http://www.BenjaminHoogterp.com/
License: MIT

*/

class xml_file
    {
    public $gid;

    public $Doc;
    public $XQuery;
    public $err;

    public $filename;

    public $mode;

    public $loaded;
    public $modified;
    public $readonly;
    public $notidy;

    public $sourceDate;
    public $saveMethod;

    
    function __construct()
        {
        $this->gid = uniqid("XMLFILE_");

        $this->clear();
        $n = func_num_args();
        $a = func_get_args();

//print "<br/>xml_file::__construct(" . print_r($a) . ")";
//debug_print_backtrace();
        if ($n>=1)
            {
            if (is_string($a[0]))
                {
                if (substr($a[0],0,1)=="<") $this->loadXML($a[0]);
                else if (file_exists($a[0])) $this->load($a[0]);
                }
            if (is_object($a[0])) $this->loadDoc($a[0]);
            }
        else
            {
//print "<br/> $gid::no load";
            }
        if ($n>=2)
            {
//          if (strstr(strtolower($a[1]), "modenone")) $this->mode='modenone';
            if (strstr(strtolower($a[1]), "xml")) $this->mode='xml';
            if (strstr(strtolower($a[1]), "xhtml")) $this->mode='xhtml';
            if (strstr(strtolower($a[1]), "readonly")) $this->readonly=true;
            if (strstr(strtolower($a[1]), "notidy")) $this->notidy=true;
            }

        if ($n>=3)
            {
            if (is_string($a[2]))
                {
                if (substr($a[2],0,1)=="<") $this->transformXSL($a[2]);
                else if (file_exists($a[2])) $this->transform($a[2]);
                }
            }

//print "<br/><b><u>XMLFILE LOAD</u>:</b> ".$this->stat(true);
        }
    function __destruct() 
        {
//print "<br/>$this - destruct()";
        unset($this->Doc);
        unset($this->XQuery);
        }

    function resolve_filename($fn)
        {
        if (file_exists($fn)) return $fn;
        if (file_exists($r="source/$fn")) return $r;
//      if (file_exists($r=WP_PLUGIN_DIR."zobjects/source/$fn")) return $r;
        return $fn;     //  nothing else to try....
        }
        
    public function clear()
        {
//print "<br/>XMLFILE::clear(), ID=".$this->gid;
        unset($this->sourceDate);
        unset($this->Doc);
        unset($this->XQuery);
        $this->loaded=false;
        $this->filename="";
        $this->mode="";
        $this->modified=false;
        $this->readonly=false;
        $this->notidy = false;
        return false;
        }

    public function stat($Nnl=false,$Sht=false)
        {
        if (!$this->loaded) return "[NOT LOADED: ".$this->gid."]";
        if ($Sht)
            $s = "FN: ".$this->filename;
        else
            $s="<b>gid:</b> $this->gid\n<b>Filename:</b> $this->filename\n<b>Loaded:</b> $this->sourceDate" . ($this->can_save()?"\n<b>[CANSAVE]</b>":"") . ($this->readonly?"\n<b>[READ ONLY]</b>":"") . ($this->modified?"\n<b>[MODIFIED]</b>":"") . (isset($this->Doc)?"":"[NO DOC]");
        if (!!$Nnl) str_replace("\n", "  ", $s);
        return $s;
        }

    public function __toString() {return $this->stat();}
        
    private function init($D = 0)
        {
        $this->sourceDate = $D == 0 ? time() : $D;
        $this->loaded = isset($this->Doc);
//print "<Br>XXX=".get_class($this->Doc);
        if (get_class($this->Doc) != "DOMDocument") juniper()->backtrace("Invalid Object Type: " . get_class($this->Doc));

        $this->XQuery = $this->loaded ? new DOMXPath($this->Doc) : null;
//print "<br/> XMLFILE::init() - ".$this;
//debug_print_backtrace();
        return $this->loaded;
        }
        
    function load($f)
        {
//print "<br/> XMLFILE::load($f)" . (file_exists($f)?"":"DOES NOT EXIST");
        $this->clear();
        $f = self::resolve_filename($f);
        if (!file_exists($f)) return false;

        $this->filename = $f;

        $this->Doc = new DomDocument;
        $res =  $this->Doc->load($f);
        if ($res === false) 
            {
            echo "<br />Failed to read: $f";
            juniper()->backtrace("Failed to read: $f");
            return $this->clear();
            }

        $x = $this->init(filemtime($f));
//print "<br/> XMLFILE::load($f), $this";
        return $x;
        }
        
    function loadXML($x)
        {
        $this->clear();
        
        $this->Doc = new DomDocument;
        $res =  @$this->Doc->loadXML($x);
//      if ($res === false) {echo "<br />Failed to load XML...";return $this->clear();}
        

        $x = $this->init();
//print "<br/> XMLFILE::loadXMl(...), $this";
        return $x;
        }

    function loadDoc($D)
        {
//print "<br/>XMLFILE::loadDoc";
        $this->clear();
        $this->Doc = $D;
        $x =  $this->init();
//print "<br/> XMLFILE::loadDoc(...), $this";
        return $x;
        }

    function can_save($f="") {return $this->loaded && ($f!="" || $this->filename != "") && !$this->readonly;}

    function saveXML($style="auto")
        {
//print "<br/>XMLFILE::saveXML ".$this;
        if (!isset($this->Doc))
            {
//          juniper()->backtrace();
//debug_print_backtrace();
            die("<br/><b><u>XMLFILE</u>::saveXML:</b> No Doc for save, $this");
            }
        $s = $this->Doc->saveXML();
        if (!$this->notidy) 
            $s = self::make_tidy_string($s, $style=="auto"?($this->mode||'xml'):$style);
        return $s;
        }
    function save($f="", $style="auto")
        {
//print "<br/>XMLFILE::save($f, $style) - ".$this;
        if (!$this->can_save($f)) return false;
        if ($f=="") $f = $this->filename;
        file_put_contents($f, $this->saveXML($style=="auto"?($this->mode||'xml'):$style));
        $this->modified = false;
        return true;
        }
        
    function query($Path) 
        {
        if ($Path=="") zoDie("No Path in XMLFILE::QUERY");
        if (!$this->loaded || $this->Doc == null) return "";//die("No file in XMLFILE::QUERY");
        if ($this->XQuery == null) $this->XQuery = new DOMXPath($this->Doc);
//print "<br/>Path=$Path";
//print "<br/>$this<br/>XMLFILE::query($Path)";
//print "<br/>XMLFILE::source = ".$this->saveXML();
        if (($res = $this->XQuery->query($Path)) === false) debug_print_backtrace();
//      $this->err = $php_errormsg;
        return $res;
        }

    
    function fetch_node($Path)
        {
//print "<br/>fetch_node($Path), ".$this->stat(true,true);
        if (($f = $this->query($Path)) == null) return;
        if ($f->length == 0) return null;
        return $f->item(0);
        }

    function root() { return $this->fetch_node("/"); }

    function node_string($Node) {return $this->Doc->saveXML($Node);}
    function part_string($Path)
        {
        if (($f = $this->query($Path)) == null) return;
        return ($f->length==0) ? "" : $this->node_string($f->item(0));
        }

    function part_string_list($Path)
        {
//print "<br/>part_string_list($Path) ".$this->stat(true);
        if (($f = $this->query($Path)) == null) return array();
//print "<br/>part_string_list($Path)...";
        $r = array();
        for ($i=0;$i<$f->length;$i++) 
            $r[$i] = $this->node_string($f->item($i));
        return $r;
        }

    function fetch_part($Path)
        {
//print "<br/>fetch_part($Path), ".$this->stat(true,true);
        if (($f = $this->query($Path)) == null) return;
        if ($f == null) return "";
//print "<br/>fetch_part($Path): length=".$f->length.", s=".($f->length == 0?"":$f->item(0)->textContent);
        return $f->length == 0?"":$f->item(0)->textContent;
        }

    function fetch_list($Path)
        {
//print "<br/>fetch_list($Path) ".$this->stat(true);
        if (($f = $this->query($Path)) == null) return array();
//print "<br/>fetch_list($Path)...";
        $r = array();
        for ($i=0;$i<$f->length;$i++) $r[$i] = $f->item($i)->textContent;
        return $r;
        }

    function fetch_nodes($Path)
        {
//print "<br/>fetch_list($Path) ".$this->stat(true);
        if (($f = $this->query($Path)) == null) return array();
//print "<br/>fetch_list($Path)...";
        $r = array();
        for ($i=0;$i<$f->length;$i++) $r[$i] = $f->item($i);
        return $r;
        }

    function count_parts($Path)
        {
//print "<br/>count_parts($Path)";
        if (($f = $this->query($Path)) == null) return;
        $r = $f->length;
//print "<br/>count_parts($Path): $r";
        return $r;
        }
        
    function map_attributes($Path)
        {
//        if (($f = $this->query($Path."/@*")) == null) return array();
//        $f = 
        }

    function get($p)        {   return $this->fetch_part($p);       }
    function set($p, $v)    {   return $this->set_part($p, $v);     }
    function lst($p)        {   return $this->fetch_list($p);       }
    function nod($p)        {   return $this->fetch_node($p);       }
    function nds($p)        {   return $this->fetch_nodes($p);      }
    function cnt($p)        {   return $this->count_parts($p);      }
    function def($p)        {   return $this->part_string($p);      }
    function map($p)        {   return $this->map_attributes($p);   }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////

    static function XMLToDoc($XML)
        {
        if ($XML=='') return null;
        $XML = self::make_tidy_string($XML);
        $D = new DOMDocument;
        $D->loadXML($XML);
        return $D;
        }

    static function FileToDoc($f)
        {
        $D = new DOMDocument;
        $D->load($f);
        return $D;
        }
        
    static function DocToXML($Doc)  {return $Doc->saveXML();}

    static function DocElToDoc($El)
        {
        $x = $El->ownerDocument->saveXML($El);
        return self::XMLToDoc($x);
        }

    static function transform_static($Doc, $f, $doRegister=true)
        {
        if (!file_exists($f)) return false;
        if (!$Doc) juniper()->backtrace("NO DOC TO TO TRANSFORM IN xml_file::transform_static()");
        if (get_class($Doc)!="DOMDocument") juniper()->backtrace("DOMDocument not supplied in xml_file::transform_static()");
        $xh = new XsltProcessor();
        $xsl = new DomDocument;
        $xsl->load($f);

        if ($doRegister) $xh->registerPHPFunctions();
        $xh->importStyleSheet($xsl);
        $D = $xh->transformToDoc($Doc);

        unset($xh);
        unset($xsl);

        return $D;
        }

    static function transformXSL_static($Doc, $XSL, $doRegister=true)
        {
        if (!file_exists($f)) return false;
        if (!$Doc) juniper()->backtrace("NO DOC TO TO TRANSFORM IN xml_file::transformXSL_static()");
        $xh = new XsltProcessor();
        $xsl = new DomDocument;
        $xsl->loadXML($XSL);

        if ($doRegister) $xh->registerPHPFunctions();
        $xh->importStyleSheet($xsl);
        $D = $xh->transformToDoc($Doc);

        unset($xh);
        unset($xsl);

        return $D;
        }

    static function NodeToString($node, $part="all")
        {
        switch($part)
            {

            case "open":
                $ss = '<'.$node->nodeName;
                foreach($node->attributes as $att) $ss .= ' ' . $att->nodeName . "='" . str_replace("'",'"',$att->nodeValue) . "'";
                $ss .= $node->hasChildNodes()?">":" />";
                return $ss;
            case "contents":
                $ss = "";
                foreach($node->childNodes as $child)  $ss .= "\n".$child->ownerDocument->saveXML($child);
                return $ss;
            case "close":       return ($node->hasChildNodes()) ? "</".$node->nodeName.">" : '';
            default:        return $N->ownerDocument->saveXML($N);      // all
        }
    }

    function transform($f, $doRegister=true)
        {
        if (!file_exists($f)) return false;
        if (!$this->Doc) juniper()->backtrace("NO DOC TO TO TRANSFORM IN xml_file::transform()");
        $xh = new XsltProcessor();
        $xsl = new DomDocument;
        $xsl->load($f);

        if ($doRegister) $xh->registerPHPFunctions();
        $xh->importStyleSheet($xsl);
        $this->Doc = $xh->transformToDoc($this->Doc);

        unset($xh);
        unset($xsl);

        return true;
        }

    function transformXSL($xsl, $doRegister=true)
        {
        $xh = new XsltProcessor();
        $xsl = new DomDocument;
        $xsl->loadXML($xsl);

        if ($doRegister) $xh->registerPHPFunctions();
        $xh->importStyleSheet($xsl);
        $this->Doc = $xh->transformToDoc($this->Doc);

        unset($xh);
        unset($xsl);

        return true;
        }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    static function xpathsplit($string){return self::qsplit("/",$string,"'",false);}
    static function qsplit($separator=",", $string, $delim="\"", $remove=true)
        {
//print "<br/>qsplit($separator, $string, $delim, $remove)";
        $elements = explode($separator, $string);
        for ($i = 0; $i < count($elements); $i++)
            {
            $nquotes = substr_count($elements[$i], $delim);
            if ($nquotes %2 == 1)
                {
                for ($j = $i+1; $j < count($elements); $j++)
                    {
                    if (substr_count($elements[$j], $delim) %2 == 1) 
                        {
                        // Put the quoted string's pieces back together again
                        array_splice($elements, $i, $j-$i+1, implode($separator, array_slice($elements, $i, $j-$i+1)));
                        break;
                        }
                    }
                }
                if ($remove && $nquotes > 0)
                    {
                    // Remove first and last quotes, then merge pairs of quotes
                    $qstr =& $elements[$i];
                    $qstr = substr_replace($qstr, '', strpos($qstr, $delim), 1);
                    $qstr = substr_replace($qstr, '', strrpos($qstr, $delim), 1);
                    $qstr = str_replace($delim.$delim, $delim, $qstr);
                    }
            }
//print_r($elements);
        return $elements;
        }

//  Extends XPaths correctly
    static function extend_path($b, $l, $m)
        {
//print "<br/>extend_path($b, $l, $m)";
        if ($b=="") $b='/';
        if ($b[strlen($b)-1]!='/') $b=$b."/";

        if ($m=='@') $m = $b.'@'.$l;
        else if ($m=='') $m = $b.$l;
        else if ($m[0]!='/') $m = $b.$m;
        return $m;
        }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////



    static function has_field_accessor($part)           {   return strstr($part, "[*]")!==false;    }
    static function remove_field_accessor($part)        {   return str_replace("[*]","",$part); }
    static function replace_field_accessor($part, $val) {   return str_replace("[*]",$val,$part);   }
    static function add_field_accessor($part)
        {
        if (strpos($part, "[*]")===false)
            {
            $s = explode("/", $part);
            if (substr($s[count($s)-1],0,1)=="@")
                $s[count($s)-2]=$s[count($s)-2] . "[*]";
            else
                $s[count($s)-1]=$s[count($s)-1] . "[*]";
//print "<br/>a=" . $s[count($s)-1] . ", b=" . $s[count($s)-2];
            $part = implode("/", $s);
            }
        return $part;
        }
        

    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function replace_content($node, $value, $allow_delete=true)
        {
//print "<br/>XML_replace_content([...], $value, $allow_delete)";
//log_file("xml-mainp", "XML_replace_content([...], $value, $allow_delete)";
        $dom = $node->ownerDocument;
        $newnode = $dom->createElement($node->tagName);

        if (strstr($value, "<")!==false || strstr($value, ">")!==false || strstr($value, "\n")!==false)
            $newt = $dom->createCDATASection($value);
        else
            $newt = $dom->createTextNode($value);
        
//log_file("xml-manip", "XML_replace_content..count: " . $node->childNodes->length);
        if ($node->hasChildNodes())
            for ($i=$node->childNodes->length - 1;$i>=0; $i--)
                {
                $c = $node->childNodes->item($i);
//log_file("xml-manip", "XML_replace_content..nodeType: " .$c->nodeType);
                if ($c->nodeType == XML_TEXT_NODE || $c->nodeType == XML_CDATA_SECTION_NODE) $node->removeChild($c);
                }
        
        if (! ($allow_delete && $value==""))
            $node->appendChild($newt);
        } 

    private function replace_attribute($node, $attr, $value, $allow_delete=true)
        {
//print "<br/>XML_replace_attribute(..., $attr, $value, $allow_delete)";
//print "<br/>";print_r($node);
        if ($node->nodeType == XML_ATTRIBUTE_NODE) $node = $node->parentNode;
        if ($node->hasAttribute($attr)) $node->removeAttribute($attr);
        if (!$allow_delete || $value != "")
            $node->setAttribute($attr, $value);
        return $value;
        }
        
    function delete_part($srcx) {return $this->delete_node($srcx);}
    function delete_node($srcx)
        {
//print "<br/>DeleteXMLNode(".(is_object($file)?"...":$file).", $srcx)";
        if (substr($srcx,strlen($srcx)-1,1) == "/") $srcx = substr($srcx, 0, strlen($srcx) - 1);

        $p = $this->fetch_node($srcx);
        if ($p == null) return;

        $k=$p->parentNode;
//die($k->ownerDocument->saveXML($k));
        if ($p->nodeType == XML_ATTRIBUTE_NODE)
            {
//print "<br/>Removing Attr";
            $k->removeAttribute($p->nodeName);
            if (!$k->hasAttributes())
                {
//print "<br/>Removing Attr Source";
                $k->parentNode->removeChild($k);
                }
            }
        else
            {
            $k->removeChild($p);
            }

        $this->modified=true;
        return true;
        }
        
    private function XPathAttribute($S, &$lvl, &$attr, &$val)
        {
//print "<br/>XPathAttribute($S, &$lvl, &$attr, &$val)\n";
        $lvl = $S;
        $attr = "";
        $val = "";
        
        $a = strpos($S, "[");
        if ($a===false) return false;
        $b = strpos($S, "]");
        
        $Sa = substr($S, 0, $a);
        $Sx = substr($S, $a+1, $b - $a - 1);
        if (is_numeric($Sx)) $Sx = "position()=$Sx";
//print "<br/>Sx=$Sx\n";
        $Sy = explode("=", $Sx);
        if (count($Sy)==2)
            {
            $Sb = $Sy[0];
            $Sc = $Sy[1];
            }
        else return false;
        
        if (substr($Sb, 0, 1) == "@") $Sb = substr($Sb, 1);
        if (substr($Sc, 0, 1) == "'" && substr($Sc, strlen($Sc)-1) == "'" ||
            substr($Sc, 0, 1) == '"' && substr($Sc, strlen($Sc)-1) == '"')
                $Sc = substr($Sc, 1, strlen($Sc) - 2);
                
        $lvl = $Sa;
        $attr = $Sb;
        $val = $Sc;
//print "<br/>XPathAttribute:  lvl=$lvl, attr=$attr, val=$val\n";
        return true;
        }


    private function CreateXMLNode($srcx, $value="")
        {
//print "<br/>CreateXMLNode(".(is_object($file)?"...":$file).", $srcx, $value)\n";
        $parent = $this->root();
        
        $s = "";
        $xsx = $this->xpathsplit($srcx);
        foreach($xsx as $n=>$m)
            {
            $pre_s = $s;
            if (!($m == "" && $s == "")) $s = "$s/$m";
            if ($s=="") continue;
            
//print "<br/>m=$m";
//print "<br/>s=$s";
            $en = $this->query($s);
//print "<br/>length=".$en->length. "\n";
        if ($en->length == 0)
                {
                if ($m[0] == '@')
                    {
//print "<br/>  Add Attr: s=$s, m=$m, value=$value\n";
//print "<br/>";print_r($parent);
//print "<br/>XMLpre:[".$this->Doc->saveXML($parent)."]";
                    $this->replace_attribute($parent, substr($m, 1), $value, false);
//print "<br/>XMLpost:[".$this->Doc->saveXML($parent)."]";
                    }
                else
                    {
                    if (!$this->XPathAttribute($m, $a, $b, $c))
                        {
//print "<br/>  Add Node: $m\n";
                        $dd = $this->Doc->createElement($m);
                        if ($n==count($xsx)-1)
                            $this->replace_content($dd, $value);
                        $parent->appendChild($dd);
                        }
                    else
                        {
//print "<br/>  Add w/Attr: $m...  a=$a, b=$b, c=$c";
                        $dd = $this->Doc->createElement($a);
                        if ($n==count($xsx)-1) $this->replace_content($dd, $value);
                        if ($b!="position()") $this->replace_attribute($dd, $b, $c, true);
                        $parent->appendChild($dd);
                        if ($b=="position()")
                            {
//print "<br/> Positioned Element...";
                            $dp = str_replace("$a"."["."position()=$c"."]", "$a", $s);
                            $d = $this->count_parts($dp);
//print "<br/> New Position=" . $d;
                            $s = str_replace("$a"."["."position()=$c"."]", "$a"."["."position()=$d"."]", $s);
                            }
                        }
                    $parent=$dd;
                    }
                }
            else 
                $parent = $en->item(0);
            }
            
        $this->modified = true;
        return true;
        }
        
    function set_part($path, $value, $allow_delete=true)
        {
//print "<br/>set_part($path, value=$value, allow_delete=$allow_delete)\n";
//log_file("xml-manip", "set_part(...,$path, $value, $allow_delete)");

//print "<br/>path=$path";
        $entries = $this->query($path);
        if ($entries == null) return false;
        if ($entries->length == 0)
            {
//print "<br/>Did Not Exist: $path\n";
            if (!$allow_delete || $value != "")  // no delete if not existant
                $this->CreateXMLNode($path, $value);
            }
        else
            {
//print "<br/>found item (".$entries->length.")..\n";
            $target = $entries->item(0);
//print "<br/>path=$path, nodeName=".$target->nodeName.", Attr=".YesNo($target->nodeType == XML_ATTRIBUTE_NODE);
            if ($target->nodeType == XML_ATTRIBUTE_NODE)
                {
                $p = $target->parentNode;
                $this->replace_attribute($target, $target->nodeName, $value);
                }
            else
                {
                $p = $target;
                $this->replace_content($target, $value);
                }
            if ($allow_delete && !$p->hasAttributes() && !is_object($p->firstChild))
                $p->parentNode->removeChild($p);
            }

        $this->modified = true;
        return true;
        }

    function adjust_part($path, $adj)
        {
//print "<br/>adjust_part($path, $adj)\n";
//log_file("zsave","AdjustXMLFilePart($path, $adj)");
        if ($adj===0) return;  // go no where
        if (substr($path,strlen($path)-1,1) == "/") $path= substr($path, 0, strlen($path) - 1);

        $entries = $this->query($path);
        if ($entries == null) return;
        if ($entries->length != 1)
            {
            if (substr($path, strlen($path)-1)=="/") return $this->adjust_part(substr($path, 0, strlen($path)-1), $adj);
//if ($entries->length==0) print "<br/>Did Not Exist: $path\n"; else print "<br/>Ambiguous: $path\n";
//if ($entries->length==0) log_file("zsave", "Did Not Exist: $path"); else log_file("zsave", "Ambiguous: $path");
//die($this->saveXML());
            unset($D);
            return false;
            }
//print "<br/>found item (".$entries->length.")..\n";
//log_file("zsave","<br/>found item (".$entries->length.")..\n");


        $target = $entries->item(0);
        $NN = $target->nodeName;
        $x = $target->cloneNode(true);
        $parent = $target->parentNode;

//print "<br/>Adjust Node [$adj]";
        if ($adj=="top")
            {
//die($this->saveXML());
            $parent->insertBefore($x, $parent->firstChild);
            $parent->removeChild($target);
//die($this->saveXML());
            }
        else if ($adj=="bottom")
            {
//die($this->saveXML());
            $parent->appendChild($x);
            $parent->removeChild($target);
//die($this->saveXML());
            }

        if ($adj<0)
            {
//die($this->saveXML());
            $px = $prev = $target;
            while ($adj<0)
                {
                if (($prev = $prev->previousSibling) == null) break; 
                $px = $prev;
                if ($px->nodeName == $NN) $adj++;
                }
            $parent->insertBefore($x, $px);
            $parent->removeChild($target);
//die($this->saveXML());
            }
        else if ($adj > 0)
            {
//die($this->saveXML());
            $next = $target;
            $adj++;
            while ($adj>0)
                {
                if (($next = $next->nextSibling) == null) break; 
                if ($next->nodeName == $NN) $adj--;
                }
            if ($next==null)
                $parent->appendChild($x);
            else
                $parent->insertBefore($x, $next);
            $parent->removeChild($target);
//die($this->saveXML());
            }

        $this->modified = true;
        return true;
        }
    

        static function tidyXML_OPT()
            {
//print "<br/>XML";
            $topt = array();
    
            $topt["wrap"]           = 0;
            $topt["input-xml"]      = true;
            $topt["output-xml"]     = true;
            $topt["add-xml-decl"]   = false;
            $topt["quiet"]      = true;
            $topt["fix-bad-comments"]   = true;
            $topt["fix-backslash"]  = true;
            $topt["tidy-mark"]      = false;
            $topt["char-encoding"]  = "raw";
            $topt["indent"]     = true;
            $topt["indent-spaces"]  = 4;
            $topt["indent-cdata"]   = false;
            $topt["add-xml-space"]  = true;
            $topt["escape-cdata"]   = false;
            $topt["write-back"]     = true;
            $topt["literal-attributes"] = true;
              
            $topt["force-output"]   = true;

            return $topt;
            }

        static function tidyXHTML_OPT()
            {
//print "<br/>XHTML";
            $topt = array();

            //new-blocklevel-tags
            //new-inline-tags 
            //new-pre-tags  
            //new-empty-tags

//          $topt["clean"]      = true;
//          $topt["doctype"]        = "auto";
//          $topt["drop-font-tags"] = true;
//          $topt["error-file"]     = "error.log";

            $topt["input-xml"]      = false;
            $topt["output-xhtml"]   = true;
            $topt["output-xml"]     = false;
            $topt["markup"]     = true;

            $topt["new-empty-tags"] = "page, field, caption";
            $topt["add-xml-decl"]   = false;
//          $topt["add-xml-pi"]     = false;
            $topt["alt-text"]       = "Image";
            $topt["break-before-br"]    = true;
            $topt["drop-empty-paras"]   = false;
            $topt["fix-backslash"]  = true;
            $topt["fix-bad-comments"]   = true;
            $topt["hide-endtags"]   = false;
            $topt["char-encoding"]  = "raw";
            $topt["indent"]     = true;
            $topt["indent-spaces"]  = 2;
            $topt["indent-cdata"]   = false;
            $topt["escape-cdata"]   = false;
            $topt["quiet"]      = true;
            $topt["tidy-mark"]      = false;
            $topt["uppercase-attributes"]=false;
            $topt["uppercase-tags"] = false;
            $topt["word-2000"]      = false;
            $topt["wrap"]           = false;
            $topt["wrap-asp"]       = true;
            $topt["wrap-attributes"]    = true;
            $topt["wrap-jste"]      = true;
            $topt["wrap-php"]       = true;
            $topt["write-back"]     = true;
            $topt["add-xml-space"]  = true;
              
            $topt["force-output"]   = true;
            $topt["show-body-only"] = true;
    
//          $topt["preserve-entities"]  = true;
//          $topt["quote-marks"]        = true;
//          $topt["literal-attributes"] = true;
//          $topt["break-br"]       = true;
        
            return $topt;
            }
        static function tidy_opt($style="xml") {return $style=="xhtml" ? self::tidyXHTML_OPT() : self::tidyXML_OPT();}
        static function make_tidy_string($str, $style="auto")
            {
//print "<br/>xml_file::make_tidy_string($str, $style)";
            if ($style=="none") return $str;

//print "<br/>str=$str";
//          preg_match_all('#<script.*</script>#mixU', $str, $x1);
//          $x1 = $x1[0];
//print "<br/>x1: ";print_r($x1);print "<br/>c=".sizeof($x1)."\n";
//          preg_match_all('#<style.*</style>#mixU', $str, $x2);
//          $x2 = $x2[0];
//print "<br/>x2: ";print_r($x2);print "<br/>c=".sizeof($x2)."\n";

            $tidy = new tidy;
            $tidy->parseString($str, self::tidy_opt($style));
            $tidy->CleanRepair();
            $s = $tidy->value;

//print "<br/>s=".$s;
//if (count($x1)) 
//{print "<br/>X1: ";print_r($x1);}
//          if (count($x1)) $s = preg_replace(array_fill(0, count($x1), '#<script.*</script>#mixU'), $x1, $s);
//          if (count($x2)) $s = preg_replace(array_fill(0, count($x2), '#<style.*</style>#mixU'), $x2, $s);
//print "-------------";print $x;die();

            return self::tidy_cleanup($s, $style);
            }
        static function make_tidy_doc($D, $style="auto")
            {
            if ($style=="none") return $D;
            if (!$D) return "";
            $x = $D->saveXML();
            $x = self::make_tidy_string($x, $style);
            $x = str_replace("&nbsp;", "&#160;", $x);
            $x = self::tidy_cleanup($x, $style);
            $D = new DOMDocument;
            $D->loadXML($x);
            return $D;
            }
        static function make_tidy($filename, $style="xml")
            {
            if ($style=="none") return true;
            $tidy = new tidy;
            $tidy->parseFile($filename, self::tidy_opt($style));
            $tidy->CleanRepair();
            return file_put_contents($str, $tidy->value);
            }
        static function tidy_cleanup($s, $style='auto')             // when tidy makes a mess....
            {
// Tidy wont stop indenting CDATA, which adds extra line feeds at the beginning and end of of CDATA fields
// despite indent-cdata being set to false
            if ($style=="none") return $s;
            $s = preg_replace("/\n( )*<![[]CDATA[[](.*)[]][]]>\n/U", "<![CDATA[$2]]>", $s);
            switch($style)
                {
                case "xhtml":
                    $s = preg_replace("\$(<textarea([^>])*>)\n(.*)\n(</textarea>)\$U", "$1$3$4", $s);
                    break;
                case "xmlfragment":
                    $s = preg_replace("\$\<.xml (.)*?\>\$", "", $s);
                }
            return $s;
            }


    }       // xml_file






    function xml_manip_test_result($n, $r, $k, &$A=false)
        {
        print "<tr><td>$n</td><td>";
        print_r($r);
        print "</td><td align='center'" . ($k?" bgcolor='lightgreen'><b>OK</b>":" bgcolor='pink'>ERROR") . " </td></tr>";
        $A = $A && $k;
        }
    function xml_manip_test_simplify($s) {return str_replace(array("\n","\r","\t"," "),"",$s);}


    function xml_manip_test()
        {

DEFINE('TIDY_XML', true);

        $All = true;
        
        $startcontent = <<<XML
<?xml version="1.0" encoding="us-ascii"?>
<pages>
  <global>
    <extension>.asp</extension>
    <globaltitle append="1">Scribe</globaltitle>
    <css src="/scribe/css/scribe.css" />
    <css src="/scribe/css/scribe2.css" />
    <transform id="navigation" src="a" />
    <transform id="inset-box" src="b" />
    <transform id="footer" src="c" />
  </global>
</pages>
XML;
        $f = "xml-manip-test.xml";
        $X = new zobject_xml_file($startcontent);

        print "<html><title>XML-MANIP TEST SUITE</title><body>";
        print "<table align='center' width='900' border='1' style='border-collapse:collapse;border-color:black;border-width:3'>";
        print "<tr><td colspan='3' align='center' bgcolor='gray'><font size='+3' color='white'>XML MANIP TEST</font></td></tr>";
        print "<tr style='background-color:lightblue'><td>Test</td><td>Result</td><td align='center' width='50'>OK?</td></tr>";
        
        $testname = "/pages/global/non-existent-entry";
        $s = "/pages/global/non-existent-entry";
        $v = "";
        $testdesc = "$f [> $s";         
        $testresult = $X->fetch_part($s);
        $testexpect = "";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Extension";
        $s = "/pages/global/extension/text()";
        $v = "";
        $testdesc = "$f [> $s";         
        $testresult = $X->fetch_part($s);
        $testexpect = ".asp";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Title";
        $s = "/pages/global/globaltitle/text()";
        $testresult = $X->fetch_part($s);
        $testexpect = "Scribe";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Append Title";
        $s = "/pages/global/globaltitle/@append";
        $testresult = $X->fetch_part($s);
        $testexpect = "1";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS";
        $s = "/pages/global/css/@src";
        $testresult = $X->fetch_part($s);
        $testexpect = "/scribe/css/scribe.css";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Change CSS";
        $s = "/pages/global/css/@src";
        $X->set_part($s, "-X-");
        $s = "/pages/global/css/@src";
        $testresult = $X->fetch_part($s);
        $testexpect = "-X-";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Revert CSS";
        $s = "/pages/global/css/@src";
        $X->set_part($s, "/scribe/css/scribe.css");
        $testresult = $X->fetch_part($s);
        $testexpect = "/scribe/css/scribe.css";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS #2";
        $s = "/pages/global/css[2]/@src";
        $testresult = $X->fetch_part($s);
        $testexpect = "/scribe/css/scribe2.css";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS #3 (non-existant)";
        $s = "/pages/global/css[3]/@src";
        $testdesc = "$f [> $s"; 
        $testresult = $X->fetch_part($s);
        $testexpect = "";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS Count";
        $s = "/pages/global/css";
        $testresult = $X->count_parts($s);
        $testexpect = 2;
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS List";
        $s = "/pages/global/css/@src";
        $testresult = $X->fetch_list($s);
        $testexpect = 2;
        $testok = (count($testresult)==2 && $testresult[0]=="/scribe/css/scribe.css");
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Transform List";
        $s = "/pages/global/transform/@id";
        $testresult = $X->fetch_list($s);
        $testok = (count($testresult)==3 && $testresult[0]=="navigation" && $testresult[1]=="inset-box" && $testresult[2]== "footer");
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Create CSS #3";
        $X->set_part("/pages/global/css[3]/@src", "/scribe/css/scribe3.css");
        $s = "/pages/global/css[3]/@src";
        $testresult = $X->fetch_part($s);
        $testexpect = "/scribe/css/scribe3.css";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Delete Non-existent Attribute from CSS #3";
        $X->set_part("/pages/global/css[3]/@css", "");
        $s = "/pages/global/css[3]/@src";
        $testresult = $X->fetch_part($s);
        $testexpect = "/scribe/css/scribe3.css";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Set CSS #3 Text Element";
        $X->set_part("/pages/global/css[3]", "TEST");
        $s = "/pages/global/css[3]";
        $testresult = $X->fetch_part($s);
        $testexpect = "TEST";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS 3 Move Up to 2";
        $a1 = $X->fetch_part("/pages/global/css[1]/@src");
        $b1 = $X->fetch_part("/pages/global/css[2]/@src");
        $c1 = $X->fetch_part("/pages/global/css[3]/@src");
        $X->adjust_part("/pages/global/css[3]",-1);
        $a2 = $X->fetch_part("/pages/global/css[1]/@src");
        $b2 = $X->fetch_part("/pages/global/css[3]/@src");
        $c2 = $X->fetch_part("/pages/global/css[2]/@src");
        $testresult = "[1] == $a2, [2] == $c2, [3] == $b2";
        $testok = ($a1==$a2 && $b1 == $b2 && $c1 == $c2);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS 2 Move Up to 1";
        $a1 = $X->fetch_part("/pages/global/css[1]/@src");
        $b1 = $X->fetch_part("/pages/global/css[2]/@src");
        $c1 = $X->fetch_part("/pages/global/css[3]/@src");
        $X->adjust_part("/pages/global/css[2]",-1);
        $a2 = $X->fetch_part("/pages/global/css[2]/@src");
        $b2 = $X->fetch_part("/pages/global/css[1]/@src");
        $c2 = $X->fetch_part("/pages/global/css[3]/@src");
        $testresult = "[1] == $a2";
        $testok = ($a1==$a2 && $b1 == $b2 && $c1 == $c2);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS 1 Try to move up 1 (no change)";
        $a1 = $X->fetch_part("/pages/global/css[1]/@src");
        $b1 = $X->fetch_part("/pages/global/css[2]/@src");
        $c1 = $X->fetch_part("/pages/global/css[3]/@src");
        $X->adjust_part("/pages/global/css[1]",-1);
        $a2 = $X->fetch_part("/pages/global/css[1]/@src");
        $b2 = $X->fetch_part("/pages/global/css[2]/@src");
        $c2 = $X->fetch_part("/pages/global/css[3]/@src");
        $testresult = "[1] == $a2";
        $testok = ($a1==$a2 && $b1 == $b2 && $c1 == $c2);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS 1 move to bottom";
        $a1 = $X->fetch_part("/pages/global/css[1]/@src");
        $b1 = $X->fetch_part("/pages/global/css[2]/@src");
        $c1 = $X->fetch_part("/pages/global/css[3]/@src");
        $X->adjust_part("/pages/global/css[1]","bottom");
        $a2 = $X->fetch_part("/pages/global/css[3]/@src");
        $b2 = $X->fetch_part("/pages/global/css[1]/@src");
        $c2 = $X->fetch_part("/pages/global/css[2]/@src");
        $testresult = "[3] == $a2";
        $testok = ($a1==$a2 && $b1 == $b2 && $c1 == $c2);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS 3 move to top";
        $a1 = $X->fetch_part("/pages/global/css[1]/@src");
        $b1 = $X->fetch_part("/pages/global/css[2]/@src");
        $c1 = $X->fetch_part("/pages/global/css[3]/@src");
        $X->adjust_part("/pages/global/css[3]","top");
        $a2 = $X->fetch_part("/pages/global/css[2]/@src");
        $b2 = $X->fetch_part("/pages/global/css[3]/@src");
        $c2 = $X->fetch_part("/pages/global/css[1]/@src");
        $testresult = "[1] == $c2";
        $testok = ($a1==$a2 && $b1 == $b2 && $c1 == $c2);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS 1 move down 5 (only goes to 3)";
        $a1 = $X->fetch_part("/pages/global/css[1]/@src");
        $b1 = $X->fetch_part("/pages/global/css[2]/@src");
        $c1 = $X->fetch_part("/pages/global/css[3]/@src");
        $X->adjust_part("/pages/global/css[1]",5);
        $a2 = $X->fetch_part("/pages/global/css[3]/@src");
        $b2 = $X->fetch_part("/pages/global/css[1]/@src");
        $c2 = $X->fetch_part("/pages/global/css[2]/@src");
        $testresult = "[3] == $a2";
        $testok = ($a1==$a2 && $b1 == $b2 && $c1 == $c2);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CSS 4 move up 2 (only goes to 3, no change)";
        $a1 = $X->fetch_part("/pages/global/css[1]/@src");
        $b1 = $X->fetch_part("/pages/global/css[2]/@src");
        $c1 = $X->fetch_part("/pages/global/css[3]/@src");
        $X->adjust_part("/pages/global/css[4]",5);
        $a2 = $X->fetch_part("/pages/global/css[1]/@src");
        $b2 = $X->fetch_part("/pages/global/css[2]/@src");
        $c2 = $X->fetch_part("/pages/global/css[3]/@src");
        $testresult = "[3] == $c2";
        $testok = ($a1==$a2 && $b1 == $b2 && $c1 == $c2);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Recheck CSS #3 Text Element after moves";
        $s = "/pages/global/css[3]";
        $testresult = $X->fetch_part($s);
        $testexpect = "TEST";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Clear CSS #3 Text Element (src should remain)";
        $X->set_part("/pages/global/css[3]", "");
        $s = "/pages/global/css[3]";
        $testresult = $X->fetch_part($s);
        if ($X->fetch_part("/pages/global/css[3]/@src") != "/scribe/css/scribe3.css") $testresult="SRC Attribute Wrong: " . $X->fetch_part("/pages/global/css[3]/@src");
        $testexpect = "";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Delete CSS #3";
        $X->set_part("/pages/global/css[3]/@src", "");
        $s = "/pages/global/css[3]/@src";
        $testresult = $X->fetch_part($s);
        $testexpect = "";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Count ZObjects (0)";
        $s = "/pages/zobjectdef/@name";
        $testresult = $X->count_parts($s);
        $testexpect = 0;
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Create new Zobject";
        $X->set_part("/pages/zobjectdef[@name='milk/juice']/@text", "Drink");
        $s = "/pages/zobjectdef[@name='milk/juice']/@text";
        $testresult = $X->fetch_part($s);
        $testexpect = "Drink";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Create template";
        $X->set_part("/pages/zobjectdef[@name='pizza']/template/@src", "some-file.xml");
        $s = "/pages/zobjectdef[@name='pizza']/template/@src";
        $testresult = $X->fetch_part($s);
        $testexpect = "some-file.xml";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Count ZObjects (2)";
        $s = "/pages/zobjectdef/@name";
        $testresult = $X->count_parts($s);
        $testexpect = 2;
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Create title";
        $X->set_part("/pages/zobjectdef[@name='pizza']/title", "The Pizza Object");
        $s = "/pages/zobjectdef[@name='pizza']/title";
        $testresult = $X->fetch_part($s);
        $testexpect = "The Pizza Object";   
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Create Content Label";
        $X->set_part("/pages/zobjectdef[@name='pizza']/content[@id='one']/@type", "text-file");
        $s = "/pages/zobjectdef[@name='pizza']/content[@id='one']/@type";
        $testresult = $X->fetch_part($s);
        $testexpect = "text-file";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Count ZObjects (2+)";
        $s = "/pages/zobjectdef/@name";
        $testresult = $X->count_parts($s);
        $testexpect = 2;
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "Delete both Zobjects";
        $X->delete_part("/pages/zobjectdef[@name='milk/juice']");
        $X->delete_part("/pages/zobjectdef[@name='pizza']");
        $s = "/pages/zobjectdef[@name='pizza']/@name";
        $testresult = $X->fetch_part($s);
        $testexpect = "";
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);
        
        $testname = "Count ZObjects (-)";
        $s = "/pages/zobjectdef/@name";
        $testresult = $X->count_parts($s);
        $testexpect = 0;
        $testok = ($testresult == $testexpect);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $modstart = str_replace(array("\n","\r","\t"," "),array("","","",""),$startcontent);

        $testname = "<b>Final Equivalence Test</b>";
        $endcontent = $X->saveXML();
        $testok = xml_manip_test_simplify($startcontent) == xml_manip_test_simplify($endcontent);
        xml_manip_test_result($testname, "", $testok);

        $testname = "<b>Final Equivalence Test by FILE</b>";
        $X->save();
        $endcontent = file_get_contents($f);
        $testok = xml_manip_test_simplify($startcontent) == xml_manip_test_simplify($endcontent);
        xml_manip_test_result($testname, "", $testok);

        $startcontent2 = <<<XML
<?xml version="1.0"?>
<items name="Gatherer About Page" style="Scheduled">
  <item>
    <content><![CDATA[Why]]></content>
    <content2>Why</content2>
  </item>
</items>
XML;
        $f2 = "xml-manip-test2.xml";
        file_put_contents($f2, $startcontent2);
        $X2 = new zobject_xml_file($f2);

        $s1 = "/items/item[1]/content";
        $s2 = "/items/item[1]/content2";
        
        $testname = "CDATA Value Test";
        $testok = $X2->fetch_part($s1) == "Why";
        $testresult = $X2->fetch_part($s1);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CDATA Equivalence Test";
        $testok = $X2->fetch_part($s1) == $X2->fetch_part($s2);
        $testresult = $X2->fetch_part($s1) . ($testok?" == ":" <b>!=</b> ") . $X2->fetch_part($s2);
        xml_manip_test_result($testname, $testresult, $testok, $All);

        $testname = "CDATA Replace Test";
        $X->set_part($s1, "Why");
        $testok = $X2->fetch_part($s1) == "Why";
        $testresult = $X2->fetch_part($s1);
        xml_manip_test_result($testname, $testresult, $testok, $All);

//      $testname = "<b>Final Equivalence Test (TEST 2)</b>";
//      $endcontent = file_get_contents($f2);
//      $testok = str_replace(array("\n","\r","\t"," ","<!CDATA[","]]>"),array(),$startcontent) == str_replace(array("\n","\r"," ","<!CDATA[","]]>"),array(),$endcontent);
//      xml_manip_test_result($testname, $endcontent, $testok);

        $testname = "CDATA TIDY Test";
        $X2->save();
        $testresult = $X2->fetch_part($s1);
        $testok = $testresult == "Why";
        $testresult = "$testresult == Why";
        xml_manip_test_result($testname, $testresult, $testok, $All);

        echo $All ? "All Tests Passed." : "Some Tests Failed";

        print "</table></body></html>";
        die();
        }

if ($_SERVER['SCRIPT_FILENAME']==__FILE__) xml_manip_test();

?>
