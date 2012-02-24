# PHP LESS Demand Bridge


## What does it do and what is it good for?

Good question. Did you already know the [lessphp project](https://github.com/leafo/lessphp)?
It is all about lessphp and it depends on it, of course. Many of us don't want to care about LESS compilation - we just want to use it!

This project gives you the ability to get your compiled stylesheet based on LESS source files - on demand.
If the LESS stuff already has been compiled, the Demand Bridge will pass through a cached version of it.
Of course, this Bridge will also detect changes you may have recently made and compiles your LESS code again.

For this reason, it is simply called "Demand Bridge".

## How to use it?

Clone/download this archive into your project

	git clone git@github.com:MorphexX/PhpLessDemandBridge.git
	
Clone/download [lessphp](https://github.com/leafo/lessphp) into the lib folder

	giit clone git://github.com/leafo/lessphp.git

Move the package contents to the desired directory in your project.
You can put it, for example, in a css/engine/ directory to use the bridge through a simple HTML link-tag

	<link rel="stylesheet" type="text/css" media="all"  href="css/engine/css.php?file=bootstrap.less" />

I give you a little example of a project structure and how to implement and configure the Demand Bridge:

	/project root/
		public directory/
			css/
				engine/
					cache/
					lib/
					config.php
					css.php
				bootstrap.less
				forms.less (imported by bootstrap.less)
				mixins.less (imported by bootstrap.less)
				patterns.less (imported by bootstrap.less)
				variables.less (imported by bootstrap.less)

To use it properly, you have to adjust the config file (config.php) depending to your needs and file storage.
There are two important settings in the config file.

The LESS root file difinition is needed in every case. The whole Demand Bridge bases on it.

    // LESS root file
    // String: path/to/file.less, relative to css.php - can be overridden via GET var
    'lessFile'		=> '../bootstrap.less',

The CSS compile dir is only needed when you want to compile - if not, just ignore it.

    // Stylesheet compiling dir
    // String: path/to/dir/ to put the compiled CSS in, relative to css.php
    'compilePath' 	=> '../',

## Caching system

Oh yeah, it's really the coolest feature. The Bridge's caching works on both sides: the server's and the client's.

If you're using it for the first time, the Bridge will compile everything through lessphp and saves the result as a cache file.
Additional to the afterwards delivered css, the Bridge tells your Browser to cache it client-side which is being realized by some cache headers you can manipulate in the config file.

## Changes detection

Of course, the Bridge will also detect recently made changes at LESS stuff. The next time anybody requests the css, the Bridge recompiles it and caches everything again.

## Something's not clear enough? Wanna help me improving this app?

Please help me improving the quick start guide and code. 
It is not possible to consider every case of usage, to avoid bugs and so forth.

This project has the ability to get much stronger and faster - but only with your hints and reported bugs. Don't hesitate!