<?php

/*
Module Name:  xml_file
Module Source: http://www.github.com/bhoogter/xml-file
Description: A convenient XML file wrapper designed for datastore and retrieval.
Version: 1.0.1
Author: Benjamin Hoogterp
Author URI: http://www.BenjaminHoogterp.com/
License: BSD (2 Clause)
*/

class xml_file extends xml_file_base
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
    public $stacktrace;
    public $sourceDate;
    public $saveMethod;

    private const cDOMDocument = "DOMDocument";
    private const cDOMElement = "DOMElement";
    private const cDOMNode = "DOMNode";


    function type()
    {
        return get_class($this);
    }
    function __construct()
    {
        $this->gid = uniqid($this->type() . "_");
        $this->clear();
        $n = func_num_args();
        $a = func_get_args();
        if ($n >= 1) {
            if (is_string($a[0])) {
                if (substr($a[0], 0, 1) == "<") $this->loadXML($a[0]);
                else $this->load($a[0]);
            }
            if (is_object($a[0])) {
                if (is_a($a[0], self::cDOMDocument)) $this->loadDoc($a[0]);
                else if (is_a($a[0], self::cDOMElement)) $this->loadDoc(self::nodeXmlDoc($a[0]));
                else if (is_a($a[0], get_class())) {
                    if ($a[0]->loaded) {
                        if ($a[0]->filename && $a[0]->filename != '') $this->load(self::resolve_filename($a[0]->filename));
                        else $this->loadXML($a[0]->saveXML());
                    }
                }
            }
        } else {
        }
        if ($n >= 2) {
            if (strstr(strtolower($a[1]), "xml")) $this->mode = 'xml';
            if (strstr(strtolower($a[1]), "xhtml")) $this->mode = 'xhtml';
            if (strstr(strtolower($a[1]), "readonly")) $this->readonly = true;
            if (strstr(strtolower($a[1]), "notidy")) $this->notidy = true;
            if (strstr(strtolower($a[1]), "stacktrace")) $this->stacktrace = true;
        }
        if ($n >= 3) {
            $this->transform(self::toDoc($a[2]));
            // if (is_string($a[2])) {
            //     if (substr($a[2], 0, 1) == "<") $this->transformXSL($a[2]);
            //     else if (file_exists($a[2])) $this->transform($a[2]);
            // } else if (is_a($a[2], get_class())) {
            //     $this->transform(self::toDoc($a[2]));
            // }
        }
    }

    function __destruct()
    {
        unset($this->Doc);
        unset($this->XQuery);
    }


    static function resolve_filename($fn)
    {
        if (file_exists(realpath($fn))) return realpath($fn);
        if (file_exists($r = "source/$fn")) return $r;
        return $fn;     //  nothing else to try....
    }

    public function clear()
    {
        unset($this->sourceDate);
        unset($this->Doc);
        unset($this->XQuery);
        $this->clear_metadata();
        $this->loaded = false;
        $this->filename = "";
        $this->mode = "";
        $this->modified = false;
        $this->readonly = false;
        $this->notidy = false;
        return false;
    }

    public function stat($Nnl = false, $Sht = false)
    {
        if (!$this->loaded) return "[NOT LOADED: " . $this->gid . "]";
        if ($Sht)
            $s = "FN: " . $this->filename;
        else
            $s = "<b>gid:</b> $this->gid\n<b>Filename:</b> $this->filename\n<b>Loaded:</b> $this->sourceDate" . ($this->can_save() ? "\n<b>[CANSAVE]</b>" : "") . ($this->readonly ? "\n<b>[READ ONLY]</b>" : "") . ($this->modified ? "\n<b>[MODIFIED]</b>" : "") . (isset($this->Doc) ? "" : "[NO DOC]");
        if (!!$Nnl) str_replace("\n", "  ", $s);
        return $s;
    }

    public function __toString()
    {
        return $this->stat();
    }

    private function init($D = 0)
    {
        $this->sourceDate = $D == 0 ? time() : $D;
        $this->loaded = isset($this->Doc);
        if (get_class($this->Doc) != self::cDOMDocument)
            if ($this->stacktrace) throw new Exception("Invalid Object Type: " . get_class($this->Doc));
        $this->XQuery = $this->loaded ? new DOMXPath($this->Doc) : null;
        $this->init_metadata();
        return $this->loaded;
    }

    function load($file)
    {
        $this->clear();
        $file = self::resolve_filename($file);
        if (!file_exists($file)) return false;
        $this->filename = $file;
        $this->Doc = new DomDocument;
        $res = false;
        try {
            $tmpDoc = $this->Doc;
            $res = xml_file::protected_file_operation($file, "r", function ($fp) use ($tmpDoc, $file) {
                return $tmpDoc->load($file);
            });
        } catch (Exception $e) {
            $this->err = $e->getMessage();
            $res = false;
            echo "<br />Failed to read: $file\n";
            if ($this->stacktrace) throw new Exception("Failed to read: $file");
            return $this->clear();
        }

        $x = $this->init(filemtime($file));
        return $x;
    }

    function merge_list($scan)
    {
        // print "\nxml_file::merge_list=";print_r($scan);
        if (is_array($scan)) return $scan;
        if (is_string($scan)) return glob($scan);
        return array();
    }

    function merge_update_required($scan, $persist)
    {
        if (($persist ?? '') == '') return true;
        $sysTime = 0;
        $sysTime = @filemtime($persist);
        if (!$sysTime) return true;
        //print "\n<br/>xml_file::merge_update_required - sysTime=$sysTime, aPath=$this->aPath";
        //print_r($this->merge_list());
        foreach ($this->merge_list($scan) as $accessor)
            if (@filemtime($accessor) > $sysTime) return true;
        return false;
    }

    function merge_join_to_xml($scan, $root, $item, $target, $persist)
    {
        // print "\n<br/>xml_file::merge_join_to_xml(..., $root, $item, $persist)";
        if (!$this->loaded) {
            $x  = "";
            $x .= "<?xml version='1.0' encoding='iso-8859-1'?>\n";
            $x .= "<$root />\n";
            $this->loadXML($x);
        }

        // print "\n<br/>xml_file:: merge_join_to_xml - list="; print_r($this->merge_list($scan));
        foreach ($this->merge_list($scan) as $accessor) {
            // print "\n<br/>xml_file::merge_join_to_xml - accessor=$accessor";
            $M = new xml_file($accessor);
            $n = 0;
            while (++$n > 0) { // Always.  See break below.
                // print "\n<br/>>xml_file::merge_join_to_xml - cnt = " . $this->cnt("/$root/$item");
                // print "\n<br/>>xml_file::merge_join_to_xml - path = " . "/$root/${item}[$n]";
                $node = $M->nde("/$root/${item}[$n]");
                // print "\n<br/>>xml_file::merge_join_to_xml - node = "; print_r($node);
                if ($node == null) break;

                // print "\n<br/>xml_file::merge_join_to_xml - n=" . $M->saveXML($n);
                $el = $this->Doc->importNode($node, true);

                if ($target == null)
                    $this->Doc->documentElement->appendChild($el);
                else
                    $this->nde($target)->appendChild($el);
                // print "\n<br/>>xml_file::merge_join_to_xml - cnt = " . $this->cnt("/$root/$item");
            }
        }

        if (is_string($persist) && $persist != '') {
            $D = new xml_file($x);
            if (!$D->can_save($persist)) print "<br/>FAILED TO SAVE MASTER LIST";
            $D->save($persist);
        }

        return true;
    }

    function merge($scan, $root = null, $item = null, $persist = null)
    {
        if (!$this->merge_update_required($scan, $persist)) $this->load($persist);
        else $this->merge_join_to_xml($scan, $root, $item, null, $persist);
    }

    function merge_to($scan, $root = null, $item = null, $target = null, $persist = null)
    {
        if (!$this->merge_update_required($scan, $persist)) $this->load($persist);
        else $this->merge_join_to_xml($scan, $root, $item, $target, $persist);
    }

    function loadXML($x)
    {
        $this->clear();

        $this->Doc = new DomDocument;
        $res = @$this->Doc->loadXML($x);

        $x = $this->init();
        return $x;
    }

    function loadDoc($D)
    {
        $this->clear();
        $this->Doc = $D;
        $x = $this->init();
        return $x;
    }

    function can_save($f = "")
    {
        return $this->loaded && ($f != "" || $this->filename != "") && !$this->readonly;
    }

    function saveXML($style = "auto")
    {
        if (!isset($this->Doc)) {
            if ($this->stacktrace) throw new Exception("No doc for save");
            return "";
        }
        $s = $this->Doc->saveXML();
        if (!$this->notidy)
            $s = self::make_tidy_string($s, $style == "auto" ? ($this->mode || 'xml') : $style);
        return $s;
    }

    function save($f = "", $style = "auto")
    {
        if (!$this->can_save($f)) return false;
        if ($f == "") $f = $this->filename;
        $this->write_file($f, $this->saveXML($style == "auto" ? ($this->mode || 'xml') : $style));
        $this->modified = false;
        return true;
    }

    function query($Path)
    {
        if ($Path == "") die("No Path in XMLFILE::QUERY");
        if (!$this->loaded || $this->Doc == null) return ""; //die("No file in XMLFILE::QUERY");
        if ($this->XQuery == null) $this->XQuery = new DOMXPath($this->Doc);
        if (($res = $this->XQuery->query($Path)) === false) debug_print_backtrace();
        return $res;
    }

    function fetch_node($Path)
    {
        if (($f = $this->query($Path)) == null) return;
        if ($f->length == 0) return null;
        return $f->item(0);
    }

    function root()
    {
        return $this->fetch_node("/");
    }

    function node_string($Node)
    {
        return $this->Doc->saveXML($Node);
    }

    function part_string($Path)
    {
        if (($f = $this->query($Path)) == null) return;
        return ($f->length == 0) ? "" : $this->node_string($f->item(0));
    }

    function part_string_list($Path)
    {
        if (($f = $this->query($Path)) == null) return array();
        $r = array();
        for ($i = 0; $i < $f->length; $i++)
            $r[$i] = $this->node_string($f->item($i));
        return $r;
    }

    function fetch_part($Path)
    {
        if (($f = $this->query($Path)) == null) return;
        if ($f == null) return "";
        return $f->length == 0 ? "" : $f->item(0)->textContent;
    }

    function fetch_list($Path)
    {
        if (($f = $this->query($Path)) == null) return array();
        $r = array();
        for ($i = 0; $i < $f->length; $i++) $r[$i] = $f->item($i)->textContent;
        return $r;
    }

    function fetch_nodes($Path)
    {
        if (($f = $this->query($Path)) == null) return array();
        $r = array();
        for ($i = 0; $i < $f->length; $i++) $r[$i] = $f->item($i);
        return $r;
    }

    function count_parts($Path)
    {
        if (($f = $this->query($Path)) == null) return;
        $r = $f->length;
        return $r;
    }

    function map_attributes($Path)
    {
    }

    function get($p)
    {
        return $this->fetch_part($p);
    }
    function set($p, $v)
    {
        return $this->set_part($p, $v);
    }
    function del($p)
    {
        return $this->delete_part($p);
    }
    function lst($p)
    {
        return $this->fetch_list($p);
    }
    function nde($p)
    {
        return $this->fetch_node($p);
    }
    function nds($p)
    {
        return $this->fetch_nodes($p);
    }
    function cnt($p)
    {
        return $this->count_parts($p);
    }
    function def($p)
    {
        return $this->part_string($p);
    }
    function map($p)
    {
        return $this->map_attributes($p);
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function XMLToDoc($XML)
    {
        if (!is_string($XML) || $XML == '') throw new Exception("Invalid argument 1 to XMLToDoc.  Expected string, got " . print_r($XML, true));
        $XML = self::make_tidy_string($XML);
        $D = new DOMDocument;
        $D->loadXML($XML);
        return $D;
    }

    public static function FileToDoc($f)
    {
        if (!is_string($f)) throw new Exception("Invalid argument 1 to FileToDoc.  Expected filename, got " . print_r($f, true));
        if (!file_exists($f)) throw new Exception("File not found: $f");
        $D = new DOMDocument;
        xml_file::protected_file_operation($f, "r", function ($fp) use ($D, $f) {
            $D->load($f);
        });
        return $D;
    }

    public static function DocToXML($Doc)
    {
        if (!is_object($Doc) || !is_a($Doc, self::cDOMDocument)) throw new Exception("Invalid Argument 1 to DocToXML.  Expected DOMDocument, got " . get_class($Doc));
        return $Doc->saveXML();
    }

    public static function DocElToDoc($el)
    {
        if (!is_object($el) || !is_a($el, self::cDOMElement)) throw new Exception("Invalid argument 1 to DocElToDoc.  Expected DOMElement, got " . get_class($el));
        $x = $el->ownerDocument->saveXML($el);
        return self::XMLToDoc($x);
    }

    public static function xmlDoc($XML)
    {
        return self::XMLToDoc($XML);
    }
    public static function docXml($el)
    {
        return self::DocToXML($el);
    }

    public static function docXmlFile($el)
    {
        if (!is_object($el) || !is_a($el, self::cDOMElement)) throw new Exception("Invalid argument 1 to nodeXml.  Expected DOMElement, got " . get_class($el));
        return $el->ownerDocument->saveXML($el);
    }

    public static function nodeXml($el)
    {
        if (!is_object($el) || !is_a($el, self::cDOMElement)) throw new Exception("Invalid argument 1 to nodeXml.  Expected DOMElement, got " . get_class($el));
        return $el->ownerDocument->saveXML($el);
    }

    public static function nodeXmlFile($el)
    {
        if (!is_object($el) || !is_a($el, self::cDOMElement)) throw new Exception("Invalid argument 1 to nodeXmlFile.  Expected DOMElement, got " . get_class($el));
        return new xml_file(self::nodeXml($el));
    }

    public static function nodeXmlDoc($el)
    {
        if (!is_object($el) || !is_a($el, self::cDOMElement)) throw new Exception("Invalid argument 1 to nodeXmlDoc.  Expected DOMElement, got " . get_class($el));
        return self::nodeXmlFile($el)->Doc;
    }

    /////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////

    static function toXmlFile($k)
    {
        return new xml_file($k);
    }

    static function toDoc($k)
    {
        if (is_string($k)) {
            if (file_exists($k)) return self::FileToDoc($k);
            else if (substr(trim($k), 0, 1) == '<') return self::XMLToDoc($k);
        } else if (is_object($k)) {
            if (is_a($k, self::cDOMDocument)) return $k;
            else if (is_a($k, self::cDOMElement)) return self::nodeXmlDoc($k);
            else if (is_a($k, get_class())) return $k->Doc;
        }
        return null;
    }

    static function toDocEl($k)
    {
        return self::toDoc($k)->documentElement;
    }

    static function toXml($k)
    {
        if (is_string($k)) {
            if (file_exists($k)) return xml_file::read_file($k);
            else if (substr(trim($k), 0, 1) == '<') return $k;
        } else if (is_object($k)) {
            if (is_a($k, self::cDOMDocument)) return self::docXml($k);
            else if (is_a($k, self::cDOMElement)) return self::nodeXml($k);
            else if (is_a($k, get_class())) return $k->saveXML();
        }
        return null;
    }

    static function toXhtml($k)
    {
        $s = self::toXML($k);
        if (substr($s, 0, 6) == "<?xml " && false !== ($l = strpos($s, '?>')))
            $s = substr($s, $l + 2);

        $s = trim($s);
        return $s;
    }

    static function toJson($k)
    {
        return self::toXmlFile($k)->saveJson();
    }

    static function transform_static($src, $f, $doRegister = true)
    {
        if (!($Doc = self::toDoc($src)))
            throw new Exception("Missing arg 1 for transform_static. Expected filename, xml_file, domdocument, etc.  Got " . gettype($src));
        if (!($xsl = self::toDoc($f)))
            throw new Exception("Missing arg 2 for transform_static. Expected filename, xml_file, domdocument, etc.  Got " . gettype($f));

        $xh = new XSLTProcessor();
        if ($doRegister) $xh->registerPHPFunctions();
        $xh->importStyleSheet($xsl);
        $D = $xh->transformToDoc($Doc);
        unset($xh);
        unset($xsl);
        return $D;
    }

    static function transformXSL_static($f, $XSL, $doRegister = true)
    {
        return self::transform_static($f, self::XMLToDoc($XSL), $doRegister);
    }

    static function transformXML_static($XML, $f, $doRegister = true)
    {
        return xml_file::transform_static(xml_file::XMLToDoc($XML), $f, $doRegister);
    }

    static function transformXMLXSL_static($XML, $XSL, $doRegister = true)
    {
        return xml_file::transform_static(xml_file::XMLToDoc($XML), xml_file::XMLToDoc($XSL), $doRegister);
    }

    static function NodeToString($node, $part = "all")
    {
        switch ($part) {
            case "open":
                $ss = '<' . $node->nodeName;
                foreach ($node->attributes as $att) $ss .= ' ' . $att->nodeName . "='" . str_replace("'", '"', $att->nodeValue) . "'";
                $ss .= $node->hasChildNodes() ? ">" : " />";
                return $ss;
            case "contents":
                $ss = "";
                foreach ($node->childNodes as $child) $ss .= "\n" . $child->ownerDocument->saveXML($child);
                return $ss;
            case "close":
                return ($node->hasChildNodes()) ? "</" . $node->nodeName . ">" : '';
            default:
                return $node->ownerDocument->saveXML($node);      // all
        }
    }

    function transformToDoc($f, $doRegister = true)
    {
        if (!($f = self::toDoc($f)))
            throw new Exception("Invalid argument 1 to transform().");

        $xh = new XSLTProcessor();
        if ($doRegister) $xh->registerPHPFunctions();
        $xh->importStyleSheet($f);
        $result = $xh->transformToDoc($this->Doc);
        unset($xh);
        return $result;
    }

    function transform($f, $doRegister = true)
    {
        $this->Doc = $this->transformToDoc($f, $doRegister);
        return true;
    }

    function transformXSL($xslt, $doRegister = true)
    {
        $xsl = new DomDocument;
        $xsl->loadXML($xslt);
        $result = $this->transform($xsl, $doRegister);
        unset($xsl);
        return $result;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    static function xpathsplit($string)
    {
        return self::qsplit("/", $string, "'", false);
    }

    static function qsplit($separator = ",", $string, $delim = "\"", $remove = true)
    {
        $elements = explode($separator, $string);
        for ($i = 0; $i < count($elements); $i++) {
            $nquotes = substr_count($elements[$i], $delim);
            if ($nquotes % 2 == 1) {
                for ($j = $i + 1; $j < count($elements); $j++) {
                    if (substr_count($elements[$j], $delim) % 2 == 1) {
                        // Put the quoted string's pieces back together again
                        array_splice($elements, $i, $j - $i + 1, implode($separator, array_slice($elements, $i, $j - $i + 1)));
                        break;
                    }
                }
            }
            if ($remove && $nquotes > 0) {
                // Remove first and last quotes, then merge pairs of quotes
                $qstr = &$elements[$i];
                $qstr = substr_replace($qstr, '', strpos($qstr, $delim), 1);
                $qstr = substr_replace($qstr, '', strrpos($qstr, $delim), 1);
                $qstr = str_replace($delim . $delim, $delim, $qstr);
            }
        }
        return $elements;
    }

    //  Extends XPaths correctly
    static function extend_path($base, $field, $accessor)
    {
        if ($base == "") $base = '/';
        if ($base[strlen($base) - 1] != '/') $base = $base . "/";
        if ($accessor == '@') $accessor = $base . '@' . $field;
        else if ($accessor == '') $accessor = $base . $field;
        else if ($accessor[0] != '/') $accessor = $base . $accessor;
        return $accessor;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    static function has_field_accessor($part)
    {
        return strstr($part, "[*]") !== false;
    }

    static function remove_field_accessor($part)
    {
        return str_replace("[*]", "", $part);
    }

    static function replace_field_accessor($part, $val)
    {
        return str_replace("[*]", $val, $part);
    }

    static function add_field_accessor($part)
    {
        if (strpos($part, "[*]") === false) {
            $s = explode("/", $part);
            if (substr($s[count($s) - 1], 0, 1) == "@")
                $s[count($s) - 2] = $s[count($s) - 2] . "[*]";
            else
                $s[count($s) - 1] = $s[count($s) - 1] . "[*]";
            $part = implode("/", $s);
        }
        return $part;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    private function replace_content($node, $value, $allow_delete = true)
    {
        $dom = $node->ownerDocument;
        $newnode = $dom->createElement($node->tagName);
        if (strstr($value, "<") !== false || strstr($value, ">") !== false || strstr($value, "\n") !== false)
            $newt = $dom->createCDATASection($value);
        else
            $newt = $dom->createTextNode($value);

        if ($node->hasChildNodes())
            for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
                $c = $node->childNodes->item($i);
                if ($c->nodeType == XML_TEXT_NODE || $c->nodeType == XML_CDATA_SECTION_NODE) $node->removeChild($c);
            }

        if (!($allow_delete && $value == ""))
            $node->appendChild($newt);
    }

    private function replace_attribute($node, $attr, $value, $allow_delete = true)
    {
        if ($node->nodeType == XML_ATTRIBUTE_NODE) $node = $node->parentNode;
        if ($node->hasAttribute($attr)) $node->removeAttribute($attr);
        if (!$allow_delete || $value != "")
            $node->setAttribute($attr, $value);
        return $value;
    }

    function delete_part($srcx)
    {
        return $this->delete_node($srcx);
    }

    function delete_node($srcx)
    {
        if (substr($srcx, strlen($srcx) - 1, 1) == "/") $srcx = substr($srcx, 0, strlen($srcx) - 1);
        $p = $this->fetch_node($srcx);
        if ($p == null) return;
        $k = $p->parentNode;
        if ($p->nodeType == XML_ATTRIBUTE_NODE) {
            $k->removeAttribute($p->nodeName);
            if (!$k->hasAttributes()) {
                $k->parentNode->removeChild($k);
            }
        } else {
            $k->removeChild($p);
        }
        $this->modified = true;
        return true;
    }

    private function XPathAttribute($S, &$lvl, &$attr, &$val)
    {
        $lvl = $S;
        $attr = "";
        $val = "";

        $a = strpos($S, "[");
        if ($a === false) return false;
        $b = strpos($S, "]");

        $Sa = substr($S, 0, $a);
        $Sx = substr($S, $a + 1, $b - $a - 1);
        if (is_numeric($Sx)) $Sx = "position()=$Sx";
        $Sy = explode("=", $Sx);
        if (count($Sy) == 2) {
            $Sb = $Sy[0];
            $Sc = $Sy[1];
        } else return false;

        if (substr($Sb, 0, 1) == "@") $Sb = substr($Sb, 1);
        if (
            substr($Sc, 0, 1) == "'" && substr($Sc, strlen($Sc) - 1) == "'" ||
            substr($Sc, 0, 1) == '"' && substr($Sc, strlen($Sc) - 1) == '"'
        )
            $Sc = substr($Sc, 1, strlen($Sc) - 2);

        $lvl = $Sa;
        $attr = $Sb;
        $val = $Sc;
        return true;
    }

    private function CreateXMLNode($srcx, $value = "")
    {
        $parent = $this->root();

        $s = "";
        $xsx = $this->xpathsplit($srcx);
        foreach ($xsx as $n => $accessor) {
            $pre_s = $s;
            if (!($accessor == "" && $s == "")) $s = "$s/$accessor";
            if ($s == "") continue;

            $en = $this->query($s);
            if ($en->length == 0) {
                if ($accessor[0] == '@') {
                    $this->replace_attribute($parent, substr($accessor, 1), $value, false);
                } else {
                    if (!$this->XPathAttribute($accessor, $a, $b, $c)) {
                        $dd = $this->Doc->createElement($accessor);
                        if ($n == count($xsx) - 1)
                            $this->replace_content($dd, $value);
                        $parent->appendChild($dd);
                    } else {
                        $dd = $this->Doc->createElement($a);
                        if ($n == count($xsx) - 1) $this->replace_content($dd, $value);
                        if ($b != "position()") $this->replace_attribute($dd, $b, $c, true);
                        $parent->appendChild($dd);
                        if ($b == "position()") {
                            $dp = str_replace("$a" . "[" . "position()=$c" . "]", "$a", $s);
                            $d = $this->count_parts($dp);
                            $s = str_replace("$a" . "[" . "position()=$c" . "]", "$a" . "[" . "position()=$d" . "]", $s);
                        }
                    }
                    $parent = $dd;
                }
            } else
                $parent = $en->item(0);
        }

        $this->modified = true;
        return true;
    }

    function set_part($path, $value, $allow_delete = true)
    {
        $entries = $this->query($path);
        if ($entries == null) return false;
        if ($entries->length == 0) {
            if (!$allow_delete || $value != "")  // no delete if not existant
                $this->CreateXMLNode($path, $value);
        } else {
            $target = $entries->item(0);
            if ($target->nodeType == XML_ATTRIBUTE_NODE) {
                $p = $target->parentNode;
                $this->replace_attribute($target, $target->nodeName, $value);
            } else {
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
        if ($adj === 0) return;  // go no where
        if (substr($path, strlen($path) - 1, 1) == "/") $path = substr($path, 0, strlen($path) - 1);
        $entries = $this->query($path);
        if ($entries == null) return;
        if ($entries->length != 1) {
            if (substr($path, strlen($path) - 1) == "/") return $this->adjust_part(substr($path, 0, strlen($path) - 1), $adj);
            unset($D);
            return false;
        }
        $target = $entries->item(0);
        $NN = $target->nodeName;
        $x = $target->cloneNode(true);
        $parent = $target->parentNode;
        if ($adj == "top") {
            $parent->insertBefore($x, $parent->firstChild);
            $parent->removeChild($target);
        } else if ($adj == "bottom") {
            $parent->appendChild($x);
            $parent->removeChild($target);
        }
        if ($adj < 0) {
            $px = $prev = $target;
            while ($adj < 0) {
                if (($prev = $prev->previousSibling) == null) break;
                $px = $prev;
                if ($px->nodeName == $NN) $adj++;
            }
            $parent->insertBefore($x, $px);
            $parent->removeChild($target);
        } else if ($adj > 0) {
            $next = $target;
            $adj++;
            while ($adj > 0) {
                if (($next = $next->nextSibling) == null) break;
                if ($next->nodeName == $NN) $adj--;
            }
            if ($next == null)
                $parent->appendChild($x);
            else
                $parent->insertBefore($x, $next);
            $parent->removeChild($target);
        }
        $this->modified = true;
        return true;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function loadJson($json, $root = 'jsonData')
    {
        if (file_exists($json)) $json = xml_file::read_file($json);
        $this->Doc = $this->jsonToDomDocument($json, $root);
        $this->init();
    }

    private function jsonToDomDocument($json, $root = 'jsonData')
    {
        // print("\n<br/>jsonToDomDocument()");
        try {
            $data = json_decode($json);
        } catch (Exception $e) {
            throw new Exception("jsonToDomDocument: json_decode failed: " + e . getMessage());
        }
        // print_r($data);

        // print("\n<br/>jsonToDomDocument(): Creating document");
        $doc = new DomDocument();
        // print("\n<br/>jsonToDomDocument(): Loading document");
        $doc->loadXML("<?xml version=\"1.0\" ?>\n<$root />");

        // print("\n<br/>jsonToDomDocument(): loading data");
        $this->jsonToDomDocumentItem($data, $doc->documentElement, $doc);
        // print("\n<br/>jsonToDomDocument(): loaded data");
        return $doc;
    }

    private function cleanXmlName($name)
    {
        // Element names are case-sensitive
        // Element names must start with a letter or underscore
        // Element names cannot start with the letters xml (or XML, or Xml, etc)
        // Element names can contain letters, digits, hyphens, underscores, and periods
        // Element names cannot contain spaces
        $name = preg_replace("[\]\[ &<>,]", "-", $name);
        if (preg_match("[a-zA-Z_]", $name) === false) $name = "_$name";
        return $name;
    }

    private function cleanXmlVal($name)
    {
        return "$name";
    }

    private function jsonToDomDocumentItem($el,  $parent, $doc)
    {
        // print "\n<br/>jsonToDomDocumentArray(): class=" . (is_object($el) ? get_class($el) : "--");
        if (is_a($el, "stdClass")) {
            foreach ($el as $item => $val) {
                // print "\n<br/>jsonToDomDocumentArray(): item=$item [" . $this->cleanXmlName($item) . "]";
                $node = $doc->createElement($this->cleanXmlName($item));
                $parent->appendChild($node);
                $this->jsonToDomDocumentItem($val, $node, $doc);
                // print "\n<br/>jsonToDomDocumentArray(): val=";
                // print_r($val);
            }
        } else if (is_array($el)) {
            foreach ($el as $v) {
                $n = 0;
                $node = $doc->createElement("item");
                $parent->appendChild($node);
                $this->jsonToDomDocumentItem($v, $node, $doc);
            }
        } else {
            $parent->textContent = $this->cleanXmlVal($el);
        }
    }

    public static function saveJsonXsltStandard()
    {
        return <<<'EOC'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text" encoding="utf-8"/>
    <xsl:variable name='q'>"</xsl:variable>
    
    <xsl:template match="/*[node()]">
        <xsl:text>{</xsl:text>
        <xsl:apply-templates select="." mode="detect" />
        <xsl:text>}</xsl:text>
    </xsl:template>
    
    <xsl:template match="*" mode="detect">
        <xsl:choose>
            <xsl:when test="name(preceding-sibling::*[1]) = name(current()) and name(following-sibling::*[1]) != name(current())">
                    <xsl:apply-templates select="." mode="obj-content" />
                <xsl:text>]</xsl:text>
                <xsl:if test="count(following-sibling::*[name() != name(current())]) &gt; 0">, </xsl:if>
            </xsl:when>
            <xsl:when test="name(preceding-sibling::*[1]) = name(current())">
                    <xsl:apply-templates select="." mode="obj-content" />
                    <xsl:if test="name(following-sibling::*) = name(current())">, </xsl:if>
            </xsl:when>
            <xsl:when test="following-sibling::*[1][name() = name(current())]">
                <xsl:text>"</xsl:text><xsl:value-of select="name()"/><xsl:text>" : [</xsl:text>
                    <xsl:apply-templates select="." mode="obj-content" /><xsl:text>, </xsl:text>
            </xsl:when>
            <xsl:when test="count(./child::*) > 0 or count(@*) > 0">
                <xsl:value-of select='concat($q, name(), $q, ": ")' /><xsl:apply-templates select="." mode="obj-content" />
                <xsl:if test="count(following-sibling::*) &gt; 0">, </xsl:if>
            </xsl:when>
            <xsl:when test="count(./child::*) = 0">
                <xsl:value-of select='concat($q, name(), $q, ": ", $q, ., $q)' />
                <xsl:if test="count(following-sibling::*) &gt; 0">, </xsl:if>
            </xsl:when>
        </xsl:choose>
    </xsl:template>
    
    <xsl:template match="*" mode="obj-content">
        <xsl:text>{</xsl:text>
            <xsl:apply-templates select="@*" mode="attr" />
            <xsl:if test="count(@*) &gt; 0 and (count(child::*) &gt; 0 or text())">, </xsl:if>
            <xsl:apply-templates select="./*" mode="detect" />
            <xsl:if test="count(child::*) = 0 and text() and not(@*)">
                <xsl:value-of select='concat($q, name(), $q, ": ", $q, text(), $q)' />
            </xsl:if>
            <xsl:if test="count(child::*) = 0 and text() and @*">
                <xsl:value-of select='concat($q, "text", $q, ": ", $q, text(), $q)' />
            </xsl:if>
        <xsl:text>}</xsl:text>
        <xsl:if test="not(position() &gt;= last())">, </xsl:if>

    </xsl:template>
    
    <xsl:template match="@*" mode="attr">
        <xsl:value-of select='concat($q, name(), $q, ": ", $q, ., $q)' />
        <xsl:if test="not(position() &gt;= last())">,</xsl:if>
    </xsl:template>
    
    <xsl:template match="node/@TEXT | text()" name="removeBreaks">
        <xsl:param name="pText" select="normalize-space(.)"/>
        <xsl:choose>
            <xsl:when test="not(contains($pText, '&#xA;'))"><xsl:copy-of select="$pText"/></xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="concat(substring-before($pText, '&#xD;&#xA;'), ' ')"/>
                <xsl:call-template name="removeBreaks">
                    <xsl:with-param name="pText" select="substring-after($pText, '&#xD;&#xA;')"/>
                </xsl:call-template>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
</xsl:stylesheet>
EOC;
    }

    public static function saveJsonXsltRecordset()
    {
        return <<<'EOC'
<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text"/>
    <xsl:variable name='q'>"</xsl:variable>

    <xsl:template match="/recordset">
        <xsl:value-of select='concat("{", $q, @zname, $q, ": [")' />
            <xsl:for-each select='/*/row'>
                {
                <xsl:for-each select='field'>
                    <xsl:value-of select='concat($q, @id, $q)' />: <xsl:value-of select='concat($q, normalize-space(.), $q)' />
                    <xsl:if test="following-sibling::*">,</xsl:if>
                </xsl:for-each>
                }
            <xsl:if test="following-sibling::*">,</xsl:if>
            </xsl:for-each>
]}
    </xsl:template>
</xsl:stylesheet>
EOC;
    }

    public static function saveJsonStylesheet($mode)
    {
        switch ($mode) {
            case "recordset":
                return self::saveJsonXsltRecordset();
            default:
                return self::saveJsonXsltStandard();
        }
    }

    public function saveJson($mode = '', $options = JSON_PRETTY_PRINT)
    {
        $str = self::docXml(self::transformXMLXSL_static($this->saveXML(), self::saveJsonStylesheet($mode)));
        $str = str_replace('<?xml version="1.0"?>', '', $str);
        $str = str_replace("\n", '', $str);
        $result = self::tidyJson_string($str, $options);
        return $result !== false ? $result : $str;  // in case tidy fails
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected static function protected_file_operation($filename, $filemode, $operation)
    {
        $fp = fopen($filename, $filemode);
        $lock_success = false;
        $result = "";

        $attempts = 15;
        while ($attempts-- > 0) {
            if (flock($fp, $filemode == "r" ? LOCK_SH : LOCK_EX)) {  // acquire lock
                $result = $operation($fp, $filename);
                flock($fp, LOCK_UN);    // release the lock
                $lock_success = true;
                break;
            }
        }

        fclose($fp);
        if (!$lock_success) echo "Couldn't get the lock: $filename";
        return $result;
    }

    protected static function read_file($filename)
    {
        return xml_file::protected_file_operation($filename, "r", function ($fp) use ($filename) {
            return fread($fp, filesize($filename));
        });
    }

    protected static function write_file($filename, $contents)
    {
        // We use same format for read and write, and read doesn't accept LOCK_SH
        // file_put_contents($filename, $contents, LOCK_EX);

        return xml_file::protected_file_operation($filename, "w",  function ($fp) use ($contents) {
            ftruncate($fp, 0);      // truncate (erase/overwrite) file
            fwrite($fp, $contents);
            fflush($fp);            // flush output before releasing the lock
            return "";              // Supply return value.  Not used on write.
        });
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    static function tidyJson_string($json, $options = JSON_PRETTY_PRINT)
    {
        if (!is_string($json)) throw new InvalidArgumentException("Expecting string, got [" . gettype($json) . "]");
        if ($json == '') return "";

        $data = json_decode($json);
        if ($data == null) return $json;

        return json_encode($data, $options);
    }

    static function tidyXML_OPT()
    {
        $topt = array();

        $topt["wrap"] = 0;
        $topt["input-xml"] = true;
        $topt["output-xml"] = true;
        $topt["add-xml-decl"] = false;
        $topt["quiet"] = true;
        $topt["fix-bad-comments"] = true;
        $topt["fix-backslash"] = true;
        $topt["tidy-mark"] = false;
        $topt["char-encoding"] = "raw";
        $topt["indent"] = true;
        $topt["indent-spaces"] = 4;
        $topt["indent-cdata"] = false;
        $topt["add-xml-space"] = true;
        $topt["escape-cdata"] = false;
        $topt["write-back"] = true;
        $topt["literal-attributes"] = true;

        $topt["force-output"] = true;
        return $topt;
    }

    static function tidyXHTML_OPT()
    {
        $topt = array();
        $topt["input-xml"] = false;
        $topt["output-xhtml"] = true;
        $topt["output-xml"] = false;
        $topt["markup"] = true;
        $topt["new-empty-tags"] = "page, field, caption";
        $topt["add-xml-decl"] = false;
        //          $topt["add-xml-pi"]     = false;
        $topt["alt-text"] = "Image";
        $topt["break-before-br"] = true;
        $topt["drop-empty-paras"] = false;
        $topt["fix-backslash"] = true;
        $topt["fix-bad-comments"] = true;
        $topt["hide-endtags"] = false;
        $topt["char-encoding"] = "raw";
        $topt["indent"] = true;
        $topt["indent-spaces"] = 2;
        $topt["indent-cdata"] = false;
        $topt["escape-cdata"] = false;
        $topt["quiet"] = true;
        $topt["tidy-mark"] = false;
        $topt["uppercase-attributes"] = false;
        $topt["uppercase-tags"] = false;
        $topt["word-2000"] = false;
        $topt["wrap"] = false;
        $topt["wrap-asp"] = true;
        $topt["wrap-attributes"] = true;
        $topt["wrap-jste"] = true;
        $topt["wrap-php"] = true;
        $topt["write-back"] = true;
        $topt["add-xml-space"] = true;

        $topt["force-output"] = true;
        $topt["show-body-only"] = true;

        return $topt;
    }

    static function tidy_opt($style = "xml")
    {
        return $style == "xhtml" ? self::tidyXHTML_OPT() : self::tidyXML_OPT();
    }

    static function make_tidy_string($str, $style = "auto")
    {
        if ($style == "none") return $str;
        if (!class_exists("tidy")) return $str;
        $tidy = new tidy;
        $tidy->parseString($str, self::tidy_opt($style));
        $tidy->CleanRepair();
        $s = $tidy->value;
        return self::tidy_cleanup($s, $style);
    }

    static function make_tidy_doc($D, $style = "auto")
    {
        if ($style == "none") return $D;
        if (!$D) return "";
        $x = $D->saveXML();
        $x = self::make_tidy_string($x, $style);
        $x = str_replace("&nbsp;", "&#160;", $x);
        $x = self::tidy_cleanup($x, $style);
        $D = new DOMDocument;
        $D->loadXML($x);
        return $D;
    }

    static function make_tidy($filename, $style = "xml")
    {
        if ($style == "none") return true;
        if (!class_exists("tidy")) return false;
        $tidy = new tidy;
        $tidy->parseFile($filename, self::tidy_opt($style));
        $tidy->CleanRepair();
        return xml_file::write_file($filename, $tidy->value);
    }

    static function tidy_cleanup($s, $style = 'auto')             // when tidy makes a mess....
    {
        // Tidy wont stop indenting CDATA, which adds extra line feeds at the beginning and end of of CDATA fields
        // despite indent-cdata being set to false
        if ($style == "none") return $s;
        $s = preg_replace("/\n( )*<![[]CDATA[[](.*)[]][]]>\n/U", "<![CDATA[$2]]>", $s);
        switch ($style) {
            case "xhtml":
                $s = preg_replace("\$(<textarea([^>])*>)\n(.*)\n(</textarea>)\$U", "$1$3$4", $s);
                break;
            case "xmlfragment":
                $s = preg_replace("\$\<.xml (.)*?\>\$", "", $s);
        }
        return $s;
    }
}
