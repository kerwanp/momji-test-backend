<?php

use App\MyObj;
use App\UsersService;
use App\Utils\PaginationAggregator;

require 'vendor/autoload.php';

/**
 * Scenario :
 * Nous devons récolter une grande quantité de données fournies par un tiers.
 * Compte tenu du volume, il est impossible de tout charger en mémoire en amont du traitement.
 * L'API tierce ayant un API rate, nous ne pouvons pas non plus faire un appel pour chaque item,
 * sans quoi nous serons bloqués très rapidement.
 * Pour chaque item reçu, nous effectuons un traitement. Celui-ci est suffisamment
 * long pour que ce soit rentable de paralléliser le travail.
 * Chaque item sera donc envoyé à un service distant pour être traité.
 * Une fois le résultat du traitement reçu, il devient disponible pour notre client (notre object).
 * Afin que l'objet soit simple d'utilisation, nous devons pouvoir itérer dessus avec un simple foreach.
 * Un exemple d'utilisation ci-après.
 *
 *
 * A vous d'implémenter une classe qui résous notre use case.
 * Pour cet exercice, vous ne pouvez pas faire appel à un système externe comme un service de message ou à des threads.
 * Vous expliquez vos hypothèses, choix et tradeoffs.
 */

/**
 * Pour le requêtage de l'API, deux situations potentielles :
 * - L'API n'a pas de pagination il faut donc prévoir de récupérer un large volume de donnée : `json-machine`
 * - L'API a de la pagination, il faut donc pouvoir facilement query chaque page pour récupérer toutes les données.
 *   Utiliser le stream n'a pas d'utilité avec la pagination car on a besoin de la réponse complète.
 * 
 * (je vais donc inclure les deux cas): Un utilitaire pour la pagination et l'utilisation de json-machine.
 * 
 * 
 * Pour m'assurer que ça ne devienne pas rapidement bordelique je crée deux classes :
 * - UsersService : Un service de mock
 * - PaginationAggregator : Une classe utilitaire pour query une ressource complète avec la pagination
 * 
 * Les contraintes importantes :
 * - Le service initial peut retourner un très gros montant de données : Il faut les traiter au fur et à mesure.
 * - Le traitement externe des données est assez long, il faut donc pouvoir lancer les requetes en concurrence
 * - On souhaite pouvoir itérer au fûr et à mesure que les données sont traitées.
 * 
 * Au fur et à mesure que les données arrivent, on initie les requêtes concurrentes.
 * 
 * Ensuite on souhaite pouvoir utiliser la donnée traiter, deux situations :
 * - On souhaite absolument itérer : Problème, on doit donc attendre que chaque batch soit terminé
 * - On peut utiliser des callback : Chaque contenu traité appel le callback
 */

$usersService = new UsersService();

$paginationAggregator = new PaginationAggregator(
    func: function ($page, $limit) use ($usersService) {
        return $usersService->list($page, $limit);
    },
    limit: 5,
    startPage: 1
);

$myObj = new MyObj(
    query: $paginationAggregator,
    processFunc: function ($item) use ($usersService) {
        return $usersService->update($item->id, $item);
    },
    concurrent: 4
);

/**
 * Executé une fois par item après chaque traitement d'un objet.
 */
$myObj->process(function ($item) use (&$count) {
    echo $item->name . PHP_EOL;
});

/**
 * Executé une fois par item mais seulement une fois un batch terminé.
 */
foreach ($myObj as $treatmentResult) {
    echo $treatmentResult->name . PHP_EOL;
}
