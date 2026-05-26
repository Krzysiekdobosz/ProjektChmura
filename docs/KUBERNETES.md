# Uruchomienie w Kubernetes (Minikube)

## Wymagania

- Minikube (min. v1.32)
- kubectl (kompatybilny z wersja klastra)
- Docker Desktop lub Docker Engine
- Minimum 4 GB RAM i 2 CPU dostepne dla Minikube

## Architektura

Aplikacja sklada sie z trzech podow dzialajacych w namespace `najmuj-mieszkanie`:

| Pod | Obraz | Opis |
|-----|-------|------|
| mysql | mysql:8.0 | Baza danych - StatefulSet z PVC (2 Gi) |
| backend | najmuj-backend:latest | Laravel 11 / PHP 8.3 + Apache |
| frontend | najmuj-frontend:latest | Vue 3 zbudowany statycznie, serwowany przez Nginx |

Ruch HTTP wchodzi przez Ingress Controller (nginx) na hosta `najmuj.local`:

```
                        +------------------+
                        |  najmuj.local    |
                        |  Ingress (nginx) |
                        +--------+---------+
                                 |
              +------------------+------------------+
              |                                     |
     /api/*  /placeholders/*                       /
     /storage/*                                    |
              |                                     |
    +---------+----------+             +-----------+---------+
    |  backend-service   |             |  frontend-service   |
    |  (ClusterIP :80)   |             |  (ClusterIP :80)    |
    +---------+----------+             +-----------+---------+
              |                                     |
    +---------+----------+             +-----------+---------+
    |  backend Pod       |             |  frontend Pod       |
    |  Laravel + Apache  |             |  Vue 3 + Nginx      |
    +--------------------+             +---------------------+
              |
    +---------+----------+
    |  mysql-service     |
    |  (ClusterIP :3306) |
    +---------+----------+
              |
    +---------+----------+
    |  mysql Pod         |
    |  MySQL 8.0         |
    |  PVC: mysql-pvc    |
    +--------------------+
```

---

## Krok po kroku

### 1. Uruchomienie Minikube

```bash
minikube start --cpus=2 --memory=4096 --driver=docker
minikube addons enable ingress
```

Poczekaj az addon ingress bedzie gotowy (moze zajac kilka minut):

```bash
kubectl wait --namespace ingress-nginx \
  --for=condition=ready pod \
  --selector=app.kubernetes.io/component=controller \
  --timeout=120s
```

### 2. Konfiguracja Docker do Minikube

Aby obrazy budowane lokalnie byly dostepne wewnatrz Minikube, ustaw kontekst Docker:

```bash
# Windows PowerShell:
& minikube -p minikube docker-env --shell powershell | Invoke-Expression

# Linux / Mac (bash):
eval $(minikube docker-env)
```

> Uwaga: to ustawienie dziala tylko w biezacej sesji terminala. Nalezy je powtorzyc po otwarciu nowego okna.

### 3. Budowanie obrazow Docker

Wykonaj ponizsze komendy z katalogu `ProjektChmura/`:

```bash
docker build -t najmuj-backend:latest ./backend

docker build \
  --build-arg VITE_API_URL=http://najmuj.local/api/v1 \
  --build-arg VITE_APP_NAME=NajmujMieszkanie \
  -t najmuj-frontend:latest \
  ./frontend
```

### 4. Wdrozenie na Kubernetes

```bash
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/secrets.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/mysql/
kubectl apply -f k8s/backend/
kubectl apply -f k8s/frontend/
kubectl apply -f k8s/ingress.yaml
```

Poczekaj az wszystkie pody beda gotowe:

```bash
kubectl get pods -n najmuj-mieszkanie -w
```

### 5. Konfiguracja pliku hosts

Sprawdz IP Minikube:

```bash
minikube ip
```

Nastepnie dodaj wpis do pliku hosts:

- **Linux / Mac** - edytuj `/etc/hosts`
- **Windows** - edytuj `C:\Windows\System32\drivers\etc\hosts` (jako Administrator)

Dodaj linie (zastap `<minikube-ip>` rzeczywistym IP):

```
<minikube-ip>  najmuj.local
```

Przyklad:

```
192.168.49.2  najmuj.local
```

### 6. Sprawdzenie stanu

```bash
kubectl get all -n najmuj-mieszkanie
kubectl get ingress -n najmuj-mieszkanie
kubectl get pvc -n najmuj-mieszkanie
```

Oczekiwany wynik - wszystkie pody w stanie `Running`, PVC w stanie `Bound`.

### 7. Dostep do aplikacji

Otworz przegladarke i przejdz pod adres:

```
http://najmuj.local
```

---

## Konta testowe

| Rola | E-mail | Haslo | Panel |
|------|--------|-------|-------|
| Administrator | admin@example.com | password | /admin/dashboard |
| Wlasciciel | owner@example.com | password | /wlasciciel/dashboard |
| Uzytkownik | user@example.com | password | /panel/dashboard |

> Konta sa tworzone przez seeder uruchamiany automatycznie przy pierwszym starcie backendu (zmienna `RUN_SEEDER=true` w `k8s/configmap.yaml`).

---

## Przydatne komendy

### Podglad logow

```bash
# Logi backendu (Laravel)
kubectl logs -f deployment/backend -n najmuj-mieszkanie

# Logi frontendu (Nginx)
kubectl logs -f deployment/frontend -n najmuj-mieszkanie

# Logi MySQL
kubectl logs -f statefulset/mysql -n najmuj-mieszkanie
```

### Dostep do shella poda

```bash
# Shell do backendu
kubectl exec -it deployment/backend -n najmuj-mieszkanie -- bash

# Shell do MySQL
kubectl exec -it statefulset/mysql -n najmuj-mieszkanie -- mysql -u najmuj_user -p najmuj_mieszkanie
```

### Restart deploymentow

```bash
kubectl rollout restart deployment/backend -n najmuj-mieszkanie
kubectl rollout restart deployment/frontend -n najmuj-mieszkanie
```

### Usuniecie calej aplikacji

```bash
kubectl delete namespace najmuj-mieszkanie
```

---

## Aktualizacja obrazu

Po wprowadzeniu zmian w kodzie, przebuduj obraz i zrestartuj deployment:

```bash
# Upewnij sie ze docker-env jest ustawiony
& minikube -p minikube docker-env --shell powershell | Invoke-Expression

# Przebuduj backend
docker build -t najmuj-backend:latest ./backend
kubectl rollout restart deployment/backend -n najmuj-mieszkanie

# Przebuduj frontend
docker build --build-arg VITE_API_URL=http://najmuj.local/api/v1 -t najmuj-frontend:latest ./frontend
kubectl rollout restart deployment/frontend -n najmuj-mieszkanie

# Sprawdz status wdrozenia
kubectl rollout status deployment/backend -n najmuj-mieszkanie
```

---

## Zmiana sekretow

### Zmiana APP_KEY

Wygeneruj nowy klucz aplikacji Laravel:

```bash
# Mozesz uzyc: php artisan key:generate --show
# lub wygenerowac recznie: base64 z 32 losowych bajtow
```

Edytuj plik `k8s/secrets.yaml` - zaktualizuj wartosc `APP_KEY`, a nastepnie:

```bash
kubectl apply -f k8s/secrets.yaml
kubectl rollout restart deployment/backend -n najmuj-mieszkanie
```

### Zmiana DB_PASSWORD

1. Edytuj `k8s/secrets.yaml` - zaktualizuj `DB_PASSWORD`
2. Zastosuj zmiany i zrestartuj pody:

```bash
kubectl apply -f k8s/secrets.yaml
kubectl rollout restart deployment/backend -n najmuj-mieszkanie
kubectl rollout restart statefulset/mysql -n najmuj-mieszkanie
```

> Uwaga: jesli baza danych juz istnieje (PVC nie byl usuniety), sama zmiana sekretu nie zmieni hasla w MySQL. Nalezy albo usunac PVC i pozwolic MySQL inicjalizowac sie od nowa, albo zmienic haslo recznie wewnatrz poda MySQL.

---

## Troubleshooting

### Pod w stanie `CrashLoopBackOff`

Sprawdz logi poda:

```bash
kubectl logs <nazwa-poda> -n najmuj-mieszkanie --previous
```

Najczestsze przyczyny:
- Bledna konfiguracja zmiennych srodowiskowych
- Brak polaczenia z MySQL (backend startuje przed MySQL)
- Blad w migracjach lub seederach

### MySQL nie gotowy - backend nie startuje

Backend automatycznie czeka na MySQL (do 60 prob co 2 sekundy). Jesli MySQL nie stanie sie dostepny w tym czasie, backend zakonczy dzialanie z bledem. Sprawdz stan MySQL:

```bash
kubectl get pods -n najmuj-mieszkanie
kubectl describe pod -l app=mysql -n najmuj-mieszkanie
```

### Ingress nie dziala

Sprawdz czy addon jest wlaczony i kontroler dziala:

```bash
minikube addons list | grep ingress
kubectl get pods -n ingress-nginx
```

Jesli addon nie jest wlaczony:

```bash
minikube addons enable ingress
```

### Obrazy nie znalezione (`ImagePullBackOff` lub `ErrImageNeverPull`)

Sprawdz czy srodowisko Docker jest ustawione na Minikube:

```bash
# Wylistuj obrazy dostepne w Minikube
minikube image ls | grep najmuj
```

Jesli obrazow nie ma - ustaw docker-env i przebuduj:

```bash
& minikube -p minikube docker-env --shell powershell | Invoke-Expression
docker build -t najmuj-backend:latest ./backend
docker build --build-arg VITE_API_URL=http://najmuj.local/api/v1 -t najmuj-frontend:latest ./frontend
```

### Strona niedostepna mimo ze pody dzialaja

1. Sprawdz czy plik hosts ma poprawny wpis z aktualnym IP Minikube (`minikube ip`)
2. Sprawdz czy Ingress ma przypisany adres: `kubectl get ingress -n najmuj-mieszkanie`
3. Sprawdz czy Ingress Controller dziala: `kubectl get pods -n ingress-nginx`
