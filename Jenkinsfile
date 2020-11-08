pipeline {
    agent any
	stages {
		stage('Checkout') {
			steps {
				checkout scm
			}
		}
		stage('Composer Install') {
			steps {
				sh 'composer install --no-scripts --ignore-platform-reqs --no-progress --no-suggest'
			}
		}
		stage ('Static code analysis') {
			steps {
				parallel (
					"Check PSR-12": {
						sh 'php74 vendor/bin/phpcs'
					},
					"PHPStan": {
						sh 'php74 vendor/bin/phpstan analyse -c phpstan.neon --no-progress'
					}
				)
			}
		}
		stage('Unit Tests') {
			steps {
				sh 'php74 vendor/bin/phpunit -c phpunit.xml.dist --testsuite=unit'
			}
		}
		stage('Integration Tests') {
			steps {
				sh 'php74 vendor/bin/phpunit -c phpunit.xml.dist --testsuite=integration'
			}
		}
	}
}
