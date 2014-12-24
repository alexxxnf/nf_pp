all: nf_pp.php

src/nf_pp.min.js:
	@echo Minimizing JS
	@curl \
		-s \
		-d compilation_level=WHITESPACE_ONLY \
		-d output_format=text \
		-d output_info=compiled_code \
		--data-urlencode "js_code@src/nf_pp.js" \
		http://closure-compiler.appspot.com/compile \
	| sed \
		-e 's/\\\\/\\\\\\\\/' \
		-e "s/'/\\'/" > src/nf_pp.min.js

src/nf_pp.min.css:
	@echo Minimizing CSS
	@curl \
		-s \
		--data-urlencode "input@src/nf_pp.css" \
		http://cssminifier.com/raw \
	| sed \
		-e 's/\\\\/\\\\\\\\/' \
		-e "s/'/\\'/" > src/nf_pp.min.css

nf_pp.php: src/nf_pp.min.js src/nf_pp.min.css
	@echo Injecting JS and CSS into PHP
	@sed \
		-e '/{{nf_pp.js}}/r src/nf_pp.min.js' \
		-e '/{{nf_pp.css}}/r src/nf_pp.min.css' \
		-e 's/{{nf_pp.js}}//' \
		-e 's/{{nf_pp.css}}//' \
		src/nf_pp.php > nf_pp.php

clean:
	@echo Cleaning
	@rm -f src/nf_pp.min.js src/nf_pp.min.css
