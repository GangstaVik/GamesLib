<?php
// check se è già loggato
require_once 'includes/auth_functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}


// Gestione delle operazioni CRUD per la libreria giochi
class LibreriaGiochi {
    private $xmlFile;
    
    public function __construct() {
        require_once 'includes/xml_functions.php';
        $this->xmlFile = initUserGamesXML();
        
        // Create the file if not exists
        if (!file_exists($this->xmlFile)) {
            $this->creaXMLVuoto();
        }
    }
    
    private function creaXMLVuoto() {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Lib></Lib>');
        $this->salvaXML($xml);
    }
    
    public function caricaGiochi() {
        if (file_exists($this->xmlFile)) {
            return simplexml_load_file($this->xmlFile);
        }
        return new SimpleXMLElement('<Lib></Lib>');
    }
    
    public function aggiungiGioco($titolo, $voto, $dataRilascio, $console) {
        $xml = $this->caricaGiochi();
        
        $gioco = $xml->addChild('Gioco');
        $gioco->addChild('Titolo', htmlspecialchars($titolo));
        $gioco->addChild('Voto', (float)$voto);
        $gioco->addChild('DoR', htmlspecialchars($dataRilascio));
        $gioco->addChild('Console', htmlspecialchars($console));
        $gioco->addChild('AddedBy', $_SESSION['user']['username']);
        $gioco->addChild('AddedOn', date('c'));
        
        return $this->salvaXML($xml);
    }
    
    public function eliminaGioco($indice) {
        $xml = $this->caricaGiochi();
        $giochi = $xml->xpath('//Gioco');
        
        if (isset($giochi[$indice])) {
            $dom = dom_import_simplexml($giochi[$indice]);
            $dom->parentNode->removeChild($dom);
            return $this->salvaXML($xml);
        }
        return false;
    }
    
    public function modificaGioco($indice, $titolo, $voto, $dataRilascio, $console) {
        $xml = $this->caricaGiochi();
        $giochi = $xml->xpath('//Gioco');
        
        if (isset($giochi[$indice])) {
            $giochi[$indice]->Titolo = htmlspecialchars($titolo);
            $giochi[$indice]->Voto = (float)$voto;
            $giochi[$indice]->DoR = htmlspecialchars($dataRilascio);
            $giochi[$indice]->Console = htmlspecialchars($console);
            $giochi[$indice]->LastModified = date('c');
            $giochi[$indice]->ModifiedBy = $_SESSION['user']['username'];
            
            return $this->salvaXML($xml);
        }
        return false;
    }
    
    private function salvaXML($xml) {
        $prettyXML = prettifyXML($xml->asXML());
        return file_put_contents($this->xmlFile, $prettyXML) !== false;
    }
    
    public function importFromXML($xmlContent) {
    try {
        // Try to load the XML
        $sourceXml = simplexml_load_string($xmlContent);
        if ($sourceXml === false) {
            throw new Exception("Invalid XML format");
        }

        // Get current XML
        $currentXml = $this->caricaGiochi();

        // Handle different XML structures
        if (count($sourceXml->Gioco) > 0) {
            // Standard format (<Lib><Gioco>...</Gioco></Lib>)
            $games = $sourceXml->Gioco;
        } elseif (count($sourceXml->children()) > 0) {
            // Alternative format (<Lib><game1>...</game1><game2>...</game2></Lib>)
            $games = $sourceXml->children();
        } else {
            throw new Exception("No games found in XML");
        }

        // Import each game
        foreach ($games as $gioco) {
            // Skip if not a valid game node
            if (!isset($gioco->Titolo) || !isset($gioco->Voto) || 
                !isset($gioco->DoR) || !isset($gioco->Console)) {
                continue;
            }

            // Add the game (this will handle duplicates automatically)
            $this->aggiungiGioco(
                (string)$gioco->Titolo,
                (float)$gioco->Voto,
                (string)$gioco->DoR,
                (string)$gioco->Console
            );
        }

        return true;
    } catch (Exception $e) {
        error_log("XML Import Error: " . $e->getMessage());
        return false;
    }
    }
    
    public function validateGameXML($xmlContent) {
    $requiredFields = ['Titolo', 'Voto', 'DoR', 'Console'];
    
    try {
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) return false;

        // Check if it's our format (either old or new)
        if (!isset($xml->Gioco) && !isset($xml->Lib)) return false;

        // Validate at least one game has all required fields
        foreach ($xml->children() as $game) {
            $valid = true;
            foreach ($requiredFields as $field) {
                if (!isset($game->$field)) {
                    $valid = false;
                    break;
                }
            }
            if ($valid) return true;
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
    }
    /**
     * Export games to formatted XML string
     */
    public function exportXML() {
        $xml = $this->caricaGiochi();
        return prettifyXML($xml->asXML());
    }
    
    /**
     * Get XML file statistics
     */
    public function getXMLStats() {
        $xml = $this->caricaGiochi();
        $stats = [
            'game_count' => count($xml->Gioco),
            'last_modified' => file_exists($this->xmlFile) ? date('Y-m-d H:i:s', filemtime($this->xmlFile)) : 'N/A',
            'file_size' => file_exists($this->xmlFile) ? round(filesize($this->xmlFile) / 1024, 2).' KB' : '0 KB'
        ];
        
        // Add owner info if available
        if (isset($xml['owner'])) {
            $stats['owner'] = (string)$xml['owner'];
        }
        
        return $stats;
    }
}

// Inizializza la libreria
$libreria = new LibreriaGiochi();
$messaggio = '';
$tipoMessaggio = '';


// Gestione delle operazioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $azione = $_POST['azione'] ?? '';

    // switch basato sul valore della var "azione" in modo tale da riconoscere cosa è stato richiesto per tag
    switch ($azione) {

        case 'aggiungi':

            $titolo = trim($_POST['titolo'] ?? '');

            $voto = $_POST['voto'] ?? 0;

            $dataRilascio = $_POST['data_rilascio'] ?? '';

            $console = $_POST['console'] ?? '';

            // se ovviamente i campi sono stati tutti riempiti correttamente, il gioco verrà aggiunto all'xml con messaggio success

            if (!empty($titolo) && !empty($dataRilascio) && !empty($console)) {

                if ($libreria->aggiungiGioco($titolo, $voto, $dataRilascio, $console)) {

                    $messaggio = "Gioco '$titolo' aggiunto con successo!";

                    $tipoMessaggio = 'success';

                }

            } else {

                $messaggio = "Tutti i campi sono obbligatori!";

                $tipoMessaggio = 'danger';

            }

            break;

            

        case 'elimina':

            // discorso simile all'aggiunta con la differenza che la ricerca è fatta per ID

            $indice = (int)($_POST['indice'] ?? -1);

            if ($libreria->eliminaGioco($indice)) {

                $messaggio = "Gioco eliminato con successo!";

                $tipoMessaggio = 'success';

            } else {

                $messaggio = "Errore nell'eliminazione del gioco!";

                $tipoMessaggio = 'danger';

            }

            break;

            

        case 'modifica':

            // gestisce la modifica delle info basandosi sull'ID che viene passato

            $indice = (int)($_POST['indice'] ?? -1);

            // stessa logica di prima per il riempimento delle info

            $titolo = trim($_POST['titolo'] ?? '');

            $voto = $_POST['voto'] ?? 0;

            $dataRilascio = $_POST['data_rilascio'] ?? '';

            $console = $_POST['console'] ?? '';
        

            if ($libreria->modificaGioco($indice, $titolo, $voto, $dataRilascio, $console)) {

                $messaggio = "Gioco modificato con successo!";

                $tipoMessaggio = 'success';

            } else {

                $messaggio = "Errore nella modifica del gioco!";

                $tipoMessaggio = 'danger';

            }

            break;
            
        case 'import':
            if (isset($_FILES['xmlFile']) && $_FILES['xmlFile']['error'] === UPLOAD_ERR_OK) {
                // Read file content safely
                $fileContent = file_get_contents($_FILES['xmlFile']['tmp_name']);
                if ($fileContent === false) {
                    $messaggio = "Error reading file";
                    $tipoMessaggio = 'danger';
                    break;
                }

                // Remove any UTF-8 BOM if present
                $bom = pack('H*','EFBBBF');
                $fileContent = preg_replace("/^$bom/", '', $fileContent);

                if ($libreria->importFromXML($fileContent)) {
                    $messaggio = count($libreria->caricaGiochi()->Gioco) . " games imported successfully!";
                    $tipoMessaggio = 'success';
                }else {
                    $messaggio = "Error during import - invalid file format";
                    $tipoMessaggio = 'danger';
                }
            }else {
                $messaggio = "Please select a valid XML file (Error: " . 
                ($_FILES['xmlFile']['error'] ?? 'UNKNOWN') . ")";
                $tipoMessaggio = 'danger';
            }
        break;


    }

}



// Carica tutti i giochi per l'out
$giochiXML = $libreria->caricaGiochi();

$giochi = [];

foreach ($giochiXML->Gioco as $gioco) {

    $giochi[] = [

        'titolo' => (string)$gioco->Titolo,

        'voto' => (float)$gioco->Voto,

        'data_rilascio' => (string)$gioco->DoR,

        'console' => (string)$gioco->Console

    ];

}

?>



<!DOCTYPE html>
<html lang="it">
<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Libreria Giochi</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">

</head>

<body>

    <div class="container-fluid">

        <!-- Header -->
        <header class="row bg-primary text-white py-4 mb-4">

        <div class="col-12 text-center">

            <h1><i class="fas fa-gamepad me-3"></i>Libreria Giochi</h1>

            <p class="lead mb-0">Gestisci la tua collezione di videogiochi</p>

            <div class="mt-2">
                <span class="badge bg-light text-dark me-2">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['user']['username']) ?>
                </span>
                <a href="auth/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>

        </header>



        <!-- Messaggi -->
        <?php if ($messaggio): ?>

        <div class="row">

            <div class="col-12">

                <div class="alert alert-<?php echo $tipoMessaggio; ?> alert-dismissible fade show" role="alert">

                    <?php echo htmlspecialchars($messaggio); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

                </div>

            </div>

        </div>

        <?php endif; ?>



        <div class="row">

            <!-- Form di inserimento -->
            <div class="col-lg-4 col-md-6 mb-4">

                <div class="card shadow-sm">

                    <div class="card-header bg-success text-white">

                        <h4 class="mb-0"><i class="fas fa-plus me-2"></i>Aggiungi Gioco</h4>
                        <button class="btn btn-sm btn-outline-light me-1" data-bs-toggle="modal" data-bs-target="#modalImport">
                        <i class="fas fa-file-import me-1"></i>Import
                        </button>
                    </div>

                    <div class="card-body">

                        <form method="POST" id="formAggiungi">

                            <input type="hidden" name="azione" value="aggiungi">

                            

                            <div class="mb-3">

                                <label for="titolo" class="form-label">Titolo *</label>

                                <input type="text" class="form-control" id="titolo" name="titolo" required>

                            </div>

                            

                            <div class="mb-3">

                                <label for="voto" class="form-label">Voto (1-10)</label>

                                <input type="number" class="form-control" id="voto" name="voto" 

                                       min="1" max="10" step="0.1" value="5">

                            </div>

                            

                            <div class="mb-3">

                                <label for="data_rilascio" class="form-label">Data di Rilascio *</label>

                                <input type="date" class="form-control" id="data_rilascio" name="data_rilascio" required>

                            </div>

                            

                            <div class="mb-3">

                                <label for="console" class="form-label">Console *</label>

                                <select class="form-select" id="console" name="console" required>

                                    <option value="">Seleziona console...</option>

                                    <option value="PC">PC</option>

                                    <option value="PlayStation 5">PS5</option>

                                    <option value="Xbox Series X/S">Xbox X/S</option>

                                    <option value="Nintendo Switch">Nintendo Switch</option>

                                    <option value="PlayStation 4">PS4</option>

                                    <option value="Xbox One">Xbox One</option>

                                    <option value="Multipiattaforma">Multiplatform</option>

                                </select>

                            </div>

                            

                            <button type="submit" class="btn btn-success w-100">

                                <i class="fas fa-save me-2"></i>Aggiungi Gioco

                            </button>

                        </form>

                    </div>

                </div>

            </div>



            <!-- Lista giochi -->
            <div class="col-lg-8 col-md-6">

                <div class="card shadow-sm">

                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">

                        <h4 class="mb-0"><i class="fas fa-list me-2"></i>I Tuoi Giochi</h4>
                        
                    </div>
                        <span class="badge bg-light text-dark"><?php echo count($giochi); ?> giochi</span>

                </div>

                    <div class="card-body p-0">

                        <?php if (empty($giochi)): ?>

                        <div class="text-center py-5">

                            <i class="fas fa-ghost fa-3x text-muted mb-3"></i>

                            <p class="text-muted">Nessun gioco nella libreria. Aggiungi il primo!</p>

                        </div>

                        <?php else: ?>

                        <!-- Tabella di inserimento delle info con azione da eseguire su quei dati inseriti -->

                        <div class="table-responsive">

                            <table class="table table-hover mb-0">

                                <thead class="table-dark">

                                    <tr>

                                        <th>Titolo</th>

                                        <th>Voto</th>

                                        <th>Data Rilascio</th>

                                        <th>Console</th>

                                        <th width="120">Azioni</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($giochi as $indice => $gioco): ?>

                                    <tr class="gioco-row" data-indice="<?php echo $indice; ?>">

                                        <td>

                                            <strong><?php echo htmlspecialchars($gioco['titolo']); ?></strong>

                                        </td>

                                        <td>

                                            <span class="badge bg-<?php echo $gioco['voto'] >= 8 ? 'success' : ($gioco['voto'] >= 6 ? 'warning' : 'danger'); ?>">

                                                <?php echo $gioco['voto']; ?>/10

                                            </span>

                                        </td>

                                        <td><?php echo date('d/m/Y', strtotime($gioco['data_rilascio'])); ?></td>

                                        <td>

                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($gioco['console']); ?></span>

                                        </td>

                                        <td>

                                            <button class="btn btn-sm btn-outline-primary me-1 btn-modifica" 

                                                    data-indice="<?php echo $indice; ?>"

                                                    data-titolo="<?php echo htmlspecialchars($gioco['titolo']); ?>"

                                                    data-voto="<?php echo $gioco['voto']; ?>"

                                                    data-data="<?php echo $gioco['data_rilascio']; ?>"

                                                    data-console="<?php echo htmlspecialchars($gioco['console']); ?>">

                                                <i class="fas fa-edit"></i>

                                            </button>

                                            <button class="btn btn-sm btn-outline-danger btn-elimina" 

                                                    data-indice="<?php echo $indice; ?>"

                                                    data-titolo="<?php echo htmlspecialchars($gioco['titolo']); ?>">

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </td>

                                    </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Modal per import XML -->
    <div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Import Games</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="xmlFile" class="form-label">Select XML File</label>
                        <input class="form-control" type="file" id="xmlFile" name="xmlFile" accept=".xml" required>
                        <div class="form-text">Select your existing games XML file to import</div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will merge the imported games with your current collection.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-file-import me-2"></i>Import Games
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
    
    <!-- Modal per export XML -->
    <div class="modal fade" id="modalExport" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Export Games</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">XML Stats:</label>
                    <div class="card bg-light p-3">
                        <?php $stats = $libreria->getXMLStats(); ?>
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted">Games Count</small>
                                <div class="fw-bold"><?= $stats['game_count'] ?></div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Last Modified</small>
                                <div class="fw-bold"><?= $stats['last_modified'] ?></div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">File Size</small>
                                <div class="fw-bold"><?= $stats['file_size'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Formatted XML:</label>
                    <textarea class="form-control font-monospace" rows="15" readonly><?= htmlspecialchars($libreria->exportXML()) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="downloadXML()">
                    <i class="fas fa-download me-2"></i>Download XML
                </button>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal per conferma eliminazione -->
    <div class="modal fade" id="modalElimina" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header bg-danger text-white">

                    <h5 class="modal-title">Conferma Eliminazione</h5>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">

                    <p>Sei sicuro di voler eliminare il gioco <strong id="titoloElimina"></strong>?</p>

                    <p class="text-muted">Questa azione non può essere annullata.</p>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>

                    <form method="POST" style="display: inline;">

                        <input type="hidden" name="azione" value="elimina">

                        <input type="hidden" name="indice" id="indiceElimina">

                        <button type="submit" class="btn btn-danger">Elimina</button>

                    </form>

                </div>

            </div>

        </div>

    </div>



    <!-- Modal per modifica -->
    <div class="modal fade" id="modalModifica" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header bg-primary text-white">

                    <h5 class="modal-title">Modifica Gioco</h5>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>

                </div>

                <form method="POST" id="formModifica">

                    <div class="modal-body">

                        <input type="hidden" name="azione" value="modifica">

                        <input type="hidden" name="indice" id="indiceModifica">

                        

                        <div class="mb-3">

                            <label for="titoloModifica" class="form-label">Titolo *</label>

                            <input type="text" class="form-control" id="titoloModifica" name="titolo" required>

                        </div>

                        

                        <div class="mb-3">

                            <label for="votoModifica" class="form-label">Voto (1-10)</label>

                            <input type="number" class="form-control" id="votoModifica" name="voto" 

                                   min="1" max="10" step="0.1">

                        </div>

                        

                        <div class="mb-3">

                            <label for="dataModifica" class="form-label">Data di Rilascio *</label>

                            <input type="date" class="form-control" id="dataModifica" name="data_rilascio" required>

                        </div>

                        

                        <div class="mb-3">

                            <label for="consoleModifica" class="form-label">Console *</label>

                            <select class="form-select" id="consoleModifica" name="console" required>

                                <option value="">Seleziona console...</option>

                                <option value="PC">PC</option>

                                <option value="PlayStation 5">PS5</option>

                                <option value="Xbox Series X/S">Xbox X/S</option>

                                <option value="Nintendo Switch">Nintendo Switch</option>

                                <option value="PlayStation 4">PS4</option>

                                <option value="Xbox One">Xbox One</option>

                                <option value="Multipiattaforma">Multiplatform</option>

                            </select>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>

                        <button type="submit" class="btn btn-primary">Salva Modifiche</button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="script.js"></script>

</body>

</html>
