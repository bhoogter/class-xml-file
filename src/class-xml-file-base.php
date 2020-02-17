<?php

abstract class xml_file_base implements xml_file_interface {
    public $metadata;

    abstract function type();

    abstract function nde($p);
    abstract function nds($p);
    abstract function def($p);
    abstract function get($p);
    abstract function set($p, $x);
    abstract function lst($p);
    abstract function cnt($p);
    abstract function del($p);

    private function ensure_metadata() {
        if (!is_array($this->metadata)) $this->metadata = [];
        return $this->metadata;
    }

    public function init_metadata()              { /* Overridable */ }
    public function get_property_list()          { return array_keys($this->ensure_metadata()); }
    public function has_property($field)         { return in_array($field, $this->get_property_list()); }
    public function get_property($field)         { return $this->ensure_metadata()[$field]; }
    public function set_property($field, $value) { $this->ensure_metadata(); return $this->metadata[$field] = $value; }

    abstract function load($src);
    abstract function save($f = '', $style = 'auto');
    abstract function can_save();
    abstract function merge($scan, $root = null, $item = null, $persist = null);

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
