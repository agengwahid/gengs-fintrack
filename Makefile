.PHONY: help dev run test lint format build migrate fresh seed clean setup queue sync-sheets

# Target default jika hanya mengetik `make`
.DEFAULT_GOAL := help

help: ## Menampilkan daftar perintah yang tersedia
	@echo "\033[1;32mFinTrack Development Commands:\033[0m"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

dev: ## Menjalankan SEMUA service (Server, Vite, Queue Worker Google Sheets, Log)
	@composer run dev

run: dev ## Alias untuk `make dev`

setup: ## Setup awal proyek (install vendor, npm, .env, migrate, build)
	@test -f .env || cp .env.example .env
	@composer install
	@npm install
	@php artisan key:generate
	@php artisan migrate
	@php artisan db:seed --class=CategorySeeder --force
	@npm run build
	@echo "\033[1;32mSetup FinTrack selesai! Jalankan 'make dev' untuk memulai.\033[0m"

test: ## Menjalankan seluruh pengujian otomatis dengan Pest
	@./vendor/bin/pest

lint: ## Memformat kode PHP dengan Laravel Pint
	@vendor/bin/pint --format agent

format: lint ## Alias untuk `make lint`

build: ## Mengompilasi aset frontend untuk produksi (Vite)
	@npm run build

migrate: ## Menjalankan migrasi basis data PostgreSQL
	@php artisan migrate

fresh: ## Reset ulang basis data dan isi kategori bawaan
	@php artisan migrate:fresh --seed

queue: ## Menjalankan worker antrean latar belakang saja
	@php artisan queue:work

sync-sheets: ## Memicu sinkronisasi manual seluruh data ke Google Sheets
	@php artisan fintrack:sync-sheets

clean: ## Membersihkan seluruh cache Laravel (config, route, view)
	@php artisan optimize:clear

