# Aruodas.lt naujų objektų tikrinimo robotukas

## Problemos
1) naujienlaiškis apie naujai įkeltus objektus atkeliauja tik kartą per parą  
2) neišeina filtruoti objektų atmetimo būdu, pvz. tikrai nenoriu matyti objektų iš "Ulvydo" gatvės  

## Problem solved
1) Nueiti į aruodas.lt, atsifiltruoti preliminariai objektus  
2) Nukopijuoti URL su visais filtro parametrais  
3) Paleisti robotuką:  
```
    $ php search.php http://www.aruodas.lt/?FAreaOverAllMin=50&FDistrict=1&obj=1&FPriceMax=90000&FQuartal%5B0%5D=23&FRegion=461&FRoomNumMin=3&mod=Siulo&act=makeSearch&date_from=1471866843 '/ulvydo|nedidelis/'
```

## Išsižadėjimas
Kurta buitinėm reikmėm. Naudokite protingai.
