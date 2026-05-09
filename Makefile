.PHONY: start stop reset shell migrate logs

## Запустить Zentra CMF локально (первый запуск)
start:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		sed -i.bak 's/^DB_HOST=.*/DB_HOST=database/'       .env && rm -f .env.bak; \
		sed -i.bak 's/^DB_DATABASE=.*/DB_DATABASE=zentra/' .env && rm -f .env.bak; \
		sed -i.bak 's/^DB_USERNAME=.*/DB_USERNAME=zentra/' .env && rm -f .env.bak; \
		sed -i.bak 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' .env && rm -f .env.bak; \
		echo "  .env создан из .env.example"; \
	fi
	docker compose up -d --build
	@echo "  Ожидание запуска контейнеров..."
	docker compose exec app composer install --no-interaction --prefer-dist
	@if grep -qE '^APP_KEY=$$' .env || grep -qE "^APP_KEY=''$$" .env; then \
		docker compose exec app php artisan key:generate; \
	fi
	@echo ""
	@echo "Zentra CMF запущен: http://localhost:8000"
	@echo ""
	@echo "Данные для шага 3 инсталлятора:"
	@echo "  Хост БД:  database"
	@echo "  База:     zentra"
	@echo "  Логин:    zentra"
	@echo "  Пароль:   secret"
	@echo ""

## Остановить контейнеры
stop:
	docker compose down

## Сбросить окружение (удалить контейнеры, тома, .env и маркер установки)
reset:
	docker compose down -v
	rm -f .env storage/.installed
	@echo "Окружение сброшено. Запустите 'make start' для новой установки."

## Открыть shell внутри PHP-контейнера
shell:
	docker compose exec app sh

## Выполнить миграции
migrate:
	docker compose exec app php artisan migrate --force

## Показать логи
logs:
	docker compose logs -f
