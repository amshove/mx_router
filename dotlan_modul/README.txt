#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

mx_router ist ein Dotlan-Modul als SelfService-Client fuer den Router.
Mit diesem Modul koennen sich die User selbst das Internet freischalten.
Dafuer wird den Usern ein Timeslot von x Minuten zugeteilt, den sie
selbst einteilen koennen.

mx_router_turnier ist ein Dotlan-Modul fuer Turniere, die Internet benoetigen.
Mit dem Modul koennen die User sich fuer das Internet freischalten, wenn
beide Teams einer Begegnung auf bereit stehen. Sobald das Ergebnis eingetragen
wurde, wird die Freischaltung geloescht. Zusaetzlich wird dafuer sorge getragen,
dass die User eines Matches auf der gleichen Leitung landen.

Installation:
- den Ordner mx_router ins dotlan-Verzeichnis verschieben
- mx_router/index.php editieren und die drei $api_ Variablen anpassen
