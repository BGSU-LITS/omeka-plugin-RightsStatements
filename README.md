# omeka-plugin-RightsStatements
Omeka plugin to display URLs in the Dublin Core Rights field as images describing a rights statement linked to that URL. Both [Rights Statements][http://rightsstatements.org/] and [Creative Commons][https://creativecommons.org/] are currently supported.

## Dublin Core Rights
It is expected that URL is the only text per Dublin Core Rights value, and that the URL matches one of the following only differing in license code and version.

* http://rightsstatements.org/page/CNE/1.0/
* http://rightsstatements.org/vocab/CNE/1.0/
* https://creativecommons.org/licenses/by/4.0/
* https://creativecommons.org/publicdomain/mark/1.0/

Other values found in the Dublin Core Rights field will be left as-is.

## Installing Releases
Released versions of this plugin are [available for download](https://github.com/BGSU-LITS/omeka-plugin-RightsStatements/releases). You may extract the archive to your Omeka plugin directory. [Installing the plugin in Omeka.](http://omeka.org/codex/Managing_Plugins_2.0)

## Installing Source Code
You will need to place the source code within a directory named RightsStatements in your Omeka plugin directory.

## Configuration
### Image Preference
If there is both a Rights Statements and Creative Commons URL in the the Dublin Core Rights field, you can decide whether to display only one or the other or both.

### Open in New Window
The link to the URL can be set to target a window of name `_blank`, typically causing a new window to open.

### Image Format
For both Rights Statements and Creative Commons, you can choose which image format to display. Selecting Disabled will prevent the URLs for that site from being converted into linked images.

### Image Height
For both Rights Statements and Creative Commons, you can set the height of the image displayed. For Creative Commons, it is recommended to change the default to match the Image Format.

## Development
This plugin was developed by the [University Libraries at Bowling Green State University](http://www.bgsu.edu/library.html). Development is [hosted on GitHub](https://github.com/BGSU-LITS/omeka-plugin-RightsStatements).
