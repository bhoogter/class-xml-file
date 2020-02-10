<?php

abstract class xml_file_base {
    public $ID;
    public $longdesc;
    public $shortdesc;
    public $version;

    abstract function type();

    abstract function nde($p);
    abstract function nds($p);
    abstract function def($p);
    abstract function get($p);
    abstract function set($p, $x);
    abstract function lst($p);
    abstract function cnt($p);
    abstract function del($p);

    abstract function load($src);
    abstract function save($f = '', $style = 'auto');
    abstract function can_save();

    function node($p) { return $this->nde($p); }
    function nodes($p) { return $this->nds($p); }
    function fetch_node($p) { return $this->nde($p); }
    function fetch_nodes($p) { return $this->nodes($p); }
    function delete_node($p) { return $this->del($p); }
    function part_string($p) { return $this->def($p); }
    function fetch_part($p) { return $this->get($p); }
    function set_part($p, $v) { return $this->set($p, $v); }
    function fetch_list($p) { return $this->lst($p); }
    function count_parts($p) { return $this->cnt($p); }
}
