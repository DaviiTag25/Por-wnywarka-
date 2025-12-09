# Instrukcja Wdrożenia na home.pl z GitHub

## Sposób 1: Bezpośrednie kopiowanie plików

1. **Pobierz repozytorium:**
   ```bash
   git clone https://github.com/twoja-nazwa/comperia.git
   ```

2. **Prześlij pliki na serwer FTP:**
   - Połącz się z serwerem home.pl przez FTP
   - Prześlij wszystkie pliki do katalogu `/public_html/`
   - Ustaw uprawnienia: katalogi 755, pliki 644

3. **Uruchom instalator:**
   - Otwórz w przeglądarce: `https://twoja-domena.pl/install.php`
   - Wypełnij formularz z danymi bazy i API

4. **Zakończ instalację:**
   - Skopiuj `includes/config.example.php` na `includes/config.local.php`
   - Ustaw dane w `config.local.php`
   - Usuń plik `install.php`

## Sposób 2: Użycie GitHub Actions (automatyczne wdrożenie)

1. **Skonfiguruj serwer home.pl:**
   - Włącz dostęp SSH w panelu home.pl
   - Wygeneruj klucze SSH

2. **Dodaj secrets w GitHub:**
   - `HOST`: adres serwera FTP
   - `USERNAME`: użytkownik FTP
   - `PASSWORD`: hasło FTP
   - `REMOTE_PATH`: `/public_html/`

3. **Utwórz plik `.github/workflows/deploy.yml`:**
   ```yaml
   name: Deploy to home.pl
   on:
     push:
       branches: [ main ]
   jobs:
     deploy:
       runs-on: ubuntu-latest
       steps:
       - uses: actions/checkout@v2
       - name: Deploy to home.pl
         uses: SamKirkland/FTP-Deploy-Action@4.3.0
         with:
           server: ${{ secrets.HOST }}
           username: ${{ secrets.USERNAME }}
           password: ${{ secrets.PASSWORD }}
           local-dir: ./
           server-dir: ${{ secrets.REMOTE_PATH }}
   ```

## Sposób 3: Użycie GitHub Pages (dla testów)

1. **Włącz GitHub Pages** w ustawieniach repozytorium
2. **Wybierz gałąź `main`** jako źródło
3. **Dostosuj ścieżki** w konfiguracji dla GitHub Pages

## Konfiguracja po wdrożeniu

1. **Ustaw domyślną stronę** w panelu home.pl na `index.php`
2. **Włącz HTTPS** dla domeny
3. **Skonfiguruj bazę danych** w panelu home.pl
4. **Ustaw klucz API Comperia** w `config.local.php`

## Testowanie

1. **Sprawdź instalator:** `https://twoja-domena.pl/install.php`
2. **Zaloguj się:** `https://twoja-domena.pl/login.php`
3. **Przetestuj API:** Sprawdź listę produktów w panelu

## Rozwiązywanie problemów

- **Błąd 500:** Sprawdź uprawnienia plików (644) i katalogów (755)
- **Błąd bazy danych:** Sprawdź dane w `config.local.php`
- **Brak API:** Sprawdź klucz API Comperia
- **Błędy PHP:** Włącz wyświetlanie błędów w `config.local.php`

## Aktualizacje

Aby zaktualizować aplikację:
```bash
git pull origin main
# lub pobierz ponownie pliki i wyślij na FTP
```

## Bezpieczeństwo

- Zawsze usuwaj `install.php` po instalacji
- Ustaw silne hasła do bazy danych
- Zmień domyślne hasło administratora
- Regularnie aktualizuj aplikację
