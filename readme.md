# Moja Zastávka
## API and main repository
You are in a new city with the intention to visit some particular building or visit shopping center for example.
Ok, so what if your destination point is on the opposite side of this big city?
You probably want to use public city transport to get there.
But the question is: "Which bus/tram is right and where should I get off?"
The answer is **Moja Zastávka**.

## Tech stack
 - *iOS app:* React Native, Redux, Google maps API
 - *API:* Lumen, Google maps API, MariaDB
 - *3rd party resources:* stops list parsed from [OMA Slovakia](http://www.oma.sk)

## Example of stops resource URL
- KE: http://www.oma.sk/api?region=-1690324&tabulka=poi&typ=zastavka,zastavka-elektricky,stanica&limit=1000

In database is currently more than **3500** stops that covers top **10** greatest Slovak cities

## API Endpoint

Current version prefix is `/api/v2`
```
/find/stops?[stops_params] - finds stops according to given params
/find/departures?[departures_params] - finds 3 nearest departures for given stop
/cities - list of cities in DB
```

where `stops_params` are: 
```
start[name] - optional if lat and lng are provided
start[lat] - optional
start[lng] - optional

destination[name] - optional if lat and lng are provided
destination[lat] - optional
destination[lng - optional

city - optioal, defines city, when city is filled in, start and destination address may be without this city
count - optional, default 3, defines count of stops you want to fetch in nearby of the point
directions - optional, default true, enables loading directions for every stop to show polyline in map
```

`departures_params`:
```
start - name of start stop
destination - name of destination stop
city - city name
time - optional, in format *HH:MM d.m*
```

Examples of valid URL: 

http://mojazastavka.jozefcipa.com/api/v2/find/stops?start[lat]=48.99123119&start[lng]=21.2459537&destination[lat]=48.998669&destination[lng]=21.22763914&count=3

or 

http://mojazastavka.jozefcipa.com/api/v2/find/stops?city=Prešov&start[name]=SPŠE&destination[name]=Na%20Hlavnej&directions=false

&copy;2016-2017 Jozef Cipa & [OMA Slovakia](http://www.oma.sk)

This project uses data from http://openstreetmap.org. These data are available under [ODbL](http://opendatacommons.org/licenses/odbl/summary) license.
