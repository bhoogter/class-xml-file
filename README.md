# XMLFile (class)

A mid-level library class for simplifying XML access (read/write).  Useful for XML-based config, setting/fetching settings,
modifying contents, including creating new entries.

## Benefits

1. Returning empty string or passed default on no contents.
1. Create a new node/attribute regardless of whether parent nodes exist.
1. Perform most standard operations without creating a new XML query object.
1. Can automatically TIDY output on save, in a variety of pre-defined configurations (XML, XHTML)--or pass your own.

## Additions

- Load/save JSON strings/files.  Perform XPATH just like on XML (because it is internally converted to XML on load).  Save JSON <-> XML.
- Tidy (both static and local) for both XML (if HTML Tidy is enabled, which it usually is) and JSON (via json_encode).
- Merge multiple sources.  Pass it a glob, array, or wildcard path, along with scan directives to combine multiple sources into a single query-able XML.

## Credits

Benjamin Hoogterp  
http://www.BenjaminHoogterp.com

## License

BSD 2 Clause
