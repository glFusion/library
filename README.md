# Library plugin for glFusion

Provides library functions for glFusion, including check-in and check-out of
products. Normally this would be used to track physical items in an
organization's inventory, such as books and DVS's.

## Item Entry
Each item requires a unique ID such as an ISBN or SKU.
A default ID will be automatically generated. For books, the ID will ideally
be the ISBN number as it can be used to look up details online.

### Item Lookup
First, select an option other than "None" from the plugin configuration. When
that is done a search icon will be shown ext to the item ID field when editing
an item. Click that to search online for details about the book.

Lookup options are:
1. openlibrary.org - This requires no configuraiton and uses the freely-available
API at https://openlibrary.org.
1. Astore Plugin - This requires the installation of the Amazon Astore plugin
for glFusion (version 0.2.0 or later), and you must create an Amazon Affiliate account.
at https://affiliate-program.amazon.com/.
The positive side is that a description of the item is returned from Amazon.

Requires glFusion 1.7.0 or higher and the lgLib plugin
