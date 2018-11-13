# Library plugin for glFusion

Provides library functions for glFusion, including check-in and check-out of
products. Normally this would be used to track physical items in an
organization's inventory, such as books and DVS's.

## Item Entry
Each item requires a unique ID such as an ISBN or SKU.
A default ID will be automatically generated. For books, the ID will ideally
be the ISBN number as it can be used to look up details online.

If you have multiple instances of an item in the library, such as multiple instances of a book,
then you can add instances without creating a new item for each one. The instances are not
individually tracked; if you need to keep track of every instance they you will need to create
uniquely-identified items.

### Item Lookup
First, select an option other than "None" from the plugin configuration. When
that is done a search icon will be shown ext to the item ID field when editing
an item. Click that to search online for details about the book.

Lookup options are:
1. openlibrary.org - This requires no configuraiton and uses the freely-available
API at https://openlibrary.org. Either ISBN or OpenLibrary ID numbers can be used.
1. Astore Plugin - This requires the installation of the Amazon Astore plugin
for glFusion (version 0.2.0 or later), and you must create an Amazon Affiliate account.
at https://affiliate-program.amazon.com/. Only ISBN numbers can be retrieved.

Requires glFusion 1.7.0 or higher and the lgLib plugin
