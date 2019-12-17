#Salesforce

Po zalozeni uctu je potreba vytvorit "Connected App", ktera umozni integraci s salesforce. 

Cesta k vytvoreni "Connected app": Platform Tools → Apps → App Manager → New Connected App - zde je potreba vyplnit pozadovane udaje + vybrat kolonku "Enable OAuth Settings". Zde vyplnit "Callback URL" a potrebne oauth scopes(Access your basic information a Access and manage your data) 

Cesta k Client ID a Client Secret: Platform Tools → Apps → App Manager - U nasi nove vytvorene aplikace vybrat policku View, ktere nas odkaze na stranku, kde je Consumer Key (Client ID) a Consumer Secret (Client secret).