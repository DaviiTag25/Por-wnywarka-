# Comperia - Panel Administracyjny

Aplikacja do zarządzania produktami zintegrowana z API Comperia.

## Wymagania

- PHP 7.4 lub nowszy
- MySQL 5.7 lub nowszy
- Serwer Apache z modułem rewrite
- Rozszerzenia PHP: json, mbstring, openssl, curl

## Instalacja

### 1. Skopiuj pliki na serwer

Prześlij wszystkie pliki do katalogu głównego na serwerze home.pl.

### 2. Konfiguracja bazy danych

1. Zaloguj się do panelu home.pl
2. Utwórz nową bazę danych
3. Zaktualizuj dane w pliku `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nazwa_bazy');
define('DB_USER', 'nazwa_uzytkownika');
define('DB_PASS', 'haslo_bazy');
```

### 3. Konfiguracja API

W pliku `includes/config.php` ustaw klucz API Comperia:

```php
define('COMPERIA_API_KEY', 'twoj_klucz_api');
```

### 4. Ustawienia uprawnień

- Katalogi: 755
- Pliki: 644
- Plik `.htaccess`: 644

## Struktura katalogów

```
/
├── assets/
│   ├── css/
│   │   └── custom.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── includes/
│   ├── config.php
│   ├── ComperiaAPI.php
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── views/
│   ├── dashboard.php
│   ├── products.php
│   ├── product_edit.php
│   └── settings.php
├── index.php
├── login.php
├── logout.php
└── .htaccess
```

## Funkcjonalności

- Zarządzanie produktami
- Integracja z API Comperia
- Panel administracyjny
- System logowania
- Paginacja wyników
- Filtry i wyszukiwanie

## API Comperia

Aplikacja integruje się z API Comperia pod adresem:
- Bazowy URL: `https://www.autoplan24.pl/api/`
- Klucz API: skonfigurowany w `includes/config.php`

### Dostępne endpointy:

- `GET /products` - lista produktów
- `GET /products/{id}` - szczegóły produktu
- `PUT /products/{id}` - aktualizacja produktu
- `PUT /products/{id}/status` - zmiana statusu produktu
- `GET /categories` - lista kategorii

## Bezpieczeństwo

- Pliki konfiguracyjne chronione przez `.htaccess`
- Wymuszony HTTPS
- Zabezpieczenie przed SQL injection
- Filtracja danych wejściowych

## Wsparcie

W przypadku problemów z instalacją lub działaniem aplikacji, skontaktuj się z administratorem.

## Licencja

© 2024 Autoplan24. Wszelkie prawa zastrzeżone.
