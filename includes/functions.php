<?php
function verificaLogin() {
    return isset($_SESSION['user_id']);
}

function getUpcomingEvents($limit = 6) {
    global $conn;
    $sql = "SELECT e.*, c.nume as categorie_nume 
            FROM events e 
            LEFT JOIN event_categories c ON e.categorie_id = c.id 
            WHERE e.status = 'activ' AND e.data_inceput >= CURDATE() 
            ORDER BY e.data_inceput 
            LIMIT $limit";
    
    $result = $conn->query($sql);
    $events = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    
    return $events;
}

function getEventById($id) {
    global $conn;
    $id = $conn->real_escape_string($id);
    
    $sql = "SELECT e.*, c.nume as categorie_nume 
            FROM events e 
            LEFT JOIN event_categories c ON e.categorie_id = c.id 
            WHERE e.id = '$id'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

function getCategories() {
    global $conn;
    $sql = "SELECT * FROM event_categories ORDER BY nume";
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

function getTicketTypesByEventId($eventId) {
    global $conn;
    $eventId = $conn->real_escape_string($eventId);
    
    $sql = "SELECT * FROM ticket_types WHERE event_id = '$eventId' AND status = 'disponibil'";
    $result = $conn->query($sql);
    $ticketTypes = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $ticketTypes[] = $row;
        }
    }
    
    return $ticketTypes;
}

function formatData($data) {
    return date("d.m.Y", strtotime($data));
}

function afiseazaMesaj() {
    if(isset($_SESSION['mesaj'])) {
        $class = isset($_SESSION['tip_mesaj']) ? $_SESSION['tip_mesaj'] : 'primary';
        echo '<div class="alert alert-'.$class.' alert-dismissible fade show" role="alert">
                '.$_SESSION['mesaj'].'
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['mesaj']);
        unset($_SESSION['tip_mesaj']);
    }
}
?>