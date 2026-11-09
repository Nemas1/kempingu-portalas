# Single-file Magic Systems Jython macro. All model data is embedded.
"""Vienintelis savaranki\u0161kas kemping\u0173 portalo UML k\u016brimo failas.
Magic Systems > Tools > Macros > Jython. Ankstesni\u0173 skript\u0173 ar JSON fail\u0173 nereikia."""
from __future__ import unicode_literals
import json
import traceback
import codecs
import os
from java.awt import Rectangle, Point
from java.io import File
from java.util import ArrayList
from javax.swing import JOptionPane
from com.nomagic.magicdraw.core import Application
from com.nomagic.magicdraw.openapi.uml import SessionManager, ModelElementsManager, PresentationElementsManager
from com.nomagic.magicdraw.uml import DiagramTypeConstants
from com.nomagic.magicdraw.export.image import ImageExporter
from com.nomagic.uml2.ext.jmi.helpers import CoreHelper
from java.awt import Color, Font
from com.nomagic.magicdraw.properties import PropertyManager, PropertyID
from com.nomagic.uml2.ext.magicdraw.classes.mdkernel import VisibilityKindEnum
from com.nomagic.uml2.ext.magicdraw.interactions.mdbasicinteractions import MessageSortEnum
from com.nomagic.uml2.ext.magicdraw.interactions.mdfragments import InteractionOperatorKindEnum
from com.nomagic.magicdraw.uml.symbols.shapes import SplitView
from java.lang import System
MODEL = json.loads('{"title": "Kemping\\u0173 portalas", "version": 3, "functions": [{"id": "F01", "name": "Registruotis", "role": "Sve\\u010dias", "input": "Vardas, pavard\\u0117, unikalus prisijungimo vardas, el. pa\\u0161tas, slapta\\u017eodis ir jo pakartojimas.", "output": "Sukuriama aktyvi kliento paskyra; parodomas registracijos patvirtinimas.", "screen": "E01"}, {"id": "F02", "name": "Prisijungti", "role": "Visos paskyr\\u0173 kategorijos", "input": "Prisijungimo vardas ir slapta\\u017eodis.", "output": "Sukuriama sesija ir atveriama rolei skirta aplinka; neteisingi duomenys arba blokuota paskyra atmetami.", "screen": "E01"}, {"id": "F03", "name": "Atsijungti", "role": "Visi prisijung\\u0119 vartotojai", "input": "Komanda \\u201eAtsijungti\\u201c.", "output": "Sesija panaikinama; atveriamas vie\\u0161as pradinis puslapis.", "screen": "E02\\u2013E10"}, {"id": "F04", "name": "Ie\\u0161koti kemping\\u0173", "role": "Sve\\u010dias ir prisijung\\u0119 vartotojai", "input": "Pavadinimo dalis arba miestas.", "output": "Aktyvi\\u0173 kemping\\u0173 s\\u0105ra\\u0161as su adresu ir vidutiniu \\u012fvertinimu; tu\\u0161\\u010dio s\\u0105ra\\u0161o prane\\u0161imas.", "screen": "E02"}, {"id": "F05", "name": "Per\\u017ei\\u016br\\u0117ti kemping\\u0105", "role": "Sve\\u010dias ir prisijung\\u0119 vartotojai", "input": "Pasirinktas kempingas.", "output": "Apra\\u0161ymas, kontaktai, aktyvios paslaugos, paros kainos, matomi atsiliepimai ir j\\u0173 reitingo vidurkis.", "screen": "E02"}, {"id": "F06", "name": "Tikrinti paslaug\\u0173 prieinamum\\u0105", "role": "Klientas", "input": "Kempingas, prad\\u017eios ir pabaigos datos, pageidaujami paslaug\\u0173 kiekiai.", "output": "Kiekvienos paslaugos laisvas kiekis visam laikotarpiui ir apskai\\u010diuota kaina; prieinamumas galutinai tikrinamas teikiant rezervacij\\u0105.", "screen": "E03"}, {"id": "F07", "name": "Pateikti rezervacij\\u0105", "role": "Klientas", "input": "Vienas kempingas, datos ir viena ar daugiau paslaug\\u0173 su kiekiais.", "output": "Sukuriama rezervacija laukiaPatvirtinimo, u\\u017efiksuojamos kainos, suteikiamas numeris ir 30 min. terminas; vykdomas F08.", "screen": "E03"}, {"id": "F08", "name": "Si\\u0173sti patvirtinimo lai\\u0161k\\u0105", "role": "Sistema ir el. pa\\u0161to serveris", "input": "Rezervacijos numeris, kliento el. pa\\u0161tas, vienkartin\\u0117 URL nuoroda ir galiojimo terminas.", "output": "Lai\\u0161kas perduodamas el. pa\\u0161to serveriui; siuntimo klaidos atveju rodoma pakartotinio siuntimo galimyb\\u0117. Serverio pri\\u0117mimas negarantuoja pristatymo \\u012f pa\\u0161to d\\u0117\\u017eut\\u0119.", "screen": "E03, E04"}, {"id": "F09", "name": "Patvirtinti rezervacij\\u0105 pagal URL", "role": "Klientas, turintis nuorod\\u0105", "input": "Lai\\u0161ke gautas \\u017eetonas ir paspaudimas \\u201ePatvirtinti rezervacij\\u0105\\u201c.", "output": "Galiojanti rezervacija tampa patvirtinta, \\u017eetonas pa\\u017eymimas panaudotu; negaliojanti nuoroda pateikia ai\\u0161k\\u0173 prane\\u0161im\\u0105.", "screen": "E05"}, {"id": "F10", "name": "Per\\u017ei\\u016br\\u0117ti savo rezervacijas", "role": "Klientas", "input": "Pasirinktas b\\u016bsenos filtras ir (arba) rezervacijos numeris.", "output": "Tik savo rezervacij\\u0173 s\\u0105ra\\u0161as, datos, paslaugos, sumos, b\\u016bsenos ir leid\\u017eiami veiksmai.", "screen": "E04"}, {"id": "F11", "name": "At\\u0161aukti rezervacij\\u0105", "role": "Klientas arba atitinkamo kempingo administratorius", "input": "Rezervacijos numeris, at\\u0161aukimo patvirtinimas; administratoriui \\u2013 prie\\u017eastis.", "output": "Dar neprasid\\u0117jusi laukiaPatvirtinimo arba patvirtinta rezervacija tampa atsaukta, vietos atlaisvinamos, nuoroda nebegalioja.", "screen": "E04, E09"}, {"id": "F12", "name": "Prad\\u0117ti pokalb\\u012f", "role": "Klientas", "input": "Kempingas, tema ir pirmos \\u017einut\\u0117s tekstas.", "output": "Vienu veiksmu sukuriamas pokalbis ir pirmoji \\u017einut\\u0117; pokalbis matomas klientui ir to kempingo administratoriui.", "screen": "E06"}, {"id": "F13", "name": "Skaityti ir si\\u0173sti \\u017einutes", "role": "Klientas arba atitinkamo kempingo administratorius", "input": "Pasirinktas pokalbis; siun\\u010diant \\u2013 netu\\u0161\\u010dias iki 2000 \\u017eenkl\\u0173 tekstas.", "output": "Parodoma chronologin\\u0117 pokalbio istorija; i\\u0161si\\u0173sta \\u017einut\\u0117 pridedama vien\\u0105 kart\\u0105; gav\\u0117jui atv\\u0117rus pa\\u017eymima perskaityta.", "screen": "E06"}, {"id": "F14", "name": "Pateikti atsiliepim\\u0105", "role": "Klientas", "input": "Pasibaigusi patvirtinta savo rezervacija, \\u012fvertinimas 1\\u20135 ir komentaras iki 2000 \\u017eenkl\\u0173.", "output": "I\\u0161saugomas vienas atsiliepimas rezervacijai ir perskai\\u010diuojamas kempingo reitingas; pakartotinis atsiliepimas atmetamas.", "screen": "E07"}, {"id": "F15", "name": "Keisti savo kempingo informacij\\u0105", "role": "Kempingo administratorius", "input": "Priskirtas kempingas, apra\\u0161ymas, adresas, miestas ir kontaktai.", "output": "Atnaujinama savo kempingo vie\\u0161a informacija; svetimo kempingo duomenys nepasiekiami.", "screen": "E08"}, {"id": "F16", "name": "Valdyti savo kempingo paslaugas", "role": "Kempingo administratorius", "input": "Paslaugos tipas, pavadinimas, apra\\u0161ymas, paros kaina, bendras kiekis, aktyvumas.", "output": "Paslauga sukuriama, pakei\\u010diama arba i\\u0161jungiama; esam\\u0173 rezervacij\\u0173 kainos nekinta; kiekis nema\\u017einamas pa\\u017eeid\\u017eiant galiojan\\u010dias rezervacijas.", "screen": "E08"}, {"id": "F17", "name": "Per\\u017ei\\u016br\\u0117ti kempingo rezervacijas", "role": "Kempingo administratorius", "input": "Priskirtas kempingas, datos ir b\\u016bsenos filtras.", "output": "Tik to kempingo rezervacijos, rezervavusi\\u0173 klient\\u0173 kontaktai ir paslaug\\u0173 kiekiai; galima pereiti \\u012f F11 arba susira\\u0161in\\u0117jim\\u0105.", "screen": "E09"}, {"id": "F18", "name": "Valdyti paskyras ir teises", "role": "Portalo administratorius", "input": "Paskyra, aktyvumas; kuriant administratori\\u0173 \\u2013 jo duomenys ir rol\\u0117.", "output": "Sukuriama kempingo administratoriaus paskyra arba pakei\\u010diama paskyros b\\u016bsena; klientas pats administratoriaus teisi\\u0173 gauti negali.", "screen": "E10"}, {"id": "F19", "name": "Valdyti kempingus ir priskirti administratorius", "role": "Portalo administratorius", "input": "Kempingo pavadinimas, kontaktai, aktyvumas ir aktyvi kempingo administratoriaus paskyra.", "output": "Kempingas sukuriamas arba kei\\u010diamas jo aktyvumas ir administratorius; galiojan\\u010dios rezervacijos i\\u0161saugomos.", "screen": "E10"}, {"id": "F20", "name": "Moderuoti atsiliepimus", "role": "Portalo administratorius", "input": "Atsiliepimas, veiksmas \\u201ePasl\\u0117pti\\u201c arba \\u201eAtkurti\\u201c, pasl\\u0117pimo prie\\u017eastis.", "output": "Netinkamas atsiliepimas paslepiamas arba atkuriamas; reitingas skai\\u010diuojamas tik i\\u0161 matom\\u0173 atsiliepim\\u0173.", "screen": "E10"}, {"id": "F21", "name": "Pakartotinai si\\u0173sti patvirtinimo lai\\u0161k\\u0105", "role": "Klientas", "input": "Savo rezervacija, kurios patvirtinimo terminas dar nesibaig\\u0117.", "output": "Sugeneruojamas naujas \\u017eetonas ir vykdomas F08; senoji nuoroda nustoja galioti, pradinis 30 min. terminas neprat\\u0119siamas.", "screen": "E04"}], "entities": [{"id": "user", "name": "Naudotojas", "attributes": [{"name": "vardas", "type": "String", "key": "", "description": "Vardas.", "optional": false}, {"name": "pavarde", "type": "String", "key": "", "description": "Pavard\\u0117.", "optional": false}, {"name": "prisijungimoVardas", "type": "String", "key": "Unikalus", "description": "Unikalus prisijungimo vardas.", "optional": false}, {"name": "elPastas", "type": "String", "key": "Unikalus", "description": "Unikalus normalizuotas el. pa\\u0161tas.", "optional": false}, {"name": "slaptazodzioMaisa", "type": "String", "key": "", "description": "Slapta\\u017eod\\u017eio mai\\u0161a su druska; atviro slapta\\u017eod\\u017eio n\\u0117ra.", "optional": false}, {"name": "role", "type": "Naudotojo rol\\u0117", "key": "", "description": "Viena paskyros rol\\u0117.", "optional": false}, {"name": "busena", "type": "Paskyros b\\u016bsena", "key": "", "description": "aktyvi arba blokuota.", "optional": false}, {"name": "registracijosData", "type": "date", "key": "", "description": "Suk\\u016brimo laikas.", "optional": false}]}, {"id": "camp", "name": "Kempingas", "attributes": [{"name": "pavadinimas", "type": "String", "key": "", "description": "Kempingo pavadinimas.", "optional": false}, {"name": "aprasymas", "type": "String", "key": "", "description": "Vie\\u0161as apra\\u0161ymas.", "optional": false}, {"name": "adresas", "type": "String", "key": "", "description": "Adresas.", "optional": false}, {"name": "miestas", "type": "String", "key": "", "description": "Miestas ar vietov\\u0117.", "optional": false}, {"name": "kontaktinisElPastas", "type": "String", "key": "", "description": "Vie\\u0161as kontaktas.", "optional": false}, {"name": "telefonas", "type": "String", "key": "", "description": "Kontaktinis telefonas.", "optional": false}, {"name": "aktyvus", "type": "boolean", "key": "", "description": "Ar rodomas ir priima naujas rezervacijas.", "optional": false}, {"name": "reitingas", "type": "double", "key": "I\\u0161vestinis", "description": "Matom\\u0173 \\u012fvertinim\\u0173 vidurkis; nesaugomas kaip atskiras laukas.", "derived": true, "optional": true}]}, {"id": "type", "name": "Paslaugos tipas", "attributes": [{"name": "pavadinimas", "type": "String", "key": "Unikalus", "description": "Palapin\\u0117s vieta, kemperio vieta, lau\\u017eaviet\\u0117, vandens dviratis, hamakas ar kita.", "optional": false}]}, {"id": "service", "name": "Paslauga", "attributes": [{"name": "pavadinimas", "type": "String", "key": "", "description": "Konkretaus pasi\\u016blymo pavadinimas.", "optional": false}, {"name": "aprasymas", "type": "String", "key": "", "description": "Paslaugos s\\u0105lygos.", "optional": false}, {"name": "parosKaina", "type": "double", "key": "", "description": "Vieno vieneto kaina parai eurais, \\u2265 0.", "optional": false}, {"name": "bendrasKiekis", "type": "int", "key": "", "description": "Vienod\\u0173 rezervuojam\\u0173 vienet\\u0173 skai\\u010dius, > 0.", "optional": false}, {"name": "aktyvi", "type": "boolean", "key": "", "description": "Ar galima nauja rezervacija.", "optional": false}]}, {"id": "booking", "name": "Rezervacija", "attributes": [{"name": "pradziosData", "type": "date", "key": "", "description": "Pirmoji rezervuota para.", "optional": false}, {"name": "pabaigosData", "type": "date", "key": "", "description": "I\\u0161vykimo data; \\u0161i para ne\\u012ftraukiama.", "optional": false}, {"name": "busena", "type": "Rezervacijos b\\u016bsena", "key": "", "description": "Rezervacijos gyvavimo b\\u016bsena.", "optional": false}, {"name": "patvirtinimoTerminas", "type": "date", "key": "", "description": "Pradinis suk\\u016brimo laikas + 30 min.", "optional": false}, {"name": "sukurimoData", "type": "date", "key": "", "description": "Suk\\u016brimo laikas.", "optional": false}, {"name": "atsaukimoData", "type": "date", "key": "Neprivalomas", "description": "At\\u0161aukimo laikas.", "optional": true}, {"name": "atsaukimoPriezastis", "type": "String", "key": "Neprivalomas", "description": "Administratoriaus at\\u0161aukimo prie\\u017eastis.", "optional": true}, {"name": "bendraSuma", "type": "double", "key": "I\\u0161vestinis", "description": "Par\\u0173 ir rezervacijos eilu\\u010di\\u0173 kain\\u0173 suma; apskai\\u010diuojama.", "derived": true, "optional": false}]}, {"id": "line", "name": "Rezervacijos eilut\\u0117", "attributes": [{"name": "kiekis", "type": "int", "key": "", "description": "Rezervuotas kiekis, > 0.", "optional": false}, {"name": "parosKaina", "type": "double", "key": "", "description": "Rezervuojant u\\u017efiksuota vieneto paros kaina, \\u2265 0.", "optional": false}]}, {"id": "token", "name": "Patvirtinimo nuoroda", "attributes": [{"name": "zetonoMaisa", "type": "String", "key": "Unikalus", "description": "SHA-256 mai\\u0161a i\\u0161 kriptografi\\u0161kai atsitiktinio \\u017eetono.", "optional": false}, {"name": "galiojimoPabaiga", "type": "date", "key": "", "description": "Sutampa su rezervacijos patvirtinimoTerminas.", "optional": false}, {"name": "panaudojimoData", "type": "date", "key": "Neprivalomas", "description": "Patvirtinimo laikas; NULL, kol nepanaudota.", "optional": true}, {"name": "sukurimoData", "type": "date", "key": "", "description": "Dabartinio \\u017eetono sugeneravimo laikas.", "optional": false}]}, {"id": "thread", "name": "Pokalbis", "attributes": [{"name": "tema", "type": "String", "key": "", "description": "Pokalbio tema.", "optional": false}, {"name": "sukurimoData", "type": "date", "key": "", "description": "Pokalbio suk\\u016brimo laikas.", "optional": false}]}, {"id": "message", "name": "\\u017dinut\\u0117", "attributes": [{"name": "tekstas", "type": "String", "key": "", "description": "Netu\\u0161\\u010dias paprastas tekstas.", "optional": false}, {"name": "issiuntimoData", "type": "date", "key": "", "description": "I\\u0161siuntimo laikas.", "optional": false}, {"name": "perskaitymoData", "type": "date", "key": "Neprivalomas", "description": "Gav\\u0117jo perskaitymo laikas.", "optional": true}]}, {"id": "review", "name": "Atsiliepimas", "attributes": [{"name": "ivertinimas", "type": "int", "key": "", "description": "Sveikas skai\\u010dius nuo 1 iki 5.", "optional": false}, {"name": "komentaras", "type": "String", "key": "", "description": "Komentaras.", "optional": false}, {"name": "sukurimoData", "type": "date", "key": "", "description": "Pateikimo laikas.", "optional": false}, {"name": "pasleptas", "type": "boolean", "key": "", "description": "Ar pasl\\u0117ptas moderuojant.", "optional": false}, {"name": "paslepimoPriezastis", "type": "String", "key": "Neprivalomas", "description": "Privaloma, kai atsiliepimas pasl\\u0117ptas.", "optional": true}]}], "enums": [{"id": "role", "name": "Naudotojo rol\\u0117", "values": ["klientas", "kempingoAdministratorius", "portaloAdministratorius"]}, {"id": "account_status", "name": "Paskyros b\\u016bsena", "values": ["aktyvi", "blokuota"]}, {"id": "booking_status", "name": "Rezervacijos b\\u016bsena", "values": ["laukiaPatvirtinimo", "patvirtinta", "atsaukta", "nepatvirtintaLaiku"]}], "relations": [{"id": "R01", "a": "user", "b": "camp", "ma": "1", "mb": "0..*", "label": "administruoja"}, {"id": "R02", "a": "user", "b": "booking", "ma": "1", "mb": "0..*", "label": "rezervuoja"}, {"id": "R03", "a": "camp", "b": "service", "ma": "1", "mb": "0..*", "label": "si\\u016blo"}, {"id": "R04", "a": "type", "b": "service", "ma": "1", "mb": "0..*", "label": "klasifikuoja"}, {"id": "R05", "a": "camp", "b": "booking", "ma": "1", "mb": "0..*", "label": "priima"}, {"id": "R06", "a": "booking", "b": "line", "ma": "1", "mb": "1..*", "label": "turi eilutes"}, {"id": "R07", "a": "service", "b": "line", "ma": "1", "mb": "0..*", "label": "pasirinkta"}, {"id": "R08", "a": "booking", "b": "token", "ma": "1", "mb": "0..1", "label": "patvirtinama"}, {"id": "R09", "a": "user", "b": "thread", "ma": "1", "mb": "0..*", "label": "pradeda"}, {"id": "R10", "a": "camp", "b": "thread", "ma": "1", "mb": "0..*", "label": "gauna pokalbius"}, {"id": "R11", "a": "thread", "b": "message", "ma": "1", "mb": "1..*", "label": "turi \\u017einutes"}, {"id": "R12", "a": "user", "b": "message", "ma": "1", "mb": "0..*", "label": "siun\\u010dia"}, {"id": "R13", "a": "booking", "b": "review", "ma": "1", "mb": "0..1", "label": "\\u012fvertinama"}], "diagrams": [{"id": "UC01", "title": "Visos sistemos panaudojimo atvejai", "kind": "usecase", "size": [1800, 2450], "actors": {"registered": [230, 260], "guest": [230, 600], "client": [230, 1110], "camp_admin": [230, 1840], "portal_admin": [230, 2220], "mail": [1650, 1000]}, "cases": {"F02": [800, 160, 440, 70], "F03": [800, 260, 440, 70], "F04": [800, 360, 440, 70], "F05": [800, 460, 440, 70], "F01": [800, 600, 440, 70], "F06": [800, 760, 440, 70], "F07": [800, 860, 440, 70], "F09": [800, 960, 440, 70], "F10": [800, 1060, 440, 70], "F11": [800, 1160, 440, 70], "F12": [800, 1260, 440, 70], "F13": [800, 1360, 440, 70], "F14": [800, 1460, 440, 70], "F21": [800, 1560, 440, 70], "F15": [800, 1740, 440, 70], "F16": [800, 1840, 440, 70], "F17": [800, 1940, 440, 70], "F18": [800, 2120, 440, 70], "F19": [800, 2220, 440, 70], "F20": [800, 2320, 440, 70], "F08": [1360, 860, 440, 70]}, "links": [["registered", "F02"], ["registered", "F03"], ["registered", "F04"], ["registered", "F05"], ["guest", "F01"], ["guest", "F04"], ["guest", "F05"], ["client", "F06"], ["client", "F07"], ["client", "F09"], ["client", "F10"], ["client", "F11"], ["client", "F12"], ["client", "F13"], ["client", "F14"], ["client", "F21"], ["camp_admin", "F11"], ["camp_admin", "F13"], ["camp_admin", "F15"], ["camp_admin", "F16"], ["camp_admin", "F17"], ["portal_admin", "F18"], ["portal_admin", "F19"], ["portal_admin", "F20"], ["mail", "F08"]], "boundary": null, "generals": [["client", "registered"], ["camp_admin", "registered"], ["portal_admin", "registered"]], "includes": [["F07", "F08"], ["F21", "F08"]], "routes": {}, "general_routes": {"client": [[75, 1130], [75, 280]], "camp_admin": [[53, 1860], [53, 280]], "portal_admin": [[31, 2240], [31, 280]]}}, {"id": "DB01", "title": "Vienos duomen\\u0173 baz\\u0117s loginis modelis", "kind": "class", "size": [2720, 1660], "positions": {"user": [40, 100, 460, 365], "camp": [730, 100, 480, 365], "service": [1470, 100, 460, 290], "type": [2180, 100, 400, 155], "thread": [40, 800, 460, 185], "booking": [710, 760, 530, 395], "line": [1480, 760, 440, 190], "token": [2180, 760, 450, 260], "message": [40, 1250, 490, 245], "review": [1460, 1230, 500, 305]}, "enum_positions": {"role": [2180, 1170, 440, 185], "account_status": [2180, 1410, 400, 140], "booking_status": [710, 1320, 540, 215]}, "relations": ["R01", "R02", "R03", "R04", "R05", "R06", "R07", "R08", "R09", "R10", "R11", "R12", "R13"], "routes": {"R02": [[570, 300], [570, 850]], "R03": [], "R04": [], "R07": [[1410, 520], [1410, 850]], "R08": [[1320, 1100], [2070, 1100], [2070, 890]], "R09": [], "R10": [[660, 520], [600, 520], [600, 880]], "R11": [], "R12": [[620, 400], [620, 1370]], "R13": [[1290, 1190], [1360, 1190], [1360, 1370]]}, "colors": {"user": "#d4f3d2", "camp": "#d4f3d2", "service": "#fff0b8", "type": "#fff0b8", "booking": "#f8d5d5", "line": "#f8d5d5", "token": "#f8d5d5", "thread": "#dcd9f4", "message": "#dcd9f4", "review": "#d4f3d2"}}, {"id": "SD01", "title": "Rezervacijos pateikimas ir patvirtinimas el. pa\\u0161tu", "kind": "sequence", "size": [1500, 2450], "participants": [{"id": "client", "name": "Klientas", "x": 190}, {"id": "system", "name": "Kemping\\u0173 portalas", "x": 720}, {"id": "mail", "name": "El. pa\\u0161to serveris", "x": 1280}], "messages": [{"id": "m01", "a": "client", "b": "system", "text": "Atidaryti rezervavimo form\\u0105", "y": 220, "sort": "call", "operand": null}, {"id": "m02", "a": "system", "b": "client", "text": "Parodyti paslaugas ir dat\\u0173 laukus", "y": 310, "sort": "reply", "operand": null}, {"id": "m03", "a": "client", "b": "system", "text": "Pateikti datas, paslaugas ir kiekius", "y": 400, "sort": "call", "operand": null}, {"id": "m04", "a": "system", "b": "system", "text": "Patikrinti duomenis ir prieinamum\\u0105", "y": 490, "sort": "call", "operand": null}, {"id": "m05", "a": "system", "b": "system", "text": "Sukurti rezervacij\\u0105 ir kain\\u0173 kopijas", "y": 680, "sort": "call", "operand": "available"}, {"id": "m06", "a": "system", "b": "system", "text": "Sugeneruoti vienkartin\\u012f \\u017eeton\\u0105", "y": 780, "sort": "call", "operand": "available"}, {"id": "m07", "a": "system", "b": "mail", "text": "Si\\u0173sti patvirtinimo lai\\u0161k\\u0105", "y": 880, "sort": "call", "operand": "available"}, {"id": "m08", "a": "mail", "b": "system", "text": "Patvirtinti lai\\u0161ko pri\\u0117mim\\u0105", "y": 980, "sort": "reply", "operand": "available"}, {"id": "m09", "a": "system", "b": "client", "text": "Parodyti rezervacijos numer\\u012f ir termin\\u0105", "y": 1080, "sort": "reply", "operand": "available"}, {"id": "m10", "a": "client", "b": "system", "text": "Atverti URL ir paspausti \\u201ePatvirtinti\\u201c", "y": 1190, "sort": "call", "operand": "available"}, {"id": "m11", "a": "system", "b": "system", "text": "Patikrinti \\u017eeton\\u0105, termin\\u0105 ir b\\u016bsen\\u0105", "y": 1290, "sort": "call", "operand": "available"}, {"id": "m12", "a": "system", "b": "system", "text": "Patvirtinti rezervacij\\u0105; panaudoti \\u017eeton\\u0105", "y": 1480, "sort": "call", "operand": "validToken"}, {"id": "m13", "a": "system", "b": "client", "text": "Parodyti patvirtint\\u0105 rezervacij\\u0105", "y": 1600, "sort": "reply", "operand": "validToken"}, {"id": "m14", "a": "system", "b": "client", "text": "Parodyti negaliojan\\u010dios nuorodos prane\\u0161im\\u0105", "y": 1840, "sort": "reply", "operand": "invalidToken"}, {"id": "m15", "a": "system", "b": "client", "text": "Parodyti klaidas arba laisv\\u0173 viet\\u0173 tr\\u016bkum\\u0105", "y": 2160, "sort": "reply", "operand": "unavailable"}], "frames": [{"id": "availability", "operator": "alt", "bounds": [75, 570, 1360, 1690], "branches": [{"id": "available", "guard": "Duomenys tinkami ir viet\\u0173 pakanka", "top": 570, "bottom": 2050}, {"id": "unavailable", "guard": "Duomenys netinkami arba viet\\u0173 tr\\u016bksta", "top": 2050, "bottom": 2260}], "parent": null, "covers": null}, {"id": "confirmation", "operator": "alt", "bounds": [100, 1380, 970, 570], "branches": [{"id": "validToken", "guard": "\\u017detonas galioja ir dar nepanaudotas", "top": 1380, "bottom": 1700}, {"id": "invalidToken", "guard": "\\u017detonas netinkamas, panaudotas arba pasibaig\\u0119s", "top": 1700, "bottom": 1950}], "parent": "available", "covers": ["client", "system"]}], "notes": ["Pradin\\u0117 s\\u0105lyga: klientas prisijung\\u0119s. Patvirtinimas pagal vienkartin\\u0119 URL nuorod\\u0105 galimas be prisijungimo tik \\u0161iai rezervacijai.", "URL atv\\u0117rimas tik pateikia form\\u0105; rezervacij\\u0105 patvirtina atskiras vartotojo veiksmas. Terminas \\u2013 30 minu\\u010di\\u0173.", "Lai\\u0161ko pri\\u0117mimas serveryje negarantuoja gavimo. Siuntimo klaidos atveju klientas gali pakartoti F21 iki pradinio termino."]}, {"id": "SD02", "title": "Kliento ir kempingo administratoriaus susira\\u0161in\\u0117jimas", "kind": "sequence", "size": [1450, 1710], "participants": [{"id": "client", "name": "Klientas", "x": 170}, {"id": "system", "name": "Kemping\\u0173 portalas", "x": 700}, {"id": "camp_admin", "name": "Kempingo administratorius", "x": 1240}], "messages": [{"id": "m01", "a": "client", "b": "system", "text": "Atidaryti naujo pokalbio form\\u0105", "y": 220, "sort": "call", "operand": null}, {"id": "m02", "a": "system", "b": "client", "text": "Parodyti temos ir \\u017einut\\u0117s laukus", "y": 310, "sort": "reply", "operand": null}, {"id": "m03", "a": "client", "b": "system", "text": "Pasirinkti kemping\\u0105 ir si\\u0173sti pirm\\u0105 \\u017einut\\u0119", "y": 400, "sort": "call", "operand": null}, {"id": "m04", "a": "system", "b": "system", "text": "Patikrinti teises ir \\u017einut\\u0117s duomenis", "y": 490, "sort": "call", "operand": null}, {"id": "m05", "a": "system", "b": "system", "text": "I\\u0161saugoti pokalb\\u012f ir pirm\\u0105 \\u017einut\\u0119", "y": 680, "sort": "call", "operand": "validMessage"}, {"id": "m06", "a": "system", "b": "client", "text": "Patvirtinti \\u017einut\\u0117s i\\u0161siuntim\\u0105", "y": 780, "sort": "reply", "operand": "validMessage"}, {"id": "m07", "a": "camp_admin", "b": "system", "text": "Atverti savo kempingo pokalb\\u012f", "y": 880, "sort": "call", "operand": "validMessage"}, {"id": "m08", "a": "system", "b": "camp_admin", "text": "Pateikti pokalbio istorij\\u0105", "y": 980, "sort": "reply", "operand": "validMessage"}, {"id": "m09", "a": "camp_admin", "b": "system", "text": "Si\\u0173sti atsakym\\u0105", "y": 1080, "sort": "call", "operand": "validMessage"}, {"id": "m10", "a": "system", "b": "system", "text": "Patikrinti priskyrim\\u0105 ir i\\u0161saugoti atsakym\\u0105", "y": 1180, "sort": "call", "operand": "validMessage"}, {"id": "m11", "a": "client", "b": "system", "text": "Atverti pokalb\\u012f", "y": 1280, "sort": "call", "operand": "validMessage"}, {"id": "m12", "a": "system", "b": "client", "text": "Pateikti atsakym\\u0105; pa\\u017eym\\u0117ti perskaityta", "y": 1380, "sort": "reply", "operand": "validMessage"}, {"id": "m13", "a": "system", "b": "client", "text": "Parodyti formos klaidas ir palikti tekst\\u0105", "y": 1550, "sort": "reply", "operand": "invalidMessage"}], "frames": [{"id": "messageCheck", "operator": "alt", "bounds": [60, 570, 1320, 1060], "branches": [{"id": "validMessage", "guard": "Teis\\u0117s ir \\u012fvestis tinkamos", "top": 570, "bottom": 1460}, {"id": "invalidMessage", "guard": "Tr\\u016bksta duomen\\u0173 arba n\\u0117ra teisi\\u0173", "top": 1460, "bottom": 1630}], "parent": null, "covers": null}], "notes": ["Skaityti ir si\\u0173sti gali tik pokalbio klientas arba \\u0161iuo metu tam kempingui priskirtas administratorius.", "Rodomas vienas apsikeitimas. Toliau F13 kartojamas tame pa\\u010diame pokalbyje."]}, {"id": "SD03", "title": "Atsiliepimo pateikimas ir kempingo reitingas", "kind": "sequence", "size": [1220, 1780], "participants": [{"id": "client", "name": "Klientas", "x": 220}, {"id": "system", "name": "Kemping\\u0173 portalas", "x": 840}], "messages": [{"id": "m01", "a": "client", "b": "system", "text": "Pasirinkti pasibaigusi\\u0105 savo rezervacij\\u0105", "y": 220, "sort": "call", "operand": null}, {"id": "m02", "a": "system", "b": "system", "text": "Patikrinti savinink\\u0105, b\\u016bsen\\u0105 ir pabaigos dat\\u0105", "y": 320, "sort": "call", "operand": null}, {"id": "m03", "a": "system", "b": "client", "text": "Parodyti atsiliepimo form\\u0105", "y": 510, "sort": "reply", "operand": "eligible"}, {"id": "m04", "a": "client", "b": "system", "text": "Pateikti \\u012fvertinim\\u0105 1\\u20135 ir komentar\\u0105", "y": 610, "sort": "call", "operand": "eligible"}, {"id": "m05", "a": "system", "b": "system", "text": "Patikrinti \\u012fvest\\u012f ir vieno atsiliepimo taisykl\\u0119", "y": 710, "sort": "call", "operand": "eligible"}, {"id": "m06", "a": "system", "b": "system", "text": "I\\u0161saugoti atsiliepim\\u0105", "y": 900, "sort": "call", "operand": "reviewOk"}, {"id": "m07", "a": "system", "b": "system", "text": "Apskai\\u010diuoti matom\\u0173 \\u012fvertinim\\u0173 vidurk\\u012f", "y": 1010, "sort": "call", "operand": "reviewOk"}, {"id": "m08", "a": "system", "b": "client", "text": "Parodyti atsiliepim\\u0105 ir atnaujint\\u0105 reiting\\u0105", "y": 1120, "sort": "reply", "operand": "reviewOk"}, {"id": "m09", "a": "system", "b": "client", "text": "Parodyti klaidas arba jau pateikt\\u0105 atsiliepim\\u0105", "y": 1340, "sort": "reply", "operand": "reviewError"}, {"id": "m10", "a": "system", "b": "client", "text": "Prane\\u0161ti, kad rezervacijos vertinti negalima", "y": 1590, "sort": "reply", "operand": "ineligible"}], "frames": [{"id": "eligibility", "operator": "alt", "bounds": [70, 410, 1070, 1260], "branches": [{"id": "eligible", "guard": "Sava patvirtinta rezervacija jau pasibaigusi", "top": 410, "bottom": 1480}, {"id": "ineligible", "guard": "Rezervacija neatitinka vertinimo s\\u0105lyg\\u0173", "top": 1480, "bottom": 1670}], "parent": null, "covers": null}, {"id": "reviewCheck", "operator": "alt", "bounds": [100, 800, 1010, 620], "branches": [{"id": "reviewOk", "guard": "\\u012evestis tinkama ir atsiliepimo dar n\\u0117ra", "top": 800, "bottom": 1220}, {"id": "reviewError", "guard": "\\u012evestis netinkama arba atsiliepimas jau pateiktas", "top": 1220, "bottom": 1420}], "parent": "eligible", "covers": null}], "notes": ["Vienai rezervacijai leid\\u017eiamas vienas atsiliepimas. Reitingas skai\\u010diuojamas tik i\\u0161 matom\\u0173 atsiliepim\\u0173."]}], "actors": [{"id": "guest", "name": "Sve\\u010dias", "abstract": false}, {"id": "registered", "name": "Registruotas naudotojas", "abstract": true}, {"id": "client", "name": "Klientas", "abstract": false}, {"id": "camp_admin", "name": "Kempingo administratorius", "abstract": false}, {"id": "portal_admin", "name": "Portalo administratorius", "abstract": false}, {"id": "mail", "name": "El. pa\\u0161to serveris", "abstract": false}]}')

def resolve_path_endpoints(relationship, left, right, points=None):
    """Respect MagicDraw's actual client/supplier order, including associations."""
    client = CoreHelper.getClientElement(relationship)
    supplier = CoreHelper.getSupplierElement(relationship)
    left_element = left.getElement()
    right_element = right.getElement()
    if client == left_element and supplier == right_element:
        return (left, right, points)
    if client == right_element and supplier == left_element:
        return (right, left, list(reversed(points)) if points else points)
    raise ValueError('Ry\u0161io galai neatitinka simboli\u0173: ' + str(relationship) + '; API client=' + str(client) + '; API supplier=' + str(supplier) + '; kair\u0117=' + str(left_element) + '; de\u0161in\u0117=' + str(right_element))

class KempinguPortaloKurimas(object):

    def __init__(self, project):
        self.project = project
        self.factory = project.getElementsFactory()
        self.mem = ModelElementsManager.getInstance()
        self.pem = PresentationElementsManager.getInstance()
        self.elements = {}
        self.relationships = {}
        self.diagrams = []

    def create(self, kind, owner, name=None):
        element = getattr(self.factory, 'create' + kind + 'Instance')()
        if name is not None:
            element.setName(name)
        self.mem.addElement(element, owner)
        return element

    def comment(self, owner, body):
        comment = self.factory.createCommentInstance()
        comment.setBody(body)
        owner.getOwnedComment().add(comment)

    def multiplicity(self, element, mult):
        bits = mult.split('..')
        lo = int(bits[0])
        hi = -1 if bits[-1] == '*' else int(bits[-1])
        lower = self.factory.createLiteralIntegerInstance()
        lower.setValue(lo)
        upper = self.factory.createLiteralUnlimitedNaturalInstance()
        upper.setValue(hi)
        element.setLowerValue(lower)
        element.setUpperValue(upper)

    def association(self, owner, left, right, name='', lm='1', rm='1'):
        link = self.create('Association', owner, name)
        ends = list(link.getMemberEnd())
        if not ends:
            for unused in range(2):
                end = self.factory.createPropertyInstance()
                link.getOwnedEnd().add(end)
                if end not in link.getMemberEnd():
                    link.getMemberEnd().add(end)
            ends = list(link.getMemberEnd())
        if len(ends) != 2:
            raise ValueError('Nauja asociacija turi tur\u0117ti du galus; rasta: ' + str(len(ends)))
        for (end, classifier, mult) in [(ends[0], right, rm), (ends[1], left, lm)]:
            end.setType(classifier)
            self.multiplicity(end, mult)
        if CoreHelper.getClientElement(link) != left or CoreHelper.getSupplierElement(link) != right:
            raise ValueError('Asociacijos tip\u0173 priskyrimas nepavyko: ' + str(name) + '; gal\u0173 tipai=' + str([end.getType() for end in ends]))
        return link

    def generalization(self, child, parent):
        gen = self.factory.createGeneralizationInstance()
        gen.setGeneral(parent)
        child.getGeneralization().add(gen)
        return gen

    def diagram(self, owner, type_constant, name):
        diagram = self.mem.createDiagram(type_constant, owner)
        diagram.setName(name)
        view = self.project.getDiagram(diagram)
        view.ensureLoaded()
        self.diagrams.append((name.split(' ')[0], view))
        return view

    def shape(self, element, parent, box):
        view = self.pem.createShapeElement(element, parent)
        self.pem.reshapeShapeElement(view, Rectangle(*[int(v) for v in box]))
        return view

    def text(self, parent, text, box):
        view = self.pem.createTextBox(parent)
        self.pem.setText(view, text)
        self.pem.reshapeShapeElement(view, Rectangle(*[int(v) for v in box]))
        return view

    def path(self, rel, left, right, points=None):
        (left, right, points) = resolve_path_endpoints(rel, left, right, points)
        view = self.pem.createPathElement(rel, left, right)
        if points:
            jpoints = ArrayList()
            for (x, y) in points:
                jpoints.add(Point(int(x), int(y)))
            self.pem.changePathBreakPoints(view, jpoints)
        return view

    def create_usecase_model(self, root):
        package = self.create('Package', root, '1 Panaudojimo atvejai')
        self.uc_package = package
        subject = self.create('Class', package, 'Kemping\u0173 portalas')
        self.elements['system'] = subject
        for a in MODEL['actors']:
            actor = self.create('Actor', package, a['name'])
            actor.setAbstract(a['abstract'])
            self.elements[a['id']] = actor
        for d in MODEL['diagrams']:
            if d['kind'] != 'usecase':
                continue
            for (child, parent) in d['generals']:
                key = 'gen_' + child
                if key not in self.relationships:
                    self.relationships[key] = self.generalization(self.elements[child], self.elements[parent])
        for f in MODEL['functions']:
            case = self.create('UseCase', package, f['name'])
            case.getSubject().add(subject)
            self.comment(case, f['id'] + '\nVartotojai: ' + f['role'] + '\n\u012evestis: ' + f['input'] + '\nRezultatas: ' + f['output'])
            self.elements[f['id']] = case
        for d in MODEL['diagrams']:
            if d['kind'] != 'usecase':
                continue
            for (a, b) in d['links']:
                key = a + '_' + b
                if key not in self.relationships:
                    self.relationships[key] = self.association(package, self.elements[a], self.elements[b])
            for (a, b) in d['includes']:
                include = self.factory.createIncludeInstance()
                include.setAddition(self.elements[b])
                self.elements[a].getInclude().add(include)
                self.relationships[a + '_' + b] = include

    def draw_usecase(self, d):
        view = self.diagram(self.uc_package, DiagramTypeConstants.UML_USECASE_DIAGRAM, d['id'] + ' ' + d['title'])
        self.layout_usecase(d, view)
        return view

    def create_class_model(self, root):
        package = self.create('Package', root, '2 Esybi\u0173 klas\u0117s')
        self.db_package = package
        self.types = {}
        types = self.create('Package', package, 'Tipai ir b\u016bsenos')
        for e in MODEL['enums']:
            enum = self.create('Enumeration', types, e['name'])
            for name in e['values']:
                literal = self.factory.createEnumerationLiteralInstance()
                literal.setName(name)
                enum.getOwnedLiteral().add(literal)
            self.elements[e['id']] = enum
            self.types[e['name']] = enum
        for e in MODEL['entities']:
            cls = self.create('Class', package, e['name'])
            self.elements[e['id']] = cls
            for a in e['attributes']:
                if a['type'] not in self.types:
                    self.types[a['type']] = self.create('PrimitiveType', types, a['type'])
                prop = self.factory.createPropertyInstance()
                prop.setName(a['name'])
                prop.setType(self.types[a['type']])
                prop.setVisibility(VisibilityKindEnum.PRIVATE)
                self.multiplicity(prop, '0..1' if a.get('optional') else '1')
                if a.get('derived'):
                    prop.setDerived(True)
                    prop.setReadOnly(True)
                cls.getOwnedAttribute().add(prop)
                self.comment(prop, a['description'] + (' Apribojimas: ' + a['key'] if a['key'] else ''))
        for r in MODEL['relations']:
            self.relationships[r['id']] = self.association(package, self.elements[r['a']], self.elements[r['b']], r['label'], r['ma'], r['mb'])
        self.comment(package, 'Visos esyb\u0117s priklauso vienai duomen\u0173 bazei. I\u0161oriniai raktai UML atributuose nedubliuojami: juos atstoja asociacijos. /reitingas ir /bendraSuma yra i\u0161vestin\u0117s savyb\u0117s. Fiziniame modelyje pinigin\u0117ms reik\u0161m\u0117ms pasirenkamas tikslus de\u0161imtainis tipas.')

    def draw_class(self, d):
        view = self.diagram(self.db_package, DiagramTypeConstants.UML_CLASS_DIAGRAM, d['id'] + ' ' + d['title'])
        symbols = {}
        for (eid, box) in list(d['positions'].items()) + list(d['enum_positions'].items()):
            symbol = self.shape(self.elements[eid], view, box)
            color = d['colors'].get(eid, '#e4e1f5')
            self.decorate(symbol, color)
            self.property_value(symbol, 'SUPPRESS_CLASS_OPERATIONS', True)
            self.property_value(symbol, 'SUPPRESS_CLASS_ATTRIBUTES', False)
            self.property_value(symbol, 'SHOW_ATTRIBUTES_TYPE', True)
            self.property_value(symbol, 'ATTRIBUTE_FONT', Font('Arial', Font.PLAIN, 16))
            symbols[eid] = symbol
        for r in MODEL['relations']:
            self.path(self.relationships[r['id']], symbols[r['a']], symbols[r['b']], d['routes'].get(r['id']))
        return view

    def full(self, root):
        self.create_usecase_model(root)
        self.create_class_model(root)
        sequences = self.create('Package', root, '3 Sek\u0173 diagramos')
        for d in MODEL['diagrams']:
            if d['kind'] == 'usecase':
                self.draw_usecase(d)
            elif d['kind'] == 'class':
                self.draw_class(d)
            elif d['kind'] == 'sequence':
                self.draw_sequence(d, sequences)

    def property_value(self, view, key, value):
        pid = getattr(PropertyID, key)
        prop = self.pem.getProperty(view, pid)
        if prop is None:
            return
        current = prop.getValue()
        if isinstance(value, bool) and (not isinstance(current, bool)):
            return
        if isinstance(value, Font) and current is not None and (not isinstance(current, Font)):
            return
        if isinstance(value, Color) and current is not None and (not isinstance(current, Color)):
            return
        cloned = prop.clone()
        cloned.setValue(value)
        manager = PropertyManager()
        manager.addProperty(cloned)
        self.pem.setPresentationElementProperties(view, manager)

    def decorate(self, view, color=None):
        self.property_value(view, 'FONT', Font('Arial', Font.PLAIN, 16))
        if color:
            self.property_value(view, 'USE_FILL_COLOR', True)
            self.property_value(view, 'FILL_COLOR', Color.decode(color))

    def layout_usecase(self, d, view):
        relations = [self.relationships['gen_' + child] for (child, parent) in d['generals']]
        relations += [self.relationships[a + '_' + b] for (a, b) in d['links'] + d['includes']]
        for relationship in relations:
            old = view.findPresentationElement(relationship, None)
            if old is not None:
                self.pem.deletePresentationElement(old)
        symbols = {}
        for (aid, xy) in d['actors'].items():
            symbol = self.reuse_shape(self.elements[aid], view, [xy[0] - 65, xy[1] - 50, 130, 140])
            self.decorate(symbol, '#fffed8')
            symbols[aid] = symbol
        for (fid, box) in d['cases'].items():
            (x, y, w, h) = box
            symbol = self.reuse_shape(self.elements[fid], view, [x - w / 2, y - h / 2, w, h])
            self.decorate(symbol, '#fffed8')
            symbols[fid] = symbol
        for (child, parent) in d['generals']:
            self.usecase_path(self.relationships['gen_' + child], symbols[child], symbols[parent], d['general_routes'].get(child))
        for (a, b) in d['links']:
            self.usecase_path(self.relationships[a + '_' + b], symbols[a], symbols[b], d['routes'].get(a + '_' + b))
        for (a, b) in d['includes']:
            bends = [[1080, d['cases'][a][1]], [1080, d['cases'][b][1]]] if a == 'F21' else None
            self.usecase_path(self.relationships[a + '_' + b], symbols[a], symbols[b], bends)

    def reuse_shape(self, element, view, box):
        symbol = view.findPresentationElement(element, None)
        if symbol is None:
            return self.shape(element, view, box)
        self.pem.reshapeShapeElement(symbol, Rectangle(*[int(v) for v in box]))
        return symbol

    def usecase_path(self, relationship, left, right, points=None):
        (left, right, points) = resolve_path_endpoints(relationship, left, right, points)
        path = self.pem.createPathElement(relationship, left, right)
        a = left.getBounds()
        b = right.getBounds()
        if points:
            client = Point(a.x if points[0][0] < a.x else a.x + a.width, int(a.y + a.height / 2))
            supplier = Point(b.x if points[-1][0] < b.x else b.x + b.width, int(b.y + b.height / 2))
        else:
            to_right = b.x >= a.x
            client = Point(a.x + a.width if to_right else a.x, int(a.y + a.height / 2))
            supplier = Point(b.x if to_right else b.x + b.width, int(b.y + b.height / 2))
        bends = ArrayList()
        for (x, y) in points or []:
            bends.add(Point(int(x), int(y)))
        self.pem.changePathPoints(path, supplier, client, bends)
        self.property_value(path, 'SHOW_END_MULTIPLICITY', False)
        self.property_value(path, 'SHOW_MULTIPLICITY', False)
        for child in path.getPresentationElements():
            self.property_value(child, 'SHOW_END_MULTIPLICITY', False)
            self.property_value(child, 'SHOW_MULTIPLICITY', False)
        return path

    def sequence_lifelines(self, interaction, d):
        lifelines = {}
        for p in d['participants']:
            role = self.factory.createPropertyInstance()
            role.setName('')
            role.setType(self.elements[p['id']])
            interaction.getOwnedAttribute().add(role)
            life = self.factory.createLifelineInstance()
            life.setName(p['name'])
            life.setRepresents(role)
            interaction.getLifeline().add(life)
            lifelines[p['id']] = life
        return lifelines

    def sequence_frame(self, interaction, definition, lifelines, operands):
        cf = self.factory.createCombinedFragmentInstance()
        cf.setName(definition['id'])
        cf.setInteractionOperator(InteractionOperatorKindEnum.ALT)
        for pid in definition.get('covers') or list(lifelines):
            cf.getCovered().add(lifelines[pid])
        parent = operands[definition['parent']] if definition.get('parent') else interaction
        parent.getFragment().add(cf)
        existing = list(cf.getOperand())
        if len(existing) > len(definition['branches']):
            raise ValueError('Nenumatytas alternatyvos \u0161ak\u0173 skai\u010dius.')
        for (i, b) in enumerate(definition['branches']):
            operand = existing[i] if i < len(existing) else self.factory.createInteractionOperandInstance()
            if operand not in cf.getOperand():
                cf.getOperand().add(operand)
            operand.setName(b['id'])
            guard = self.factory.createInteractionConstraintInstance()
            specification = self.factory.createLiteralStringInstance()
            specification.setValue(b['guard'])
            guard.setSpecification(specification)
            operand.setGuard(guard)
            operands[b['id']] = operand
        return cf

    def sequence_message(self, interaction, definition):
        message = self.factory.createMessageInstance()
        message.setName(definition['text'])
        sort = {'call': MessageSortEnum.SYNCHCALL, 'reply': MessageSortEnum.REPLY, 'async': MessageSortEnum.ASYNCHCALL}[definition['sort']]
        message.setMessageSort(sort)
        interaction.getMessage().add(message)
        return message

    def sequence_occurrences(self, interaction, message, definition, lifelines, operands):
        parent = operands[definition['operand']] if definition.get('operand') else interaction
        for (sending, pid) in [(True, definition['a']), (False, definition['b'])]:
            event = message.getSendEvent() if sending else message.getReceiveEvent()
            if event is None:
                event = self.factory.createMessageOccurrenceSpecificationInstance()
                if sending:
                    message.setSendEvent(event)
                else:
                    message.setReceiveEvent(event)
            if lifelines[pid] not in event.getCovered():
                event.getCovered().add(lifelines[pid])
            if event not in parent.getFragment():
                parent.getFragment().add(event)

    def sequence_events(self, d):
        events = [(f['bounds'][1], 0, f) for f in d['frames']]
        events.extend(((m['y'], 1, m) for m in d['messages']))
        return sorted(events, key=lambda item: (item[0], item[1]))

    def draw_sequence(self, d, package):
        interaction = self.create('Interaction', package, d['title'])
        self.comment(interaction, '\n'.join(d['notes']))
        view = self.diagram(interaction, DiagramTypeConstants.UML_SEQUENCE_DIAGRAM, d['id'] + ' ' + d['title'])
        view.open()
        self.property_value(view, 'SHOW_MESSAGE_NUMBERS', True)
        lifelines = self.sequence_lifelines(interaction, d)
        symbols = {}
        for p in d['participants']:
            symbol = self.shape(lifelines[p['id']], view, [p['x'] - 105, 90, 210, d['size'][1] - 240])
            self.decorate(symbol, '#d2eadb')
            symbols[p['id']] = symbol
        operands = {}
        frames = {}
        messages = {}
        paths = {}
        previous = None
        previous_y = 140
        for (y, kind, definition) in self.sequence_events(d):
            if kind == 0:
                frames[definition['id']] = self.sequence_frame(interaction, definition, lifelines, operands)
            else:
                message = self.sequence_message(interaction, definition)
                path = self.pem.createSequenceMessage(message, message.getMessageSort(), symbols[definition['a']], symbols[definition['b']], False, 0, previous, max(35, int(y - previous_y)))
                self.sequence_occurrences(interaction, message, definition, lifelines, operands)
                messages[definition['id']] = message
                paths[definition['id']] = path
                previous = message
                previous_y = y
        self.layout_sequence(d, view, frames, operands, paths)
        for definition in d['messages']:
            self.sequence_occurrences(interaction, messages[definition['id']], definition, lifelines, operands)
        last = max((max(p.getClientPoint().y, p.getSupplierPoint().y) for p in paths.values())) + 170
        self.text(view, '\n'.join(d['notes']), [35, last + 35, d['size'][0] - 70, 160])
        return view

    def layout_sequence(self, d, view, frames, operands, paths):
        anchors = [(m['y'], min(paths[m['id']].getClientPoint().y, paths[m['id']].getSupplierPoint().y)) for m in d['messages']]

        def mapped(y):
            if y <= anchors[0][0]:
                return int(y + anchors[0][1] - anchors[0][0])
            for ((a, b), (c, e)) in zip(anchors, anchors[1:]):
                if a <= y <= c:
                    return int(b + (e - b) * (y - a) / float(c - a))
            return int(y + anchors[-1][1] - anchors[-1][0])
        last = max((max(p.getClientPoint().y, p.getSupplierPoint().y) for p in paths.values())) + 170
        for p in d['participants']:
            life = next((l for l in view.getDiagram().getOwner().getLifeline() if l.getName() == p['name']))
            symbol = view.findPresentationElement(life, None)
            box = symbol.getBounds()
            self.pem.reshapeShapeElement(symbol, Rectangle(box.x, box.y, box.width, int(last - box.y)))
        shapes = {}
        for definition in d['frames']:
            cf = frames[definition['id']]
            (x, y, w, h) = definition['bounds']
            shape = view.findPresentationElement(cf, None)
            if shape is None:
                shape = self.pem.createShapeElement(cf, view, False, Point(int(x), mapped(y)))
            self.pem.reshapeShapeElement(shape, Rectangle(int(x), mapped(y), int(w), mapped(y + h) - mapped(y)))
            self.decorate(shape)
            shapes[definition['id']] = shape
        for definition in d['frames']:
            compartment = shapes[definition['id']].getInteractionOperandsCompartmentView()
            compartment.layoutChildren()
            splits = [s for s in compartment.getPresentationElements() if isinstance(s, SplitView)]
            for (i, branch) in enumerate(definition['branches'][1:]):
                previous = operands[definition['branches'][i]['id']]
                following = operands[branch['id']]
                split = next((s for s in splits if s.getFirstShape().getElement() == previous and s.getSecondShape().getElement() == following))
                box = split.getBounds()
                self.pem.reshapeShapeElement(split, Rectangle(box.x, mapped(branch['top']), box.width, box.height))
        self.verify_sequence(d, view, operands, paths)

    def verify_sequence(self, d, view, operands, paths):
        for definition in d['messages']:
            if not definition.get('operand'):
                continue
            operand = operands[definition['operand']]
            frame = view.findPresentationElement(operand.getOwner(), None)
            symbol = next((s for s in frame.getInteractionOperandsCompartmentView().getViews() if s.getElement() == operand))
            box = symbol.getBounds()
            path = paths[definition['id']]
            top = min(path.getClientPoint().y, path.getSupplierPoint().y)
            bottom = max(path.getClientPoint().y, path.getSupplierPoint().y)
            if top < box.y + 25 or bottom > box.y + box.height - 5:
                raise ValueError('Prane\u0161imas nepateko \u012f tinkam\u0105 alt \u0161ak\u0105: ' + definition['text'] + '; ' + definition['operand'] + '; y=' + str((top, bottom)) + '; \u0161akos ribos=' + str(box))

    def repair_existing(self, root):
        owned = list(root.getOwnedElement())
        package = next((e for e in owned if hasattr(e, 'getName') and e.getName() == '1 Panaudojimo atvejai'))
        children = list(package.getOwnedElement())
        for a in MODEL['actors']:
            self.elements[a['id']] = next((e for e in children if hasattr(e, 'getName') and e.getName() == a['name']))
        for f in MODEL['functions']:
            self.elements[f['id']] = next((e for e in children if hasattr(e, 'getName') and e.getName() == f['name']))
        uc = next((d for d in MODEL['diagrams'] if d['kind'] == 'usecase'))
        for (a, b) in uc['links']:
            self.relationships[a + '_' + b] = next((e for e in children if hasattr(e, 'getMemberEnd') and CoreHelper.getClientElement(e) == self.elements[a] and (CoreHelper.getSupplierElement(e) == self.elements[b])))
        for (child, parent) in uc['generals']:
            self.relationships['gen_' + child] = next((e for e in self.elements[child].getGeneralization() if e.getGeneral() == self.elements[parent]))
        for (a, b) in uc['includes']:
            self.relationships[a + '_' + b] = next((e for e in self.elements[a].getInclude() if e.getAddition() == self.elements[b]))
        views = list(self.project.getDiagrams())
        view = next((v for v in views if v.getDiagram().getOwner() == package and v.getDiagram().getName().startswith('UC01 ')))
        view.ensureLoaded()
        self.layout_usecase(uc, view)
        self.diagrams.append(('UC01', view))
        db_package = next((e for e in owned if hasattr(e, 'getName') and e.getName() == '2 Esybi\u0173 klas\u0117s'))
        sequence_package = next((e for e in owned if hasattr(e, 'getName') and e.getName() == '3 Sek\u0173 diagramos'))
        db = next((v for v in views if v.getDiagram().getOwner() == db_package and v.getDiagram().getName().startswith('DB01 ')))
        db.ensureLoaded()
        self.diagrams.append(('DB01', db))
        for d in MODEL['diagrams']:
            if d['kind'] != 'sequence':
                continue
            interaction = next((e for e in sequence_package.getOwnedElement() if hasattr(e, 'getLifeline') and e.getName() == d['title']))
            view = next((v for v in views if v.getDiagram().getOwner() == interaction))
            view.ensureLoaded()
            view.open()
            interaction = view.getDiagram().getOwner()
            fragments = {}
            operands = {}

            def collect(owner):
                for fragment in owner.getFragment():
                    if hasattr(fragment, 'getOperand'):
                        fragments[fragment.getName()] = fragment
                        for operand in fragment.getOperand():
                            operands[operand.getName()] = operand
                            collect(operand)
            collect(interaction)
            messages = {m['id']: next((e for e in interaction.getMessage() if e.getName() == m['text'])) for m in d['messages']}
            paths = {key: view.findPresentationElement(value, None) for (key, value) in messages.items()}
            self.layout_sequence(d, view, fragments, operands, paths)
            lifelines = {p['id']: next((l for l in interaction.getLifeline() if l.getName() == p['name'])) for p in d['participants']}
            for m in d['messages']:
                self.sequence_occurrences(interaction, messages[m['id']], m, lifelines, operands)
            self.diagrams.append((d['id'], view))
ROOT_PACKAGE_NAME = 'Kemping\u0173 portalas'
OUTPUT_DIR = None

def output_directory(project):
    directory = project.getDirectory()
    if directory:
        parent = File(directory)
    else:
        parent = File(System.getProperty('java.io.tmpdir'))
    destination = File(parent, 'Kempingu_portalas_rezultatai')
    if not destination.isDirectory() and (not destination.mkdirs()):
        raise RuntimeError('Nepavyko sukurti rezultat\u0173 aplanko: ' + destination.getAbsolutePath())
    return destination.getAbsolutePath()

def main():
    global OUTPUT_DIR
    application = Application.getInstance()
    project = application.getProject()
    if project is None:
        project = application.getProjectsManager().createProject()
    session = SessionManager.getInstance()
    if session.isSessionCreated(project):
        raise RuntimeError('U\u017ebaikite projekte vykstant\u012f redagavimo veiksm\u0105 ir paleiskite makrokomand\u0105 dar kart\u0105.')
    existing = None
    for element in project.getPrimaryModel().getOwnedElement():
        if hasattr(element, 'getName') and element.getName() == ROOT_PACKAGE_NAME:
            existing = element
            break
    OUTPUT_DIR = output_directory(project)
    builder = KempinguPortaloKurimas(project)
    session.createSession(project, 'Sutvarkyti kemping\u0173 portalo diagramas' if existing else 'Sukurti vis\u0105 kemping\u0173 portalo model\u012f')
    try:
        if existing:
            builder.repair_existing(existing)
        else:
            root = builder.create('Package', project.getPrimaryModel(), ROOT_PACKAGE_NAME)
            builder.full(root)
        if len(builder.diagrams) != 5:
            raise RuntimeError('Tik\u0117tasi penki\u0173 diagram\u0173; sukurta: ' + str(len(builder.diagrams)))
        session.closeSession(project)
    except:
        session.cancelSession(project)
        raise
    export_dir = os.path.join(OUTPUT_DIR, 'diagramos')
    if not os.path.isdir(export_dir):
        os.makedirs(export_dir)
    warnings = []
    for (name, view) in builder.diagrams:
        try:
            ImageExporter.export(view, ImageExporter.PNG, File(export_dir, name + '.png'))
            ImageExporter.export(view, ImageExporter.SVG, File(export_dir, name + '.svg'))
        except:
            warnings.append(name + ': ' + traceback.format_exc())
    with codecs.open(os.path.join(OUTPUT_DIR, 'kurimo_rezultatas.txt'), 'w', 'utf-8') as log:
        log.write(('ATNAUJINTA' if existing else 'SUKURTA') + '\nPaketas: ' + ROOT_PACKAGE_NAME + '\nDiagram\u0173: ' + str(len(builder.diagrams)) + '\n')
        log.write('\n'.join(warnings))
    builder.diagrams[0][1].open()
    message = ('Atnaujintas diagram\u0173 i\u0161d\u0117stymas.' if existing else 'Sukurtas visas modelis: 5 diagramos.') + '\n1 panaudojimo atvej\u0173, 1 DB klasi\u0173 ir 3 sek\u0173 diagramos.\n\nProjekt\u0105 i\u0161saugokite per File > Save Project As.\nRezultat\u0173 aplankas: ' + OUTPUT_DIR
    if warnings:
        message += '\nKai kuri\u0173 vaizd\u0173 eksportas nepavyko; informacija rezultat\u0173 faile.'
    JOptionPane.showMessageDialog(None, message, 'Kemping\u0173 portalas', JOptionPane.INFORMATION_MESSAGE)
try:
    main()
except:
    details = traceback.format_exc()
    error_path = None
    if OUTPUT_DIR:
        try:
            error_path = os.path.join(OUTPUT_DIR, 'klaida.txt')
            with codecs.open(error_path, 'w', 'utf-8') as log:
                log.write(details)
        except:
            error_path = None
    Application.getInstance().getGUILog().log(details)
    message = 'Modelio k\u016brimas nepavyko.\nK\u016brimo sesijos pakeitimai, jei sesija buvo prad\u0117ta, at\u0161aukti.\nKlaidos tekstas pateiktas programos \u017eurnale.'
    if error_path:
        message += '\nKlaidos failas: ' + error_path
    JOptionPane.showMessageDialog(None, message, 'Kemping\u0173 portalas', JOptionPane.ERROR_MESSAGE)
    raise
