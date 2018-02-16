# Library plugin for glFusion

Provides library functions for glFusion, including check-in and check-out of
products. Normally this would be used to track physical items in an
organization's inventory, such as books and DVS's.

## Item Entry
Each item requires a unique ID such as an ISBN or SKU.
A default ID will be automatically generated. For books, the ID will ideally
be the ISBN number as it can be used to look up details online.

### Item Lookup
Click the search icon next to the item ID field to search online for details
about the book. The default configuration uses Open Library (https://openlibrary.org)
to get details. For the item lookup to work, the item ID must be the ISBN.

You can also look up book details from Amazon via Astore plugin. The Astore plugin
version 0.2.0 or higher must be installed and enabled, and you'll need to obtain
an Amazon Associate ID. Open Library does not require any configuration or authentication.

Requires glFusion 1.7.0 or higher and the lgLib plugin
