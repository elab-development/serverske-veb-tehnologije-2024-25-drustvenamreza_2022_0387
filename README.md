
# E-Twitter
E-Twitter je jednostavna Laravel aplikacija koja modeluje osnovne funkcionalnosti društvene mreže nalik Twitter-u.

## Tehnologije i pokretanje projekta:
#### Laravel Backend
1. Instaliranje [VS Code](https://code.visualstudio.com/download)  
3. Instaliranje [PHP](https://www.php.net/downloads) (>= 8.1)
4. Instaliranje [Composer](https://getcomposer.org/download/)
5. git clone https://github.com/elab-development/serverske-veb-tehnologije-2024-25-drustvenamreza_2022_0387.git
6. Instaliranje zavisnosti u root folderu Laravel projekta: composer install
7. Kopiranje šablonskog u stvarni .env fajl: cp .env.example .env
8. Generisanje aplikacionog ključa: php artisan key:generate
9. Pokretanje migracija i seed podataka: php artisan migrate --seed
10. Startovanje lokalnog servera: php artisan serve

# Funkcionalnosti
Sistem koristi četiri ključna modela: User, Post, Comment i Follow. 
- **Korisnici** (User) imaju ulogu user ili admin i opciono kratak bio.
- **Objave** (Post) predstavljaju kratke tekstualne poruke do 280 karaktera.
- **Komentari** (Comment) omogućavaju diskusiju ispod objava.
- **Follow** uvodi relaciju praćenja između korisnika (follower_id → following_id).
Ovako svedena šema daje minimalni, ali potpun okvir za news-feed i interakcije.

# Autorizacija i pravila pristupa
Administratori ne kreiraju postove, ne komentarišu i ne prate druge naloge (u skladu sa seederom i kontrolerima), dok regularni korisnici mogu da kreiraju, ažuriraju i brišu sopstvene postove i komentare, te da prate/otpraćuju druge korisnike. Pregled postova (index) je kontekstualan: admin vidi sve objave (uz opcione filtere), a običan korisnik vidi samo objave naloga koje prati (uz filter po autoru i kontrolisani sort). Pregled komentara zahteva post_id, uz mogućnost pretrage po sadržaju komentara, imenu ili e-pošti autora.

# API
- **JSON REST API** sa Resource klasama: UserResource, PostResource, CommentResource, FollowResource.
- **Validacija ulaza** i jasne poruke o greškama:
  - 401 Unauthorized
  - 403 Forbidden
  - 404 Not Found
  - 422 Validation Error
- **Testiranje API-ja**:
  - Preporučeno korišćenje [Postman](https://www.postman.com/) alata
  - Sve rute su dostupne nakon pokretanja lokalnog servera (php artisan serve) na adresi: http://127.0.0.1:8000/api

# Performanse i UX 
Implementirano je keširanje rezultata index akcije nad postovima (po korisniku i parametrima upita), sa kontrolisanom dužinom trajanja i jednostavnim bustovanjem keša pri svakoj promeni koja utiče na feed (kreiranje/izmena/brisanje posta ili komentara, kao i follow/unfollow). Ovo omogućava brze odgovore i smanjuje opterećenje baze podataka, a zadržava konzistentnost podataka.
Razvoj i testiranje olakšani su pomoću factory klasa i seedera: generiše se admin nalog i veći broj regularnih korisnika, zatim se kreiraju postovi i komentari, a follow mreža se puni nasumično, pritom striktno poštujući jedinstvenost parova i zabranu samopraćenja. Seederi su usklađeni sa poslovnim pravilima (admini nemaju postove/komentare/follow veze), čime se već u startu dobija dataset veran realnoj upotrebi.
Aplikacija je spremna za proširenja bez promene osnovnog modela: lako se mogu dodati “lajkovi” (Like) i notifikacije, pretraga i filtriranje po naprednijim kriterijumima, kao i integracije za admin analitiku (npr. Guardian Content API za agregaciju popularnih tagova u vestima). Zadržavanjem minimalnog skupa modela i jasne role-based kontrole, rešenje ostaje pregledno, održivo i pogodno za dalji razvoj ili prikaz u seminarskom radu.

## Autori
- [Tara Marković](https://github.com/TaraMarkovic)
- [Nađa Mladenović](https://github.com/nadjamladenovic)
- [Tijana Šikanja](https://github.com/TijanaSikanja)
