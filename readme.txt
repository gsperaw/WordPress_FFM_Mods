=== Front File Manager ===
Contributors: JohnnyPea
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=FUT8H7SGMYE5E
Tags: file, files, front-end, upload, media, media library, media category, media categories, attachment, attachments, shortcode
Requires at least: 3.2
Tested up to: 3.3.1
Stable tag: 0.1

Allow your visitors to upload files from front-end. You can list these files on any page using shortcode.

== Description ==

This plugin is derived from [Front End Upload](http://wordpress.org/extend/plugins/front-end-upload/) (uploader) and [PS Taxonomy Expander](http://wordpress.org/extend/plugins/ps-taxonomy-expander/) (media taxonomy features) plugins.

It provides multi-upload interface for front-end and utilizes media library so it is fully integrated into WordPress.

Interesting features:

* [Plupload](http://www.plupload.com/) upload handler allows upload multiple files at once directly from front-end
* Media categories based on build in [custom taxonomies API](http://codex.wordpress.org/Taxonomies)
* Place your upload form and file list on any page using shortcodes ( `[ffm-uploader]` and `[ffm-list]` )
* Table layout with file icons, download links and filterable by category
* Pagination included!
* Settings page for the upload form
* Media library integration, filtering, downloadable status and category display
* Fully translatable!

**Are you missing something or isn't it working as expected ? I am open to suggestions to improve the plugin !**

Thanks the [Slovak WordPress community](http://wp.sk/) and [webikon.sk](http://www.webikon.sk/) for the support. You can find free support for WordPress related stuff on [Techforum.sk](http://www.techforum.sk/). For more information about me check out my [personal page](http://johnnypea.wp.sk/).

== Installation ==

1. Upload the `front-file-manager` folder to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Edit your options under **Settings > Front File Manager**
1. Place `[ffm-uploader]` in the editor where you would like the uploader to appear
1. Place `[ffm-list]` in the editor where you would like the media list to appear
1. Customize your CSS

== Frequently Asked Questions ==

= Can I email you with the support questions ? =

No. Please use integrated forum support system.

= Do you provide some extra "premium" customization ? =

Yes. You can email me in this case.

== Other Notes ==


Every file in media gallery can be set as "downloadable" just check the checbox on media edit page. You can turn on/off automatic assigning downloadable status to newly uploaded files through front-end form on settings page.

Check "Only Registered Users" if you want to user must be logged in to upload the files.

Parameters for `[ffm-list]` shortcode:

* **type** - what type of media you want to display (image, video, pdf etc. ), default is ANY type
* **count** - number of items per page, default is 10
* **parent** - set this to "null" if you want to display files already attached to posts, default 0
* **downloadable** - display only items which are flagged as "downloadable", to list all set to "false", default TRUE
* **pagination** - where to display pagination, "top", "bottom", "both", default is bottom

Example: `[ffm-list type=pdf count=2 parent=null downloadable=false pagination=both]`

== Screenshots ==

1. Front-end Upload Form.
2. File List.
3. Media Library Columns.
4. Additional fields on media edit page.

== Changelog ==

= 0.1 =
initial release