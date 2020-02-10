<?php

interface xml_file_interface {
    function type();

    function nde($p);
    function nds($p);
    function def($p);
    function get($p);
    function set($p, $x);
    function lst($p);
    function cnt($p);
    function del($p);

    function load($src);
    function save($f = '', $style = 'auto');
    function can_save();
    function merge($scan, $root = null, $item = null, $persist = null);

    function node($p);
    function nodes($p);
    function fetch_node($p);
    function fetch_nodes($p);
    function delete_node($p);
    function part_string($p);
    function fetch_part($p);
    function set_part($p, $v);
    function fetch_list($p);
    function count_parts($p);
}
