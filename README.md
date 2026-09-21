# sae5-01-back

Projet académique, création d'une application de chasse au trésor, partie back-end

## Auteurs du projet

- Tony Hadj Sadoun ``hadj0030``
- Sandjy Gaudin ``gaud0055``
- Natan Sorel ``sore0012`` ``Limeen``
- Wassime Moussaoui ``mous0111``
- Gaël Lefèvre ``lefe0306``

## Installation

```
git clone https://iut-info.univ-reims.fr/gitlab/sore0012/sae5-01-back
```

```
docker compose up -d
```

```
composer start
```

## Présentation du projet

### Introduction

< écrire ici >

### Modèle Conceptuel de données

< insérer MCD ici >

### Fonctionnalités

## Scripts composer

Vous trouverez ci-dessous des renseignements sur les actions des scripts.

- ``start`` : qui lance le serveur web de test (« symfony serve ») sans restriction de durée d'exécution
- ``test:phpcs`` : qui lance la commande de vérification du code par PHP CS Fixer
- ``fix:phpcs`` : qui lance la commande de correction du code par PHP CS Fixer
- ``test:twigcs`` : qui lance la commande de vérification du code par Twig CS Fixer
- ``fix:twigcs`` : qui lance la commande de correction du code par Twig CS Fixer
- ``test`` : qui lance les scripts Composer « test:phpcs » et « test:twigcs »
- ``fix`` : qui lance les scripts Composer « fix:phpcs » et « fix:twigcs »
- ``test:codeception`` :
    - Nettoyage du répertoire « _output » et du code généré par Codeception
    - Destruction silencieuse forcée de la base de données
    - Création silencieuse de la base de données
    - Création silencieuse du schéma de la base de données
    - Exécution des tests Codeception
- ``db`` :
    - Destruction forcée de la base de données
    - Création de la base de données
    - Application des migrations successives sans questions interactives
    - Génération des données factices sans questions interactives
- ``fix:db``:
    - ⚠️ **à utiliser que en cas de nécessité**
    - Réinitialise le système de migrations de Doctrine.
    - Supprime tous les anciens fichiers du dossier « migrations ».
    - Recrée une base de données propre et vide.
    - Génère une nouvelle migration unique à partir de tes entités.
    - Exécute le script ``composer db`` pour installer le schéma et les données de test.