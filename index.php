<?php

// Connessione al database
$servername = "localhost";
$username = "program1";
$password = "12345";
$dbname = "esempio_webservice";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica della connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Validazione dei dati in input
function validateData($data) {
    if (
        !isset($data['nome']) || !is_string($data['nome']) ||
        !isset($data['cognome']) || !is_string($data['cognome']) ||
        !isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
        !isset($data['eta']) || !is_numeric($data['eta']) ||
        !isset($data['data_iscrizione']) || !strtotime($data['data_iscrizione'])
    ) {
        return false;
    }
    return true;
}

$array = explode('/', $_SERVER['REQUEST_URI']);
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    if (count($array) == 4 && $array[3] != '') {
        // Se è specificato un ID nella richiesta GET
        $id = $array[3];
        $sql = "SELECT * FROM dati WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode($row);
        } else {
            echo "Nessun risultato trovato con ID $id";
        }
    } elseif (count($array) == 4 && $array[3] == '') {
        // Se non è specificato un ID nella richiesta GET
        $sql = "SELECT * FROM dati";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $rows = array();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            echo json_encode($rows);
        } else {
            echo "Nessun risultato trovato nella tabella.";
        }
    } else {
        // Se il metodo HTTP non è GET
        http_response_code(405); // Metodo non consentito
        echo "Metodo non consentito";
    }
} elseif ($method == 'POST') {
    // Esegui l'inserimento dei dati
    $data = json_decode(file_get_contents("php://input"), true);

    // Verifica se i dati sono stati inviati correttamente
    if (!empty($data) && validateData($data)) {
        // Esegui l'inserimento nel database
        $sql = "INSERT INTO dati (nome, cognome, email, eta, data_iscrizione) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssis", $data['nome'], $data['cognome'], $data['email'], $data['eta'], $data['data_iscrizione']);

        if ($stmt->execute()) {
            echo "Dati inseriti con successo.";
        } else {
            echo "Errore durante l'inserimento dei dati.";
        }
    } else {
        echo "Dati non validi.";
    }
} elseif ($method == 'PUT') {
    // Esegui l'aggiornamento dei dati
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Verifica se l'ID è stato fornito
    if (count($array) == 4 && $array[3] != '') {
        $id = $array[3];
        
        // Esegui l'aggiornamento nel database
        if (!empty($data) && validateData($data)) {
            $sql = "UPDATE dati SET nome=?, cognome=?, email=?, eta=?, data_iscrizione=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $data['nome'], $data['cognome'], $data['email'], $data['eta'], $data['data_iscrizione'], $id);

            if ($stmt->execute()) {
                echo "Dati aggiornati con successo.";
            } else {
                echo "Errore durante l'aggiornamento dei dati.";
            }
        } else {
            echo "Dati non validi.";
        }
    } else {
        echo "ID non specificato.";
    }
} elseif ($method == 'DELETE') {
    // Esegui la cancellazione dei dati
    if (count($array) == 4 && $array[3] != '') {
        $id = $array[3];
        
        // Esegui la cancellazione nel database
        $sql = "DELETE FROM dati WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo "Dati cancellati con successo.";
        } else {
            echo "Errore durante la cancellazione dei dati.";
        }
    } else {
        echo "ID non specificato.";
    }
} else {
    // Se il metodo HTTP non è supportato
    http_response_code(405); // Metodo non consentito
    echo "Metodo non consentito";
}

$conn->close();

?>
