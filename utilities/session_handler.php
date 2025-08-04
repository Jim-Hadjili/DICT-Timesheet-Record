<?php
/**
 * Session Handler Utility Functions
 * 
 * This file contains functions for managing session messages and variables
 */

/**
 * Sets a success message in the session
 * 
 * @param string $message The message to set
 * @return void
 */
function setSuccessMessage($message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = "success";
}

/**
 * Sets an error message in the session
 * 
 * @param string $message The message to set
 * @return void
 */
function setErrorMessage($message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = "error";
}

/**
 * Sets an info message in the session
 * 
 * @param string $message The message to set
 * @return void
 */
function setInfoMessage($message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = "info";
}

/**
 * Sets a warning message in the session
 * 
 * @param string $message The message to set
 * @return void
 */
function setWarningMessage($message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = "warning";
}

/**
 * Retrieves and clears a message from the session
 * 
 * @return string|null The message if it exists, null otherwise
 */
function getAndClearMessage() {
    $message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
    $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : null;
    
    if (isset($_SESSION['message'])) {
        unset($_SESSION['message']);
    }
    if (isset($_SESSION['message_type'])) {
        unset($_SESSION['message_type']);
    }
    
    return ['message' => $message, 'type' => $type];
}
?>