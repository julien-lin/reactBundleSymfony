.PHONY: test test-unit test-integration test-security test-coverage lint fix-lint stan all help

help:
	@echo "╔════════════════════════════════════════════════════════════════╗"
	@echo "║               ReactBundle Symfony - Development Tasks         ║"
	@echo "╚════════════════════════════════════════════════════════════════╝"
	@echo ""
	@echo "Tests:"
	@echo "  make test              - Lancer tous les tests"
	@echo "  make test-unit         - Tests unitaires seulement"
	@echo "  make test-integration  - Tests d'intégration seulement"
	@echo "  make test-security     - Tests de sécurité seulement"
	@echo "  make test-coverage     - Tests avec rapport de couverture"
	@echo ""
	@echo "Code Quality:"
	@echo "  make lint              - Vérifier PSR-12 compliance"
	@echo "  make fix-lint          - Fixer les violations PSR-12"
	@echo "  make stan              - Analyse statique PHPStan (level 5)"
	@echo ""
	@echo "Combined:"
	@echo "  make all               - Lint + Stan + Tests + Coverage"
	@echo "  make help              - Afficher cette aide"

test:
	@echo "🧪 Lancing all tests..."
	php vendor/bin/phpunit tests/

test-unit:
	@echo "🧪 Lancing unit tests..."
	php vendor/bin/phpunit tests/Service tests/Twig tests/DependencyInjection

test-integration:
	@echo "🧪 Lancing integration tests..."
	php vendor/bin/phpunit tests/Integration

test-security:
	@echo "🛡️  Lancing security tests..."
	php vendor/bin/phpunit tests/Security

test-coverage:
	@echo "🧪 Lancing tests with coverage report..."
	php vendor/bin/phpunit tests/ --coverage-html=.coverage-report
	@echo ""
	@echo "✅ Coverage report generated in .coverage-report/"

lint:
	@echo "🔍 Checking PSR-12 compliance..."
	php vendor/bin/phpcs --standard=PSR12 src/ tests/ || true
	@echo ""

fix-lint:
	@echo "🔧 Fixing PSR-12 violations..."
	php vendor/bin/phpcbf --standard=PSR12 src/ tests/
	@echo ""
	@echo "✅ Fixed!"

stan:
	@echo "🔬 Running PHPStan (level 5)..."
	php vendor/bin/phpstan analyse src/ --level=5 || true
	@echo ""

all: lint stan test-coverage
	@echo ""
	@echo "╔════════════════════════════════════════════════════════════════╗"
	@echo "║                    ✅ All checks passed!                        ║"
	@echo "╚════════════════════════════════════════════════════════════════╝"

clean:
	@echo "🧹 Cleaning up..."
	rm -rf .coverage-report/
	rm -rf .phpunit.cache/
	rm -f coverage.xml
	@echo "✅ Done!"
