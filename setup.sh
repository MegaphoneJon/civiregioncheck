#!/bin/sh
mkdir data
curl -o ./data/countries.csv https://raw.githubusercontent.com/lukes/ISO-3166-Countries-with-Regional-Codes/master/slim-2/slim-2.csv
curl -o ./data/regions.zip https://www.ip2location.com/downloads/ip2location-iso3166-2.zip
unzip ./data/regions.zip -d ./data
