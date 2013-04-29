<?php //netteCache[01]000387a:2:{s:4:"time";s:21:"0.07317500 1367216359";s:9:"callbacks";a:2:{i:0;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:9:"checkFile";}i:1;s:65:"/usr/share/php/data/ApiGen/templates/default/elementlist.js.latte";i:2;i:1346325656;}i:1;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:10:"checkConst";}i:1;s:25:"Nette\Framework::REVISION";i:2;s:30:"2f3808e released on 2012-07-30";}}}?><?php

// source file: /usr/share/php/data/ApiGen/templates/default/elementlist.js.latte

?><?php
// prolog Nette\Latte\Macros\CoreMacros
list($_l, $_g) = Nette\Latte\Macros\CoreMacros::initRuntime($template, '3mgawl1aoi')
;
// prolog Nette\Latte\Macros\UIMacros

// snippets support
if (!empty($_control->snippetMode)) {
	return Nette\Latte\Macros\UIMacros::renderSnippets($_control, $_l, get_defined_vars());
}

//
// main template
// ?>

var ApiGen = ApiGen || {};
ApiGen.elements = <?php echo Nette\Templating\Helpers::escapeJs($elements) ?>;
