#!/usr/bin/env bash


# lists defined cups printer on this host, in the following format
#
# lpstat -t
# lpstat -t
# scheduler is running
# system default destination: LinePrinterHarkema
# device for Beehives_HP: lpd://192.168.178.47/
# device for Canon_iP5200: dnssd://Canon%20iP5200._riousbprint._tcp.local.
# device for LinePrinterHarkema: lpd://192.168.178.105/
# Beehives_HP accepting requests since ma  7 mrt 14:57:48 2011
# Canon_iP5200 accepting requests since wo 23 mrt 20:04:54 2011
# LinePrinterHarkema accepting requests since wo 23 mrt 21:11:37 2011
# printer Beehives_HP is idle.  enabled since ma  7 mrt 14:57:48 2011
# printer Canon_iP5200 is idle.  enabled since wo 23 mrt 20:04:54 2011
# 	Ready to print.
# printer LinePrinterHarkema now printing LinePrinterHarkema-334.  enabled since wo 23 mrt 21:11:37 2011
# 	Connecting to printer...
# LinePrinterHarkema-334  _www              3072   wo 23 mrt 21:11:37 2011

# lpstat -s
# system default destination: LinePrinterHarkema
# device for Beehives_HP: lpd://192.168.178.47/
# device for Canon_iP5200: dnssd://Canon%20iP5200._riousbprint._tcp.local.
# device for LinePrinterHarkema: lpd://192.168.178.105/


bin=`which lpstat`

$bin -s

