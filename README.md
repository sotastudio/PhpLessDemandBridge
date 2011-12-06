PHP LESS Demand Bridge
======================

What does it and what is it good for?
=====================================

Good question. Did you already know the [phpless-Project](https://github.com/leafo/lessphp)?
It is all about lessphp. Many of us don't want to care about LESS compilation - we just want to use it!

This project gives you the ability to get your compiled stylesheet based on LESS source files - on demand.
If the LESS stuff already has been compiled, the Demand Bridge will pass through a cached version of it.
Of course, this Bridge will also detect changes you may have recently made and compiles your LESS code again.