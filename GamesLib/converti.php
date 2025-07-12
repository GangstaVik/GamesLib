<?php
// converti.php - Script temporaneo per la conversione
$oldXml = simplexml_load_file('libreria.xml');
$newXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Lib></Lib>');

// Aggiungi attributi
$newXml->addAttribute('owner', 'Admin');
$newXml->addAttribute('created', date('c'));

foreach ($oldXml->Gioco as $game) {
    $newGame = $newXml->addChild('Gioco');
    $newGame->addChild('Titolo', (string)$game->Titolo);
    $newGame->addChild('Voto', (float)$game->Voto);
    $newGame->addChild('DoR', (string)$game->DoR);
    $newGame->addChild('Console', (string)$game->Console);
    $newGame->addChild('AddedBy', 'Admin');
    $newGame->addChild('AddedOn', date('c'));
}

// Formatta l'output
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($newXml->asXML());

// Aggiungi commento
$comment = $dom->createComment("\n    Generated on ".date('Y-m-d H:i:s')." by TUO_USERNAME\n    ");
$dom->insertBefore($comment, $dom->firstChild);

// Salva il file
$dom->save('games_user_converted.xml');
echo "File convertito salvato come games_user_converted.xml";
?>