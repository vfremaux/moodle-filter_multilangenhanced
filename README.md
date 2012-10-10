moodle-filter_multilangenhanced
===============================

Major enhancement is done on the lang block processing algorithm. The actual algorithm
supports nested <span> tags that may mark language c ontent or other text formatting
aspects. The standard algorithm fails to deal with several nested <span></span> tags, 
because of the nesting effect on a single RegExp fetch ahead.

The enhanced algoithm is aware fo span nesting and will correctly fetch ahead the 
actual corresponding closing </span> tag for the language block start marker.

When using the old syntax, with <lang> tag, a standard single regexp fetch ahead is performed.

Other changes:

This filter processes the lang blocks one by one, eliminating all unwanted (non matching)
languages. 

Todo: 
Check wether a default language version feature can be added back.  

Versions
========

Moodle 1.9 : on branch MOODLE_19_STABLE

Moodle 2.x : on branch master