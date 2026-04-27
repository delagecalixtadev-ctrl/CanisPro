# Projet-Canis-Pro
Site de gestion de cours, seances, chiens, membres car les dogos c'est les plus pipou-chou au monde (pas autant que les chats par contre)&lt;3


# Git
Si probleme quand push faire :
```
bash
git pull origin main --rebase
```

Ajout :
```
bash
git add .
```
Commit :
```
bash
git commit -m "Message décrivant les changements"
```
push :
```
bash
git push --set-upstream origin main
```
# Symfony 

Création d'un Controlleur :
```
bash
symfony console make:Controller NomController
```

Création d'un Entity :
```
bash
symfony console make:Entity nomEntity
```

Création d'une migration :
```
bash
symfony console make:migration  
symfony console doctrine:migrations:migrate
```

Faire le load des fixtures :
```
bash
symfony console doctrine:fixtures:load
```

Si fixtures marche pas supprimer le fichier dans migratioins puis faire :
```
bash
symfony console doctrine:database:drop --force
symfony console doctrine:database:create
symfony console make:migration
symfony console doctrine:migrations:migrate

```

Pour les mot de passe :
```
bash
symfony console security:hash-password leMotDePasse
```

Afficher la phpdoc
```
bash
php -S localhost:8081 -t docs/
```
Faire les tests

```
./bin/phpunit --testsuite 'Project Test Suite'
```