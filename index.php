<?php
/* ------------------------------------------------ */
/* Exercice d'évaluation technique – GAC Technology */

//Connexion bdd
include('connexion.php');

//Create table to add data we need
if ( $mysqli->query('CREATE TABLE Tickets (id_ticket INT NOT NULL AUTO_INCREMENT ,no_abonne VARCHAR(255) NOT NULL,date_ticket DATE NOT NULL,heure_ticket TIME NOT NULL,duree_volume_reel VARCHAR(255) NOT NULL,duree_volume_facture VARCHAR(255) NOT NULL, type_ticket VARCHAR(255) NOT NULL, PRIMARY KEY (id_ticket)) ;')) {
     echo 'Table crée !</br> ';
}
else
    printf("Erreur : %s\n", $mysqli->error);

//Add csv data in the table

$row = 0;
if (($handle = fopen("csv/tickets_appels_201202.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if( $row > 2  )
        {
            //Formatage Date YYYY-MM-DD
            $dateArray = explode('/',$data[3]);

            //Verifier si la date est valide + si la fonction arrive à parser le temps elle retourne le timestamp sinon ce n'est pas un temps (on ignore la ligne)
            if( checkdate($dateArray[1],$dateArray[0],$dateArray[2]) && strtotime($data[4]))
            {
                $newFormatDate = $dateArray[2].'-'.$dateArray[1].'-'.$dateArray[0];

                $request = sprintf('INSERT INTO Tickets (no_abonne ,date_ticket,heure_ticket,duree_volume_reel,duree_volume_facture,type_ticket) VALUES ("%s","%s","%s","%s","%s","%s")',
                                   $data[2],
                                   $newFormatDate,
                                   $data[4],
                                   $data[5],
                                   $data[6],
                                   $mysqli->real_escape_string(utf8_encode($data[7])));

                $result =$mysqli->query($request);

                if(!$result)
                    printf(" Ligne ".($row+1)." Erreur : %s\n", $mysqli->error);
            }
        }

        $row++;
    }
    fclose($handle);
}


//REQUEST THE TABLE
//1 - Retrouver la durée totale réelle des appels effectués après le 15/02/2012 (inclus)

$dureeVolumeReelToTal = "00:00:00";
$result = $mysqli->query('SELECT duree_volume_reel From Tickets WHERE duree_volume_reel LIKE "%:%" AND date_ticket >= "2012-02-15" ;');
while ($row = $result->fetch_assoc()) {

    $dvrExplode = explode(";",$row['duree_volume_reel']);
    $dvrtExplode = explode(";",$dureeVolumeReelToTal);

    $dureeVolumeReelToTal = date('H:i:s',mktime($dvrExplode[0],$dvrExplode[1],$dvrExplode[2])+mktime($dvrtExplode[0],$dvrtExplode[1],$dvrtExplode[2]));

}

echo "Résultat de la durée totale réelle des appels effectués après le 15/02/2012 : ".$dureeVolumeReelToTal."</br></br>";

//2 - TOP 10 des volumes data facturés en dehors de la tranche horaire 8h00- 18h00, par abonné

echo 'Résultat TOP 10 des volumes data facturés en dehors de la tranche horaire 8h00- 18h00, par abonné </br>';
//Recuperer les differents numero d'abonnes
$resultAbo = $mysqli->query('SELECT DISTINCT no_abonne From Tickets WHERE no_abonne IS NOT NULL AND no_abonne!="" AND duree_volume_reel NOT LIKE "%:%" AND duree_volume_reel !=""  AND heure_ticket NOT BETWEEN "00:08:00" AND "00:18:00"');
while ($rowAbo = $resultAbo->fetch_assoc()) {

    echo '- '.$rowAbo['no_abonne'].' : ';

    $resultTop10 = $mysqli->query('SELECT duree_volume_reel From Tickets WHERE no_abonne ="'.$rowAbo['no_abonne'].'" AND duree_volume_reel NOT LIKE "%:%" AND duree_volume_reel !="" AND heure_ticket NOT BETWEEN "00:08:00" AND "00:18:00" ORDER BY CAST(duree_volume_reel as unsigned) DESC LIMIT 0,10');
    while ($row = $resultTop10->fetch_assoc()) {
        echo '[ '.$row['duree_volume_reel'].' ] ';
    }
    echo '</br> ';
}


//3 - Retrouver la quantité totale de SMS envoyés par l'ensemble des abonnés

$resultSms = $mysqli->query('SELECT COUNT(*) AS NbrSms FROM Tickets WHERE duree_volume_reel ="" AND duree_volume_facture="" AND no_abonne IS NOT NULL AND no_abonne!="" AND type_ticket LIKE "%envoi%" AND type_ticket LIKE "%sms%"');
$rowSms = $resultSms->fetch_assoc();

 echo '</br> Quantité de SMS envoyés par l\'ensemble des abonnés : '.$rowSms['NbrSms'];





?>
