CWD:=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))
.DEFAULT_GOAL := default

phpcs:
	php vendor/bin/phpcs
phpcbf:
	php vendor/bin/phpcbf
phpstan:
	php vendor/bin/phpstan analyse -c phpstan.neon
phpmd:
	php vendor/bin/phpmd src ansi codesize,unusedcode,naming
phpunit:
	php vendor/bin/phpunit -c phpunit.xml.dist
archive:
	touch archive.tar & tar -czf archive.tar . \
		--exclude=archive.tar \
		--exclude=docker \
		--exclude=tests \
		--exclude=var \
		--exclude=docker-compose.yml \
		--exclude=phpunit.xml.dist \
		--exclude=Makefile \
		--exclude=Jenkinsfile \
		--exclude=.git \
		--exclude=.gitignore \
		--exclude=README.md

default:
	@echo "Static analysis and unit tests...";
	$(call run, PHP Code Sniffer, php vendor/bin/phpcs)
	$(call run, PHP Stan, php vendor/bin/phpstan analyse -c phpstan.neon --no-progress)
	$(call run, Unit Tests, php vendor/bin/phpunit -c phpunit.xml.dist --testsuite=unit)
	$(call run, Integration Tests, php vendor/bin/phpunit -c phpunit.xml.dist --testsuite=integration)

define run
    @$(2) > /dev/null; if [ $$? -eq 0 ]; then echo "[+] $(1)"; else echo "[-] $(1)"; fi
endef
