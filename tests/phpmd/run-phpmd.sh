#!/bin/sh

phpmd ../../src/ html cleancode,codesize,design,controversial,naming,unusedcode --reportfile phpmd.html
