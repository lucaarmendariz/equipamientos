# Ekipamenduen Kudeaketa Aplikazioa

Proiektu honek informatikako mintegi batean ekipamenduen kudeaketa egiteko web-aplikazio bat da. Ekipamenduak, periferikoak eta beste material batzuk kontrolatzen ditu, eta stocka eta erregistroak eguneratzen ditu. Sistema honek erabiltzailearen rolen kudeaketa (admin eta user) ere onartzen du, eta administratzaileek erabiltzaileak eta inbentarioa kudeatzeko baimenak dituzte. Horrez gain, aplikazioak MariaDB/MySQL datu-base bat eta PHP bidez garatutako API bat erabiltzen ditu.

## Deskribapena

Aplikazio honek mintegi batean informatikako ekipamenduen kudeaketa ahalbidetzen du, eta **altak, bajak eta aldaketak** egiteko aukera ematen du inbentarioan. Erabiltzaileek saioa hasi behar dute login bidez, eta administratzaileek bakarrik dituzte erabiltzaileak kudeatzeko baimenak.

Aplikazioak honako funtzionalitateak ditu:

- Ekipamenduak kudeatzea (ordenagailuak, portatiluak, periferikoak, eta abar)
- Stockaren eguneraketa eta inbentarioa kudeatzea
- Erabiltzaileen rolak kudeatzea (admin eta user)
- Inbentarioaren txostenak eta auditoriak

## Funtzionalitateak

### Backend

- **PHP** erabiltzen da bezeroak egindako eskaerei erantzuteko eta datu-basearekin komunikatzeko.
- **API Key** erabiltzen da erabiltzaileak saioa hasi ondoren, eskatutako eskaerak autentifikatzeko.
- **Datu-basea** MariaDB/MySQL erabiliz kudeatzen da eta honako taulak ditu: ekipamenduak, kategoriak, erabiltzaileak eta stocka.
- Backend-ak honako funtzionalitateak eskaintzen ditu:
  - Ekipamenduak
  - Kategoriak
  - Erabiltzaileak (rola: admin, user)
  - Stockaren kudeaketa (alta eta baja)
  - Ekipamenduen etiketen kudeaketa
  - Saioen kudeaketa `apiKey` bidez

### Frontend

- **HTML5 eta CSS3** erabiltzen dira web-orrien diseinua egiteko.
- **SASS** erabiltzen da estiloak antolatzeko.
- **JavaScript (Fetch eta JQuery)** interaktibitatea eta formularioen baliozkotzea egiteko.
- Formularioen baliozkotzea bezeroaren eta zerbitzariaren aldean egiten da.
- Interfazearen diseinua **Figma** erabiliz egin da, erabilgarritasun eta irisgarritasun arauak kontuan hartuta.

