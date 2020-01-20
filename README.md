# XMLFile (class)

A mid-level library class for simplifying XML access (read/write).  Useful for XML-based config, setting/fetching settings,
modifying contents, including creating new entries.

## Benefits

1. Returning empty string or passed default on no contents.
1. Create a new node/attribute regardless of whether parent nodes exist.
1. Perform most standard operations without creating a new XML query object.
1. Can automatically TIDY output on save, in a variety of pre-defined configurations (XML, XHTML)--or pass your own.

## Additions

- Also loads and saves JSON strings / files and allows performing XPATH queries on them (because it internally converts it to XML).
- Tidy (both static and local) for both XML (if HTML Tidy is enabled, which it usually is) and JSON (via json_encode).

## Credits

Benjamin Hoogterp
http://www.BenjaminHoogterp.com

## License

LGPL v3.0
