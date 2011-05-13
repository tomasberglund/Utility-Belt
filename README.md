PHP Utility Belt
================

Introduction
------------
Like [many other developers][2] I have amassed a collection of various functions that I frequently call upon in day-to-day projects. I've pulled these into namespaced classes and made them publicly available on [GitHub][3].

I've tried to give attribution or link to further information where apposite but if I've been lax and missed someone or something out then be assured that it was merely simple oversight. Please let me know and I'll rectify the situation.

Requirements
------------
These classes have been developed with PHP 5.3 in mind. However, with minimal tweaking, most methods should work with PHP 5.2.  They are by-and-large stand-alone in that they don't depend on any other code. The notable exception is `text.class.php` which makes use of the [`Normalizer`][4] class and the [`SimpleXML`][5] extension.

[1]: http://nevstokes.com/
[2]: http://allinthehead.com/retro/345/whats-in-your-utility-belt
[3]: https://github.com/nevstokes/Utility-Belt
[4]: http://www.php.net/manual/en/class.normalizer.php
[5]: http://uk2.php.net/manual/en/book.simplexml.php

- - -

License
-------

<a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/88x31.png" /></a><br />This <span xmlns:dct="http://purl.org/dc/terms/" href="http://purl.org/dc/dcmitype/Text" rel="dct:type">work</span> by <a xmlns:cc="http://creativecommons.org/ns#" href="http://nevstokes.com" property="cc:attributionName" rel="cc:attributionURL">Nev Stokes</a> is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons Attribution-ShareAlike 3.0 Unported License</a>.
