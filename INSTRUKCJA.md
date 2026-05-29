# Instrukcja uruchomienia - NajmujMieszkanie

Niniejszy dokument opisuje dwa sposoby uruchomienia aplikacji:

- **Tryb deweloperski** - uruchamianie lokalne bez Dockera, do pracy nad kodem
- **Tryb Kubernetes (Minikube)** - uruchamianie w lokalnym klastrze, odwzorowujace srodowisko produkcyjne

---

## Spis tresci

1. [Wymagania](#wymagania)
2. [Tryb deweloperski (lokalnie)](#tryb-deweloperski-lokalnie)
   - [Konfiguracja backendu](#1-konfiguracja-backendu)
   - [Konfiguracja frontendu](#2-konfiguracja-frontendu)
   - [Uruchomienie](#3-uruchomienie)
3. [Tryb Kubernetes - Minikube](#tryb-kubernetes---minikube)
   - [Wymagania Kubernetes](#wymagania-kubernetes)
   - [Krok 1 - Uruchomienie Minikube](#krok-1---uruchomienie-minikube)
   - [Krok 2 - Konfiguracja Docker](#krok-2---konfiguracja-docker)
   - [Krok 3 - Budowanie obrazow Docker](#krok-3---budowanie-obrazow-docker)
   - [Krok 4 - Wdrozenie manifestow](#krok-4---wdrozenie-manifestow)
   - [Krok 5 - Konfiguracja pliku hosts](#krok-5---konfiguracja-pliku-hosts)
   - [Krok 6 - Sprawdzenie stanu klastra](#krok-6---sprawdzenie-stanu-klastra)
   - [Krok 7 - Dostep do aplikacji](#krok-7---dostep-do-aplikacji)
4. [Konta domyslne](#konta-domyslne)
5. [Struktura plikow Kubernetes](#struktura-plikow-kubernetes)
6. [Przydatne komendy kubectl](#przydatne-komendy-kubectl)
7. [Rozwiazywanie problemow](#rozwiazywanie-problemow)
8. [Zatrzymanie i czyszczenie](#zatrzymanie-i-czyszczenie)

---

## Wymagania

### Tryb deweloperski

| Narzedzie | Minimalna wersja | Sprawdzenie |
|-----------|-----------------|-------------|
| PHP | 8.3+ | `php -v` |
| Composer | 2.x | `composer --version` |
| Node.js | 18+ (zalecane 22) | `node -v` |
| npm | 9+ | `npm -v` |
| MySQL | 8.0+ | `mysql --version` |

> Jesli masz zainstalowane nvm, mozesz przelaczac wersje Node.js komenda `nvm use 22`.

### Tryb Kubernetes

| Narzedzie | Minimalna wersja | Sprawdzenie |
|-----------|-----------------|-------------|
| Docker Desktop / Docker Engine | 24+ | `docker --version` |
| Minikube | 1.32+ | `minikube version` |
| kubectl | kompatybilny z klastrem | `kubectl version --client` |
| Minimum 4 GB RAM dostepne dla Minikube | - | - |
| Minimum 2 CPU dostepne dla Minikube | - | - |

---

## Tryb deweloperski (lokalnie)

### 1. Konfiguracja backendu

Przejdz do katalogu `backend/`:

```bash
cd ProjektChmura/backend
```

Zainstaluj zaleznosci PHP:

```bash
composer install
```

Skopiuj plik konfiguracyjny:

```bash
cp .env.example .env
```

Otworz plik `.env` i uzupelnij dane bazy danych:

```env
APP_NAME=NajmujMieszkanie
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=najmuj_mieszkanie
DB_USERNAME=root
DB_PASSWORD=          # <- wpisz swoje haslo do MySQL

FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

Wygeneruj klucz aplikacji:

```bash
php artisan key:generate
```

Utworz baze danych w MySQL (jesli nie istnieje):

```sql
CREATE DATABASE najmuj_mieszkanie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Uruchom migracje i seedery:

```bash
php artisan migrate --seed
```

Uruchom serwer deweloperski:

```bash
php artisan serve --port=8000
```

Backend bedzie dostepny pod adresem: `http://localhost:8000`

---

### 2. Konfiguracja frontendu

W nowym oknie terminala przejdz do katalogu `frontend/`:

```bash
cd ProjektChmura/frontend
```

Jesli uzywasz nvm, przelacz na Node.js 22:

```bash
nvm use 22
```

Zainstaluj zaleznosci:

```bash
npm install
```

Utworz plik `.env` w katalogu `frontend/`:

```env
VITE_API_URL=http://localhost:8000/api/v1
VITE_APP_NAME=NajmujMieszkanie
VITE_APP_URL=http://localhost:5173
```

---

### 3. Uruchomienie

Uruchom serwer deweloperski Vite:

```bash
npm run dev
```

Frontend bedzie dostepny pod adresem: `http://localhost:5173`

> Upewnij sie, ze backend (`php artisan serve`) i frontend (`npm run dev`) dzialaja jednoczesnie w oddzielnych oknach terminala.

---

## Tryb Kubernetes - Minikube

Aplikacja jest skonteneryzowana i gotowa do uruchomienia w lokalnym klastrze Kubernetes przy uzyciu Minikube. Wszystkie pliki manifestow znajduja sie w katalogu `k8s/`.

> **WAZNE (Windows + Docker driver):** Po wdrozeniu nalezy uruchomic port-forward w oknie PowerShell jako Administrator:
> ```powershell
> kubectl port-forward -n ingress-nginx svc/ingress-nginx-controller 80:80
> ```
> Oraz dodac do `C:\Windows\System32\drivers\etc\hosts`:
> ```
> 127.0.0.1  najmuj.local
> ```

### Architektura klastra

```
                    http://najmuj.local
                           |
              +------------+------------+
              |    Ingress Controller   |
              |        (nginx)          |
              +------------+------------+
                           |
          +----------------+------------------+
          |                                   |
  /api/*  /placeholders/*                    /
  /storage/*                                 |
          |                                   |
  +-------+--------+             +-----------+--------+
  | backend-service|             | frontend-service   |
  | ClusterIP :80  |             | ClusterIP :80      |
  +-------+--------+             +-----------+--------+
          |                                   |
  +-------+--------+             +-----------+--------+
  |  backend Pod   |             |  frontend Pod      |
  |  Laravel 11    |             |  Vue 3 + Nginx     |
  |  PHP 8.3-Apache|             |  (pliki statyczne) |
  +-------+--------+             +--------------------+
          |
  +-------+--------+
  | mysql-service  |
  | ClusterIP :3306|
  +-------+--------+
          |
  +-------+--------+
  |   mysql Pod    |
  |   MySQL 8.0    |
  |   PVC: 2 Gi    |
  +----------------+
```

Namespace: `najmuj-mieszkanie`

---

### Krok 1 - Uruchomienie Minikube

Uruchom lokalny klaster z wystarczajacymi zasobami:

```bash
minikube start --cpus=2 --memory=4096 --driver=docker
```

Wlacz addon Ingress Controller (nginx):

```bash
minikube addons enable ingress
```

Poczekaj az Ingress Controller bedzie gotowy:

```bash
kubectl wait --namespace ingress-nginx \
  --for=condition=ready pod \
  --selector=app.kubernetes.io/component=controller \
  --timeout=120s
```

Sprawdz, ze klaster dziala poprawnie:

```bash
minikube status
kubectl get nodes
```

---

### Krok 2 - Konfiguracja Docker

Aby obrazy Docker budowane lokalnie byly dostepne wewnatrz Minikube (bez potrzeby wypychania do registry), skieruj Docker CLI na demon Dockera wewnatrz Minikube.

**Windows (PowerShell):**

```powershell
& minikube -p minikube docker-env --shell powershell | Invoke-Expression
```

**Linux / macOS (bash):**

```bash
eval $(minikube docker-env)
```

> **Wazne:** to ustawienie obowiazuje tylko w biezacej sesji terminala. Po otwarciu nowego okna nalezy je powtorzyc przed budowaniem obrazow.

Weryfikacja - w liscie obrazow powinny byc widoczne obrazy systemowe Minikube:

```bash
docker images | head -20
```

---

### Krok 3 - Budowanie obrazow Docker

Wykonaj ponizsze komendy z katalogu glownego projektu (`ProjektChmura/`):

**Backend (Laravel + PHP 8.3 + Apache):**

```bash
docker build -t najmuj-backend:latest ./backend
```

**Frontend (Vue 3 zbudowany statycznie, serwowany przez Nginx):**

```bash
docker build \
  --build-arg VITE_API_URL=http://najmuj.local/api/v1 \
  --build-arg VITE_APP_NAME=NajmujMieszkanie \
  -t najmuj-frontend:latest \
  ./frontend
```

> Adres API (`VITE_API_URL`) jest wbudowany w pliki statyczne na etapie budowania. Jesli zmienisz adres hosta, musisz przebudowac obraz frontendu.

Sprawdz, ze obrazy zostaly zbudowane:

```bash
docker images | grep najmuj
```

Oczekiwany wynik:

```
najmuj-frontend   latest   ...
najmuj-backend    latest   ...
```

---

### Krok 4 - Wdrozenie manifestow

Zastosuj pliki manifestow w odpowiedniej kolejnosci:

```bash
# 1. Namespace - musi byc pierwszy
kubectl apply -f k8s/namespace.yaml

# 2. Sekrety i konfiguracja
kubectl apply -f k8s/secrets.yaml
kubectl apply -f k8s/configmap.yaml

# 3. Baza danych (PVC + StatefulSet + Service)
kubectl apply -f k8s/mysql/

# 4. Backend (Deployment + Service)
kubectl apply -f k8s/backend/

# 5. Frontend (Deployment + Service)
kubectl apply -f k8s/frontend/

# 6. Ingress (routing HTTP)
kubectl apply -f k8s/ingress.yaml
```

Lub wszystko jednym poleceniem (namespace i kolejnosc zostana zachowane przez Kubernetes):

```bash
kubectl apply -f k8s/namespace.yaml && \
kubectl apply -f k8s/secrets.yaml && \
kubectl apply -f k8s/configmap.yaml && \
kubectl apply -f k8s/mysql/ && \
kubectl apply -f k8s/backend/ && \
kubectl apply -f k8s/frontend/ && \
kubectl apply -f k8s/ingress.yaml
```

Sledz uruchamianie podow (Ctrl+C aby wyjsc):

```bash
kubectl get pods -n najmuj-mieszkanie -w
```

Wszystkie pody powinny osiagnac stan `Running`:

```
NAME                        READY   STATUS    RESTARTS   AGE
mysql-0                     1/1     Running   0          2m
backend-xxxxxxxxx-xxxxx     1/1     Running   0          90s
frontend-xxxxxxxxx-xxxxx    1/1     Running   0          90s
```

> **Uwaga:** Backend przy pierwszym uruchomieniu czeka na MySQL (do 2 minut) i uruchamia migracje oraz seedery. Moze byc chwilowo w stanie `Init` lub `0/1 Running` - to normalne zachowanie.

---

### Krok 5 - Konfiguracja pliku hosts

Sprawdz adres IP klastra Minikube:

```bash
minikube ip
```

Przykladowy wynik: `192.168.49.2`

Dodaj wpis do pliku hosts systemu operacyjnego:

**Windows** - otworz Notatnik jako Administrator, nastepnie otworz plik:
```
C:\Windows\System32\drivers\etc\hosts
```

**Linux / macOS:**
```bash
sudo nano /etc/hosts
```

Dodaj na koncu pliku (zastap `192.168.49.2` rzeczywistym IP z `minikube ip`):

```
192.168.49.2  najmuj.local
```

Zapisz plik. Na Windows moze byc wymagane potwierdzenie uprawnien administratora.

---

### Krok 6 - Sprawdzenie stanu klastra

```bash
# Wszystkie zasoby w namespace
kubectl get all -n najmuj-mieszkanie

# Ingress - sprawdz czy ma przypisany adres
kubectl get ingress -n najmuj-mieszkanie

# Persistent Volume Claims - sprawdz czy sa w stanie Bound
kubectl get pvc -n najmuj-mieszkanie
```

Oczekiwany wynik dla Ingress:

```
NAME               CLASS   HOSTS          ADDRESS        PORTS
backend-ingress    nginx   najmuj.local   192.168.49.2   80
frontend-ingress   nginx   najmuj.local   192.168.49.2   80
```

---

### Krok 7 - Dostep do aplikacji

Otworz przegladarke i przejdz pod adres:

```
http://najmuj.local
```

Aplikacja powinna byc w pelni funkcjonalna. Dane testowe (50 ofert, 3 konta uzytkownikow) sa ladowane automatycznie przez seeder przy pierwszym uruchomieniu backendu.

---

## Konta domyslne

Po uruchomieniu (w obu trybach) dostepne sa nastepujace konta:

| Rola | Nazwa | E-mail | Haslo | Panel |
|------|-------|--------|-------|-------|
| Administrator | Administrator | admin@example.com | password | `/admin/dashboard` |
| Wlasciciel | Jan Wlasciciel | owner@example.com | password | `/wlasciciel/dashboard` |
| Uzytkownik | Anna Kowalska | user@example.com | password | `/panel/dashboard` |

> Po zalogowaniu aplikacja automatycznie przekierowuje do odpowiedniego panelu na podstawie roli.

---

## Struktura plikow Kubernetes

```
ProjektChmura/
├── k8s/
│   ├── namespace.yaml          # Namespace: najmuj-mieszkanie
│   ├── secrets.yaml            # APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD
│   ├── configmap.yaml          # Zmienne srodowiskowe aplikacji
│   ├── ingress.yaml            # Routing HTTP (dwa obiekty Ingress)
│   ├── mysql/
│   │   ├── pvc.yaml            # PersistentVolumeClaim 2 Gi
│   │   ├── statefulset.yaml    # MySQL 8.0 StatefulSet
│   │   └── service.yaml        # ClusterIP :3306
│   ├── backend/
│   │   ├── deployment.yaml     # Laravel/Apache Deployment
│   │   └── service.yaml        # ClusterIP :80
│   └── frontend/
│       ├── deployment.yaml     # Vue 3/Nginx Deployment
│       └── service.yaml        # ClusterIP :80
├── backend/
│   ├── Dockerfile              # Obraz backendu (PHP 8.3-Apache)
│   ├── docker-vhost.conf       # Konfiguracja Apache VirtualHost
│   ├── docker-entrypoint.sh    # Skrypt startowy (migrate, seed)
│   └── .dockerignore
└── frontend/
    ├── Dockerfile              # Obraz frontendu (node:22 -> nginx)
    ├── nginx.conf              # Konfiguracja Nginx (SPA routing)
    └── .dockerignore
```

---

## Przydatne komendy kubectl

### Podglad logow

```bash
# Logi backendu (Laravel / Apache)
kubectl logs -f deployment/backend -n najmuj-mieszkanie

# Logi frontendu (Nginx)
kubectl logs -f deployment/frontend -n najmuj-mieszkanie

# Logi bazy danych (MySQL)
kubectl logs -f statefulset/mysql -n najmuj-mieszkanie
```

### Dostep do shella kontenera

```bash
# Shell do backendu (Laravel artisan, logi itp.)
kubectl exec -it deployment/backend -n najmuj-mieszkanie -- bash

# Shell do MySQL
kubectl exec -it statefulset/mysql -n najmuj-mieszkanie -- \
  mysql -u najmuj_user -pNajmujPass123! najmuj_mieszkanie
```

### Restart podow

```bash
kubectl rollout restart deployment/backend -n najmuj-mieszkanie
kubectl rollout restart deployment/frontend -n najmuj-mieszkanie
```

### Status wdrozenia

```bash
kubectl rollout status deployment/backend -n najmuj-mieszkanie
kubectl rollout status deployment/frontend -n najmuj-mieszkanie
```

### Aktualizacja po zmianie kodu

```bash
# Ustaw docker-env (PowerShell)
& minikube -p minikube docker-env --shell powershell | Invoke-Expression

# Przebuduj i wdróz backend
docker build -t najmuj-backend:latest ./backend
kubectl rollout restart deployment/backend -n najmuj-mieszkanie

# Przebuduj i wdróz frontend
docker build --build-arg VITE_API_URL=http://najmuj.local/api/v1 -t najmuj-frontend:latest ./frontend
kubectl rollout restart deployment/frontend -n najmuj-mieszkanie
```

---

## Rozwiazywanie problemow

### Pod w stanie `CrashLoopBackOff`

```bash
# Sprawdz logi biezace
kubectl logs <nazwa-poda> -n najmuj-mieszkanie

# Sprawdz logi poprzedniego uruchomienia
kubectl logs <nazwa-poda> -n najmuj-mieszkanie --previous

# Szczegoly poda (eventy, konfiguracja)
kubectl describe pod <nazwa-poda> -n najmuj-mieszkanie
```

Najczestsze przyczyny:
- Backend nie moze polaczyc sie z MySQL - sprawdz czy pod MySQL jest w stanie `Running`
- Bledna zmienna srodowiskowa - sprawdz `kubectl describe pod`
- Blad w migracjach lub seederach - sprawdz logi backendu

---

### Backend nie startuje - MySQL nie gotowy

Backend automatycznie czeka na MySQL (do 60 prob co 2 sekundy = ok. 2 minuty). Jesli MySQL nie bedzie gotowy w tym czasie, backend zakonczy dzialanie z bledem i Kubernetes go zrestartuje.

Sprawdz stan MySQL:

```bash
kubectl get pods -n najmuj-mieszkanie -l app=mysql
kubectl describe pod -l app=mysql -n najmuj-mieszkanie
```

---

### Blad `ImagePullBackOff` lub `ErrImageNeverPull`

Obrazy nie zostaly znalezione w Minikube. Najczesciej przyczyna jest pominiety krok konfiguracji docker-env.

```bash
# Sprawdz dostepne obrazy w Minikube
minikube image ls | grep najmuj
```

Jesli lista jest pusta - ustaw docker-env i przebuduj:

```bash
# PowerShell
& minikube -p minikube docker-env --shell powershell | Invoke-Expression

# Przebuduj
docker build -t najmuj-backend:latest ./backend
docker build --build-arg VITE_API_URL=http://najmuj.local/api/v1 -t najmuj-frontend:latest ./frontend
```

---

### Strona niedostepna mimo ze pody dzialaja

1. Sprawdz, ze Ingress ma przypisany adres IP:
   ```bash
   kubectl get ingress -n najmuj-mieszkanie
   ```

2. Sprawdz, ze plik hosts zawiera poprawny wpis:
   ```bash
   # Windows PowerShell
   Get-Content C:\Windows\System32\drivers\etc\hosts | Select-String "najmuj"

   # Linux / macOS
   grep najmuj /etc/hosts
   ```

3. Sprawdz aktualne IP Minikube - moze sie zmienic po restarcie:
   ```bash
   minikube ip
   ```

4. Sprawdz, ze Ingress Controller dziala:
   ```bash
   kubectl get pods -n ingress-nginx
   minikube addons list | grep ingress
   ```

---

### Ingress nie ma adresu IP (kolumna ADDRESS pusta)

```bash
# Poczekaj kilka minut i sprawdz ponownie
kubectl get ingress -n najmuj-mieszkanie

# Jesli problem trwa - sprawdz addon
minikube addons enable ingress
kubectl get pods -n ingress-nginx
```

---

### Blad CORS lub 401 na froncie

Sprawdz, ze frontend zostal zbudowany z poprawnym adresem API:

```bash
# Zbuduj ponownie z poprawnym VITE_API_URL
docker build \
  --build-arg VITE_API_URL=http://najmuj.local/api/v1 \
  -t najmuj-frontend:latest \
  ./frontend
kubectl rollout restart deployment/frontend -n najmuj-mieszkanie
```

---

---

## Codzienne uruchomienie Minikube (po konfiguracji)

1. Uruchom **Docker Desktop** i poczekaj az wieloryb w pasku zadan przestanie sie krecic
2. W PowerShell:
```powershell
minikube start
```
3. W PowerShell jako **Administrator**:
```powershell
kubectl port-forward -n ingress-nginx svc/ingress-nginx-controller 80:80
```
4. Otworz przegladarke: http://najmuj.local

---

## Przebudowa po zmianie kodu

### Backend (Laravel):
```powershell
cd "B:\Chmura projekt zaliczeniowy\ProjektZaliczeniowy"
& minikube -p minikube docker-env --shell powershell | Invoke-Expression
docker build -t najmuj-backend:latest ./backend
kubectl rollout restart deployment/backend -n najmuj-mieszkanie
kubectl rollout status deployment/backend -n najmuj-mieszkanie
```

### Frontend (Vue):
```powershell
cd "B:\Chmura projekt zaliczeniowy\ProjektZaliczeniowy"
& minikube -p minikube docker-env --shell powershell | Invoke-Expression
docker build --build-arg VITE_API_URL=http://najmuj.local/api/v1 --build-arg VITE_APP_NAME=NajmujMieszkanie -t najmuj-frontend:latest ./frontend
kubectl rollout restart deployment/frontend -n najmuj-mieszkanie
kubectl rollout status deployment/frontend -n najmuj-mieszkanie
```

---

## Zatrzymanie i czyszczenie

### Zatrzymanie trybu deweloperskiego

```bash
# Zatrzymaj serwer Laravel (Ctrl+C w terminalu z php artisan serve)
# Zatrzymaj Vite (Ctrl+C w terminalu z npm run dev)
```

### Zatrzymanie Minikube

```bash
# Zatrzymaj klaster (zachowuje dane)
minikube stop

# Uruchom ponownie
minikube start
```

### Usuniecie aplikacji z klastra (zachowuje Minikube)

```bash
kubectl delete namespace najmuj-mieszkanie
```

> Usuniecie namespace usuwa wszystkie zasoby lacznie z PVC i danymi bazy danych.

### Usuniecie calego klastra Minikube

```bash
minikube delete
```

> Usuwa caly klaster, wszystkie dane i konfiguracje. Nalezy ponownie wykonac wszystkie kroki konfiguracji.
