=== Masala Relevanssi ===
Contributors: n/a
Tags: search, relevance, better search
Requires at least: 3.8
Tested up to: 3.8
Stable tag: 1.0
License: GPL2

Make your Relevanssi search engine to index your attachments (e.g. PDF, DOC, DOCX, PPT, PPTX). The plugin requires third-party command line conversion utilities. Support may be added any file that can be read as text-only.

== Description ==

Masala Relevanssi adds attachments to your Relevanssi search index. In other words the search is targeting not only your pages and posts but also inside your attachment files. For example if your post contains a PDF with text the search may find this attachment.

= Operation in a nutshell =

When a new attachment is added, the Masala Relevanssi checks whether it has a configured helper for the file type. The helper is selected based in the file extension. For each extension you have to configure a command line helper (e.g. pdftotext or docx2txt) which takes the source file (e.g. PDF or DOCX) and outputs the contentes to standard output as text. This text is added to the Relevanssi index.

As the text is added as additional metadata the normal attachment descriptions and other metadata are not affected by Masala Relevanssi.

The file specific command line helpers are not part of Masala Relevanssi distribution.

= Background and Status =

Masala Relevanssi is based on two software
* [Relevanssi search engine for Wordpress] (http://www.relevanssi.com/) which offers an easy hook to bring additional content for search index.
* Original [Masala WordPress plugin] (http://avatari.net/public/wordpress/masala/) which uses Apache Tika to convert attachments to text. Almost all code was taken from Masala written by Alex Nano.

== Installation ==

1. Install Relevanssi. Free version works.
1. Make Relevanssi to include attachments when indexing posts: Settings > Relevanssi > Indexing Options > Choose post types to index > Select "attachment".
1. Install Masala Relevanssi.
1. Edit $MASALA_RELEVANSSI_HELPERS in masala_relevanssi.php. Define a key-value pair for each file type you wish to support. The helpers are executed with file path (parameter %f) and they should print the file content to STDOUT. The Masala Relevanssi settings page checks whether the scripts are executable.
1. Running recreate_metadata.php ("cd /path/to/wp-content/plugins/masala_relevanssi/ && php recreate_metadata.php") recreates Masala Relevanssi metadata for existing attachments. During the normal operations the metadata is created automatically so you do not have to run recreate_metadata.php after the installation.
1. Create Relevanssi search index (see Relevanssi settings page).

== Frequently Asked Questions ==

No questions asked so far.

== Changelog ==

= 2014-02-22 =
* Initial version.
