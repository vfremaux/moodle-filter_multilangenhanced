$Id: README.txt,v 1.6 2006/12/12 10:39:17 diml Exp $

WHy it is an enhanced filter: 

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

To Install it:
    - Enable if from "Administration/Filters".
  
To Use it:
    - Create your contents in multiple languages.
    - Enclose every language content between:
        <span lang="XX" class="multilang">your_content_here</span>
	- Text 
    - Test it (by changing your language).

How it works:
    - look for "lang blocks" in the code.
    - for each "lang block":
        - if there are texts in the currently active language, print them.
        - else, if there exists texts in the current parent language, print them.
        - else, do not print.
    - text out of "lang blocks" will be showed always.

Definition of "lang block":
    Is a collection of lang tags separated only by whitespace chars (space,
    tab, linefeed or return chars).

One example in action:
    - This text:
        <span lang="en" class="multilang">Hello!</span><span lang="es" class="multilang">Hola!</span>
        This text is common for every language because it's out from any lang block.
        <span lang="en" class="multilang">Bye!</span><span lang="it" class="multilang">Ciao!</span>

    - Will print, if current language is English:
        Hello!
        This text is common for every language because it's out from any lang block.
        Bye!

    - And, in Spanish, it will print:
        Hola!
        This text is common for every language because it's out from any lang block.
    
